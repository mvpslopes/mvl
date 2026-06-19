INSERT INTO proj_tipos (nome, cor, ordem) VALUES
  ('Pessoal', '#10b981', 1),
  ('Trabalho', '#3b82f6', 2),
  ('Lembrete', '#f97316', 3)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
