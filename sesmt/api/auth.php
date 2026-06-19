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

$data = sesmt_json_input();
$username = isset($data['username']) ? trim((string) $data['username']) : '';
$password = $data['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuário e senha são obrigatórios.']);
    exit;
}

$config = sesmt_config();
$pdo = sesmt_pdo();

try {
    $stmt = $pdo->prepare('
        SELECT u.id, u.nome, u.username, u.senha_hash, u.ativo
        FROM usuarios u
        WHERE u.username = :username
        LIMIT 1
    ');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['senha_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        exit;
    }

    if (!(int) $user['ativo']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Usuário inativo.']);
        exit;
    }

    $perfis = sesmt_user_perfis($pdo, (int) $user['id']);
    $perfilNomes = array_column($perfis, 'nome');
    $role = in_array('root', $perfilNomes, true) ? 'root' : ($perfilNomes[0] ?? 'admin');

    $token = bin2hex(random_bytes(32));
    $ttl = (int) ($config['app']['token_ttl_hours'] ?? 24);
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$ttl} hours"));

    sesmt_ensure_auth_tables($pdo);
    $pdo->prepare('DELETE FROM auth_tokens WHERE user_id = :uid')->execute(['uid' => $user['id']]);
    $pdo->prepare('
        INSERT INTO auth_tokens (token, user_id, user_role, expires_at)
        VALUES (:token, :uid, :role, :exp)
    ')->execute([
        'token' => $token,
        'uid' => $user['id'],
        'role' => $role,
        'exp' => $expiresAt,
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['auth_token'] = $token;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $role;

    echo json_encode([
        'success' => true,
        'token' => $token,
        'name' => $user['nome'],
        'username' => $user['username'],
        'role' => $role,
        'perfis' => $perfilNomes,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('SESMT auth: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor.']);
}
