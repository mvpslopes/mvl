-- Finanças pessoais MVL — schema MySQL 8+

CREATE TABLE IF NOT EXISTS fin_config (
  id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  saldo_referencia DECIMAL(14, 2) NOT NULL DEFAULT 0,
  data_referencia DATE NOT NULL,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO fin_config (id, saldo_referencia, data_referencia)
VALUES (1, 0, CURDATE())
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS fin_categorias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(80) NOT NULL,
  tipo ENUM('receita', 'despesa', 'ambos') NOT NULL DEFAULT 'ambos',
  cor VARCHAR(7) NOT NULL DEFAULT '#6366f1',
  ativa TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_fin_cat_nome (nome)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_recorrencias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo ENUM('receita', 'despesa') NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(14, 2) NOT NULL,
  dia_vencimento TINYINT UNSIGNED NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NULL,
  categoria_id INT UNSIGNED NULL,
  ativa TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rec_ativa (ativa),
  KEY idx_rec_cat (categoria_id),
  CONSTRAINT fk_rec_cat FOREIGN KEY (categoria_id) REFERENCES fin_categorias (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_lancamentos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo ENUM('receita', 'despesa') NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(14, 2) NOT NULL,
  valor_realizado DECIMAL(14, 2) NULL,
  data_vencimento DATE NOT NULL,
  data_efetivacao DATE NULL,
  mes_referencia CHAR(7) NOT NULL,
  status ENUM('prevista', 'recebida', 'paga', 'cancelada') NOT NULL DEFAULT 'prevista',
  categoria_id INT UNSIGNED NULL,
  recorrencia_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lanc_mes (mes_referencia),
  KEY idx_lanc_venc (data_vencimento),
  KEY idx_lanc_efet (data_efetivacao),
  KEY idx_lanc_cat (categoria_id),
  KEY idx_lanc_rec_mes (recorrencia_id, mes_referencia),
  CONSTRAINT fk_lanc_rec FOREIGN KEY (recorrencia_id) REFERENCES fin_recorrencias (id) ON DELETE SET NULL,
  CONSTRAINT fk_lanc_cat FOREIGN KEY (categoria_id) REFERENCES fin_categorias (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_auth_tokens (
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (token),
  KEY idx_fin_token_exp (expires_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
