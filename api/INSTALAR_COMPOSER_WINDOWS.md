# Como Instalar Composer no Windows

## Método Rápido (Recomendado)

1. **Baixe o instalador do Composer:**
   - Acesse: https://getcomposer.org/download/
   - Clique em "Composer-Setup.exe" para baixar

2. **Execute o instalador:**
   - Execute o arquivo baixado
   - Siga o assistente de instalação
   - O instalador detectará automaticamente o PHP se estiver instalado
   - Se não tiver PHP, o instalador pode instalar também

3. **Verifique a instalação:**
   - Abra um novo PowerShell
   - Execute: `composer --version`
   - Deve mostrar a versão do Composer

4. **Instale a biblioteca:**
   ```powershell
   cd C:\projetos\SiteMVL\api
   composer require google/analytics-data
   ```

## Método Manual (Se o instalador não funcionar)

1. Baixe o arquivo `composer.phar` de: https://getcomposer.org/download/
2. Coloque na pasta `C:\projetos\SiteMVL\api\`
3. Execute:
   ```powershell
   cd C:\projetos\SiteMVL\api
   php composer.phar require google/analytics-data
   ```

## Alternativa: Fazer no Servidor

Se preferir, você pode instalar diretamente no servidor Hostinger via SSH ou File Manager, sem precisar instalar o Composer no Windows.

