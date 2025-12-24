<?php
/**
 * Script de Teste da API Real Driver
 * 
 * Este script testa se a API está retornando os dados corretamente.
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Teste API - Real Driver</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #5568d3; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🧪 Teste da API Real Driver</h1>";

// Teste 1: Verificar se a API está acessível
echo "<div class='test-section'>";
echo "<h2>1. Teste de Acesso à API</h2>";

$apiUrl = '/api/realdriver-api.php?action=getAll';
echo "<p>URL: <code>{$apiUrl}</code></p>";

// Simular requisição
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_GET['token'] ?? 'test-token')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p class='error'>❌ Erro ao conectar: {$error}</p>";
} else {
    echo "<p>Status HTTP: <strong>{$httpCode}</strong></p>";
    
    if ($httpCode === 200) {
        echo "<p class='success'>✅ API acessível</p>";
        $data = json_decode($response, true);
        
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "<p class='success'>✅ API retornou sucesso</p>";
                
                if (isset($data['data'])) {
                    echo "<h3>Dados retornados:</h3>";
                    echo "<ul>";
                    foreach ($data['data'] as $type => $items) {
                        $count = is_array($items) ? count($items) : 0;
                        echo "<li><strong>{$type}:</strong> {$count} registros</li>";
                    }
                    echo "</ul>";
                    
                    echo "<h3>Resposta completa (primeiros 1000 caracteres):</h3>";
                    echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "...</pre>";
                } else {
                    echo "<p class='error'>❌ Resposta não contém 'data'</p>";
                    echo "<pre>" . htmlspecialchars($response) . "</pre>";
                }
            } else {
                echo "<p class='error'>❌ API retornou erro: " . htmlspecialchars($data['message'] ?? 'Desconhecido') . "</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<p class='error'>❌ Resposta não é JSON válido</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p class='error'>❌ Erro HTTP: {$httpCode}</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}

echo "</div>";

// Teste 2: Testar diretamente via PHP
echo "<div class='test-section'>";
echo "<h2>2. Teste Direto via PHP</h2>";

require_once __DIR__ . '/realdriver-config.php';
require_once __DIR__ . '/realdriver-permissions.php';

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];
$prefix = $config['system']['table_prefix'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Conexão com banco estabelecida</p>";
    
    // Testar handleGetAll
    $types = ['motoristas', 'veiculos', 'diarias', 'manutencoes', 'contratos', 'financeiro'];
    $result = [];
    
    foreach ($types as $type) {
        $table = $prefix . $type;
        try {
            $stmt = $pdo->query("SELECT * FROM `{$table}` ORDER BY id DESC LIMIT 5");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatar dados
            $formatted = array_map(function($item) use ($type) {
                return formatData($type, $item);
            }, $data);
            
            $result[$type] = $formatted;
            echo "<p class='success'>✅ {$type}: " . count($formatted) . " registros (limitado a 5)</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Erro ao consultar {$type}: " . htmlspecialchars($e->getMessage()) . "</p>";
            $result[$type] = [];
        }
    }
    
    echo "<h3>Dados formatados:</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erro de conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Função formatData (copiada do realdriver-api.php)
function formatData($type, $data) {
    switch ($type) {
        case 'motoristas':
            return [
                'id' => (int)$data['id'],
                'nome' => $data['nome'],
                'cpf' => $data['cpf'],
                'cnh' => $data['cnh'],
                'telefone' => $data['telefone'],
                'endereco' => $data['endereco'],
                'status' => $data['status']
            ];
        case 'veiculos':
            return [
                'id' => (int)$data['id'],
                'modelo' => $data['modelo'],
                'marca' => $data['marca'],
                'placa' => $data['placa'],
                'ano' => (int)$data['ano'],
                'cor' => $data['cor'],
                'motoristaId' => $data['motorista_id'] ? (int)$data['motorista_id'] : null,
                'status' => $data['status']
            ];
        case 'diarias':
            return [
                'id' => (int)$data['id'],
                'motoristaId' => (int)$data['motorista_id'],
                'veiculoId' => (int)$data['veiculo_id'],
                'data' => $data['data'],
                'valor' => (float)$data['valor'],
                'status' => $data['status']
            ];
        case 'manutencoes':
            return [
                'id' => (int)$data['id'],
                'veiculoId' => (int)$data['veiculo_id'],
                'tipo' => $data['tipo'],
                'data' => $data['data'],
                'valor' => (float)$data['valor'],
                'descricao' => $data['descricao']
            ];
        case 'contratos':
            return [
                'id' => (int)$data['id'],
                'veiculoId' => (int)$data['veiculo_id'],
                'motoristaId' => (int)$data['motorista_id'],
                'dataInicio' => $data['data_inicio'],
                'dataVencimento' => $data['data_vencimento'],
                'duracao' => (int)$data['duracao_dias'],
                'valorMensal' => (float)$data['valor_mensal'],
                'observacoes' => $data['observacoes'],
                'status' => $data['status']
            ];
        default:
            return $data;
    }
}

echo "</div>";

// Teste 3: Verificar se os scripts estão sendo carregados
echo "<div class='test-section'>";
echo "<h2>3. Verificar Carregamento de Scripts</h2>";
echo "<p>Teste no console do navegador (F12):</p>";
echo "<pre>
// Verificar se a API está disponível
console.log('RealDriverAPI:', window.RealDriverAPI);
console.log('USE_API_MODE:', window.USE_API_MODE);
console.log('REALDRIVER_API_URL:', window.REALDRIVER_API_URL);

// Testar carregamento de dados
if (window.RealDriverAPI) {
    window.RealDriverAPI.loadAllData(true).then(data => {
        console.log('Dados carregados:', data);
    }).catch(err => {
        console.error('Erro:', err);
    });
}
</pre>";
echo "</div>";

echo "</div></body></html>";

