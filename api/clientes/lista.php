<?php

declare(strict_types=1);

require_once __DIR__ . '/clientes.php';

clientes_cors();
clientes_options_exit();

if (clientes_request_method() !== 'GET') {
    clientes_json(['success' => false, 'message' => 'Método não permitido.'], 405);
}

try {
    $list = array_map('clientes_public_item', clientes_all());
    clientes_json(['success' => true, 'clientes' => $list]);
} catch (Throwable $e) {
    error_log('clientes lista: ' . $e->getMessage());
    clientes_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}
