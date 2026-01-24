# 📤 Guia de Upload - Arquivos Atualizados

## 🎯 Resumo das Alterações Recentes

As seguintes alterações foram feitas e precisam ser enviadas:

1. **Hero Section** - Novo design tecnológico com gradientes e animações
2. **Dashboard** - Cores amarelas alteradas para azul (#1052E0)
3. **Animações CSS** - Novas animações de blob adicionadas

---

## 📋 Passo a Passo para Upload

### 1️⃣ **Fazer Build do Projeto**

Primeiro, você precisa compilar o projeto React:

```bash
npm run build
```

Isso vai gerar a pasta `dist/` com todos os arquivos compilados.

---

### 2️⃣ **Arquivos para Upload na Hostinger**

#### **Opção A: Upload Completo (Recomendado)**

Faça upload de **TODA a pasta `dist/`** para `public_html/` na Hostinger:

```
public_html/
├── index.html          ← Arquivo principal do site
├── assets/            ← CSS, JS e imagens compilados
│   ├── index-*.js
│   ├── index-*.css
│   └── ...
├── api/               ← APIs PHP (já copiadas pelo build)
│   ├── analytics.php
│   ├── auth.php
│   ├── change-password.php
│   ├── users.php
│   ├── config.php
│   └── vendor/        ← Dependências PHP
└── send-contact.php   ← Formulário de contato
```

#### **Opção B: Upload Seletivo (Apenas o que mudou)**

Se você já tem o site no servidor e quer atualizar apenas o que mudou:

**Arquivos que mudaram:**
- `dist/index.html` (pode ter mudado)
- `dist/assets/index-*.js` (JavaScript compilado - **SEMPRE muda**)
- `dist/assets/index-*.css` (CSS compilado - **SEMPRE muda**)

**Arquivos PHP (se foram alterados):**
- `dist/api/change-password.php` (novo)
- `dist/api/users.php` (novo)

---

### 3️⃣ **Arquivos Especiais (Upload Manual)**

⚠️ **IMPORTANTE:** Estes arquivos NÃO são copiados automaticamente pelo build:

#### **`api/credentials.json`**
- Este arquivo contém credenciais sensíveis
- **NÃO** está na pasta `dist/` por segurança
- Faça upload manual para `public_html/api/credentials.json`
- Permissão: **600** ou **644**

---

### 4️⃣ **Estrutura Final no Servidor**

```
public_html/
├── index.html
├── assets/
│   ├── index-[hash].js
│   ├── index-[hash].css
│   └── [outras imagens/assets]
├── api/
│   ├── credentials.json          ← Upload manual
│   ├── config.php
│   ├── analytics.php
│   ├── auth.php
│   ├── change-password.php       ← Novo
│   ├── users.php                 ← Novo
│   └── vendor/                   ← Dependências Composer
└── send-contact.php
```

---

### 5️⃣ **Verificar Permissões**

Após o upload, verifique as permissões:

- **Pastas:** `755`
- **Arquivos PHP:** `644`
- **credentials.json:** `600` ou `644`

---

### 6️⃣ **Testar**

1. Acesse: `https://seusite.com.br/`
   - Verifique se o Hero está com o novo design

2. Acesse: `https://seusite.com.br/dashboard`
   - Verifique se os gráficos estão azuis (não amarelos)
   - Teste a funcionalidade de alterar senhas

3. Verifique o console do navegador (F12) para erros

---

## 🚀 Comandos Rápidos

```bash
# 1. Fazer build
npm run build

# 2. A pasta dist/ está pronta para upload
# 3. Faça upload via FTP/File Manager da Hostinger
```

---

## ⚠️ Observações Importantes

1. **Sempre faça backup** antes de fazer upload
2. O arquivo `credentials.json` **NÃO** está no build por segurança
3. Os arquivos em `dist/assets/` têm hash no nome (ex: `index-a1b2c3.js`)
4. Se algo não funcionar, limpe o cache do navegador (Ctrl+F5)

---

## 📝 Checklist

- [ ] Executei `npm run build`
- [ ] Fiz upload da pasta `dist/` completa
- [ ] Fiz upload manual do `api/credentials.json`
- [ ] Verifiquei permissões dos arquivos
- [ ] Testei o site no navegador
- [ ] Testei o dashboard
- [ ] Verifiquei console do navegador (F12)
