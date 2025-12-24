# 🔍 Como Validar que os Dados Vêm do Google Analytics

Este documento explica como verificar que os dados exibidos no dashboard são reais e vêm diretamente do Google Analytics.

## ✅ Métodos de Validação

### 1. Script de Validação Automática

Execute o script de validação que mostra todos os detalhes:

```
https://mvlopes.com.br/api/test-validate-analytics.php
```

Este script mostra:
- ✅ Metadados da requisição (Property ID, período, etc.)
- ✅ Dados brutos retornados pela API do Google Analytics
- ✅ Comparação entre dados brutos e processados
- ✅ Logs de requisições ao Google Analytics
- ✅ Instruções para validação manual

### 2. Parâmetro de Validação na API

Adicione `?validate=true` na URL da API para receber informações extras:

```
https://mvlopes.com.br/api/analytics.php?days=7&validate=true
```

A resposta incluirá uma seção `validation` com:
- Fonte dos dados: `Google Analytics Data API v1beta`
- Property ID usado
- Classe da API utilizada
- Timestamp da requisição
- Métricas e dimensões solicitadas

**Exemplo de resposta:**
```json
{
  "success": true,
  "data": { ... },
  "dateRange": { ... },
  "validation": {
    "source": "Google Analytics Data API v1beta",
    "property_id": "517334916",
    "api_client": "Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient",
    "data_source": "Real-time Google Analytics data",
    "mock_data": false,
    "timestamp": "2025-12-24 11:30:00",
    "requests_made": {
      "main_metrics": "activeUsers, sessions, screenPageViews, bounceRate",
      "top_pages": "pagePath dimension with screenPageViews metric",
      "traffic_sources": "sessionSource dimension with sessions metric"
    }
  }
}
```

### 3. Verificação no Código Fonte

Abra o arquivo `api/analytics.php` e verifique:

**Linha ~21:** Configuração
```php
'use_mock_data' => false, // Usando dados reais do Google Analytics
```

**Linha ~289:** Cliente do Google Analytics
```php
$client = new \Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient([
  'credentials' => $credentialsPath,
]);
```

**Linha ~310:** Requisição à API
```php
$request->setProperty("properties/$propertyId");
$response = $client->runReport($request);
```

### 4. Verificação nos Logs do Servidor

Os logs do servidor mostram todas as requisições ao Google Analytics:

```
/home/u179630068/.logs/error_log_mvlopes_com_br
```

Procure por linhas contendo:
- `Google Analytics API - Fazendo requisição ao Property ID:`
- `Google Analytics API - Resposta recebida com sucesso`
- `Google Analytics API - Dados processados:`

**Exemplo de log:**
```
[24-Dec-2025 11:30:00 UTC] Google Analytics API - Fazendo requisição ao Property ID: 517334916
[24-Dec-2025 11:30:00 UTC] Google Analytics API - Período: 2025-12-17 até 2025-12-24 (7 dias)
[24-Dec-2025 11:30:00 UTC] Google Analytics API - Resposta recebida com sucesso
[24-Dec-2025 11:30:00 UTC] Google Analytics API - Número de linhas: 1
[24-Dec-2025 11:30:00 UTC] Google Analytics API - Dados processados: Users=17, Sessions=26, PageViews=130, BounceRate=100
```

### 5. Comparação Manual no Google Analytics

1. Acesse: https://analytics.google.com
2. Selecione a propriedade: **517334916**
3. Vá em **Relatórios** → **Engajamento**
4. Configure o período para os mesmos dias do dashboard
5. Compare os números:
   - **Usuários ativos** = Visitantes Únicos no dashboard
   - **Sessões** = Total de Visitas no dashboard
   - **Visualizações de página** = Visualizações no dashboard
   - **Taxa de rejeição** = Taxa de Saída no dashboard

**Nota:** Pode haver pequenas diferenças devido a:
- Processamento em tempo real vs dados processados
- Fuso horário
- Dados ainda sendo processados pelo Google Analytics

### 6. Verificação da Biblioteca Instalada

Execute no servidor:
```bash
cd /home/u179630068/domains/mvlopes.com.br/public_html/api
composer show google/analytics-data
```

Você deve ver:
```
name     : google/analytics-data
descrip. : Google Analytics Data API client library
versions : * v0.23.1
```

### 7. Teste Direto da API

Execute o script de teste:
```
https://mvlopes.com.br/api/test-connection-simple.php
```

Este script faz uma requisição real ao Google Analytics e mostra:
- ✅ Property ID usado
- ✅ Credenciais validadas
- ✅ Resposta da API do Google Analytics
- ✅ Dados retornados

## 🔒 Garantias de Validação

### ✅ Configuração Correta

- **Property ID:** `517334916` (configurado em `api/config.php`)
- **Mock Data:** `false` (dados reais, não simulados)
- **Biblioteca:** `google/analytics-data v0.23.1` (oficial do Google)

### ✅ Evidências no Código

1. **Não usa dados mockados:**
   ```php
   if ($useMockData) {
     // Este bloco NÃO é executado
   }
   ```

2. **Usa cliente oficial do Google:**
   ```php
   $client = new \Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient
   ```

3. **Faz requisições reais:**
   ```php
   $response = $client->runReport($request);
   ```

4. **Processa resposta real:**
   ```php
   foreach ($response->getRows() as $row) {
     // Processa dados reais do Google Analytics
   }
   ```

## 📊 Dados que Vêm do Google Analytics

Todos estes dados são buscados diretamente da API do Google Analytics:

- ✅ **Visitantes Únicos** → `activeUsers`
- ✅ **Total de Visitas** → `sessions`
- ✅ **Visualizações** → `screenPageViews`
- ✅ **Taxa de Saída** → `bounceRate`
- ✅ **Páginas Mais Visitadas** → `pagePath` dimension
- ✅ **Origem do Tráfego** → `sessionSource` dimension

## ⚠️ Importante

- Os dados são **reais** e vêm **diretamente** do Google Analytics
- Não há dados mockados ou simulados
- Todas as requisições são registradas nos logs
- O Property ID está correto e validado

## 🗑️ Limpeza

Após validar, remova os arquivos de teste por segurança:
- `api/test-validate-analytics.php`
- `api/test-connection-simple.php`
- `api/test-analytics-debug.php`
- `api/test-analytics-direct.php`

---

**Última atualização:** 24/12/2025  
**Property ID:** 517334916  
**Status:** ✅ Dados reais do Google Analytics

