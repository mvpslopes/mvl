<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();
fin_require_auth();

$svc = fin_load_service();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $mes = trim((string) ($_GET['mes'] ?? date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            fin_json(['success' => false, 'message' => 'Mês inválido.'], 400);
        }
        $items = $svc->lancamentosDoMes($mes);
        fin_json([
            'success' => true,
            'mes' => $mes,
            'lancamentos' => $items,
            'resumo' => $svc->resumoMesComparado($mes),
            'vencimentos_proximos' => $svc->vencimentosProximos($items, $mes),
        ]);
    }

    if ($method === 'POST') {
        $data = fin_json_input();
        $lanc = $svc->createLancamento(fin_validate_lancamento($data));
        fin_json(['success' => true, 'lancamento' => $lanc], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = fin_json_input();

        if (!empty($data['projetado']) && !empty($data['recorrencia_id']) && !empty($data['mes_referencia'])) {
            $status = (string) ($data['status'] ?? 'prevista');
            $lanc = $svc->materializarRecorrencia(
                (int) $data['recorrencia_id'],
                (string) $data['mes_referencia'],
                $status
            );
            $patch = [];
            foreach (['descricao', 'valor', 'data_vencimento', 'valor_realizado', 'data_efetivacao', 'categoria_id', 'status'] as $f) {
                if (array_key_exists($f, $data)) {
                    $patch[$f] = $data[$f];
                }
            }
            if ($patch !== []) {
                $lanc = $svc->updateLancamento((int) $lanc['id'], $patch);
            }
            fin_json(['success' => true, 'lancamento' => $lanc]);
        }

        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            fin_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $lanc = $svc->updateLancamento($id, $data);
        fin_json(['success' => true, 'lancamento' => $lanc]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? fin_json_input()['id'] ?? 0);
        if ($id <= 0) {
            fin_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $svc->deleteLancamento($id);
        fin_json(['success' => true]);
    }
} catch (InvalidArgumentException $e) {
    fin_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('financas lancamentos: ' . $e->getMessage());
    fin_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);

function fin_validate_lancamento(array $data): array
{
    $tipo = $data['tipo'] ?? '';
    if (!in_array($tipo, ['receita', 'despesa'], true)) {
        throw new InvalidArgumentException('Tipo inválido.');
    }
    $desc = trim((string) ($data['descricao'] ?? ''));
    if ($desc === '') {
        throw new InvalidArgumentException('Descrição obrigatória.');
    }
    $valor = (float) ($data['valor'] ?? 0);
    if ($valor <= 0) {
        throw new InvalidArgumentException('Valor deve ser maior que zero.');
    }
    $venc = trim((string) ($data['data_vencimento'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
        throw new InvalidArgumentException('Data de vencimento inválida.');
    }
    $status = $data['status'] ?? 'prevista';
    $validStatus = $tipo === 'receita'
        ? ['prevista', 'recebida', 'cancelada']
        : ['prevista', 'paga', 'cancelada'];
    if (!in_array($status, $validStatus, true)) {
        $status = 'prevista';
    }

    $dataEfet = isset($data['data_efetivacao']) && $data['data_efetivacao'] !== ''
        ? trim((string) $data['data_efetivacao'])
        : null;
    if ($dataEfet !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataEfet)) {
        throw new InvalidArgumentException('Data de efetivação inválida.');
    }

    return [
        'tipo' => $tipo,
        'descricao' => $desc,
        'valor' => $valor,
        'valor_realizado' => isset($data['valor_realizado']) && $data['valor_realizado'] !== ''
            ? (float) $data['valor_realizado']
            : null,
        'data_vencimento' => $venc,
        'data_efetivacao' => $dataEfet,
        'mes_referencia' => substr($venc, 0, 7),
        'status' => $status,
        'categoria_id' => isset($data['categoria_id']) && $data['categoria_id'] !== ''
            ? (int) $data['categoria_id']
            : null,
        'recorrencia_id' => isset($data['recorrencia_id']) ? (int) $data['recorrencia_id'] : null,
    ];
}
