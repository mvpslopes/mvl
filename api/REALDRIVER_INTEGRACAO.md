# 🚗 Integração do Sistema Real Driver

Este documento explica como integrar o sistema Real Driver ao site, usando banco de dados MySQL ao invés de LocalStorage.

## 📋 Pré-requisitos

1. Banco de dados MySQL configurado
2. PHP 7.4 ou superior
3. Extensão PDO MySQL habilitada

## 🚀 Passo a Passo

### 1. Criar as Tabelas no Banco de Dados

Acesse via navegador:
```
https://seusite.com.br/api/realdriver-db-setup.php
```

Este script criará todas as tabelas necessárias:
- `realdriver_motoristas`
- `realdriver_veiculos`
- `realdriver_diarias`
- `realdriver_manutencoes`
- `realdriver_contratos`
- `realdriver_financeiro`

**⚠️ IMPORTANTE:** Após criar as tabelas, remova ou proteja este arquivo por segurança.

### 2. Migrar Dados do Backup

Se você já tem dados no sistema Real Driver, importe-os do backup JSON:

Acesse via navegador:
```
https://seusite.com.br/api/realdriver-migrate.php?file=SisRealDriver_Backup_2025-12-24_12-27-38.json
```

Ou use o backup mais recente:
```
https://seusite.com.br/api/realdriver-migrate.php
```

**⚠️ IMPORTANTE:** Após migrar os dados, remova ou proteja este arquivo por segurança.

### 3. Acessar o Sistema

O sistema Real Driver estará disponível em:
```
https://seusite.com.br/api/realdriver.php
```

Ou através da seção de Sistemas Embarcados:
```
https://seusite.com.br/sistemas-embarcados
```

## 📁 Estrutura de Arquivos

```
api/
├── realdriver-config.php          # Configuração do banco de dados
├── realdriver-db-setup.php         # Script de criação das tabelas
├── realdriver-migrate.php          # Script de migração de dados
├── realdriver-api.php              # API REST para gerenciar dados
├── realdriver.php                  # Servidor do sistema Real Driver
└── realdriver-api-wrapper.js       # Wrapper JavaScript para usar API

SistemasEmbarcados/
└── realdriver/
    ├── index.html                  # Interface do sistema
    ├── script.js                   # Lógica do sistema
    ├── styles.css                  # Estilos
    └── backups/                    # Backups JSON
```

## 🔧 Configuração

### Arquivo de Configuração

Edite `api/realdriver-config.php` se necessário:

```php
return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'u179630068_mvl',
        'username' => 'u179630068_mvlroot',
        'password' => '/5ld=SX7j;W',
        'charset' => 'utf8mb4'
    ],
    'system' => [
        'name' => 'Real Driver',
        'version' => '1.0.0',
        'table_prefix' => 'realdriver_'
    ]
];
```

## 📡 API REST

A API está disponível em `/api/realdriver-api.php`:

### Endpoints

#### Carregar Todos os Dados
```
GET /api/realdriver-api.php?action=getAll
```

#### Carregar Dados de um Tipo
```
GET /api/realdriver-api.php?action=get&type=motoristas
GET /api/realdriver-api.php?action=get&type=veiculos
GET /api/realdriver-api.php?action=get&type=diarias
GET /api/realdriver-api.php?action=get&type=manutencoes
GET /api/realdriver-api.php?action=get&type=contratos
GET /api/realdriver-api.php?action=get&type=financeiro
```

#### Carregar Item Específico
```
GET /api/realdriver-api.php?action=get&type=motoristas&id=1
```

#### Salvar Item
```
POST /api/realdriver-api.php?action=save&type=motoristas
Content-Type: application/json

{
  "nome": "João Silva",
  "cpf": "123.456.789-00",
  "cnh": "123456789",
  "telefone": "31999999999",
  "endereco": "Rua Exemplo, 123",
  "status": "Ativo"
}
```

#### Deletar Item
```
GET /api/realdriver-api.php?action=delete&type=motoristas&id=1
```

## 🔄 Como Funciona

1. **Carregamento de Dados:**
   - O sistema tenta carregar dados da API
   - Se a API não estiver disponível, usa LocalStorage como fallback
   - Dados são carregados uma vez e mantidos em cache

2. **Salvamento de Dados:**
   - Quando um item é salvo (motorista, veículo, etc.), é enviado para a API
   - O cache é invalidado para forçar recarregamento
   - Backups locais continuam funcionando

3. **Exclusão de Dados:**
   - Quando um item é excluído, a API é chamada
   - O cache é invalidado

## 🛡️ Segurança

1. **Remova os Scripts de Setup:**
   - Após criar as tabelas, remova `realdriver-db-setup.php`
   - Após migrar dados, remova `realdriver-migrate.php`

2. **Proteja a API:**
   - Considere adicionar autenticação à API
   - Use HTTPS em produção
   - Valide todos os inputs

3. **Backups:**
   - Faça backups regulares do banco de dados
   - Mantenha os backups JSON em local seguro

## 🐛 Troubleshooting

### Erro: "Tabelas não encontradas"
- Execute `realdriver-db-setup.php` novamente
- Verifique as permissões do banco de dados

### Erro: "Dados não carregam"
- Verifique se a API está acessível
- Verifique o console do navegador (F12)
- Verifique se o banco de dados está conectado

### Erro: "Erro ao salvar"
- Verifique se os campos obrigatórios estão preenchidos
- Verifique o console do navegador para mais detalhes
- Verifique as permissões de escrita no banco de dados

## 📝 Notas Importantes

- Os dados são armazenados em um banco de dados separado (mesmo servidor, tabelas diferentes)
- O sistema mantém compatibilidade com backups locais
- A API funciona de forma assíncrona (async/await)
- O cache é invalidado automaticamente após operações de escrita

## 🔗 Links Úteis

- Sistema Real Driver: `/api/realdriver.php`
- Seção de Sistemas Embarcados: `/sistemas-embarcados`
- API REST: `/api/realdriver-api.php`

---

**Desenvolvido para integração completa do sistema Real Driver ao site MVLopes**

