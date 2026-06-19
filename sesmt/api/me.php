<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

sesmt_cors();
sesmt_options_exit();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$user = sesmt_require_auth();

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'nome' => $user['nome'],
        'username' => $user['username'],
        'role' => $user['role'],
        'perfis' => $user['perfis'],
    ],
], JSON_UNESCAPED_UNICODE);
