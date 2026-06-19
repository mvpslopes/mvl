<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

proj_cors();
proj_options_exit();
proj_require_auth();

$svc = proj_load_service();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (!empty($_GET['backlog'])) {
            $filtro = trim((string) ($_GET['status'] ?? ''));
            proj_json([
                'success' => true,
                'demandas' => $svc->backlog($filtro !== '' ? $filtro : null),
            ]);
        }

        $semana = trim((string) ($_GET['semana'] ?? proj_current_iso_week()));
        $filtro = trim((string) ($_GET['status'] ?? ''));
        $data = $svc->semanaDemandas($semana, $filtro !== '' ? $filtro : null);
        proj_json(['success' => true, ...$data]);
    }

    if ($method === 'POST') {
        $data = proj_json_input();
        $demanda = $svc->createDemanda(proj_validate_demanda($data));
        proj_json(['success' => true, 'demanda' => $demanda], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = proj_json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            proj_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $patch = proj_validate_demanda($data, false);
        if (isset($data['status'])) {
            $patch['status'] = $data['status'];
        }
        $demanda = $svc->updateDemanda($id, array_merge($data, $patch));
        proj_json(['success' => true, 'demanda' => $demanda]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? proj_json_input()['id'] ?? 0);
        if ($id <= 0) {
            proj_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $svc->deleteDemanda($id);
        proj_json(['success' => true]);
    }
} catch (InvalidArgumentException $e) {
    proj_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('projetos demandas: ' . $e->getMessage());
    proj_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

proj_json(['success' => false, 'message' => 'Método não permitido.'], 405);

function proj_validate_demanda(array $data, bool $requireTitulo = true): array
{
    $titulo = trim((string) ($data['titulo'] ?? ''));
    if ($requireTitulo && $titulo === '') {
        throw new InvalidArgumentException('Título obrigatório.');
    }

    $out = [];
    if ($titulo !== '' || !$requireTitulo) {
        $out['titulo'] = $titulo;
    }

    if (array_key_exists('descricao', $data)) {
        $out['descricao'] = trim((string) $data['descricao']) ?: null;
    }

    if (isset($data['tipo_id'])) {
        $out['tipo_id'] = (int) $data['tipo_id'];
        if ($requireTitulo && $out['tipo_id'] <= 0) {
            throw new InvalidArgumentException('Tipo obrigatório.');
        }
    } elseif ($requireTitulo) {
        throw new InvalidArgumentException('Tipo obrigatório.');
    }

    if (array_key_exists('data_prevista', $data)) {
        $dp = trim((string) ($data['data_prevista'] ?? ''));
        $out['data_prevista'] = $dp === '' ? null : $dp;
    }

    if (isset($data['prioridade'])) {
        if (!in_array($data['prioridade'], ['baixa', 'media', 'alta'], true)) {
            throw new InvalidArgumentException('Prioridade inválida.');
        }
        $out['prioridade'] = $data['prioridade'];
    }

    if (isset($data['status'])) {
        if (!in_array($data['status'], ['pendente', 'em_andamento', 'concluida', 'cancelada'], true)) {
            throw new InvalidArgumentException('Status inválido.');
        }
        $out['status'] = $data['status'];
    }

    if (array_key_exists('checklist', $data)) {
        $out['checklist'] = is_array($data['checklist']) ? $data['checklist'] : [];
    }

    return $out;
}
