# Como Fazer Upload para a Hostinger

## Estrutura de Pastas no Servidor

Na Hostinger, normalmente a estrutura é:

```
public_html/
├── index.html (seu site React buildado)
├── api/
│   ├── credentials.json
│   ├── config.php
│   ├── analytics.php
│   ├── auth.php
│   ├── test-connection.php
│   └── vendor/ (pasta inteira)
└── send-contact.php
```

## Passo a Passo

### 1. Via File Manager da Hostinger:

1. Acesse o painel da Hostinger
2. Vá em **File Manager**
3. Navegue até `public_html/`
4. Crie a pasta `api/` se não existir
5. Faça upload dos arquivos:
   - `api/credentials.json`
   - `api/config.php`
   - `api/analytics.php`
   - `api/auth.php`
   - `api/test-connection.php`
6. Faça upload da pasta `vendor/` inteira para dentro de `api/`

### 2. Verificar Permissões:

- `credentials.json` deve ter permissão 600 ou 644
- Pastas devem ter permissão 755
- Arquivos PHP devem ter permissão 644

### 3. Testar:

Acesse: `https://mvlopes.com.br/api/test-connection.php`

## Alternativa: Testar Diretamente o Dashboard

Se preferir, pode testar diretamente o dashboard:

1. Acesse: `https://mvlopes.com.br/login`
2. Faça login
3. Acesse: `https://mvlopes.com.br/dashboard`
4. Se houver erro, verifique o console do navegador (F12)

## Estrutura Alternativa (se não funcionar)

Se a estrutura acima não funcionar, tente:

```
public_html/
├── index.html
├── api/
│   └── (arquivos aqui)
```

OU

```
public_html/
├── index.html
├── dist/ (se você fez build)
│   └── api/
│       └── (arquivos aqui)
```

## Verificar Caminho Correto

Para descobrir o caminho correto, você pode:

1. Criar um arquivo `info.php` na raiz:
```php
<?php phpinfo(); ?>
```

2. Acessar: `https://mvlopes.com.br/info.php`
3. Verificar o caminho em `$_SERVER['DOCUMENT_ROOT']`

