# ✅ Checklist de Configuração - Google Analytics API

Marque cada item conforme você completa:

## Fase 1: Google Cloud Console

- [ ] Criar projeto no Google Cloud Console
  - [ ] Acessar: https://console.cloud.google.com/
  - [ ] Criar novo projeto (ex: "MVLopes Analytics")
  - [ ] Selecionar o projeto criado

- [ ] Habilitar a API
  - [ ] Ir em "APIs e Serviços" → "Biblioteca"
  - [ ] Buscar "Google Analytics Data API"
  - [ ] Clicar em "ATIVAR"

- [ ] Criar Service Account
  - [ ] Ir em "APIs e Serviços" → "Credenciais"
  - [ ] Criar credenciais → "Conta de serviço"
  - [ ] Nome: `mvlopes-analytics-service`
  - [ ] Criar e continuar (pode pular as permissões)
  - [ ] Ir na aba "Chaves"
  - [ ] Adicionar chave → Criar nova chave → JSON
  - [ ] **Arquivo JSON foi baixado**

- [ ] Preparar credenciais
  - [ ] Renomear arquivo baixado para `credentials.json`
  - [ ] Mover para a pasta `api/` do projeto

## Fase 2: Google Analytics

- [ ] Conceder acesso ao Google Analytics
  - [ ] Acessar: https://analytics.google.com/
  - [ ] Ir em "Administração" → "Acesso à propriedade"
  - [ ] Adicionar usuário
  - [ ] Copiar email da Service Account (do arquivo JSON, campo `client_email`)
  - [ ] Colar email e dar permissão "Visualizador"
  - [ ] Salvar

## Fase 3: Servidor (Hostinger)

- [ ] Instalar Composer (se necessário)
  - [ ] Acessar servidor via SSH ou File Manager
  - [ ] Navegar até pasta `api/`
  - [ ] Executar: `composer require google/analytics-data`
  - [ ] OU fazer upload da pasta `vendor/` se instalou localmente

- [ ] Verificar arquivos
  - [ ] `api/credentials.json` existe
  - [ ] `api/vendor/` existe (pasta com bibliotecas)
  - [ ] `api/config.php` existe

## Fase 4: Testar Conexão

- [ ] Testar conexão
  - [ ] Acessar: `https://seusite.com.br/api/test-connection.php`
  - [ ] Verificar se mostra "✅ Tudo funcionando!"
  - [ ] Se houver erro, verificar mensagem e corrigir

## Fase 5: Ativar API Real

- [ ] Atualizar configuração
  - [ ] Abrir `api/config.php`
  - [ ] Alterar `'use_mock_data' => false`

- [ ] Substituir código
  - [ ] Fazer backup de `api/analytics.php`
  - [ ] Copiar conteúdo de `api/analytics-real.php`
  - [ ] Colar em `api/analytics.php` (substituir todo o conteúdo)

- [ ] Testar dashboard
  - [ ] Fazer login em `/login`
  - [ ] Acessar `/dashboard`
  - [ ] Verificar se dados reais aparecem

## Fase 6: Limpeza e Segurança

- [ ] Segurança
  - [ ] Verificar se `api/credentials.json` está no `.gitignore`
  - [ ] Remover `api/test-connection.php` (após testar)
  - [ ] Verificar permissões do arquivo `credentials.json` (deve ser 600 ou 644)

## 🎉 Concluído!

Se todos os itens estão marcados, seu dashboard está funcionando com dados reais do Google Analytics!

---

## 📞 Precisa de Ajuda?

Consulte o arquivo `GUIA_CONFIGURACAO.md` para instruções detalhadas de cada passo.

