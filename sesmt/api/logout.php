<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

sesmt_cors();
sesmt_options_exit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$token = sesmt_bearer_token();
if ($token) {
    $pdo = sesmt_pdo();
    $pdo->prepare('DELETE FROM auth_tokens WHERE token = :token')->execute(['token' => $token]);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();

echo json_encode(['success' => true, 'message' => 'Sessão encerrada.'], JSON_UNESCAPED_UNICODE);
