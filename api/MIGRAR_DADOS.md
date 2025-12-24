# 📦 Como Migrar os Dados do Backup

## ✅ Correções Realizadas

1. **Botão de Cancelar no Login** - Adicionado botão para voltar ao site principal
2. **Correção do CSS** - Caminhos dos arquivos estáticos corrigidos
3. **Script de Migração** - Atualizado para procurar o backup em vários caminhos

## 🚀 Migrar os Dados

### Passo 1: Fazer Upload do Backup

Faça upload do arquivo de backup para uma das seguintes pastas:

**Opção 1 (Recomendado):**
```
public_html/api/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json
```

**Opção 2:**
```
public_html/SistemasEmbarcados/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json
```

### Passo 2: Executar a Migração

Acesse no navegador:
```
https://mvlopes.com.br/api/realdriver-migrate.php
```

O script irá:
- ✅ Procurar o arquivo de backup automaticamente
- ✅ Importar todos os motoristas
- ✅ Importar todos os veículos
- ✅ Importar todas as diárias
- ✅ Importar todas as manutenções
- ✅ Importar todos os contratos
- ✅ Importar dados financeiros (se houver)

### Passo 3: Verificar

Após a migração, acesse o sistema:
```
https://mvlopes.com.br/api/realdriver.php
```

Os dados devem aparecer no dashboard e em todas as seções.

## 📋 Dados que Serão Importados

- **4 Motoristas**
- **4 Veículos**
- **153 Diárias**
- **109 Manutenções**
- **4 Contratos**
- **0 Transações Financeiras** (se houver no backup)

## ⚠️ Importante

- O script usa `ON DUPLICATE KEY UPDATE`, então pode ser executado várias vezes sem duplicar dados
- Os IDs originais serão preservados
- Após migrar, remova ou proteja o arquivo `realdriver-migrate.php` por segurança

---

**Arquivo de backup:** `SisRealDriver_Backup_2025-12-24_12-27-38.json`

