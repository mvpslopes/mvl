# 🔐 Sistema de Perfis de Acesso - Real Driver

## 📋 Perfis Disponíveis

O sistema Real Driver possui **3 perfis de acesso** com diferentes níveis de permissão:

### 1. **Root** 👑
**Permissões completas:**
- ✅ Criar, editar e excluir usuários
- ✅ Gerenciar todos os dados (motoristas, veículos, diárias, manutenções, contratos, financeiro)
- ✅ Acessar interface de gestão de usuários
- ✅ Trocar própria senha

### 2. **Admin** 🔧
**Permissões de gestão:**
- ✅ Ler todos os dados
- ✅ Criar, editar e excluir registros (motoristas, veículos, diárias, etc.)
- ✅ Trocar própria senha
- ❌ Não pode criar ou gerenciar usuários

### 3. **User** 👤
**Permissões de consulta:**
- ✅ Apenas ler/consultar dados
- ❌ Não pode criar, editar ou excluir registros
- ❌ Não pode trocar senha
- ❌ Não pode gerenciar usuários

---

## 🚀 Como Usar

### 1. Primeiro Acesso

Após executar o script `realdriver-db-setup.php`, um usuário **root** padrão é criado:

- **Email:** `admin@realdriver.com`
- **Senha:** `admin123`

⚠️ **IMPORTANTE:** Altere a senha padrão após o primeiro login!

### 2. Login no Sistema

1. Acesse: `https://seusite.com.br/api/realdriver.php`
2. Você será redirecionado para a tela de login
3. Digite seu email e senha
4. Clique em "Entrar"

### 3. Criar Novos Usuários (Apenas Root)

1. Faça login como **root**
2. Clique na aba **"Usuários"** no menu
3. Clique em **"Novo Usuário"**
4. Preencha os dados:
   - Nome
   - Email
   - Senha
   - Perfil (root, admin ou user)
   - Status (ativo/inativo)
5. Clique em **"Salvar"**

### 4. Trocar Senha (Root e Admin)

1. Clique no seu nome no canto superior direito
2. Selecione **"Trocar Senha"**
3. Digite a senha atual
4. Digite a nova senha (mínimo 6 caracteres)
5. Confirme a nova senha
6. Clique em **"Alterar Senha"**

### 5. Logout

1. Clique no seu nome no canto superior direito
2. Selecione **"Sair"**
3. Confirme a ação

---

## 🔒 Segurança

### Autenticação
- Tokens de autenticação com expiração de 24 horas
- Senhas armazenadas com hash (bcrypt)
- Verificação de permissões em todas as requisições à API

### Permissões
- Cada ação verifica se o usuário tem permissão
- Usuários **user** não podem modificar dados
- Apenas **root** pode gerenciar usuários
- Não é possível deletar seu próprio usuário

---

## 📡 APIs Disponíveis

### Autenticação
- `POST /api/realdriver-auth.php?action=login` - Fazer login
- `POST /api/realdriver-auth.php?action=logout` - Fazer logout
- `GET /api/realdriver-auth.php?action=check` - Verificar token
- `POST /api/realdriver-auth.php?action=changePassword` - Trocar senha

### Gestão de Usuários (Apenas Root)
- `GET /api/realdriver-users.php?action=list` - Listar usuários
- `POST /api/realdriver-users.php?action=create` - Criar usuário
- `POST /api/realdriver-users.php?action=update&id=X` - Atualizar usuário
- `POST /api/realdriver-users.php?action=delete&id=X` - Deletar usuário
- `GET /api/realdriver-users.php?action=getPerfis` - Listar perfis

### Dados do Sistema
- Todas as requisições à `/api/realdriver-api.php` verificam permissões automaticamente

---

## 🗄️ Estrutura do Banco de Dados

### Tabela: `perfis`
Armazena os perfis disponíveis:
- `id` - ID do perfil
- `nome` - Nome do perfil (root, admin, user)
- `descricao` - Descrição do perfil
- `permissoes` - JSON com lista de permissões

### Tabela: `usuarios`
Armazena os usuários do sistema:
- `id` - ID do usuário
- `nome` - Nome completo
- `email` - Email (único)
- `senha_hash` - Hash da senha
- `perfil_id` - ID do perfil (FK)
- `ativo` - Status (1 = ativo, 0 = inativo)
- `ultimo_acesso` - Data do último acesso
- `created_at` - Data de criação
- `updated_at` - Data de atualização

### Tabela: `auth_tokens`
Armazena tokens de autenticação:
- `id` - ID do token
- `token` - Token único
- `usuario_id` - ID do usuário (FK)
- `perfil_nome` - Nome do perfil (cache)
- `created_at` - Data de criação
- `expires_at` - Data de expiração (24 horas)

---

## 🎯 Permissões por Ação

| Ação | Root | Admin | User |
|------|------|-------|------|
| Ler dados | ✅ | ✅ | ✅ |
| Criar registros | ✅ | ✅ | ❌ |
| Editar registros | ✅ | ✅ | ❌ |
| Excluir registros | ✅ | ✅ | ❌ |
| Criar usuários | ✅ | ❌ | ❌ |
| Editar usuários | ✅ | ❌ | ❌ |
| Excluir usuários | ✅ | ❌ | ❌ |
| Trocar senha | ✅ | ✅ | ❌ |

---

## 🔧 Troubleshooting

### "Não autenticado"
- Verifique se fez login
- Verifique se o token não expirou (24 horas)
- Faça logout e login novamente

### "Permissão negada"
- Verifique se seu perfil tem a permissão necessária
- Entre em contato com um usuário root para ajustar seu perfil

### "Email já cadastrado"
- O email deve ser único no sistema
- Use outro email ou edite o usuário existente

### Não consigo criar usuários
- Apenas usuários com perfil **root** podem criar usuários
- Verifique seu perfil no menu do usuário

---

## 📝 Notas Importantes

1. **Senha padrão:** Altere a senha do usuário root após o primeiro acesso
2. **Tokens:** Expirem após 24 horas de inatividade
3. **Perfis:** Não podem ser deletados (são necessários para o sistema)
4. **Usuários:** Não podem deletar a si mesmos
5. **Backup:** Faça backup regular do banco de dados

---

**Sistema de perfis implementado com sucesso! 🎉**

