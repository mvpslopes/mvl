<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();
fin_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);
}

$token = fin_bearer_token();
if ($token === null && session_status() === PHP_SESSION_NONE) {
    session_name(fin_config()['app']['session_name'] ?? 'FINSESSID');
    session_start();
    $token = $_SESSION['auth_token'] ?? null;
}

if ($token) {
    fin_invalidate_token(fin_pdo(), $token);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_destroy();
}

fin_json(['success' => true]);
