# World Cup Simulator

Simulador de Copa do Mundo com **32 seleções**, fase de grupos e mata-mata.  
Estado em `$_SESSION` · interface com **Tailwind CSS (CDN)** · bandeiras via [flagcdn.com](https://flagcdn.com).

## Requisitos

- PHP 8.0+ com sessões habilitadas

## Deploy (`worldcupsimulator.mvlopes.com.br`)

1. Envie **todo o conteúdo** de `dist/` para a raiz do subdomínio:

```
dist/
├── index.php
├── .htaccess
├── flags/          ← 32 bandeiras PNG
└── data/           ← histórico de campeões (gravável pelo PHP)
    ├── campeoes.json
    └── .htaccess
```

A pasta `data/` precisa de permissão de **escrita** no servidor (chmod 755 ou 775).
2. Acesse no navegador — nada mais é necessário.

## Fluxo do jogo

| Etapa | Ação do botão |
|-------|----------------|
| Início | Simular Fase de Grupos |
| Grupos | Simular Oitavas de Final |
| Oitavas / Quartas / Semi | Simular Próxima Fase |
| Campeão | Reiniciar Campeonato |

## Simulação

- Placares baseados na **força** da seleção + **fator zebra** (±12).
- Grupos: todos contra todos · 3/1/0 · desempate: pts, saldo, gols pró.
- Mata-mata: **pênaltis** se empate no tempo normal.
