<?php
/**
 * Teste Mínimo - Verifica apenas se PHP está funcionando
 * Use este arquivo se os outros testes não funcionarem
 * 
 * Acesse: https://mvlopes.com.br/api/test-minimal.php
 */

// Mostrar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Mínimo - API</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .ok { color: green; font-weight: bold; }
        .erro { color: red; font-weight: bold; }
        .info { color: #666; background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste Mínimo - API</h1>
        
        <h2>1. Verificação Básica do PHP</h2>
        <p class="ok">✅ PHP está funcionando!</p>
        <p><strong>Versão PHP:</strong> <?php echo phpversion(); ?></p>
        <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'; ?></p>
        <p><strong>Diretório atual:</strong> <?php echo __DIR__; ?></p>
        
        <h2>2. Verificação de Arquivos</h2>
        <?php
        $files = [
            'config.php' => __DIR__ . '/config.php',
            'credentials.json' => __DIR__ . '/credentials.json',
            'vendor/autoload.php' => __DIR__ . '/vendor/autoload.php',
        ];
        
        foreach ($files as $name => $path) {
            if (file_exists($path)) {
                $size = is_file($path) ? filesize($path) : 'diretório';
                echo "<p class='ok'>✅ $name existe (tamanho: $size bytes)</p>";
            } else {
                echo "<p class='erro'>❌ $name NÃO encontrado em: $path</p>";
            }
        }
        ?>
        
        <h2>3. Verificação de Extensões PHP</h2>
        <?php
        $extensions = ['json', 'curl', 'openssl', 'mbstring', 'pdo', 'pdo_mysql'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<p class='ok'>✅ Extensão $ext carregada</p>";
            } else {
                echo "<p class='erro'>❌ Extensão $ext NÃO carregada</p>";
            }
        }
        ?>
        
        <h2>4. Teste de Configuração</h2>
        <?php
        $configPath = __DIR__ . '/config.php';
        if (file_exists($configPath)) {
            try {
                $config = require $configPath;
                echo "<p class='ok'>✅ config.php carregado com sucesso</p>";
                echo "<div class='info'>";
                echo "<strong>Conteúdo do config.php:</strong><br>";
                echo "<pre>" . htmlspecialchars(print_r($config, true)) . "</pre>";
                echo "</div>";
            } catch (Exception $e) {
                echo "<p class='erro'>❌ Erro ao carregar config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
            } catch (Error $e) {
                echo "<p class='erro'>❌ Erro fatal ao carregar config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>Arquivo: " . htmlspecialchars($e->getFile()) . " na linha " . $e->getLine() . "</p>";
            }
        } else {
            echo "<p class='erro'>❌ config.php não encontrado</p>";
        }
        ?>
        
        <h2>5. Informações do Servidor</h2>
        <div class="info">
            <strong>PHP Info:</strong><br>
            <pre><?php
            $info = [
                'PHP Version' => phpversion(),
                'Server API' => php_sapi_name(),
                'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
                'Script Filename' => __FILE__,
                'Current Directory' => __DIR__,
                'Memory Limit' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time'),
                'Upload Max Filesize' => ini_get('upload_max_filesize'),
            ];
            foreach ($info as $key => $value) {
                echo "$key: $value\n";
            }
            ?></pre>
        </div>
        
        <hr>
        <p><strong>✅ Se você vê esta página, o PHP está funcionando corretamente!</strong></p>
        <p>Se os outros testes não funcionam, verifique os erros acima e consulte a documentação.</p>
    </div>
</body>
</html>

