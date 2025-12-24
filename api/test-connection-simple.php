<?php
/**
 * Versão simplificada do teste - mostra erros detalhados
 * 
 * Acesse: https://mvlopes.com.br/api/test-connection-simple.php
 */

// Mostrar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Capturar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo "<h1>Erro Fatal</h1>";
        echo "<p style='color: red;'>" . htmlspecialchars($error['message']) . "</p>";
        echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($error['file']) . "</p>";
        echo "<p><strong>Linha:</strong> " . $error['line'] . "</p>";
    }
});

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Teste de Conexão - Google Analytics API</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .ok { color: green; } .erro { color: red; } .info { color: blue; }</style>";

// 1. Verificar PHP
echo "<h2>1. Verificação do PHP</h2>";
echo "<p class='ok'>✅ PHP versão: " . phpversion() . "</p>";

// 2. Verificar arquivo de configuração
echo "<h2>2. Arquivo de Configuração</h2>";
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    echo "<p class='ok'>✅ config.php encontrado</p>";
    try {
        $config = require $configPath;
        echo "<p class='ok'>✅ config.php carregado com sucesso</p>";
        echo "<p class='info'>Property ID: " . ($config['ga4']['property_id'] ?? 'não encontrado') . "</p>";
    } catch (Exception $e) {
        echo "<p class='erro'>❌ Erro ao carregar config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    } catch (Error $e) {
        echo "<p class='erro'>❌ Erro fatal ao carregar config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='info'>Arquivo: " . htmlspecialchars($e->getFile()) . " na linha " . $e->getLine() . "</p>";
        exit;
    }
} else {
    echo "<p class='erro'>❌ config.php NÃO encontrado em: $configPath</p>";
    exit;
}

// 3. Verificar credenciais
echo "<h2>3. Arquivo de Credenciais</h2>";
$credentialsPath = $config['ga4']['credentials_path'] ?? __DIR__ . '/credentials.json';
if (file_exists($credentialsPath)) {
    echo "<p class='ok'>✅ credentials.json encontrado</p>";
    try {
        $creds = json_decode(file_get_contents($credentialsPath), true);
        if ($creds && isset($creds['client_email'])) {
            echo "<p class='ok'>✅ credentials.json válido</p>";
            echo "<p class='info'>Email da Service Account: " . htmlspecialchars($creds['client_email']) . "</p>";
        } else {
            echo "<p class='erro'>❌ credentials.json inválido ou corrompido</p>";
        }
    } catch (Exception $e) {
        echo "<p class='erro'>❌ Erro ao ler credentials.json: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='erro'>❌ credentials.json NÃO encontrado em: $credentialsPath</p>";
    echo "<p class='info'>Caminho atual: " . __DIR__ . "</p>";
}

// 4. Verificar biblioteca
echo "<h2>4. Biblioteca do Google Analytics</h2>";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "<p class='ok'>✅ vendor/autoload.php encontrado</p>";
    try {
        require_once $autoloadPath;
        echo "<p class='ok'>✅ Biblioteca carregada com sucesso</p>";
    } catch (Exception $e) {
        echo "<p class='erro'>❌ Erro ao carregar biblioteca: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='info'>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
        exit;
    } catch (Error $e) {
        echo "<p class='erro'>❌ Erro fatal ao carregar biblioteca: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='info'>Arquivo: " . htmlspecialchars($e->getFile()) . " na linha " . $e->getLine() . "</p>";
        echo "<p class='info'>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
        exit;
    }
} else {
    echo "<p class='erro'>❌ vendor/autoload.php NÃO encontrado</p>";
    echo "<p class='info'>Caminho esperado: $autoloadPath</p>";
    echo "<p class='info'>Execute: composer require google/analytics-data na pasta api/</p>";
    exit;
}

// 5. Verificar se as classes estão disponíveis
echo "<h2>5. Verificação de Classes do Google Analytics</h2>";
$classesToCheck = [
    'Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient',
    'Google\\Analytics\\Data\\V1beta\\DateRange',
    'Google\\Analytics\\Data\\V1beta\\Metric',
];

$allClassesFound = true;
foreach ($classesToCheck as $className) {
    if (class_exists($className)) {
        echo "<p class='ok'>✅ Classe encontrada: $className</p>";
    } else {
        echo "<p class='erro'>❌ Classe NÃO encontrada: $className</p>";
        $allClassesFound = false;
    }
}

if (!$allClassesFound) {
    echo "<p class='erro'><strong>❌ Algumas classes não foram encontradas!</strong></p>";
    echo "<p class='info'>Isso geralmente significa que a biblioteca não foi instalada corretamente.</p>";
    echo "<p class='info'>Tente executar: <code>composer require google/analytics-data</code> na pasta api/</p>";
    echo "<p class='info'>Ou verifique se a pasta vendor/ está completa no servidor.</p>";
    exit;
}

// Importar classes necessárias (após verificar que existem)
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;

// 6. Verificar extensões PHP necessárias
echo "<h2>5. Extensões PHP</h2>";
$extensions = ['json', 'curl', 'openssl', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='ok'>✅ Extensão $ext carregada</p>";
    } else {
        echo "<p class='erro'>❌ Extensão $ext NÃO carregada</p>";
    }
}

// 7. Tentar criar cliente
echo "<h2>7. Teste de Conexão com Google Analytics</h2>";

// Verificar se property_id existe
if (!isset($config['ga4']['property_id']) || empty($config['ga4']['property_id'])) {
    echo "<p class='erro'>❌ Property ID não configurado em config.php</p>";
    echo "<p class='info'>Adicione 'property_id' => 'seu-id' em config.php</p>";
    exit;
}

try {
    $client = new BetaAnalyticsDataClient([
        'credentials' => $credentialsPath,
    ]);
    
    echo "<p class='ok'>✅ Cliente criado com sucesso</p>";
    
    // Tentar uma requisição simples
    $propertyId = $config['ga4']['property_id'];
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
    
    echo "<p class='info'>Tentando buscar dados do Property ID: $propertyId</p>";
    echo "<p class='info'>Período: $startDate até $endDate</p>";
    
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
    
    echo "<p class='ok'>✅ Conexão com Google Analytics funcionando!</p>";
    
    $rows = $response->getRows();
    if (count($rows) > 0) {
        $value = $rows[0]->getMetricValues()[0]->getValue();
        echo "<p class='ok'>✅ Usuários ativos (últimos 7 dias): <strong>$value</strong></p>";
    } else {
        echo "<p class='info'>ℹ️ Nenhum dado encontrado (pode ser normal se o site é novo)</p>";
    }
    
    echo "<hr>";
    echo "<p class='ok'><strong>🎉 Tudo funcionando! A API está configurada corretamente.</strong></p>";
    
} catch (Exception $e) {
    echo "<p class='erro'>❌ Erro ao conectar com Google Analytics:</p>";
    echo "<p class='erro'><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<p class='info'>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
    
    echo "<h3>Possíveis causas:</h3>";
    echo "<ul>";
    echo "<li>Service Account não tem acesso ao Google Analytics</li>";
    echo "<li>Property ID incorreto</li>";
    echo "<li>Credenciais inválidas ou expiradas</li>";
    echo "<li>API não está habilitada no Google Cloud Console</li>";
    echo "<li>Permissões incorretas no arquivo credentials.json</li>";
    echo "</ul>";
} catch (Error $e) {
    echo "<p class='erro'>❌ Erro fatal ao conectar com Google Analytics:</p>";
    echo "<p class='erro'><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<p class='info'>Arquivo: " . htmlspecialchars($e->getFile()) . " na linha " . $e->getLine() . "</p>";
    echo "<p class='info'>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
}

echo "<hr>";
echo "<p><small>⚠️ <strong>Lembre-se:</strong> Remova este arquivo após testar por questões de segurança!</small></p>";
?>
