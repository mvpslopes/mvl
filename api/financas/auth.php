<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);
}

$data = fin_json_input();
$username = trim((string) ($data['username'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($username === '' || $password === '') {
    fin_json(['success' => false, 'message' => 'Usuário e senha são obrigatórios.'], 400);
}

$config = fin_config();
$auth = $config['auth'] ?? [];

if ($username !== ($auth['username'] ?? '') || !password_verify($password, $auth['password_hash'] ?? '')) {
    fin_json(['success' => false, 'message' => 'Credenciais inválidas.'], 401);
}

$pdo = fin_pdo();
$token = fin_create_token($pdo);

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['app']['session_name'] ?? 'FINSESSID');
    session_start();
}
$_SESSION['auth_token'] = $token;

fin_json([
    'success' => true,
    'token' => $token,
    'name' => $auth['display_name'] ?? 'Finanças',
    'username' => $username,
]);
