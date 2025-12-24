<?php
/**
 * Script de Diagnóstico - Verificar Caminhos do Real Driver
 * 
 * Este script ajuda a identificar onde está a pasta do sistema Real Driver
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Diagnóstico - Caminhos do Real Driver</h1>";

echo "<h2>Informações do Servidor:</h2>";
echo "<p><strong>DOCUMENT_ROOT:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Não definido') . "</p>";
echo "<p><strong>SCRIPT_FILENAME:</strong> " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Não definido') . "</p>";
echo "<p><strong>__DIR__ (diretório atual):</strong> " . __DIR__ . "</p>";

echo "<h2>Caminhos Testados (na ordem que o realdriver.php verifica):</h2>";

$caminhos = [
    'Caminho 1 (api/SistemasEmbarcados/realdriver/) - ⭐ PRIORIDADE' => __DIR__ . '/SistemasEmbarcados/realdriver/',
    'Caminho 2 (api/sistemasembarcados/realdriver/) - Alternativo' => __DIR__ . '/sistemasembarcados/realdriver/',
    'Caminho 3 (api/realdriver/) - Alternativo direto' => __DIR__ . '/realdriver/',
    'Caminho 4 (SistemasEmbarcados/realdriver/)' => __DIR__ . '/../SistemasEmbarcados/realdriver/',
    'Caminho 5 (absoluto SistemasEmbarcados/)' => $_SERVER['DOCUMENT_ROOT'] . '/SistemasEmbarcados/realdriver/',
    'Caminho 6 (alternativo)' => dirname(__DIR__) . '/SistemasEmbarcados/realdriver/',
];

foreach ($caminhos as $nome => $caminho) {
    $existe = is_dir($caminho);
    $cor = $existe ? 'green' : 'red';
    $status = $existe ? '✅ EXISTE' : '❌ NÃO EXISTE';
    
    echo "<p style='color: {$cor};'><strong>{$nome}:</strong> {$caminho} - <strong>{$status}</strong></p>";
    
    if ($existe) {
        $arquivos = scandir($caminho);
        echo "<ul>";
        foreach ($arquivos as $arquivo) {
            if ($arquivo !== '.' && $arquivo !== '..') {
                $tipo = is_dir($caminho . $arquivo) ? '[PASTA]' : '[ARQUIVO]';
                echo "<li>{$tipo} {$arquivo}</li>";
            }
        }
        echo "</ul>";
    }
}

echo "<h2>Arquivos na raiz do projeto:</h2>";
$raiz = dirname(__DIR__);
if (is_dir($raiz)) {
    $arquivos = scandir($raiz);
    echo "<ul>";
    foreach ($arquivos as $arquivo) {
        if ($arquivo !== '.' && $arquivo !== '..' && is_dir($raiz . '/' . $arquivo)) {
            echo "<li>[PASTA] {$arquivo}</li>";
        }
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>💡 Solução:</h2>";

// Verificar se algum caminho existe
$caminhoEncontrado = false;
foreach ($caminhos as $nome => $caminho) {
    if (is_dir($caminho)) {
        $caminhoEncontrado = true;
        break;
    }
}

if (!$caminhoEncontrado) {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #856404; margin-top: 0;'>⚠️ Nenhum caminho encontrado!</h3>";
    echo "<p><strong>Você precisa criar a pasta e enviar os arquivos:</strong></p>";
    echo "<ol>";
    echo "<li>No <strong>File Manager</strong> da Hostinger, navegue até: <code>public_html/api/</code></li>";
    echo "<li>Crie uma nova pasta chamada <code>SistemasEmbarcados</code> (com S maiúsculo)</li>";
    echo "<li>Dentro de <code>SistemasEmbarcados</code>, crie outra pasta chamada <code>realdriver</code></li>";
    echo "<li>Envie os seguintes arquivos (da pasta local <code>SistemasEmbarcados/realdriver/</code>):";
    echo "<ul>";
    echo "<li><code>index.html</code></li>";
    echo "<li><code>script.js</code></li>";
    echo "<li><code>styles.css</code></li>";
    echo "<li><code>logo.webp</code></li>";
    echo "</ul>";
    echo "</li>";
    echo "<li>Após o upload, acesse este script novamente para verificar</li>";
    echo "</ol>";
    echo "<p><strong>Estrutura final no servidor:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
    echo "public_html/\n";
    echo "└── api/\n";
    echo "    └── SistemasEmbarcados/  ← CRIE ESTA PASTA (com S maiúsculo)\n";
    echo "        └── realdriver/      ← CRIE ESTA PASTA DENTRO\n";
    echo "            ├── index.html   ← ENVIE ESTES ARQUIVOS\n";
    echo "            ├── script.js\n";
    echo "            ├── styles.css\n";
    echo "            └── logo.webp\n";
    echo "</pre>";
    echo "</div>";
} else {
    echo "<p style='color: green;'>✅ Pasta encontrada! O sistema deve funcionar.</p>";
    echo "<p>Se ainda houver problemas, verifique as permissões da pasta.</p>";
}

