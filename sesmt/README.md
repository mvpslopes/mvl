# SESMT — Sistema MVLopes

Aplicação do subdomínio **sesmt.mvlopes.com.br** com autenticação, perfis **Root** e **Admin**, e gestão de usuários exclusiva do Root.

## Perfis

| Perfil | Permissões |
|--------|------------|
| **Root** | Dashboard + CRUD de usuários (criar, editar, excluir, alterar senha) |
| **Admin** | Apenas dashboard (sem menu Usuários) |

## Instalação na hospedagem

### 1. Banco de dados

1. Crie o banco MySQL no painel Hostinger.
2. Copie `api/config.example.php` → `api/config.local.php` e preencha host, banco, usuário e senha.

   Localmente: `npm run setup:config` e edite o arquivo.

3. **Obrigatório no servidor:** após enviar o `dist/`, faça upload de `api/config.local.php` para a pasta `api/` do subdomínio. Sem esse arquivo o login retorna erro 500.

4. Teste: `https://sesmt.mvlopes.com.br/api/health.php` deve retornar `"success": true`.

### 2. Tabelas e usuário root

Acesse uma vez:

```
https://sesmt.mvlopes.com.br/api/install.php
```

Isso cria as tabelas e o usuário **marcus.lopes** (senha definida no hash bcrypt do install).

**Remova ou bloqueie `install.php` após usar.**

### 3. Build do frontend

```bash
cd sesmt
npm install
npm run build
```

Envie o conteúdo de `sesmt/dist/` para a raiz do subdomínio (inclui `api/` copiada no build, **exceto** `config.local.php` — envie `config.local.php` manualmente no servidor).

### 4. Desenvolvimento local

Terminal 1 — API PHP:

```bash
cd sesmt
php -S 127.0.0.1:8080
```

Terminal 2 — Vite:

```bash
cd sesmt
npm run dev
```

O proxy do Vite encaminha `/api` para `127.0.0.1:8080`.

## API

| Endpoint | Método | Auth | Descrição |
|----------|--------|------|-----------|
| `/api/auth.php` | POST | — | Login `{ username, password }` |
| `/api/logout.php` | POST | Bearer | Encerrar sessão |
| `/api/me.php` | GET | Bearer | Usuário atual |
| `/api/users.php` | GET | Root | Listar usuários |
| `/api/users.php` | POST | Root | Criar usuário |
| `/api/users.php` | PUT | Root | Editar usuário |
| `/api/users.php` | PATCH | Root | Alterar senha `{ id, password }` |
| `/api/users.php` | DELETE | Root | Excluir `{ id }` |

## Certificados (NR-10, NR-12, NR-22, NR-35)

Menu **Certificados** — formulário com:

- Empresa (nome + logo PNG/JPG)
- Treinamento (**36 NRs vigentes**, NR-1 a NR-38; revogadas NR-2 e NR-27 ficam fora do seletor), carga horária, colaborador, CPF, data, cidade (autocomplete MG)
- Até **4 assinaturas** (nome, função, CREA ou CRM)
- **Gerar certificado** → PDF em **paisagem (A4 horizontal)** com preview na tela

### Banco (módulo certificados)

Execute `database/certificados.sql` e depois `database/seed-nr-tipos.sql` no phpMyAdmin **ou** acesse `api/migrate-certificados.php` (cria tabelas e todas as NRs).

### PDF no servidor

Na pasta `api/`, instale a biblioteca:

```bash
cd api
composer install --no-dev
```

Requisitos Hostinger: PHP 8.1+, extensão **gd** habilitada.

### Deploy

O build copia `api/vendor/` para `dist/api/vendor/`. Envie também `api/data/cidades-mg-ibge.json` (cidades de MG).

## Design

Siga `design.json` para novas telas e componentes.
