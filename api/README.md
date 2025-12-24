# Configuração da API do Google Analytics

## Status Atual

Atualmente, o sistema está retornando **dados mockados** (simulados) para demonstração. Para obter dados reais do Google Analytics, você precisa configurar a API oficial.

## Como Configurar a API do Google Analytics

### 1. Criar Projeto no Google Cloud Console

1. Acesse [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto ou selecione um existente
3. Ative a **Google Analytics Data API** no menu de APIs

### 2. Criar Credenciais OAuth 2.0

1. Vá em **APIs e Serviços** → **Credenciais**
2. Clique em **Criar credenciais** → **ID do cliente OAuth**
3. Configure a tela de consentimento (se necessário)
4. Baixe o arquivo JSON das credenciais
5. Renomeie para `credentials.json` e coloque na pasta `api/`

### 3. Property ID Configurado

✅ **Property ID já configurado**: `13183308243` (Código do Fluxo)
✅ **Measurement ID**: `G-6ZCVW4LQG9` (ID da Métrica)

Estes valores já estão configurados no arquivo `api/analytics.php`.

### 4. Instalar Biblioteca PHP

No servidor, instale o Composer e depois:

```bash
cd api
composer require google/analytics-data
```

### 5. Atualizar o arquivo `analytics.php`

O Property ID (`13183308243`) já está configurado. Agora você precisa:

1. Descomentar o código da API no final do arquivo `api/analytics.php`
2. Ajustar o caminho do arquivo `credentials.json` (se necessário)
3. Instalar a biblioteca PHP do Google Analytics via Composer

## Estrutura de Dados Retornados

A API retorna:

```json
{
  "success": true,
  "data": {
    "totalUsers": 1234,
    "totalSessions": 1567,
    "pageViews": 2345,
    "bounceRate": 45.2,
    "topPages": [
      { "page": "/", "views": 500 },
      { "page": "/servicos", "views": 300 }
    ],
    "trafficSources": [
      { "source": "Direto", "sessions": 400 },
      { "source": "Google", "sessions": 300 }
    ]
  }
}
```

## Notas Importantes

- As credenciais OAuth 2.0 são sensíveis - **nunca** as compartilhe publicamente
- O arquivo `credentials.json` deve estar no `.gitignore`
- Em produção, considere usar variáveis de ambiente para credenciais
- A API do Google Analytics tem limites de quota diária

