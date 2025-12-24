# 🔧 Configurar Acesso ao Banco de Dados - Real Driver

## ❌ Erro Identificado

```
Access denied for user 'u179630068_realdriveruser'@'localhost'
```

Isso significa que o usuário não consegue acessar o banco de dados.

## ✅ Solução: Associar Usuário ao Banco

### Passo a Passo no Painel Hostinger

1. **Acesse o painel da Hostinger**
2. **Vá em "Bancos de Dados MySQL"** ou "MySQL Databases"
3. **Encontre o banco:** `u179630068_realdriver`
4. **Encontre o usuário:** `u179630068_realdriveruser`

### Opção 1: Associar Usuário ao Banco (Recomendado)

1. Na seção **"Adicionar usuário ao banco de dados"** ou **"Add User To Database"**
2. Selecione:
   - **Usuário:** `u179630068_realdriveruser`
   - **Banco de dados:** `u179630068_realdriver`
3. Clique em **"Adicionar"** ou **"Add"**
4. Marque **TODAS as permissões** (SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, etc.)
5. Clique em **"Fazer alterações"** ou **"Make Changes"**

### Opção 2: Verificar Senha do Usuário

Se o usuário já está associado, pode ser que a senha esteja diferente:

1. Vá em **"Usuários MySQL"** ou **"MySQL Users"**
2. Encontre o usuário `u179630068_realdriveruser`
3. Verifique a senha ou **altere a senha**
4. Se alterar, atualize o arquivo `api/realdriver-config.php` com a nova senha

### Opção 3: Recriar Usuário (Se necessário)

Se nada funcionar, você pode:

1. **Deletar o usuário** `u179630068_realdriveruser` (se existir)
2. **Criar um novo usuário:**
   - Nome: `u179630068_realdriveruser`
   - Senha: `KZbHRI3$` (ou escolha uma nova)
3. **Associar ao banco** `u179630068_realdriver`
4. **Dar todas as permissões**
5. Se mudou a senha, atualize `api/realdriver-config.php`

## 🔍 Verificar Configuração Atual

Após fazer as alterações, acesse novamente:
```
https://mvlopes.com.br/api/realdriver-test-connection.php
```

Deve aparecer: **✅ Conexão bem-sucedida!**

## 📝 Atualizar Senha no Código (Se necessário)

Se você alterou a senha do usuário, edite o arquivo `api/realdriver-config.php`:

```php
'password' => 'SUA_NOVA_SENHA_AQUI',
```

## ⚠️ Importante

- O usuário **DEVE** estar associado ao banco de dados
- O usuário **DEVE** ter todas as permissões (SELECT, INSERT, UPDATE, DELETE, etc.)
- A senha no código **DEVE** ser a mesma do painel

---

**Após configurar, teste novamente o acesso ao sistema!**

