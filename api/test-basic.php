<?php
/**
 * Teste básico - apenas verifica se PHP está funcionando
 * Acesse: https://mvlopes.com.br/api/test-basic.php
 */

// Desabilitar exibição de erros para não quebrar o JSON
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Teste Básico - PHP</h1>";
echo "<p>✅ PHP está funcionando!</p>";
echo "<p>Versão PHP: " . phpversion() . "</p>";
echo "<p>Diretório atual: " . __DIR__ . "</p>";
echo "<p>Arquivo atual: " . __FILE__ . "</p>";

echo "<h2>Arquivos na pasta api/:</h2>";
echo "<ul>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $path = __DIR__ . '/' . $file;
        $type = is_dir($path) ? '[PASTA]' : '[ARQUIVO]';
        $size = is_file($path) ? ' (' . filesize($path) . ' bytes)' : '';
        echo "<li>$type $file$size</li>";
    }
}
echo "</ul>";

echo "<h2>Verificações:</h2>";

// Verificar config.php
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    echo "<p style='color: green;'>✅ config.php existe</p>";
} else {
    echo "<p style='color: red;'>❌ config.php NÃO existe</p>";
}

// Verificar credentials.json
$credsPath = __DIR__ . '/credentials.json';
if (file_exists($credsPath)) {
    echo "<p style='color: green;'>✅ credentials.json existe</p>";
} else {
    echo "<p style='color: red;'>❌ credentials.json NÃO existe</p>";
}

// Verificar vendor/
$vendorPath = __DIR__ . '/vendor';
if (is_dir($vendorPath)) {
    echo "<p style='color: green;'>✅ vendor/ existe</p>";
    $autoloadPath = $vendorPath . '/autoload.php';
    if (file_exists($autoloadPath)) {
        echo "<p style='color: green;'>✅ vendor/autoload.php existe</p>";
    } else {
        echo "<p style='color: red;'>❌ vendor/autoload.php NÃO existe</p>";
    }
} else {
    echo "<p style='color: red;'>❌ vendor/ NÃO existe</p>";
}

echo "<hr>";
echo "<p><strong>Se você vê esta mensagem, o PHP está funcionando!</strong></p>";
?>

