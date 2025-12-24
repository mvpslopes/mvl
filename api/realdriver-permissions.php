<?php
/**
 * Middleware de Permissões - Sistema Real Driver
 * 
 * Funções para verificar permissões dos usuários
 */

function getAuthToken() {
    // Tentar do header Authorization
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if ($authHeader) {
        return str_replace('Bearer ', '', $authHeader);
    }
    
    // Tentar do GET/POST
    $token = $_GET['token'] ?? $_POST['token'] ?? null;
    if ($token) {
        return $token;
    }
    
    // Tentar da sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['auth_token'] ?? null;
}

function verifyAuth($pdo, $prefix) {
    $token = getAuthToken();
    
    if (!$token) {
        return null;
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
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return null;
    }
    
    $user['permissoes'] = json_decode($user['permissoes'], true) ?? [];
    
    return $user;
}

function hasPermission($user, $permission) {
    if (!$user || !isset($user['permissoes'])) {
        return false;
    }
    
    $permissoes = is_array($user['permissoes']) ? $user['permissoes'] : [];
    
    // Root tem todas as permissões
    if ($user['perfil_nome'] === 'root') {
        return true;
    }
    
    return in_array($permission, $permissoes);
}

function requireAuth($pdo, $prefix) {
    $user = verifyAuth($pdo, $prefix);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autenticado']);
        exit;
    }
    
    return $user;
}

function requirePermission($pdo, $prefix, $permission) {
    $user = requireAuth($pdo, $prefix);
    
    if (!hasPermission($user, $permission)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        exit;
    }
    
    return $user;
}

// Mapeamento de ações para permissões
function getRequiredPermission($action, $type) {
    $permissions = [
        'get' => 'read',
        'getAll' => 'read',
        'save' => 'write',
        'delete' => 'delete',
        'update' => 'update'
    ];
    
    return $permissions[$action] ?? 'read';
}

