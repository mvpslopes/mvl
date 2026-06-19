-- Módulo de certificados (execute após schema.sql)

CREATE TABLE IF NOT EXISTS nr_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empresa_config (
    id INT PRIMARY KEY DEFAULT 1,
    nome_empresa VARCHAR(200) NOT NULL DEFAULT '',
    logo_path VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(40) NOT NULL UNIQUE,
    nr_tipo_id INT NOT NULL,
    nome_treinamento VARCHAR(120) NOT NULL,
    carga_horaria VARCHAR(40) NOT NULL,
    colaborador_nome VARCHAR(200) NOT NULL,
    colaborador_cpf VARCHAR(20) NOT NULL,
    data_certificado DATE NOT NULL,
    cidade VARCHAR(120) NOT NULL,
    empresa_nome VARCHAR(200) NOT NULL,
    empresa_id INT NULL,
    empresa_logo_path VARCHAR(255) NULL,
    assinaturas JSON NOT NULL,
    conteudo_ministrado TEXT NULL,
    pdf_path VARCHAR(255) NOT NULL,
    emitido_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nr (nr_tipo_id),
    INDEX idx_emitido (emitido_por),
    INDEX idx_colaborador (colaborador_nome),
    INDEX idx_data (data_certificado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lista completa: execute database/seed-nr-tipos.sql após este script

INSERT IGNORE INTO empresa_config (id, nome_empresa) VALUES (1, '');
