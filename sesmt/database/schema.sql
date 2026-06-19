-- SESMT — Script de instalação do banco
-- Banco: u179630068_sesmt_bd
-- Execute no phpMyAdmin (aba SQL) com o banco já selecionado.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Perfis do sistema
CREATE TABLE IF NOT EXISTS perfis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relação usuário ↔ perfil
CREATE TABLE IF NOT EXISTS usuario_perfis (
    usuario_id INT NOT NULL,
    perfil_id INT NOT NULL,
    PRIMARY KEY (usuario_id, perfil_id),
    CONSTRAINT fk_up_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_up_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tokens de sessão (login)
CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    user_role VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_token (token),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Perfis padrão
INSERT IGNORE INTO perfis (nome) VALUES ('root'), ('admin');

-- Usuário root inicial (senha: *.Admin14!)
INSERT IGNORE INTO usuarios (nome, username, senha_hash, ativo)
VALUES (
    'Marcus Vinicius Lopes',
    'marcus.lopes',
    '$2a$12$r/bk8tHp9cn97M6EdGHHje4NcObWlqLW6Le/EVmUpGrbjb9HrepNi',
    1
);

-- Vincular marcus.lopes ao perfil root
INSERT IGNORE INTO usuario_perfis (usuario_id, perfil_id)
SELECT u.id, p.id
FROM usuarios u
CROSS JOIN perfis p
WHERE u.username = 'marcus.lopes'
  AND p.nome = 'root';
