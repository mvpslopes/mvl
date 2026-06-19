-- Demandas / Projetos MVL — schema MySQL 8+

CREATE TABLE IF NOT EXISTS proj_tipos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(40) NOT NULL,
  cor VARCHAR(7) NOT NULL DEFAULT '#6366f1',
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_proj_tipo_nome (nome)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proj_demandas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(200) NOT NULL,
  descricao TEXT NULL,
  tipo_id INT UNSIGNED NOT NULL,
  data_prevista DATE NULL,
  status ENUM('pendente', 'em_andamento', 'concluida', 'cancelada') NOT NULL DEFAULT 'pendente',
  prioridade ENUM('baixa', 'media', 'alta') NOT NULL DEFAULT 'media',
  concluida_em DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_dem_data (data_prevista),
  KEY idx_dem_status (status),
  KEY idx_dem_tipo (tipo_id),
  CONSTRAINT fk_dem_tipo FOREIGN KEY (tipo_id) REFERENCES proj_tipos (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proj_checklist (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  demanda_id INT UNSIGNED NOT NULL,
  texto VARCHAR(255) NOT NULL,
  concluido TINYINT(1) NOT NULL DEFAULT 0,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_chk_dem (demanda_id),
  CONSTRAINT fk_chk_dem FOREIGN KEY (demanda_id) REFERENCES proj_demandas (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
