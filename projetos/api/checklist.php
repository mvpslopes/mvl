<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

proj_cors();
proj_options_exit();
proj_require_auth();

$svc = proj_load_service();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'PATCH' || $method === 'PUT') {
        $data = proj_json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            proj_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        if (!array_key_exists('concluido', $data)) {
            proj_json(['success' => false, 'message' => 'Campo concluido obrigatório.'], 400);
        }
        $demanda = $svc->updateChecklistItem($id, (bool) $data['concluido']);
        proj_json(['success' => true, 'demanda' => $demanda]);
    }

    if ($method === 'POST') {
        $data = proj_json_input();
        $demandaId = (int) ($data['demanda_id'] ?? 0);
        $texto = trim((string) ($data['texto'] ?? ''));
        if ($demandaId <= 0 || $texto === '') {
            proj_json(['success' => false, 'message' => 'demanda_id e texto obrigatórios.'], 400);
        }
        $demanda = $svc->addChecklistItem($demandaId, $texto);
        proj_json(['success' => true, 'demanda' => $demanda], 201);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? proj_json_input()['id'] ?? 0);
        if ($id <= 0) {
            proj_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $demanda = $svc->deleteChecklistItem($id);
        proj_json(['success' => true, 'demanda' => $demanda]);
    }
} catch (InvalidArgumentException $e) {
    proj_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('projetos checklist: ' . $e->getMessage());
    proj_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

proj_json(['success' => false, 'message' => 'Método não permitido.'], 405);
