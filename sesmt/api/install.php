<?php

declare(strict_types=1);

/**
 * Execute uma vez após criar o banco na hospedagem.
 * Acesse: https://sesmt.mvlopes.com.br/api/install.php
 * Remova ou proteja este arquivo após a instalação.
 */

require __DIR__ . '/bootstrap.php';

sesmt_cors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use GET ou POST.']);
    exit;
}

$pdo = sesmt_pdo();

$rootHash = '$2a$12$r/bk8tHp9cn97M6EdGHHje4NcObWlqLW6Le/EVmUpGrbjb9HrepNi';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS perfis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(20) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            username VARCHAR(60) NOT NULL UNIQUE,
            senha_hash VARCHAR(255) NOT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuario_perfis (
            usuario_id INT NOT NULL,
            perfil_id INT NOT NULL,
            PRIMARY KEY (usuario_id, perfil_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    sesmt_ensure_auth_tables($pdo);

    $pdo->exec("INSERT IGNORE INTO perfis (nome) VALUES ('root'), ('admin')");

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = :username');
    $stmt->execute(['username' => 'marcus.lopes']);
    $existing = $stmt->fetch();

    if (!$existing) {
        $pdo->prepare("
            INSERT INTO usuarios (nome, username, senha_hash, ativo)
            VALUES (:nome, :username, :hash, 1)
        ")->execute([
            'nome' => 'Marcus Vinicius Lopes',
            'username' => 'marcus.lopes',
            'hash' => $rootHash,
        ]);

        $userId = (int) $pdo->lastInsertId();
        $rootPerfilId = (int) $pdo->query("SELECT id FROM perfis WHERE nome = 'root'")->fetchColumn();

        $pdo->prepare('INSERT INTO usuario_perfis (usuario_id, perfil_id) VALUES (:uid, :pid)')
            ->execute(['uid' => $userId, 'pid' => $rootPerfilId]);
    }

    require_once __DIR__ . '/lib/schema_certificados.php';
    sesmt_ensure_certificados_schema($pdo);

    echo json_encode([
        'success' => true,
        'message' => 'Banco instalado (usuários + certificados). Root: marcus.lopes. Remova install.php após confirmar.',
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na instalação: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
