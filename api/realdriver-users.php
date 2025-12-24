<?php
/**
 * API de Gestão de Usuários - Sistema Real Driver
 * 
 * Apenas usuários com perfil 'root' podem acessar esta API
 * 
 * Endpoints:
 * GET  /realdriver-users.php - Listar usuários
 * POST /realdriver-users.php?action=create - Criar usuário
 * POST /realdriver-users.php?action=update&id=X - Atualizar usuário
 * POST /realdriver-users.php?action=delete&id=X - Deletar usuário
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

require_once __DIR__ . '/realdriver-permissions.php';

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

// Apenas root pode acessar
$user = requirePermission($pdo, $prefix, 'manage_users');

if ($user['perfil_nome'] !== 'root') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Apenas usuários root podem gerenciar usuários']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleList($pdo, $prefix);
        break;
    case 'create':
        handleCreate($pdo, $prefix);
        break;
    case 'update':
        handleUpdate($pdo, $prefix);
        break;
    case 'delete':
        handleDelete($pdo, $prefix);
        break;
    case 'getPerfis':
        handleGetPerfis($pdo, $prefix);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}

function handleList($pdo, $prefix) {
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    
    $stmt = $pdo->query("
        SELECT u.id, u.nome, u.email, u.ativo, u.ultimo_acesso, u.created_at, u.perfil_id,
               p.nome as perfil_nome, p.descricao as perfil_descricao
        FROM {$usuariosTable} u
        INNER JOIN {$perfisTable} p ON u.perfil_id = p.id
        ORDER BY u.created_at DESC
    ");
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $usuarios]);
}

function handleCreate($pdo, $prefix) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['nome'], $data['email'], $data['senha'], $data['perfil_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        return;
    }
    
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    
    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM {$usuariosTable} WHERE email = :email");
    $stmt->execute(['email' => $data['email']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email já cadastrado']);
        return;
    }
    
    $senhaHash = password_hash($data['senha'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO {$usuariosTable} (nome, email, senha_hash, perfil_id, ativo)
        VALUES (:nome, :email, :senha_hash, :perfil_id, :ativo)
    ");
    
    $stmt->execute([
        'nome' => $data['nome'],
        'email' => $data['email'],
        'senha_hash' => $senhaHash,
        'perfil_id' => $data['perfil_id'],
        'ativo' => $data['ativo'] ?? 1
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Buscar usuário criado
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.email, u.ativo, u.created_at,
               p.nome as perfil_nome, p.descricao as perfil_descricao
        FROM {$usuariosTable} u
        INNER JOIN {$perfisTable} p ON u.perfil_id = p.id
        WHERE u.id = :id
    ");
    $stmt->execute(['id' => $userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso', 'data' => $usuario]);
}

function handleUpdate($pdo, $prefix) {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        return;
    }
    
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    
    $fields = [];
    $params = ['id' => $id];
    
    if (isset($data['nome'])) {
        $fields[] = 'nome = :nome';
        $params['nome'] = $data['nome'];
    }
    
    if (isset($data['email'])) {
        // Verificar se email já existe em outro usuário
        $stmt = $pdo->prepare("SELECT id FROM {$usuariosTable} WHERE email = :email AND id != :id");
        $stmt->execute(['email' => $data['email'], 'id' => $id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email já cadastrado']);
            return;
        }
        $fields[] = 'email = :email';
        $params['email'] = $data['email'];
    }
    
    if (isset($data['senha']) && !empty($data['senha'])) {
        $fields[] = 'senha_hash = :senha_hash';
        $params['senha_hash'] = password_hash($data['senha'], PASSWORD_DEFAULT);
    }
    
    if (isset($data['perfil_id'])) {
        $fields[] = 'perfil_id = :perfil_id';
        $params['perfil_id'] = $data['perfil_id'];
    }
    
    if (isset($data['ativo'])) {
        $fields[] = 'ativo = :ativo';
        $params['ativo'] = $data['ativo'] ? 1 : 0;
    }
    
    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nenhum campo para atualizar']);
        return;
    }
    
    $sql = "UPDATE {$usuariosTable} SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Buscar usuário atualizado
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.email, u.ativo, u.created_at,
               p.nome as perfil_nome, p.descricao as perfil_descricao
        FROM {$usuariosTable} u
        INNER JOIN {$perfisTable} p ON u.perfil_id = p.id
        WHERE u.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso', 'data' => $usuario]);
}

function handleDelete($pdo, $prefix) {
    global $user;
    
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
        return;
    }
    
    // Não permitir deletar a si mesmo
    if ($id == $user['usuario_id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Não é possível deletar seu próprio usuário']);
        return;
    }
    
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $tokensTable = ($prefix ? $prefix : '') . 'auth_tokens';
    
    // Deletar tokens do usuário
    $pdo->prepare("DELETE FROM {$tokensTable} WHERE usuario_id = :id")->execute(['id' => $id]);
    
    // Deletar usuário
    $stmt = $pdo->prepare("DELETE FROM {$usuariosTable} WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    }
}

function handleGetPerfis($pdo, $prefix) {
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    
    $stmt = $pdo->query("SELECT id, nome, descricao FROM {$perfisTable} ORDER BY id");
    $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $perfis]);
}

