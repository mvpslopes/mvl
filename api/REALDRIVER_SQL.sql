-- ============================================
-- Script SQL para Criação do Banco de Dados
-- Sistema Real Driver
-- ============================================
-- Execute este script no banco: u179630068_realdriver
-- ============================================

-- ============================================
-- 1. TABELAS DE DADOS
-- ============================================

-- Tabela: motoristas
CREATE TABLE IF NOT EXISTS motoristas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(20) NOT NULL,
    cnh VARCHAR(50) NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    status VARCHAR(20) DEFAULT 'Ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_cpf (cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: veiculos
CREATE TABLE IF NOT EXISTS veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo VARCHAR(255) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    placa VARCHAR(10) NOT NULL UNIQUE,
    ano INT,
    cor VARCHAR(50),
    motorista_id INT,
    status VARCHAR(20) DEFAULT 'Ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_motorista (motorista_id),
    INDEX idx_placa (placa),
    FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: diarias
CREATE TABLE IF NOT EXISTS diarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motorista_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    data DATE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'Pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_motorista (motorista_id),
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_data (data),
    INDEX idx_status (status),
    FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: manutencoes
CREATE TABLE IF NOT EXISTS manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    data DATE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_tipo (tipo),
    INDEX idx_data (data),
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: contratos
CREATE TABLE IF NOT EXISTS contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    motorista_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    duracao_dias INT NOT NULL,
    valor_mensal DECIMAL(10,2) NOT NULL,
    observacoes TEXT,
    status VARCHAR(20) DEFAULT 'Ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_motorista (motorista_id),
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento),
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: financeiro
CREATE TABLE IF NOT EXISTS financeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data DATE NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    categoria VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_categoria (categoria),
    INDEX idx_data (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TABELAS DE SISTEMA
-- ============================================

-- Tabela: perfis
CREATE TABLE IF NOT EXISTS perfis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(255),
    permissoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil_id INT NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_perfil (perfil_id),
    INDEX idx_ativo (ativo),
    FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: auth_tokens
CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    usuario_id INT NOT NULL,
    perfil_nome VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_token (token),
    INDEX idx_usuario (usuario_id),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. INSERIR PERFIS PADRÃO
-- ============================================

INSERT IGNORE INTO perfis (nome, descricao, permissoes) VALUES
('root', 'Acesso total - pode criar usuários e fazer toda gestão', '["create_users","manage_users","read","write","delete","update"]'),
('admin', 'Pode lançar dados, deletar, atualizar e trocar própria senha', '["read","write","delete","update","change_own_password"]'),
('user', 'Apenas consultar dados', '["read"]');

-- ============================================
-- 4. INSERIR USUÁRIOS PRÉ-CONFIGURADOS
-- ============================================
-- IMPORTANTE: Execute primeiro os perfis acima!

-- Usuário Root: marcus@mvlopes.com.br
INSERT IGNORE INTO usuarios (nome, email, senha_hash, perfil_id)
SELECT 
    'Marcus Lopes' AS nome,
    'marcus@mvlopes.com.br' AS email,
    '$2a$12$KMd5H7XYnRYatJHkQc8hAO/T.ZmsPbZEKGediy9zIMvW5RaCaj4mG' AS senha_hash,
    id AS perfil_id
FROM perfis WHERE nome = 'root';

-- Usuário Admin: francisco@mvlopes.com.br
INSERT IGNORE INTO usuarios (nome, email, senha_hash, perfil_id)
SELECT 
    'Francisco' AS nome,
    'francisco@mvlopes.com.br' AS email,
    '$2a$12$ZrKwKffwm6eevB8w6MR5M.9/4ZYQqFtWPprbagjhDwATMalOWgnT' AS senha_hash,
    id AS perfil_id
FROM perfis WHERE nome = 'admin';

-- ============================================
-- FIM DO SCRIPT
-- ============================================
-- Verifique se todas as tabelas foram criadas:
-- SHOW TABLES;
-- 
-- Verifique os perfis:
-- SELECT * FROM perfis;
-- 
-- Verifique os usuários:
-- SELECT u.id, u.nome, u.email, p.nome as perfil FROM usuarios u INNER JOIN perfis p ON u.perfil_id = p.id;
-- ============================================

