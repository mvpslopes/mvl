# 📁 Estrutura Completa - O que Enviar para a Hospedagem

## 🎯 Resumo Rápido

**Dentro de `api/`:**
- Todos os arquivos PHP da API do Real Driver
- A pasta `SistemasEmbarcados/realdriver/` com os arquivos do frontend

**Fora de `api/` (na raiz `public_html/`):**
- Arquivos do site principal (já estão lá)

---

## 📂 Estrutura Completa no Servidor

```
public_html/                          ← Raiz do servidor
│
├── api/                              ← PASTA API (tudo dentro dela)
│   │
│   ├── realdriver.php                ← ✅ ENVIAR
│   ├── realdriver-api.php            ← ✅ ENVIAR
│   ├── realdriver-auth.php           ← ✅ ENVIAR
│   ├── realdriver-users.php          ← ✅ ENVIAR
│   ├── realdriver-permissions.php    ← ✅ ENVIAR
│   ├── realdriver-config.php         ← ✅ ENVIAR
│   ├── realdriver-login.html         ← ✅ ENVIAR
│   │
│   ├── realdriver-api-wrapper.js     ← ✅ ENVIAR
│   ├── realdriver-user-menu.js       ← ✅ ENVIAR
│   ├── realdriver-users-manager.js   ← ✅ ENVIAR
│   │
│   └── SistemasEmbarcados/          ← CRIAR ESTA PASTA
│       └── realdriver/               ← CRIAR ESTA PASTA DENTRO
│           ├── index.html            ← ✅ ENVIAR
│           ├── script.js             ← ✅ ENVIAR
│           ├── styles.css            ← ✅ ENVIAR
│           └── logo.webp             ← ✅ ENVIAR
│
├── index.html                        ← Site principal (já está)
├── assets/                           ← Assets do site (já está)
└── send-contact.php                  ← Site principal (já está)
```

---

## 📋 Lista de Arquivos para Enviar

### 1️⃣ Arquivos PHP (dentro de `api/`)

**Localização local:**
```
C:\projetos\SiteMVL\api\
```

**Arquivos para enviar:**
- ✅ `realdriver.php`
- ✅ `realdriver-api.php`
- ✅ `realdriver-auth.php`
- ✅ `realdriver-users.php`
- ✅ `realdriver-permissions.php`
- ✅ `realdriver-config.php`
- ✅ `realdriver-login.html`

**Destino no servidor:**
```
public_html/api/
```

---

### 2️⃣ Arquivos JavaScript (dentro de `api/`)

**Localização local:**
```
C:\projetos\SiteMVL\api\
```

**Arquivos para enviar:**
- ✅ `realdriver-api-wrapper.js`
- ✅ `realdriver-user-menu.js`
- ✅ `realdriver-users-manager.js`

**Destino no servidor:**
```
public_html/api/
```

---

### 3️⃣ Arquivos do Frontend (dentro de `api/SistemasEmbarcados/realdriver/`)

**Localização local:**
```
C:\projetos\SiteMVL\SistemasEmbarcados\realdriver\
```

**Arquivos para enviar:**
- ✅ `index.html`
- ✅ `script.js`
- ✅ `styles.css`
- ✅ `logo.webp`

**Destino no servidor:**
```
public_html/api/SistemasEmbarcados/realdriver/
```

**⚠️ IMPORTANTE:** 
- Primeiro crie a pasta `SistemasEmbarcados` dentro de `api/`
- Depois crie a pasta `realdriver` dentro de `SistemasEmbarcados`
- Por último, envie os 4 arquivos para dentro de `realdriver/`

---

## 🚫 O que NÃO enviar

### ❌ NÃO enviar para produção:
- Arquivos `.md` (documentação)
- Arquivos `.sql` (scripts de banco)
- Arquivos de teste (`realdriver-test-*.php`, `realdriver-debug.php`, etc.)
- Arquivos de setup (`realdriver-db-setup.php`, `realdriver-create-sql.php`, etc.)
- Pasta `vendor/` (dependências PHP - só se necessário)
- Pasta `backups/` do Real Driver
- Arquivos `.git/` ou `.github/`

---

## ✅ Checklist Final

### Dentro de `public_html/api/`:
- [ ] `realdriver.php`
- [ ] `realdriver-api.php`
- [ ] `realdriver-auth.php`
- [ ] `realdriver-users.php`
- [ ] `realdriver-permissions.php`
- [ ] `realdriver-config.php`
- [ ] `realdriver-login.html`
- [ ] `realdriver-api-wrapper.js`
- [ ] `realdriver-user-menu.js`
- [ ] `realdriver-users-manager.js`
- [ ] Pasta `SistemasEmbarcados/` criada
- [ ] Pasta `SistemasEmbarcados/realdriver/` criada
- [ ] `SistemasEmbarcados/realdriver/index.html`
- [ ] `SistemasEmbarcados/realdriver/script.js`
- [ ] `SistemasEmbarcados/realdriver/styles.css`
- [ ] `SistemasEmbarcados/realdriver/logo.webp`

---

## 🎯 Resumo Visual

```
📦 O QUE ENVIAR
│
├── 📁 api/ (todos os arquivos abaixo vão DENTRO desta pasta)
│   │
│   ├── 🔵 PHP (7 arquivos)
│   │   ├── realdriver.php
│   │   ├── realdriver-api.php
│   │   ├── realdriver-auth.php
│   │   ├── realdriver-users.php
│   │   ├── realdriver-permissions.php
│   │   ├── realdriver-config.php
│   │   └── realdriver-login.html
│   │
│   ├── 🟡 JavaScript (3 arquivos)
│   │   ├── realdriver-api-wrapper.js
│   │   ├── realdriver-user-menu.js
│   │   └── realdriver-users-manager.js
│   │
│   └── 📁 SistemasEmbarcados/realdriver/ (4 arquivos)
│       ├── index.html
│       ├── script.js
│       ├── styles.css
│       └── logo.webp
│
└── ❌ NÃO enviar: documentação, testes, backups, .git, etc.
```

---

## 🔍 Como Verificar se Está Correto

Após enviar tudo, acesse:
```
https://mvlopes.com.br/api/realdriver-check-path.php
```

Deve aparecer:
```
✅ Caminho 1 (api/SistemasEmbarcados/realdriver/) - EXISTE
✅ index.html encontrado
✅ script.js encontrado
✅ styles.css encontrado
```

---

## 📞 Precisa de Ajuda?

Se ainda tiver dúvidas, me envie:
1. Uma captura de tela da estrutura de pastas no File Manager
2. O resultado de `realdriver-check-path.php`

