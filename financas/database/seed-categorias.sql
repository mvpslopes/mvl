INSERT INTO fin_categorias (nome, tipo, cor) VALUES
  ('Salário', 'receita', '#10b981'),
  ('Freelance', 'receita', '#059669'),
  ('Investimentos', 'receita', '#34d399'),
  ('Outras receitas', 'receita', '#6ee7b7'),
  ('Moradia', 'despesa', '#ef4444'),
  ('Alimentação', 'despesa', '#f97316'),
  ('Transporte', 'despesa', '#eab308'),
  ('Saúde', 'despesa', '#ec4899'),
  ('Educação', 'despesa', '#3b82f6'),
  ('Lazer', 'despesa', '#8b5cf6'),
  ('Outras despesas', 'despesa', '#64748b')
ON DUPLICATE KEY UPDATE nome = nome;
