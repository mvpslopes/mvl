<?php
/**
 * Script de Debug - Erro 500
 * 
 * Este script ajuda a identificar o erro que está causando o 500
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug - Erro 500</h1>";

// Testar se o arquivo realdriver.php tem erros de sintaxe
$filePath = __DIR__ . '/realdriver.php';

echo "<h2>1. Verificando sintaxe do arquivo</h2>";
$output = [];
$returnVar = 0;
exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnVar);

if ($returnVar === 0) {
    echo "<p style='color: green;'>✅ Sintaxe PHP está correta</p>";
} else {
    echo "<p style='color: red;'>❌ Erro de sintaxe encontrado:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    echo htmlspecialchars(implode("\n", $output));
    echo "</pre>";
}

// Testar se consegue incluir o arquivo
echo "<h2>2. Testando inclusão do arquivo</h2>";
try {
    ob_start();
    include $filePath;
    $output = ob_get_clean();
    echo "<p style='color: green;'>✅ Arquivo pode ser incluído</p>";
} catch (Throwable $e) {
    echo "<p style='color: red;'>❌ Erro ao incluir arquivo:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    echo htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString());
    echo "</pre>";
}

// Verificar se há problemas com aspas
echo "<h2>3. Verificando problemas com aspas</h2>";
$content = file_get_contents($filePath);
$singleQuotes = substr_count($content, "'");
$doubleQuotes = substr_count($content, '"');
echo "<p>Aspas simples: $singleQuotes</p>";
echo "<p>Aspas duplas: $doubleQuotes</p>";

// Verificar se há problemas com strings não fechadas
$lines = explode("\n", $content);
$inString = false;
$stringChar = '';
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $lineNum = $i + 1;
    
    // Verificar se há strings não fechadas (simplificado)
    if (preg_match('/\$apiPatch\s*=\s*[\'"]/', $line)) {
        echo "<p style='color: orange;'>⚠️ Linha $lineNum: Possível string longa começando</p>";
    }
}

echo "<h2>4. Testando caminhos</h2>";
$possiblePaths = [
    __DIR__ . '/SistemasEmbarcados/realdriver/',
    __DIR__ . '/sistemasembarcados/realdriver/',
    __DIR__ . '/realdriver/',
];

foreach ($possiblePaths as $path) {
    $exists = is_dir($path);
    $status = $exists ? '✅' : '❌';
    echo "<p>$status <code>" . htmlspecialchars($path) . "</code></p>";
}

?>

