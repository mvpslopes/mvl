<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

sesmt_cors();
sesmt_options_exit();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = sesmt_pdo();

if ($method === 'GET') {
    sesmt_require_root();

    $stmt = $pdo->query('
        SELECT u.id, u.nome, u.username, u.ativo, u.created_at, u.updated_at
        FROM usuarios u
        ORDER BY u.nome
    ');
    $rows = $stmt->fetchAll();
    $users = [];

    foreach ($rows as $row) {
        $perfis = sesmt_user_perfis($pdo, (int) $row['id']);
        $users[] = sesmt_format_user($row, $perfis);
    }

    echo json_encode(['success' => true, 'users' => $users], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    sesmt_require_root();
    $data = sesmt_json_input();

    $nome = trim((string) ($data['nome'] ?? ''));
    $username = trim((string) ($data['username'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $role = strtolower(trim((string) ($data['role'] ?? 'admin')));
    $ativo = isset($data['ativo']) ? (int) (bool) $data['ativo'] : 1;

    if ($nome === '' || $username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nome, usuário e senha são obrigatórios.']);
        exit;
    }

    if (!in_array($role, ['root', 'admin'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Perfil inválido. Use root ou admin.']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Senha deve ter no mínimo 8 caracteres.']);
        exit;
    }

    try {
        $pdo->prepare('
            INSERT INTO usuarios (nome, username, senha_hash, ativo)
            VALUES (:nome, :username, :hash, :ativo)
        ')->execute([
            'nome' => $nome,
            'username' => $username,
            'hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'ativo' => $ativo,
        ]);

        $userId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT id FROM perfis WHERE nome = :nome');
        $stmt->execute(['nome' => $role]);
        $perfilId = (int) $stmt->fetchColumn();

        $pdo->prepare('INSERT INTO usuario_perfis (usuario_id, perfil_id) VALUES (:uid, :pid)')
            ->execute(['uid' => $userId, 'pid' => $perfilId]);

        $row = $pdo->prepare('SELECT id, nome, username, ativo, created_at, updated_at FROM usuarios WHERE id = :id');
        $row->execute(['id' => $userId]);
        $userRow = $row->fetch();
        $perfis = sesmt_user_perfis($pdo, $userId);

        echo json_encode([
            'success' => true,
            'message' => 'Usuário criado.',
            'user' => sesmt_format_user($userRow, $perfis),
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Nome de usuário já existe.']);
            exit;
        }
        throw $e;
    }
    exit;
}

if ($method === 'PUT') {
    sesmt_require_root();
    $data = sesmt_json_input();
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }

    $nome = trim((string) ($data['nome'] ?? ''));
    $username = trim((string) ($data['username'] ?? ''));
    $role = isset($data['role']) ? strtolower(trim((string) $data['role'])) : null;
    $ativo = isset($data['ativo']) ? (int) (bool) $data['ativo'] : null;

    if ($nome === '' || $username === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nome e usuário são obrigatórios.']);
        exit;
    }

    if ($role !== null && !in_array($role, ['root', 'admin'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Perfil inválido.']);
        exit;
    }

    try {
        $fields = ['nome' => $nome, 'username' => $username, 'id' => $id];
        $sql = 'UPDATE usuarios SET nome = :nome, username = :username';
        if ($ativo !== null) {
            $sql .= ', ativo = :ativo';
            $fields['ativo'] = $ativo;
        }
        $sql .= ' WHERE id = :id';

        $pdo->prepare($sql)->execute($fields);

        if ($role !== null) {
            $stmt = $pdo->prepare('SELECT id FROM perfis WHERE nome = :nome');
            $stmt->execute(['nome' => $role]);
            $perfilId = (int) $stmt->fetchColumn();

            $pdo->prepare('DELETE FROM usuario_perfis WHERE usuario_id = :uid')->execute(['uid' => $id]);
            $pdo->prepare('INSERT INTO usuario_perfis (usuario_id, perfil_id) VALUES (:uid, :pid)')
                ->execute(['uid' => $id, 'pid' => $perfilId]);
        }

        $stmt = $pdo->prepare('SELECT id, nome, username, ativo, created_at, updated_at FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
            exit;
        }

        $perfis = sesmt_user_perfis($pdo, $id);
        echo json_encode([
            'success' => true,
            'message' => 'Usuário atualizado.',
            'user' => sesmt_format_user($row, $perfis),
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Nome de usuário já existe.']);
            exit;
        }
        throw $e;
    }
    exit;
}

if ($method === 'PATCH') {
    sesmt_require_root();
    $data = sesmt_json_input();
    $id = (int) ($data['id'] ?? 0);
    $password = (string) ($data['password'] ?? '');

    if ($id <= 0 || $password === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID e nova senha são obrigatórios.']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Senha deve ter no mínimo 8 caracteres.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
    $stmt->execute([
        'hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        'id' => $id,
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        exit;
    }

    $pdo->prepare('DELETE FROM auth_tokens WHERE user_id = :id')->execute(['id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Senha alterada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'DELETE') {
    $current = sesmt_require_root();
    $data = sesmt_json_input();
    $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }

    if ($id === $current['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Você não pode excluir sua própria conta.']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Usuário excluído.'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
