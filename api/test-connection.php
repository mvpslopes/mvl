<?php
/**
 * Script de teste para verificar se a conexão com Google Analytics está funcionando
 * 
 * Acesse via navegador: https://seusite.com.br/api/test-connection.php
 * 
 * ⚠️ IMPORTANTE: Remova este arquivo após testar por questões de segurança
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Teste de Conexão - Google Analytics API</h1>";

// Verificar se o arquivo de configuração existe
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die("<p style='color: red;'>❌ Arquivo config.php não encontrado!</p>");
}

echo "<p>✅ Arquivo config.php encontrado</p>";

// Carregar configurações
$config = require $configPath;
$propertyId = $config['ga4']['property_id'] ?? null;
$credentialsPath = $config['ga4']['credentials_path'] ?? __DIR__ . '/credentials.json';

echo "<p>Property ID: <strong>$propertyId</strong></p>";
echo "<p>Caminho das credenciais: <strong>$credentialsPath</strong></p>";

// Verificar se o arquivo de credenciais existe
if (!file_exists($credentialsPath)) {
    die("<p style='color: red;'>❌ Arquivo credentials.json não encontrado em: $credentialsPath</p>");
}

echo "<p>✅ Arquivo credentials.json encontrado</p>";

// Verificar se a biblioteca está instalada
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("<p style='color: red;'>❌ Biblioteca não encontrada. Execute: <code>composer require google/analytics-data</code></p>");
}

echo "<p>✅ Biblioteca instalada</p>";

// Tentar carregar a biblioteca
try {
    require_once $autoloadPath;
    echo "<p>✅ Biblioteca carregada com sucesso</p>";
} catch (Exception $e) {
    die("<p style='color: red;'>❌ Erro ao carregar biblioteca: " . $e->getMessage() . "</p>");
}

// Tentar criar o cliente
try {
    use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
    use Google\Analytics\Data\V1beta\DateRange;
    use Google\Analytics\Data\V1beta\Metric;
    use Google\Analytics\Data\V1beta\RunReportRequest;
    
    $client = new BetaAnalyticsDataClient([
        'credentials' => $credentialsPath,
    ]);
    
    echo "<p>✅ Cliente criado com sucesso</p>";
    
    // Tentar fazer uma requisição simples
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
    
    // Criar o objeto RunReportRequest
    $dateRange = new DateRange();
    $dateRange->setStartDate($startDate);
    $dateRange->setEndDate($endDate);
    
    $metric = new Metric();
    $metric->setName('activeUsers');
    
    $request = new RunReportRequest();
    $request->setProperty("properties/$propertyId");
    $request->setDateRanges([$dateRange]);
    $request->setMetrics([$metric]);
    $request->setLimit(1);
    
    $response = $client->runReport($request);
    
    echo "<p>✅ Conexão com Google Analytics funcionando!</p>";
    echo "<p>Período testado: <strong>$startDate</strong> até <strong>$endDate</strong></p>";
    
    $rows = $response->getRows();
    if (count($rows) > 0) {
        $value = $rows[0]->getMetricValues()[0]->getValue();
        echo "<p>Usuários ativos (últimos 7 dias): <strong>$value</strong></p>";
    }
    
    echo "<hr>";
    echo "<p style='color: green;'><strong>🎉 Tudo funcionando! Você pode usar a API real agora.</strong></p>";
    echo "<p>Próximos passos:</p>";
    echo "<ol>";
    echo "<li>Altere <code>use_mock_data => false</code> em <code>config.php</code></li>";
    echo "<li>Substitua o conteúdo de <code>analytics.php</code> pelo código de <code>analytics-real.php</code></li>";
    echo "<li>Teste o dashboard em <code>/dashboard</code></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao conectar com Google Analytics:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    
    echo "<h3>Possíveis causas:</h3>";
    echo "<ul>";
    echo "<li>Service Account não tem acesso ao Google Analytics</li>";
    echo "<li>Property ID incorreto</li>";
    echo "<li>Credenciais inválidas ou expiradas</li>";
    echo "<li>API não está habilitada no Google Cloud Console</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><small>⚠️ <strong>Lembre-se:</strong> Remova este arquivo após testar por questões de segurança!</small></p>";
?>

