-- Cadastro de empresas (nome + logo) para reutilização em certificados

CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    logo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresas_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE certificados ADD COLUMN empresa_id INT NULL AFTER empresa_nome;
ALTER TABLE certificados ADD COLUMN empresa_logo_path VARCHAR(255) NULL AFTER empresa_id;
