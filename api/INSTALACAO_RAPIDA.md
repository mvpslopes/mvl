# 🚀 Instalação Rápida - Google Analytics API

## Passo a Passo Simplificado

### 1️⃣ Google Cloud Console (5 minutos)

1. Acesse: https://console.cloud.google.com/
2. **Criar Projeto**: Clique no seletor de projetos → "Novo Projeto" → Nome: `MVLopes Analytics` → Criar
3. **Ativar API**: Menu lateral → "APIs e Serviços" → "Biblioteca" → Buscar "Google Analytics Data API" → ATIVAR
4. **Criar Service Account**: 
   - "APIs e Serviços" → "Credenciais" → "Criar credenciais" → "Conta de serviço"
   - Nome: `mvlopes-analytics-service` → Criar e continuar → Concluído
5. **Baixar Credenciais**:
   - Clique na Service Account criada → Aba "Chaves" → "Adicionar chave" → "Criar nova chave" → JSON
   - **Renomeie o arquivo para `credentials.json`**
   - **Mova para a pasta `api/` do seu projeto**

### 2️⃣ Google Analytics (2 minutos)

1. Acesse: https://analytics.google.com/
2. Menu → "Administração" (engrenagem) → "Acesso à propriedade"
3. "+" → "Adicionar usuários"
4. **Copie o email da Service Account** (está no arquivo `credentials.json`, campo `client_email`)
5. Cole o email → Permissão: "Visualizador" → Adicionar

### 3️⃣ Servidor (3 minutos)

**Opção A - Via SSH:**
```bash
cd api
composer require google/analytics-data
```

**Opção B - Via File Manager:**
1. No seu computador, na pasta do projeto:
```bash
cd api
composer require google/analytics-data
```
2. Faça upload da pasta `vendor/` para `api/vendor/` no servidor

### 4️⃣ Testar a API

#### Teste 1: Verificar se PHP está funcionando
1. Acesse: `https://seusite.com.br/api/test-basic.php`
2. Deve mostrar informações sobre PHP e arquivos na pasta

#### Teste 2: Teste completo de conexão (Recomendado)
1. Acesse: `https://seusite.com.br/api/test-connection.php`
2. Este teste verifica:
   - ✅ Arquivo `config.php` existe
   - ✅ Arquivo `credentials.json` existe
   - ✅ Biblioteca do Google Analytics instalada
   - ✅ Conexão com Google Analytics funcionando
   - ✅ Busca dados reais (usuários ativos dos últimos 7 dias)
3. Se aparecer "🎉 Tudo funcionando!", continue para o próximo passo
4. Se houver erro, verifique a mensagem e consulte a seção "Problemas Comuns"

#### Teste 3: Teste detalhado (para debug)
1. Acesse: `https://seusite.com.br/api/test-connection-simple.php`
2. Mostra informações detalhadas sobre cada etapa
3. Útil quando o teste 2 falha e você precisa de mais detalhes

#### Teste 4: Testar API de Autenticação
```bash
# Via terminal (PowerShell/CMD)
curl -X POST https://seusite.com.br/api/auth.php ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"seu-email@exemplo.com\",\"password\":\"sua-senha\"}"
```

Ou use uma ferramenta como **Postman** ou **Insomnia**:
- **URL**: `https://seusite.com.br/api/auth.php`
- **Método**: `POST`
- **Headers**: `Content-Type: application/json`
- **Body** (JSON):
```json
{
  "email": "seu-email@exemplo.com",
  "password": "sua-senha"
}
```

#### Teste 5: Testar API de Analytics (após login)
1. Primeiro, faça login via `/login` no navegador ou via API `auth.php`
2. Depois, acesse: `https://seusite.com.br/api/analytics.php?days=7`
3. Deve retornar JSON com dados do Google Analytics
4. **Nota**: Requer autenticação (sessão ativa ou token)

#### ⚠️ Importante
Após testar, **remova os arquivos de teste** por segurança:
- `test-connection.php`
- `test-connection-simple.php`
- `test-basic.php` (opcional, pode manter para debug)

### 5️⃣ Ativar (1 minuto)

1. Abra `api/config.php`
2. Altere: `'use_mock_data' => false`
3. Abra `api/analytics.php`
4. Substitua TODO o conteúdo pelo código de `api/analytics-real.php`

### 6️⃣ Pronto! 🎉

1. Acesse `/login` e faça login
2. Acesse `/dashboard`
3. Veja seus dados reais do Google Analytics!

---

## ⚠️ Problemas Comuns

**"Credentials file not found"**
→ Verifique se `credentials.json` está em `api/`

**"Biblioteca não encontrada"**
→ Execute: `composer require google/analytics-data` na pasta `api/`

**"Permission denied"**
→ Verifique se adicionou o email da Service Account no Google Analytics

**"API not enabled"**
→ Verifique se ativou a "Google Analytics Data API" no Google Cloud Console

---

## 📚 Documentação Completa

Para instruções detalhadas, consulte:
- `GUIA_CONFIGURACAO.md` - Guia completo passo a passo
- `CHECKLIST.md` - Checklist interativo

