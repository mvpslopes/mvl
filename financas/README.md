# Finanças pessoais — MVLopes

Sistema de controle financeiro pessoal: receitas, despesas, recorrências, visão mensal/anual e projeção com saldo acumulado.

## Funcionalidades

- **Visão anual** — 12 meses com receitas, despesas, saldo do mês e saldo acumulado (previsto ou realizado)
- **Visão mensal** — lançamentos avulsos + projeções de recorrências
- **Recorrências** — repetir mensalmente no mesmo dia, com data fim opcional
- **Projeção** — próximos N meses com saldo acumulado
- **Saldo inicial** — ponto de partida para o acumulado

## Desenvolvimento local

```bash
cd financas
npm install
cp api/config.example.php api/config.local.php
# Edite config.local.php (banco + senha com password_hash)
npm run dev:api   # terminal 1 — API na porta 8081
npm run dev       # terminal 2 — frontend na porta 5173
```

Gerar hash da senha:

```bash
php -r "echo password_hash('sua_senha', PASSWORD_DEFAULT);"
```

Criar tabelas (opcional — a API cria automaticamente no primeiro uso):

```
GET /api/install.php?key=install
```

## Build e deploy

```bash
npm run build
```

Envie o conteúdo de **`dist/`** (raiz do repositório) para o servidor.

- Site: `https://seu-dominio.com/`
- Finanças: `https://seu-dominio.com/financas/`
- Instalar tabelas: `GET /financas/api/install.php?key=install`
- Configure `financas/api/config.local.php` no servidor (copie de `config.example.php`).

## Stack

- React 18 + TypeScript + Tailwind
- API PHP + MySQL
