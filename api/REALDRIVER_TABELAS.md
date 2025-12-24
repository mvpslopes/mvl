# 📊 Tabelas do Sistema Real Driver

## ✅ Tabelas Necessárias

O sistema Real Driver precisa de **6 tabelas** no banco de dados `u179630068_realdriver`:

### 1. **motoristas**
Cadastro de motoristas da empresa.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nome` (VARCHAR(255), NOT NULL)
- `cpf` (VARCHAR(20), NOT NULL)
- `cnh` (VARCHAR(50), NOT NULL)
- `telefone` (VARCHAR(20))
- `endereco` (TEXT)
- `status` (VARCHAR(20), DEFAULT 'Ativo')
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Índices:**
- `idx_status` (status)
- `idx_cpf` (cpf)

---

### 2. **veiculos**
Cadastro de veículos da frota.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `modelo` (VARCHAR(255), NOT NULL)
- `marca` (VARCHAR(100), NOT NULL)
- `placa` (VARCHAR(10), NOT NULL, UNIQUE)
- `ano` (INT)
- `cor` (VARCHAR(50))
- `motorista_id` (INT, FOREIGN KEY → motoristas.id)
- `status` (VARCHAR(20), DEFAULT 'Ativo')
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Índices:**
- `idx_status` (status)
- `idx_motorista` (motorista_id)
- `idx_placa` (placa)

**Foreign Keys:**
- `motorista_id` → `motoristas(id)` ON DELETE SET NULL

---

### 3. **diarias**
Controle de diárias pagas aos motoristas.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `motorista_id` (INT, NOT NULL, FOREIGN KEY → motoristas.id)
- `veiculo_id` (INT, NOT NULL, FOREIGN KEY → veiculos.id)
- `data` (DATE, NOT NULL)
- `valor` (DECIMAL(10,2), NOT NULL)
- `status` (VARCHAR(20), DEFAULT 'Pendente')
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Índices:**
- `idx_motorista` (motorista_id)
- `idx_veiculo` (veiculo_id)
- `idx_data` (data)
- `idx_status` (status)

**Foreign Keys:**
- `motorista_id` → `motoristas(id)` ON DELETE CASCADE
- `veiculo_id` → `veiculos(id)` ON DELETE CASCADE

---

### 4. **manutencoes**
Registro de manutenções dos veículos.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `veiculo_id` (INT, NOT NULL, FOREIGN KEY → veiculos.id)
- `tipo` (VARCHAR(50), NOT NULL) - Preventiva, Corretiva, Emergencial
- `data` (DATE, NOT NULL)
- `valor` (DECIMAL(10,2), NOT NULL)
- `descricao` (TEXT)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Índices:**
- `idx_veiculo` (veiculo_id)
- `idx_tipo` (tipo)
- `idx_data` (data)

**Foreign Keys:**
- `veiculo_id` → `veiculos(id)` ON DELETE CASCADE

---

### 5. **contratos**
Contratos de locação de veículos.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `veiculo_id` (INT, NOT NULL, FOREIGN KEY → veiculos.id)
- `motorista_id` (INT, NOT NULL, FOREIGN KEY → motoristas.id)
- `data_inicio` (DATE, NOT NULL)
- `data_vencimento` (DATE, NOT NULL)
- `duracao_dias` (INT, NOT NULL)
- `valor_mensal` (DECIMAL(10,2), NOT NULL)
- `observacoes` (TEXT)
- `status` (VARCHAR(20), DEFAULT 'Ativo')
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Índices:**
- `idx_veiculo` (veiculo_id)
- `idx_motorista` (motorista_id)
- `idx_status` (status)
- `idx_vencimento` (data_vencimento)

**Foreign Keys:**
- `veiculo_id` → `veiculos(id)` ON DELETE CASCADE
- `motorista_id` → `motoristas(id)` ON DELETE CASCADE

---

### 6. **financeiro**
Controle financeiro geral (receitas e despesas).

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `descricao` (VARCHAR(255), NOT NULL)
- `valor` (DECIMAL(10,2), NOT NULL)
- `data` (DATE, NOT NULL)
- `tipo` (VARCHAR(20), NOT NULL) - Receita ou Despesa
- `categoria` (VARCHAR(50)) - Diárias, Manutenção, Combustível, Outros
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Índices:**
- `idx_tipo` (tipo)
- `idx_categoria` (categoria)
- `idx_data` (data)

---

## 🚀 Como Criar as Tabelas

### Opção 1: Script Automático (Recomendado)

Acesse via navegador:
```
https://seusite.com.br/api/realdriver-db-setup.php
```

O script criará todas as tabelas automaticamente.

### Opção 2: Manual via phpMyAdmin

1. Acesse o phpMyAdmin
2. Selecione o banco `u179630068_realdriver`
3. Vá na aba "SQL"
4. Execute o script SQL gerado pelo `realdriver-db-setup.php`

---

## 📋 Resumo das Relações

```
motoristas (1) ──┐
                 ├──> veiculos (N) ──┐
motoristas (1) ──┘                   │
                                     ├──> diarias (N)
                                     │
                                     ├──> manutencoes (N)
                                     │
                                     └──> contratos (N)
                                             
financeiro (independente)
```

---

## ✅ Verificação

Após criar as tabelas, verifique:

1. ✅ Todas as 6 tabelas foram criadas
2. ✅ Foreign Keys estão configuradas corretamente
3. ✅ Índices foram criados
4. ✅ Charset é `utf8mb4`
5. ✅ Engine é `InnoDB`

---

**Próximo passo:** Execute o script de migração para importar os dados do backup.

