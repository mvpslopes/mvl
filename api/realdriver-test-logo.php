<?php
/**
 * Script de Teste - Logo do Real Driver
 * 
 * Este script testa se a logo está sendo servida corretamente
 * Acesse: https://mvlopes.com.br/api/realdriver-test-logo.php
 */

header('Content-Type: text/html; charset=utf-8');

$basePath = __DIR__;
$possiblePaths = [
    $basePath . '/SistemasEmbarcados/realdriver/',
    $basePath . '/sistemasembarcados/realdriver/',
    $basePath . '/realdriver/',
];

$realdriverPath = null;
foreach ($possiblePaths as $path) {
    if (is_dir($path)) {
        $realdriverPath = $path;
        break;
    }
}

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Logo Real Driver</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-item {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .sucesso { background: #d4edda; border-color: #28a745; }
        .erro { background: #f8d7da; border-color: #dc3545; }
        .info { background: #d1ecf1; border-color: #17a2b8; }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        img {
            max-width: 200px;
            border: 2px solid #ddd;
            padding: 10px;
            background: white;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste - Logo do Real Driver</h1>';

if (!$realdriverPath) {
    echo '<div class="test-item erro">';
    echo '<h3>❌ Pasta não encontrada</h3>';
    echo '<p>Nenhuma das pastas esperadas foi encontrada:</p>';
    echo '<ul>';
    foreach ($possiblePaths as $path) {
        echo '<li><code>' . htmlspecialchars($path) . '</code></li>';
    }
    echo '</ul>';
    echo '</div>';
} else {
    echo '<div class="test-item info">';
    echo '<h3>✅ Pasta encontrada</h3>';
    echo '<p><strong>Caminho:</strong> <code>' . htmlspecialchars($realdriverPath) . '</code></p>';
    echo '</div>';
    
    // Verificar se logo.webp existe
    $logoPath = $realdriverPath . 'logo.webp';
    if (file_exists($logoPath)) {
        $logoSize = filesize($logoPath);
        $logoSizeFormatted = $logoSize > 1024 ? number_format($logoSize / 1024, 2) . ' KB' : $logoSize . ' B';
        
        echo '<div class="test-item sucesso">';
        echo '<h3>✅ Arquivo logo.webp encontrado</h3>';
        echo '<p><strong>Tamanho:</strong> ' . $logoSizeFormatted . '</p>';
        echo '<p><strong>Caminho completo:</strong> <code>' . htmlspecialchars($logoPath) . '</code></p>';
        echo '</div>';
        
        // Testar URL direta
        $logoUrl = '/api/realdriver.php?file=logo.webp';
        echo '<div class="test-item info">';
        echo '<h3>🖼️ Teste de Exibição</h3>';
        echo '<p><strong>URL esperada:</strong> <code>' . htmlspecialchars($logoUrl) . '</code></p>';
        echo '<p>Logo abaixo (se aparecer, está funcionando):</p>';
        echo '<img src="' . htmlspecialchars($logoUrl) . '" alt="Logo Real Driver" onerror="this.style.border=\'2px solid red\'; this.alt=\'ERRO: Logo não carregou\';">';
        echo '<p><a href="' . htmlspecialchars($logoUrl) . '" target="_blank">🔗 Abrir logo em nova aba</a></p>';
        echo '</div>';
        
        // Verificar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $logoPath);
        finfo_close($finfo);
        
        echo '<div class="test-item info">';
        echo '<h3>📋 Informações do Arquivo</h3>';
        echo '<p><strong>MIME Type:</strong> <code>' . htmlspecialchars($mimeType) . '</code></p>';
        echo '<p><strong>Permissões:</strong> <code>' . substr(sprintf('%o', fileperms($logoPath)), -4) . '</code></p>';
        echo '<p><strong>Legível:</strong> ' . (is_readable($logoPath) ? '✅ Sim' : '❌ Não') . '</p>';
        echo '</div>';
        
    } else {
        echo '<div class="test-item erro">';
        echo '<h3>❌ Arquivo logo.webp NÃO encontrado</h3>';
        echo '<p><strong>Procurando em:</strong> <code>' . htmlspecialchars($logoPath) . '</code></p>';
        echo '<p>Arquivos na pasta:</p>';
        $files = scandir($realdriverPath);
        echo '<ul>';
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $tipo = is_dir($realdriverPath . $file) ? '[PASTA]' : '[ARQUIVO]';
                echo '<li>' . $tipo . ' ' . htmlspecialchars($file) . '</li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    }
}

echo '<div class="test-item info">';
echo '<h3>🔧 Solução de Problemas</h3>';
echo '<ol>';
echo '<li>Verifique se o arquivo <code>logo.webp</code> está em <code>api/SistemasEmbarcados/realdriver/</code></li>';
echo '<li>Verifique as permissões do arquivo (deve ser legível)</li>';
echo '<li>Limpe o cache do navegador (Ctrl + F5)</li>';
echo '<li>Teste a URL diretamente: <a href="/api/realdriver.php?file=logo.webp" target="_blank">/api/realdriver.php?file=logo.webp</a></li>';
echo '</ol>';
echo '</div>';

echo '</div></body></html>';
?>

