# 🧪 Como Testar a API

Este guia explica todas as formas de testar se a API está funcionando corretamente.

---

## 📋 Índice

1. [Teste Básico do PHP](#1-teste-básico-do-php)
2. [Teste de Conexão Completo](#2-teste-de-conexão-completo)
3. [Teste de Conexão Detalhado](#3-teste-de-conexão-detalhado)
4. [Testar API de Autenticação](#4-testar-api-de-autenticação)
5. [Testar API de Analytics](#5-testar-api-de-analytics)
6. [Testar via Terminal/CMD](#6-testar-via-terminalcmd)
7. [Testar via Postman/Insomnia](#7-testar-via-postmaninsomnia)
8. [Troubleshooting](#troubleshooting)

---

## 1. Teste Básico do PHP

**Objetivo**: Verificar se o PHP está funcionando no servidor.

### Como testar:
1. Acesse no navegador: `https://seusite.com.br/api/test-basic.php`
2. Deve mostrar:
   - ✅ Versão do PHP
   - ✅ Lista de arquivos na pasta `api/`
   - ✅ Verificação de arquivos importantes (`config.php`, `credentials.json`, `vendor/`)

### Resultado esperado:
```
✅ PHP está funcionando!
Versão PHP: 8.x.x
✅ config.php existe
✅ credentials.json existe
✅ vendor/ existe
```

---

## 2. Teste de Conexão Completo

**Objetivo**: Verificar se a conexão com Google Analytics está funcionando.

### Como testar:
1. Acesse no navegador: `https://seusite.com.br/api/test-connection.php`
2. Este teste verifica automaticamente:
   - ✅ Arquivo `config.php` existe e está correto
   - ✅ Arquivo `credentials.json` existe
   - ✅ Biblioteca do Google Analytics instalada
   - ✅ Cliente do Google Analytics criado
   - ✅ Conexão com Google Analytics funcionando
   - ✅ Busca dados reais (usuários ativos)

### Resultado esperado:
```
✅ Arquivo config.php encontrado
✅ Arquivo credentials.json encontrado
✅ Biblioteca instalada
✅ Cliente criado com sucesso
✅ Conexão com Google Analytics funcionando!
Usuários ativos (últimos 7 dias): 123

🎉 Tudo funcionando! Você pode usar a API real agora.
```

### Se houver erro:
- Verifique a mensagem de erro exibida
- Consulte a seção [Troubleshooting](#troubleshooting)

---

## 3. Teste de Conexão Detalhado

**Objetivo**: Obter informações detalhadas sobre cada etapa do teste (útil para debug).

### Como testar:
1. Acesse no navegador: `https://seusite.com.br/api/test-connection-simple.php`
2. Mostra informações detalhadas sobre:
   - Versão do PHP
   - Extensões PHP necessárias (json, curl, openssl, mbstring)
   - Caminhos dos arquivos
   - Stack trace de erros (se houver)

### Quando usar:
- Quando o teste completo (teste 2) falha
- Quando precisa de mais detalhes sobre o erro
- Para verificar extensões PHP instaladas

---

## 4. Testar API de Autenticação

**Objetivo**: Verificar se o login está funcionando.

### Opção A: Via Navegador (Frontend)
1. Acesse: `https://seusite.com.br/login`
2. Faça login com suas credenciais
3. Se funcionar, você será redirecionado para o dashboard

### Opção B: Via API Direta

#### Windows PowerShell:
```powershell
$body = @{
    email = "seu-email@exemplo.com"
    password = "sua-senha"
} | ConvertTo-Json

Invoke-RestMethod -Uri "https://seusite.com.br/api/auth.php" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body
```

#### Windows CMD (com curl):
```cmd
curl -X POST https://seusite.com.br/api/auth.php ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"seu-email@exemplo.com\",\"password\":\"sua-senha\"}"
```

### Resultado esperado (sucesso):
```json
{
  "success": true,
  "token": "abc123...",
  "name": "Seu Nome",
  "email": "seu-email@exemplo.com",
  "role": "root"
}
```

### Resultado esperado (erro):
```json
{
  "success": false,
  "message": "Credenciais inválidas"
}
```

---

## 5. Testar API de Analytics

**Objetivo**: Verificar se a API está retornando dados do Google Analytics.

### ⚠️ Importante:
Este endpoint **requer autenticação**. Você precisa estar logado primeiro.

### Opção A: Via Navegador (após login)
1. Faça login em `/login`
2. Acesse: `https://seusite.com.br/api/analytics.php?days=7`
3. Deve retornar JSON com dados do Google Analytics

### Opção B: Via API com Sessão

#### Windows PowerShell:
```powershell
# Primeiro, faça login e salve o cookie de sessão
$loginBody = @{
    email = "seu-email@exemplo.com"
    password = "sua-senha"
} | ConvertTo-Json

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$response = Invoke-WebRequest -Uri "https://seusite.com.br/api/auth.php" `
    -Method POST `
    -ContentType "application/json" `
    -Body $loginBody `
    -SessionVariable session

# Agora teste a API de analytics
Invoke-RestMethod -Uri "https://seusite.com.br/api/analytics.php?days=7" `
    -WebSession $session
```

### Resultado esperado (com dados mockados):
```json
{
  "success": true,
  "data": {
    "totalUsers": 456,
    "totalSessions": 789,
    "pageViews": 1234,
    "bounceRate": 45.2,
    "topPages": [...],
    "trafficSources": [...]
  },
  "note": "Dados mockados. Configure use_mock_data => false em config.php"
}
```

### Resultado esperado (com dados reais):
```json
{
  "success": true,
  "data": {
    "totalUsers": 123,
    "totalSessions": 456,
    "pageViews": 789,
    "bounceRate": 42.5,
    "topPages": [...],
    "trafficSources": [...]
  },
  "dateRange": {
    "start": "2024-01-01",
    "end": "2024-01-08",
    "days": 7
  }
}
```

### Parâmetros:
- `days` (opcional): Número de dias para buscar dados (padrão: 7)
  - Exemplo: `?days=30` para últimos 30 dias

---

## 6. Testar via Terminal/CMD

### Testar Autenticação:
```cmd
curl -X POST https://seusite.com.br/api/auth.php ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"seu-email@exemplo.com\",\"password\":\"sua-senha\"}"
```

### Testar Analytics (requer autenticação):
```cmd
curl https://seusite.com.br/api/analytics.php?days=7 ^
  -H "Cookie: PHPSESSID=seu-session-id-aqui"
```

**Nota**: Para obter o session ID, primeiro faça login via navegador e copie o cookie `PHPSESSID` do DevTools.

---

## 7. Testar via Postman/Insomnia

### Configurar Postman/Insomnia:

#### 1. Teste de Autenticação:
- **Método**: `POST`
- **URL**: `https://seusite.com.br/api/auth.php`
- **Headers**:
  - `Content-Type: application/json`
- **Body** (raw JSON):
```json
{
  "email": "seu-email@exemplo.com",
  "password": "sua-senha"
}
```

#### 2. Teste de Analytics:
- **Método**: `GET`
- **URL**: `https://seusite.com.br/api/analytics.php?days=7`
- **Headers**:
  - `Cookie: PHPSESSID=seu-session-id` (obtido do teste de autenticação)

**Dica**: No Postman, você pode configurar variáveis de ambiente para facilitar:
- `{{base_url}}` = `https://seusite.com.br`
- `{{session_id}}` = (atualizado automaticamente após login)

---

## Troubleshooting

### ❌ "Credentials file not found"
**Solução**: 
- Verifique se `credentials.json` está na pasta `api/`
- Verifique o caminho em `config.php` (campo `credentials_path`)

### ❌ "Biblioteca não encontrada"
**Solução**:
```bash
cd api
composer require google/analytics-data
```

### ❌ "Permission denied" ou "Access denied"
**Solução**:
1. Verifique se adicionou o email da Service Account no Google Analytics
2. O email está no arquivo `credentials.json` (campo `client_email`)
3. No Google Analytics: Administração → Acesso à propriedade → Adicionar usuário

### ❌ "API not enabled"
**Solução**:
1. Acesse Google Cloud Console
2. APIs e Serviços → Biblioteca
3. Busque "Google Analytics Data API"
4. Clique em "ATIVAR"

### ❌ "Property ID incorreto"
**Solução**:
1. Verifique o Property ID em `config.php`
2. Formato correto: apenas números (ex: `123456789`)
3. Não inclua "properties/" no ID

### ❌ "Não autorizado" ao testar analytics.php
**Solução**:
- Você precisa estar logado primeiro
- Faça login via `/login` ou via `auth.php`
- A API verifica a sessão PHP

### ❌ Erro 500 (Internal Server Error)
**Solução**:
1. Verifique os logs de erro do PHP
2. Verifique se todas as dependências estão instaladas
3. Execute `test-connection-simple.php` para ver detalhes do erro

### ❌ "Nenhum dado encontrado"
**Solução**:
- Pode ser normal se o site é novo
- Verifique se há dados no Google Analytics para o período testado
- Tente aumentar o período (ex: `?days=30`)

---

## ✅ Checklist de Testes

Marque cada item conforme testa:

- [ ] Teste básico do PHP (`test-basic.php`) funcionando
- [ ] Teste de conexão completo (`test-connection.php`) passou
- [ ] API de autenticação (`auth.php`) retorna token
- [ ] API de analytics (`analytics.php`) retorna dados
- [ ] Dashboard no frontend mostra dados corretamente

---

## 🔒 Segurança

⚠️ **IMPORTANTE**: Após testar, remova os arquivos de teste do servidor:
- `test-connection.php`
- `test-connection-simple.php`
- `test-basic.php` (opcional, pode manter para debug futuro)

Esses arquivos podem expor informações sensíveis sobre sua configuração.

---

## 📞 Precisa de Ajuda?

Consulte também:
- `INSTALACAO_RAPIDA.md` - Guia de instalação rápida
- `GUIA_CONFIGURACAO.md` - Guia completo de configuração
- `CHECKLIST.md` - Checklist interativo

