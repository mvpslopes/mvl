<?php

declare(strict_types=1);

require_once __DIR__ . '/briefings.php';

briefing_cors();
briefing_options_exit();
briefing_require_auth();

$method = briefing_request_method();

try {
    if ($method === 'GET') {
        $id = trim((string) ($_GET['id'] ?? ''));
        if ($id !== '') {
            $item = briefings_find($id);
            if (!$item) {
                briefing_json(['success' => false, 'message' => 'Briefing não encontrado.'], 404);
            }
            if (($item['status'] ?? '') === 'new') {
                $item = briefings_update($id, ['status' => 'read']) ?? $item;
            }
            briefing_json([
                'success' => true,
                'briefing' => $item,
                'whatsapp_url' => briefings_whatsapp_url($item),
            ]);
        }

        $status = trim((string) ($_GET['status'] ?? ''));
        $list = briefings_all();
        if ($status !== '' && $status !== 'all') {
            $list = array_values(array_filter(
                $list,
                static fn ($b) => ($b['status'] ?? '') === $status
            ));
        }

        briefing_json([
            'success' => true,
            'briefings' => $list,
            'count_new' => briefings_count_new(),
        ]);
    }

    if ($method === 'DELETE') {
        $data = briefing_json_input();
        if ($data === []) {
            $data = $_POST;
        }
        $id = trim((string) ($data['id'] ?? $_GET['id'] ?? ''));
        if ($id === '') {
            briefing_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        if (!briefings_delete($id)) {
            briefing_json(['success' => false, 'message' => 'Briefing não encontrado.'], 404);
        }
        briefing_json(['success' => true]);
    }

    if ($method === 'PUT' || $method === 'PATCH' || $method === 'POST') {
        $data = briefing_json_input();
        if ($data === []) {
            $data = $_POST;
        }
        $id = trim((string) ($data['id'] ?? $_GET['id'] ?? ''));
        if ($id === '') {
            briefing_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $status = trim((string) ($data['status'] ?? ''));
        $allowed = ['new', 'read', 'quoted', 'archived'];
        if (!in_array($status, $allowed, true)) {
            briefing_json(['success' => false, 'message' => 'Status inválido.'], 400);
        }
        $item = briefings_update($id, ['status' => $status]);
        if (!$item) {
            briefing_json(['success' => false, 'message' => 'Briefing não encontrado.'], 404);
        }
        briefing_json(['success' => true, 'briefing' => $item]);
    }
} catch (Throwable $e) {
    error_log('briefing admin: ' . $e->getMessage());
    briefing_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

briefing_json(['success' => false, 'message' => 'Método não permitido.'], 405);
