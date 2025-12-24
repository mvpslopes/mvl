# 📤 Como Fazer Upload do Sistema Real Driver

## ⚠️ Problema Identificado

A pasta `SistemasEmbarcados/realdriver/` não foi encontrada no servidor.

## ✅ Solução: Fazer Upload dos Arquivos

Você tem **2 opções** para fazer o upload:

### Opção 1: Dentro de `api/` (Recomendado)

**Estrutura no servidor:**
```
public_html/
└── api/
    ├── realdriver.php
    └── realdriver/          ← Crie esta pasta aqui
        ├── index.html
        ├── script.js
        ├── styles.css
        ├── logo.webp
        └── (outros arquivos)
```

**Arquivos necessários:**
- `index.html`
- `script.js`
- `styles.css`
- `logo.webp` (ou `logo.ico`)
- `public/logo.webp` (se existir)

### Opção 2: Na raiz do projeto

**Estrutura no servidor:**
```
public_html/
├── api/
│   └── realdriver.php
└── SistemasEmbarcados/
    └── realdriver/
        ├── index.html
        ├── script.js
        ├── styles.css
        └── (outros arquivos)
```

## 📋 Passo a Passo

### Via File Manager (Hostinger)

1. Acesse o **File Manager** no painel da Hostinger
2. Navegue até `public_html/api/`
3. Crie uma nova pasta chamada `realdriver`
4. Faça upload dos arquivos:
   - `index.html`
   - `script.js`
   - `styles.css`
   - `logo.webp`
   - Qualquer outro arquivo necessário

### Via FTP

1. Conecte-se via FTP ao servidor
2. Navegue até `public_html/api/`
3. Crie a pasta `realdriver`
4. Faça upload dos arquivos

## 🔍 Verificar Upload

Após fazer o upload, acesse:
```
https://mvlopes.com.br/api/realdriver-check-path.php
```

O script deve mostrar que o caminho existe.

## 📁 Arquivos Mínimos Necessários

Certifique-se de fazer upload de pelo menos:

- ✅ `index.html` - Interface principal
- ✅ `script.js` - Lógica do sistema
- ✅ `styles.css` - Estilos
- ✅ `logo.webp` ou `logo.ico` - Logo do sistema

## 🚀 Após o Upload

1. Acesse: `https://mvlopes.com.br/api/realdriver.php`
2. Você será redirecionado para o login
3. Faça login com:
   - **Root:** marcus@mvlopes.com.br / *.Admin14!
   - **Admin:** francisco@mvlopes.com.br / francisco2025

---

**Dica:** Se preferir, posso criar um script que copia os arquivos automaticamente durante o build do projeto.

