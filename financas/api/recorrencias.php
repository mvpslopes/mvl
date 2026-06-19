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
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            fin_json(['success' => true, 'recorrencia' => $svc->getRecorrencia($id)]);
        }
        fin_json(['success' => true, 'recorrencias' => $svc->listRecorrencias()]);
    }

    if ($method === 'POST') {
        $data = fin_json_input();
        $rec = $svc->createRecorrencia(fin_validate_recorrencia($data));
        fin_json(['success' => true, 'recorrencia' => $rec], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = fin_json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            fin_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $current = $svc->getRecorrencia($id);
        $merged = array_merge($current, $data);
        $rec = $svc->updateRecorrencia($id, fin_validate_recorrencia($merged));
        fin_json(['success' => true, 'recorrencia' => $rec]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? fin_json_input()['id'] ?? 0);
        if ($id <= 0) {
            fin_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $svc->deleteRecorrencia($id);
        fin_json(['success' => true]);
    }
} catch (InvalidArgumentException $e) {
    fin_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('financas recorrencias: ' . $e->getMessage());
    fin_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);

function fin_validate_recorrencia(array $data, bool $requireAll = true): array
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
    $dia = (int) ($data['dia_vencimento'] ?? 0);
    if ($dia < 1 || $dia > 31) {
        throw new InvalidArgumentException('Dia de vencimento inválido (1-31).');
    }
    $ini = trim((string) ($data['data_inicio'] ?? ''));
    if ($requireAll && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini)) {
        throw new InvalidArgumentException('Data de início inválida.');
    }
    $fim = isset($data['data_fim']) && $data['data_fim'] !== '' ? trim((string) $data['data_fim']) : null;
    if ($fim !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim)) {
        throw new InvalidArgumentException('Data fim inválida.');
    }

    $out = [
        'tipo' => $tipo,
        'descricao' => $desc,
        'valor' => $valor,
        'dia_vencimento' => $dia,
        'data_inicio' => $ini,
        'data_fim' => $fim,
        'ativa' => array_key_exists('ativa', $data) ? (bool) $data['ativa'] : true,
    ];

    if (array_key_exists('categoria_id', $data)) {
        $out['categoria_id'] = $data['categoria_id'] !== '' && $data['categoria_id'] !== null
            ? (int) $data['categoria_id']
            : null;
    }

    return $out;
}
