-- Ideias / observações MVL — schema MySQL 8+
-- Método: frases curtas + palavras-chave + agrupamento alfabético

CREATE TABLE IF NOT EXISTS ideias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  texto VARCHAR(500) NOT NULL,
  fonte VARCHAR(120) NULL,
  favorito TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('raw', 'usado') NOT NULL DEFAULT 'raw',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ideia_created (created_at),
  KEY idx_ideia_status (status),
  KEY idx_ideia_favorito (favorito)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ideias_keywords (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ideia_kw_slug (slug),
  KEY idx_ideia_kw_nome (nome)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ideias_ideia_keyword (
  ideia_id INT UNSIGNED NOT NULL,
  keyword_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (ideia_id, keyword_id),
  KEY idx_iik_keyword (keyword_id),
  CONSTRAINT fk_iik_ideia FOREIGN KEY (ideia_id) REFERENCES ideias (id) ON DELETE CASCADE,
  CONSTRAINT fk_iik_keyword FOREIGN KEY (keyword_id) REFERENCES ideias_keywords (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
