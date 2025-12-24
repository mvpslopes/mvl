# 📤 Como Fazer Upload do Sistema Real Driver

## ⚠️ Situação Atual

A pasta `SistemasEmbarcados/realdriver/` não está no servidor. Você precisa fazer o upload dos arquivos.

## ✅ Solução Rápida

### Opção 1: Upload Manual (Recomendado para agora)

1. **Acesse o File Manager** da Hostinger
2. **Navegue até:** `public_html/api/`
3. **Crie uma pasta** chamada `realdriver`
4. **Faça upload dos seguintes arquivos** (da pasta local `SistemasEmbarcados/realdriver/`):
   - ✅ `index.html`
   - ✅ `script.js`
   - ✅ `styles.css`
   - ✅ `logo.webp` (ou `logo.ico`)
   - ✅ Pasta `public/` (se existir, com o logo dentro)

**Estrutura final no servidor:**
```
public_html/
└── api/
    ├── realdriver.php
    └── realdriver/          ← Você cria esta pasta
        ├── index.html
        ├── script.js
        ├── styles.css
        ├── logo.webp
        └── public/
            └── logo.webp
```

### Opção 2: Upload via FTP

1. Conecte-se via FTP
2. Navegue até `public_html/api/`
3. Crie a pasta `realdriver`
4. Faça upload dos arquivos

### Opção 3: Build Automático (Futuro)

Após fazer o build do projeto React, os arquivos serão copiados automaticamente para `dist/api/realdriver/`.

## 🔍 Verificar se Funcionou

Após o upload, acesse:
```
https://mvlopes.com.br/api/realdriver-check-path.php
```

Deve aparecer:
```
Caminho 1 (relativo): .../api/realdriver/ - ✅ EXISTE
```

## 🚀 Testar o Sistema

Após confirmar que os arquivos estão no lugar certo:

1. Acesse: `https://mvlopes.com.br/api/realdriver.php`
2. Você será redirecionado para o login
3. Faça login com:
   - **Root:** marcus@mvlopes.com.br / *.Admin14!
   - **Admin:** francisco@mvlopes.com.br / francisco2025

## 📋 Checklist de Upload

- [ ] Criar pasta `public_html/api/realdriver/`
- [ ] Upload de `index.html`
- [ ] Upload de `script.js`
- [ ] Upload de `styles.css`
- [ ] Upload de `logo.webp` ou `logo.ico`
- [ ] Upload da pasta `public/` (se existir)
- [ ] Verificar caminho com `realdriver-check-path.php`
- [ ] Testar acesso em `realdriver.php`

---

**Localização dos arquivos locais:**
```
C:\projetos\SiteMVL\SistemasEmbarcados\realdriver\
```

