<?php
/**
 * API de Autenticação - Sistema Real Driver
 * 
 * Endpoints:
 * POST /realdriver-auth.php?action=login
 * POST /realdriver-auth.php?action=logout
 * GET  /realdriver-auth.php?action=check
 * POST /realdriver-auth.php?action=changePassword
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];
$prefix = $config['system']['table_prefix'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com banco de dados']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($pdo, $prefix);
        break;
    case 'logout':
        handleLogout($pdo, $prefix);
        break;
    case 'check':
        handleCheck($pdo, $prefix);
        break;
    case 'changePassword':
        handleChangePassword($pdo, $prefix);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}

function handleLogin($pdo, $prefix) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['email'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
        return;
    }
    
    $email = trim($data['email']);
    $password = $data['password'];
    
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.email, u.senha_hash, u.ativo, u.perfil_id,
               p.nome as perfil_nome, p.permissoes
        FROM {$usuariosTable} u
        INNER JOIN {$perfisTable} p ON u.perfil_id = p.id
        WHERE u.email = :email
    ");
    
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Credenciais inválidas']);
        return;
    }
    
    if (!$user['ativo']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Usuário inativo']);
        return;
    }
    
    if (!password_verify($password, $user['senha_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Credenciais inválidas']);
        return;
    }
    
    // Gerar token
    $token = bin2hex(random_bytes(32));
    
    // Salvar token
    $tokensTable = ($prefix ? $prefix : '') . 'auth_tokens';
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Remover tokens antigos do usuário
    $pdo->prepare("DELETE FROM {$tokensTable} WHERE usuario_id = :id")->execute(['id' => $user['id']]);
    
    // Salvar novo token
    $stmt = $pdo->prepare("
        INSERT INTO {$tokensTable} (token, usuario_id, perfil_nome, expires_at)
        VALUES (:token, :usuario_id, :perfil_nome, :expires_at)
    ");
    $stmt->execute([
        'token' => $token,
        'usuario_id' => $user['id'],
        'perfil_nome' => $user['perfil_nome'],
        'expires_at' => $expiresAt
    ]);
    
    // Atualizar último acesso
    $pdo->prepare("UPDATE {$usuariosTable} SET ultimo_acesso = NOW() WHERE id = :id")->execute(['id' => $user['id']]);
    
    // Iniciar sessão
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['perfil_nome'] = $user['perfil_nome'];
    $_SESSION['auth_token'] = $token;
    
    $permissoes = json_decode($user['permissoes'], true) ?? [];
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'perfil' => $user['perfil_nome'],
            'permissoes' => $permissoes
        ]
    ]);
}

function handleLogout($pdo, $prefix) {
    session_start();
    
    $token = $_GET['token'] ?? $_POST['token'] ?? $_SESSION['auth_token'] ?? null;
    
    if ($token) {
        $tokensTable = ($prefix ? $prefix : '') . 'auth_tokens';
        $pdo->prepare("DELETE FROM {$tokensTable} WHERE token = :token")->execute(['token' => $token]);
    }
    
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso']);
}

function handleCheck($pdo, $prefix) {
    $token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
    if ($token) {
        // Remover "Bearer " se presente
        $token = str_replace('Bearer ', '', $token);
    }
    
    if (!$token) {
        // Tentar da sessão
        session_start();
        $token = $_SESSION['auth_token'] ?? null;
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token não fornecido']);
        return;
    }
    
    $tokensTable = ($prefix ? $prefix : '') . 'auth_tokens';
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    
    $stmt = $pdo->prepare("
        SELECT t.usuario_id, t.perfil_nome, t.expires_at,
               u.nome, u.email, u.ativo, p.permissoes
        FROM {$tokensTable} t
        INNER JOIN {$usuariosTable} u ON t.usuario_id = u.id
        INNER JOIN {$perfisTable} p ON u.perfil_id = p.id
        WHERE t.token = :token
        AND (t.expires_at IS NULL OR t.expires_at > NOW())
        AND u.ativo = 1
    ");
    
    $stmt->execute(['token' => $token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado']);
        return;
    }
    
    $permissoes = json_decode($tokenData['permissoes'], true) ?? [];
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $tokenData['usuario_id'],
            'nome' => $tokenData['nome'],
            'email' => $tokenData['email'],
            'perfil' => $tokenData['perfil_nome'],
            'permissoes' => $permissoes
        ]
    ]);
}

function handleChangePassword($pdo, $prefix) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    
    // Verificar autenticação
    $token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if ($token) {
        $token = str_replace('Bearer ', '', $token);
    } else {
        session_start();
        $token = $_SESSION['auth_token'] ?? null;
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autenticado']);
        return;
    }
    
    $tokensTable = ($prefix ? $prefix : '') . 'auth_tokens';
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    
    // Verificar token e permissões
    $stmt = $pdo->prepare("
        SELECT t.usuario_id, u.perfil_id, p.nome as perfil_nome, p.permissoes
        FROM {$tokensTable} t
        INNER JOIN {$usuariosTable} u ON t.usuario_id = u.id
        INNER JOIN {$perfisTable} p ON u.perfil_id = p.id
        WHERE t.token = :token AND (t.expires_at IS NULL OR t.expires_at > NOW())
        AND u.ativo = 1
    ");
    $stmt->execute(['token' => $token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        return;
    }
    
    // Verificar se tem permissão para trocar senha (root ou admin)
    $permissoes = json_decode($tokenData['permissoes'], true) ?? [];
    if ($tokenData['perfil_nome'] !== 'root' && !in_array('change_own_password', $permissoes)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permissão negada para trocar senha']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['senha_atual'], $data['senha_nova'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Senha atual e nova senha são obrigatórias']);
        return;
    }
    
    // Verificar senha atual
    $stmt = $pdo->prepare("SELECT senha_hash FROM {$usuariosTable} WHERE id = :id");
    $stmt->execute(['id' => $tokenData['usuario_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($data['senha_atual'], $user['senha_hash'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
        return;
    }
    
    // Atualizar senha
    $novaSenhaHash = password_hash($data['senha_nova'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE {$usuariosTable} SET senha_hash = :senha WHERE id = :id");
    $stmt->execute(['senha' => $novaSenhaHash, 'id' => $tokenData['usuario_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso']);
}

