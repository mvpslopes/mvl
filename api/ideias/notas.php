<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

ideias_cors();
ideias_options_exit();
ideias_require_auth();

$svc = ideias_load_service();
$method = ideias_request_method();

try {
    if ($method === 'GET') {
        $view = trim((string) ($_GET['view'] ?? ''));
        $mes = trim((string) ($_GET['mes'] ?? '')) ?: null;
        $q = trim((string) ($_GET['q'] ?? '')) ?: null;

        if ($view === 'keywords') {
            ideias_json([
                'success' => true,
                'grupos' => $svc->groupByKeyword($mes, $q),
            ]);
        }

        $keyword = trim((string) ($_GET['keyword'] ?? '')) ?: null;
        $status = trim((string) ($_GET['status'] ?? '')) ?: null;
        ideias_json([
            'success' => true,
            'ideias' => $svc->listNotas($q, $mes, $keyword, $status),
        ]);
    }

    if ($method === 'POST') {
        $data = ideias_json_input();
        $ideia = $svc->createNota($data);
        ideias_json(['success' => true, 'ideia' => $ideia], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = ideias_json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            ideias_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $ideia = $svc->updateNota($id, $data);
        ideias_json(['success' => true, 'ideia' => $ideia]);
    }

    if ($method === 'DELETE') {
        $data = ideias_json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            ideias_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $svc->deleteNota($id);
        ideias_json(['success' => true]);
    }
} catch (InvalidArgumentException $e) {
    ideias_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('ideias notas: ' . $e->getMessage());
    ideias_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

ideias_json(['success' => false, 'message' => 'Método não permitido.'], 405);
