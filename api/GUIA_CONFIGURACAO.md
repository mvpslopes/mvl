# Guia Completo: Configurar Google Analytics API

## Passo 1: Criar Projeto no Google Cloud Console

1. Acesse: https://console.cloud.google.com/
2. Faça login com sua conta Google (a mesma do Google Analytics)
3. No topo da página, clique no seletor de projetos
4. Clique em **"Novo Projeto"**
5. Preencha:
   - **Nome do projeto**: `MVLopes Analytics` (ou outro nome de sua preferência)
   - Clique em **"Criar"**
6. Aguarde alguns segundos e selecione o projeto recém-criado

## Passo 2: Habilitar a Google Analytics Data API

1. No menu lateral, vá em **"APIs e Serviços"** → **"Biblioteca"**
2. Na barra de pesquisa, digite: `Google Analytics Data API`
3. Clique no resultado **"Google Analytics Data API"**
4. Clique no botão **"ATIVAR"**
5. Aguarde a confirmação (pode levar alguns segundos)

## Passo 3: Criar Credenciais (Service Account - Recomendado)

### Opção A: Service Account (Mais Seguro - Recomendado)

1. No menu lateral, vá em **"APIs e Serviços"** → **"Credenciais"**
2. Clique em **"Criar credenciais"** → **"Conta de serviço"**
3. Preencha:
   - **Nome**: `mvlopes-analytics-service`
   - **ID**: será gerado automaticamente
   - Clique em **"Criar e continuar"**
4. Na etapa "Conceder acesso a este projeto", clique em **"Continuar"** (pode pular)
5. Na etapa "Conceder aos usuários acesso a esta conta de serviço", clique em **"Concluído"**
6. Agora você verá a conta de serviço criada. Clique nela
7. Vá na aba **"Chaves"**
8. Clique em **"Adicionar chave"** → **"Criar nova chave"**
9. Selecione **"JSON"** e clique em **"Criar"**
10. O arquivo JSON será baixado automaticamente
11. **Renomeie este arquivo para `credentials.json`**
12. **Mova o arquivo para a pasta `api/` do seu projeto**

### Opção B: OAuth 2.0 (Alternativa)

Se preferir usar OAuth 2.0:

1. No menu lateral, vá em **"APIs e Serviços"** → **"Credenciais"**
2. Clique em **"Criar credenciais"** → **"ID do cliente OAuth"**
3. Se solicitado, configure a tela de consentimento:
   - Tipo de usuário: **"Externo"**
   - Preencha nome do app, email de suporte
   - Adicione seu email como usuário de teste
4. Tipo de aplicativo: **"Aplicativo da Web"**
5. Nome: `MVLopes Analytics`
6. Clique em **"Criar"**
7. Anote o **ID do cliente** e a **Chave secreta do cliente**
8. Você precisará configurar isso no código PHP

## Passo 4: Conceder Acesso ao Google Analytics

1. Acesse: https://analytics.google.com/
2. Vá em **"Administração"** (ícone de engrenagem no canto inferior esquerdo)
3. Na coluna **"Propriedade"**, clique em **"Acesso à propriedade"**
4. Clique em **"+"** → **"Adicionar usuários"**
5. Se você criou uma Service Account:
   - Copie o **Email da conta de serviço** (está no arquivo JSON baixado, campo `client_email`)
   - Cole no campo de email
   - Selecione a permissão: **"Visualizador"**
   - Clique em **"Adicionar"**
6. Se você usou OAuth 2.0:
   - Adicione seu próprio email com permissão de **"Visualizador"** ou superior

## Passo 5: Instalar Biblioteca PHP no Servidor

### No servidor Hostinger (via SSH ou File Manager):

1. Acesse o servidor via SSH ou File Manager
2. Navegue até a pasta `api/` do seu site
3. Execute:

```bash
cd api
curl -sS https://getcomposer.org/installer | php
php composer.phar require google/analytics-data
```

OU se o Composer já estiver instalado globalmente:

```bash
cd api
composer require google/analytics-data
```

### Alternativa: Instalar localmente e fazer upload

1. No seu computador, na pasta do projeto:
```bash
cd api
composer require google/analytics-data
```

2. Isso criará uma pasta `vendor/` com as bibliotecas
3. Faça upload da pasta `vendor/` para `api/vendor/` no servidor

## Passo 6: Atualizar o arquivo analytics.php

O código já está preparado! Você só precisa:

1. Abrir `api/analytics.php`
2. Descomentar o código da API (linhas 74-113)
3. Remover ou comentar o código de dados mockados
4. Ajustar o caminho do `credentials.json` se necessário

## Passo 7: Atualizar config.php

1. Abra `api/config.php`
2. Altere:
   ```php
   'use_mock_data' => false, // De true para false
   ```
3. Verifique se o caminho do `credentials.json` está correto

## Passo 8: Testar

1. Faça login no dashboard: `/login`
2. Acesse o dashboard: `/dashboard`
3. Os dados reais do Google Analytics devem aparecer!

## Troubleshooting

### Erro: "Credentials file not found"
- Verifique se o arquivo `credentials.json` está na pasta `api/`
- Verifique as permissões do arquivo (deve ser legível)

### Erro: "Permission denied"
- Verifique se a Service Account tem acesso ao Google Analytics
- Verifique se o Property ID está correto

### Erro: "API not enabled"
- Verifique se a Google Analytics Data API está ativada no Google Cloud Console

### Erro: "Composer not found"
- Instale o Composer no servidor ou use a alternativa de upload manual

## Segurança

⚠️ **IMPORTANTE:**
- O arquivo `credentials.json` contém chaves secretas
- **NUNCA** faça commit deste arquivo no Git
- Adicione `api/credentials.json` ao `.gitignore`
- Mantenha este arquivo seguro no servidor

