<?php
/**
 * Script de Debug Completo - Real Driver
 * 
 * Verifica todos os aspectos do sistema
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Debug - Real Driver</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        h2 { color: #667eea; margin-top: 30px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .test { margin: 15px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Debug Completo - Sistema Real Driver</h1>";

// 1. Verificar arquivos
echo "<h2>1. Verificação de Arquivos</h2>";

$possiblePaths = [
    __DIR__ . '/realdriver/',
    __DIR__ . '/../SistemasEmbarcados/realdriver/',
    $_SERVER['DOCUMENT_ROOT'] . '/SistemasEmbarcados/realdriver/',
    dirname(__DIR__) . '/SistemasEmbarcados/realdriver/',
];

$realdriverPath = null;
foreach ($possiblePaths as $path) {
    echo "<div class='test'>";
    echo "<strong>Caminho testado:</strong> <code>" . htmlspecialchars($path) . "</code><br>";
    if (is_dir($path)) {
        echo "<span class='success'>✅ Diretório existe</span><br>";
        $realdriverPath = $path;
        
        $requiredFiles = ['index.html', 'script.js', 'styles.css'];
        foreach ($requiredFiles as $file) {
            $filePath = $path . $file;
            if (file_exists($filePath)) {
                $size = filesize($filePath);
                echo "<span class='success'>✅ {$file}</span> ({$size} bytes)<br>";
            } else {
                echo "<span class='error'>❌ {$file} não encontrado</span><br>";
            }
        }
    } else {
        echo "<span class='error'>❌ Diretório não existe</span>";
    }
    echo "</div>";
}

if (!$realdriverPath) {
    echo "<div class='test'><span class='error'>❌ Nenhum caminho válido encontrado!</span></div>";
    echo "</div></body></html>";
    exit;
}

// 2. Verificar banco de dados
echo "<h2>2. Verificação do Banco de Dados</h2>";

require_once __DIR__ . '/realdriver-config.php';
$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='test'><span class='success'>✅ Conexão com banco estabelecida</span></div>";
    
    $tables = ['motoristas', 'veiculos', 'diarias', 'manutencoes', 'contratos', 'financeiro'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$table}`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['total'];
            $status = $count > 0 ? 'success' : 'warning';
            echo "<div class='test'><span class='{$status}'>" . ($count > 0 ? '✅' : '⚠️') . " {$table}: {$count} registros</span></div>";
        } catch (PDOException $e) {
            echo "<div class='test'><span class='error'>❌ Erro ao consultar {$table}: " . htmlspecialchars($e->getMessage()) . "</span></div>";
        }
    }
} catch (PDOException $e) {
    echo "<div class='test'><span class='error'>❌ Erro de conexão: " . htmlspecialchars($e->getMessage()) . "</span></div>";
}

// 3. Testar API
echo "<h2>3. Teste da API</h2>";

$apiUrl = '/api/realdriver-api.php?action=getAll';
echo "<div class='test'>";
echo "<strong>URL da API:</strong> <code>{$apiUrl}</code><br>";

// Simular requisição com token
session_start();
$token = $_SESSION['auth_token'] ?? 'test-token';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<strong>Status HTTP:</strong> {$httpCode}<br>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<span class='success'>✅ API funcionando</span><br>";
        if (isset($data['data'])) {
            echo "<strong>Dados retornados:</strong><br>";
            foreach ($data['data'] as $type => $items) {
                $count = is_array($items) ? count($items) : 0;
                echo "- {$type}: {$count} registros<br>";
            }
        }
    } else {
        echo "<span class='error'>❌ API retornou erro</span><br>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<span class='error'>❌ Erro HTTP: {$httpCode}</span><br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
echo "</div>";

// 4. Verificar URLs dos arquivos estáticos
echo "<h2>4. Verificação de URLs de Arquivos Estáticos</h2>";

$baseUrl = '/api/realdriver.php';
$staticFiles = [
    'styles.css' => $baseUrl . '?file=styles.css',
    'script.js' => $baseUrl . '?file=script.js',
    'logo.webp' => $baseUrl . '?file=logo.webp'
];

echo "<div class='test'>";
echo "<strong>URLs que devem ser acessíveis:</strong><br>";
foreach ($staticFiles as $file => $url) {
    $filePath = $realdriverPath . $file;
    if (file_exists($filePath)) {
        echo "<span class='success'>✅ {$file}</span> → <code>{$url}</code><br>";
    } else {
        echo "<span class='error'>❌ {$file} não encontrado</span><br>";
    }
}
echo "</div>";

// 5. Verificar HTML gerado
echo "<h2>5. Verificação do HTML Gerado</h2>";

$indexPath = $realdriverPath . 'index.html';
if (file_exists($indexPath)) {
    $html = file_get_contents($indexPath);
    
    // Verificar se tem referências a styles.css e script.js
    echo "<div class='test'>";
    if (strpos($html, 'styles.css') !== false) {
        echo "<span class='success'>✅ HTML referencia styles.css</span><br>";
    } else {
        echo "<span class='warning'>⚠️ HTML não referencia styles.css</span><br>";
    }
    
    if (strpos($html, 'script.js') !== false) {
        echo "<span class='success'>✅ HTML referencia script.js</span><br>";
    } else {
        echo "<span class='warning'>⚠️ HTML não referencia script.js</span><br>";
    }
    
    // Verificar se tem a classe SisRealDriver
    if (strpos($html, 'SisRealDriver') !== false || strpos($html, 'class SisRealDriver') !== false) {
        echo "<span class='success'>✅ HTML contém referência a SisRealDriver</span><br>";
    } else {
        echo "<span class='warning'>⚠️ HTML não contém referência a SisRealDriver</span><br>";
    }
    echo "</div>";
    
    // Mostrar primeiras linhas do HTML
    echo "<div class='test'>";
    echo "<strong>Primeiras 20 linhas do index.html:</strong><br>";
    $lines = explode("\n", $html);
    echo "<pre>" . htmlspecialchars(implode("\n", array_slice($lines, 0, 20))) . "</pre>";
    echo "</div>";
} else {
    echo "<div class='test'><span class='error'>❌ index.html não encontrado</span></div>";
}

// 6. Instruções de debug no navegador
echo "<h2>6. Debug no Navegador</h2>";
echo "<div class='test'>";
echo "<p>Abra o console do navegador (F12) e execute:</p>";
echo "<pre>
// Verificar se scripts estão carregados
console.log('RealDriverAPI:', typeof window.RealDriverAPI);
console.log('USE_API_MODE:', window.USE_API_MODE);
console.log('REALDRIVER_API_URL:', window.REALDRIVER_API_URL);
console.log('REALDRIVER_TOKEN:', window.REALDRIVER_TOKEN);

// Verificar se app está inicializado
console.log('app:', typeof window.app);

// Testar carregamento de dados
if (window.RealDriverAPI) {
    window.RealDriverAPI.loadAllData(true).then(data => {
        console.log('✅ Dados carregados:', data);
        console.log('Motoristas:', data.motoristas?.length || 0);
        console.log('Veículos:', data.veiculos?.length || 0);
    }).catch(err => {
        console.error('❌ Erro ao carregar:', err);
    });
} else {
    console.error('❌ RealDriverAPI não está disponível!');
}

// Verificar erros de CSS/JS
const links = document.querySelectorAll('link[rel=\"stylesheet\"]');
const scripts = document.querySelectorAll('script[src]');
console.log('CSS carregados:', links.length);
console.log('Scripts carregados:', scripts.length);
links.forEach(link => console.log('CSS:', link.href));
scripts.forEach(script => console.log('JS:', script.src));
</pre>";
echo "</div>";

echo "</div></body></html>";

