# 📤 Guia Completo de Upload - Sistema Real Driver

## 🎯 Resumo Rápido

**NÃO use a pasta `dist`** - ela é apenas para o build do site principal (React).

Para o **Real Driver**, você precisa enviar **2 grupos de arquivos**:

1. **Arquivos PHP e JavaScript da API** (pasta `api/`)
2. **Arquivos do Frontend** (pasta `SistemasEmbarcados/realdriver/`)

---

## 📁 Estrutura no Servidor (Como deve ficar)

```
public_html/
├── api/                                    ← Você já tem esta pasta
│   ├── realdriver.php                      ← ✅ JÁ ENVIADO
│   ├── realdriver-api.php                  ← ✅ JÁ ENVIADO
│   ├── realdriver-auth.php                 ← ✅ JÁ ENVIADO
│   ├── realdriver-api-wrapper.js           ← ✅ JÁ ENVIADO
│   ├── realdriver-user-menu.js             ← ✅ JÁ ENVIADO
│   ├── realdriver-users-manager.js         ← ✅ JÁ ENVIADO
│   ├── realdriver-users.php                ← ✅ JÁ ENVIADO
│   ├── realdriver-permissions.php          ← ✅ JÁ ENVIADO
│   ├── realdriver-config.php               ← ✅ JÁ ENVIADO
│   ├── realdriver-login.html               ← ✅ JÁ ENVIADO
│   └── sistemasembarcados/                 ← ⚠️ VOCÊ PRECISA CRIAR ESTA PASTA
│       └── realdriver/                     ← ⚠️ VOCÊ PRECISA CRIAR ESTA PASTA DENTRO
│           ├── index.html                  ← ⚠️ ENVIAR DA PASTA LOCAL
│           ├── script.js                   ← ⚠️ ENVIAR DA PASTA LOCAL
│           ├── styles.css                  ← ⚠️ ENVIAR DA PASTA LOCAL
│           ├── logo.webp                   ← ⚠️ ENVIAR DA PASTA LOCAL
│           └── public/                     ← ⚠️ ENVIAR SE EXISTIR
│               └── logo.webp
```

---

## ✅ Passo a Passo Detalhado

### Passo 1: Verificar o que já está no servidor

Acesse o **File Manager** da Hostinger e verifique se você já tem na pasta `public_html/api/`:

- ✅ `realdriver.php`
- ✅ `realdriver-api.php`
- ✅ `realdriver-auth.php`
- ✅ `realdriver-api-wrapper.js`
- ✅ `realdriver-user-menu.js`
- ✅ `realdriver-users-manager.js`
- ✅ `realdriver-users.php`
- ✅ `realdriver-permissions.php`
- ✅ `realdriver-config.php`
- ✅ `realdriver-login.html`

**Se algum estiver faltando**, envie da pasta local `api/` para `public_html/api/`.

---

### Passo 2: Criar as pastas dentro de `api/`

1. No **File Manager**, navegue até `public_html/api/`
2. Clique em **"Nova Pasta"** ou **"Create Folder"**
3. Nome da primeira pasta: `sistemasembarcados` (sem espaços, tudo minúsculo)
4. Entre na pasta `sistemasembarcados` que você criou
5. Crie outra pasta chamada: `realdriver` (sem espaços, tudo minúsculo)

**Estrutura criada:**
```
public_html/api/sistemasembarcados/realdriver/
```

---

### Passo 3: Enviar os arquivos do frontend

Na pasta local do seu computador, você tem:
```
C:\projetos\SiteMVL\SistemasEmbarcados\realdriver\
```

**Arquivos que você precisa enviar:**

1. `index.html` → Enviar para `public_html/api/sistemasembarcados/realdriver/index.html`
2. `script.js` → Enviar para `public_html/api/sistemasembarcados/realdriver/script.js`
3. `styles.css` → Enviar para `public_html/api/sistemasembarcados/realdriver/styles.css`
4. `logo.webp` → Enviar para `public_html/api/sistemasembarcados/realdriver/logo.webp`
5. Se existir a pasta `public/`, envie ela também com o conteúdo

**Como fazer o upload:**

#### Opção A: Via File Manager (Hostinger)
1. Acesse `public_html/api/sistemasembarcados/realdriver/` (a pasta que você criou)
2. Clique em **"Upload"** ou **"Enviar Arquivos"**
3. Selecione os arquivos da pasta local
4. Aguarde o upload terminar

#### Opção B: Via FTP
1. Conecte-se via FTP (FileZilla, WinSCP, etc.)
2. Navegue até `public_html/api/sistemasembarcados/realdriver/`
3. Arraste os arquivos da pasta local para o servidor

---

## 🔍 Verificar se Está Correto

Após fazer o upload, acesse:

```
https://mvlopes.com.br/api/realdriver-check-path.php
```

**Resultado esperado:**
```
✅ Caminho 1 (api/sistemasembarcados/realdriver/) - ⭐ PRIORIDADE - EXISTE
✅ index.html encontrado
✅ script.js encontrado
✅ styles.css encontrado
```

---

## 🚀 Testar o Sistema

1. Acesse: `https://mvlopes.com.br/api/realdriver.php`
2. Você será redirecionado para o login
3. Faça login com:
   - **Root:** marcus@mvlopes.com.br / *.Admin14!
   - **Admin:** francisco@mvlopes.com.br / francisco2025

---

## ❌ O que NÃO fazer

- ❌ **NÃO** envie arquivos da pasta `dist/` - ela é só para o site principal
- ❌ **NÃO** envie a pasta `SistemasEmbarcados/` inteira para a raiz
- ❌ **NÃO** envie arquivos de backup (`.json`, `.sql`) para produção
- ❌ **NÃO** envie arquivos de teste (como `realdriver-debug.php`, `realdriver-test-*.php`)

---

## 📋 Checklist Final

Antes de considerar concluído, verifique:

- [ ] Pasta `public_html/api/sistemasembarcados/` existe
- [ ] Pasta `public_html/api/sistemasembarcados/realdriver/` existe
- [ ] `index.html` está em `public_html/api/sistemasembarcados/realdriver/index.html`
- [ ] `script.js` está em `public_html/api/sistemasembarcados/realdriver/script.js`
- [ ] `styles.css` está em `public_html/api/sistemasembarcados/realdriver/styles.css`
- [ ] `logo.webp` está em `public_html/api/sistemasembarcados/realdriver/logo.webp`
- [ ] Todos os arquivos PHP estão em `public_html/api/`
- [ ] Testou o caminho: `https://mvlopes.com.br/api/realdriver-check-path.php`
- [ ] Testou o acesso: `https://mvlopes.com.br/api/realdriver.php`

---

## 🆘 Problemas Comuns

### "404 - Sistema Real Driver não encontrado"
- **Solução:** Verifique se a pasta `sistemasembarcados/realdriver/` existe dentro de `api/`
- **Solução:** Verifique se o `index.html` está dentro da pasta `sistemasembarcados/realdriver/`

### "CSS não está carregando"
- **Solução:** Verifique se `styles.css` está em `api/sistemasembarcados/realdriver/styles.css`
- **Solução:** Limpe o cache do navegador (Ctrl + F5)

### "JavaScript não está funcionando"
- **Solução:** Verifique se `script.js` está em `api/sistemasembarcados/realdriver/script.js`
- **Solução:** Abra o console (F12) e veja se há erros

### "Erro 401 - Não autenticado"
- **Solução:** Faça login primeiro em `realdriver-login.html`
- **Solução:** Verifique se o token está sendo salvo no localStorage

---

## 📞 Precisa de Ajuda?

Se ainda tiver dúvidas, me envie:
1. O resultado de `realdriver-check-path.php`
2. Uma captura de tela da estrutura de pastas no File Manager
3. Qualquer erro que aparecer no console do navegador (F12)

