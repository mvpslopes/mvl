<?php

declare(strict_types=1);

require_once __DIR__ . '/clientes.php';

clientes_cors();
clientes_options_exit();
clientes_require_auth();

$method = clientes_request_method();

try {
    if ($method === 'GET') {
        $id = trim((string) ($_GET['id'] ?? ''));
        if ($id !== '') {
            $item = clientes_find($id);
            if (!$item) {
                clientes_json(['success' => false, 'message' => 'Cliente não encontrado.'], 404);
            }
            clientes_json(['success' => true, 'cliente' => clientes_admin_item($item)]);
        }
        $list = array_map('clientes_admin_item', clientes_all());
        clientes_json(['success' => true, 'clientes' => $list]);
    }

    if ($method === 'POST') {
        $action = trim((string) ($_POST['action'] ?? ''));
        if ($action === 'reorder') {
            $rawIds = $_POST['ids'] ?? '[]';
            if (is_string($rawIds)) {
                $decoded = json_decode($rawIds, true);
                $ids = is_array($decoded) ? $decoded : [];
            } elseif (is_array($rawIds)) {
                $ids = $rawIds;
            } else {
                $ids = [];
            }
            clientes_reorder($ids);
            $list = array_map('clientes_admin_item', clientes_all());
            clientes_json(['success' => true, 'clientes' => $list]);
        }

        if ($action === 'seed') {
            $force = (($_POST['force'] ?? '') === '1' || ($_POST['force'] ?? '') === 'true');
            $n = clientes_seed_if_empty($force);
            $list = array_map('clientes_admin_item', clientes_all());
            clientes_json([
                'success' => true,
                'imported' => $n,
                'message' => $n > 0
                    ? "Importadas {$n} logos padrão."
                    : ($force ? 'Nenhuma logo no seed.' : 'Lista já possui logos; nada importado.'),
                'clientes' => $list,
            ]);
        }

        $fields = [
            'name' => $_POST['name'] ?? '',
            'url' => $_POST['url'] ?? '',
            'bg_color' => $_POST['bg_color'] ?? '#FFFFFF',
        ];
        if (isset($_POST['sort_order'])) {
            $fields['sort_order'] = $_POST['sort_order'];
        }

        $id = trim((string) ($_POST['id'] ?? ''));
        if ($id !== '') {
            $logoMeta = clientes_store_upload('logo', $id);
            $item = clientes_update($id, $fields, $logoMeta, $logoMeta !== null);
            if (!$item) {
                clientes_json(['success' => false, 'message' => 'Cliente não encontrado.'], 404);
            }
            clientes_json(['success' => true, 'cliente' => clientes_admin_item($item)]);
        }

        $newId = clientes_new_id();
        $logoMeta = clientes_store_upload('logo', $newId);
        if ($logoMeta === null) {
            clientes_json(['success' => false, 'message' => 'Envie a logo do cliente.'], 400);
        }
        $item = clientes_create($fields, $logoMeta, $newId);
        clientes_json(['success' => true, 'cliente' => clientes_admin_item($item)], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = clientes_json_input();
        if ($data === []) {
            $data = $_POST;
        }
        $id = trim((string) ($data['id'] ?? $_GET['id'] ?? ''));
        if ($id === '') {
            clientes_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $fields = [];
        foreach (['name', 'url', 'bg_color', 'sort_order'] as $k) {
            if (array_key_exists($k, $data)) {
                $fields[$k] = $data[$k];
            }
        }
        $logoMeta = null;
        if (!empty($_FILES['logo'])) {
            $logoMeta = clientes_store_upload('logo', $id);
        }
        $item = clientes_update($id, $fields, $logoMeta, $logoMeta !== null);
        if (!$item) {
            clientes_json(['success' => false, 'message' => 'Cliente não encontrado.'], 404);
        }
        clientes_json(['success' => true, 'cliente' => clientes_admin_item($item)]);
    }

    if ($method === 'DELETE') {
        $data = clientes_json_input();
        $id = trim((string) ($data['id'] ?? $_GET['id'] ?? ''));
        if ($id === '') {
            clientes_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        if (!clientes_delete($id)) {
            clientes_json(['success' => false, 'message' => 'Cliente não encontrado.'], 404);
        }
        clientes_json(['success' => true]);
    }
} catch (InvalidArgumentException $e) {
    clientes_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('clientes admin: ' . $e->getMessage());
    clientes_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

clientes_json(['success' => false, 'message' => 'Método não permitido.'], 405);
