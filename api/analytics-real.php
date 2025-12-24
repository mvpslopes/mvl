<?php
/**
 * Versão com API REAL do Google Analytics
 * 
 * INSTRUÇÕES:
 * 1. Renomeie este arquivo para analytics.php (faça backup do original)
 * 2. OU substitua o conteúdo do analytics.php por este código
 * 3. Certifique-se de ter instalado: composer require google/analytics-data
 * 4. Configure use_mock_data => false em config.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Verificar autenticação
session_start();
if (!isset($_SESSION['auth_token']) || $_SESSION['user_role'] !== 'root') {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Não autorizado']);
  exit;
}

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

// Carregar configurações
$config = require __DIR__ . '/config.php';
$propertyId = $config['ga4']['property_id'];
$useMockData = $config['api']['use_mock_data'] ?? true;

// Se ainda estiver usando dados mockados, retornar mock
if ($useMockData) {
  $mockData = [
    'totalUsers' => rand(100, 1000),
    'totalSessions' => rand(150, 1200),
    'pageViews' => rand(200, 2000),
    'bounceRate' => round(rand(30, 70) + (rand(0, 99) / 100), 1),
    'topPages' => [
      ['page' => '/', 'views' => rand(100, 500)],
      ['page' => '/servicos', 'views' => rand(50, 300)],
      ['page' => '/projetos', 'views' => rand(30, 200)],
      ['page' => '/contato', 'views' => rand(20, 150)],
    ],
    'trafficSources' => [
      ['source' => 'Direto', 'sessions' => rand(50, 400)],
      ['source' => 'Google', 'sessions' => rand(30, 300)],
      ['source' => 'Redes Sociais', 'sessions' => rand(20, 200)],
      ['source' => 'Outros', 'sessions' => rand(10, 100)],
    ],
  ];

  echo json_encode([
    'success' => true,
    'data' => $mockData,
    'note' => 'Dados mockados. Configure use_mock_data => false em config.php',
  ]);
  exit;
}

// Usar API real do Google Analytics
try {
  // Verificar se a biblioteca está instalada
  $autoloadPath = __DIR__ . '/vendor/autoload.php';
  if (!file_exists($autoloadPath)) {
    throw new Exception('Biblioteca do Google Analytics não encontrada. Execute: composer require google/analytics-data');
  }

  require_once $autoloadPath;

  use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
  use Google\Analytics\Data\V1beta\DateRange;
  use Google\Analytics\Data\V1beta\Dimension;
  use Google\Analytics\Data\V1beta\Metric;
  use Google\Analytics\Data\V1beta\RunReportRequest;

  // Verificar se o arquivo de credenciais existe
  $credentialsPath = $config['ga4']['credentials_path'] ?? __DIR__ . '/credentials.json';
  if (!file_exists($credentialsPath)) {
    throw new Exception('Arquivo credentials.json não encontrado em: ' . $credentialsPath);
  }

  // Criar cliente
  $client = new BetaAnalyticsDataClient([
    'credentials' => $credentialsPath,
  ]);

  // Calcular datas
  $startDate = date('Y-m-d', strtotime("-$days days"));
  $endDate = date('Y-m-d');

  // Buscar métricas gerais
  $dateRange = new DateRange();
  $dateRange->setStartDate($startDate);
  $dateRange->setEndDate($endDate);
  
  $metrics = [
    (new Metric())->setName('activeUsers'),
    (new Metric())->setName('sessions'),
    (new Metric())->setName('screenPageViews'),
    (new Metric())->setName('bounceRate'),
  ];
  
  $request = new RunReportRequest();
  $request->setProperty("properties/$propertyId");
  $request->setDateRanges([$dateRange]);
  $request->setMetrics($metrics);
  
  $response = $client->runReport($request);

  // Processar métricas gerais
  $totalUsers = 0;
  $totalSessions = 0;
  $pageViews = 0;
  $bounceRate = 0;

  foreach ($response->getRows() as $row) {
    $metricValues = $row->getMetricValues();
    $totalUsers += (int)$metricValues[0]->getValue();
    $totalSessions += (int)$metricValues[1]->getValue();
    $pageViews += (int)$metricValues[2]->getValue();
    $bounceRate = (float)$metricValues[3]->getValue();
  }

  // Buscar páginas mais visitadas
  $pagesDateRange = new DateRange();
  $pagesDateRange->setStartDate($startDate);
  $pagesDateRange->setEndDate($endDate);
  
  $pageDimension = new Dimension();
  $pageDimension->setName('pagePath');
  
  $pageMetric = new Metric();
  $pageMetric->setName('screenPageViews');
  
  $pagesRequest = new RunReportRequest();
  $pagesRequest->setProperty("properties/$propertyId");
  $pagesRequest->setDateRanges([$pagesDateRange]);
  $pagesRequest->setDimensions([$pageDimension]);
  $pagesRequest->setMetrics([$pageMetric]);
  $pagesRequest->setLimit(10);
  // Ordenação será feita pelo Google Analytics automaticamente
  
  $pagesResponse = $client->runReport($pagesRequest);

  $topPages = [];
  foreach ($pagesResponse->getRows() as $row) {
    $dimensionValues = $row->getDimensionValues();
    $metricValues = $row->getMetricValues();
    $topPages[] = [
      'page' => $dimensionValues[0]->getValue(),
      'views' => (int)$metricValues[0]->getValue(),
    ];
  }

  // Buscar fontes de tráfego
  $trafficDateRange = new DateRange();
  $trafficDateRange->setStartDate($startDate);
  $trafficDateRange->setEndDate($endDate);
  
  $trafficDimension = new Dimension();
  $trafficDimension->setName('sessionSource');
  
  $trafficMetric = new Metric();
  $trafficMetric->setName('sessions');
  
  $trafficRequest = new RunReportRequest();
  $trafficRequest->setProperty("properties/$propertyId");
  $trafficRequest->setDateRanges([$trafficDateRange]);
  $trafficRequest->setDimensions([$trafficDimension]);
  $trafficRequest->setMetrics([$trafficMetric]);
  $trafficRequest->setLimit(10);
  // Ordenação será feita pelo Google Analytics automaticamente
  
  $trafficResponse = $client->runReport($trafficRequest);

  $trafficSources = [];
  foreach ($trafficResponse->getRows() as $row) {
    $dimensionValues = $row->getDimensionValues();
    $metricValues = $row->getMetricValues();
    $source = $dimensionValues[0]->getValue();
    // Traduzir algumas fontes comuns
    if ($source === '(direct)') {
      $source = 'Direto';
    } elseif (strpos($source, 'google') !== false) {
      $source = 'Google';
    } elseif (strpos($source, 'facebook') !== false || strpos($source, 'instagram') !== false) {
      $source = 'Redes Sociais';
    }
    $trafficSources[] = [
      'source' => $source,
      'sessions' => (int)$metricValues[0]->getValue(),
    ];
  }

  // Retornar dados formatados
  echo json_encode([
    'success' => true,
    'data' => [
      'totalUsers' => $totalUsers,
      'totalSessions' => $totalSessions,
      'pageViews' => $pageViews,
      'bounceRate' => round($bounceRate * 100, 1), // Converter para porcentagem
      'topPages' => $topPages,
      'trafficSources' => $trafficSources,
    ],
    'dateRange' => [
      'start' => $startDate,
      'end' => $endDate,
      'days' => $days,
    ],
  ]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Erro ao buscar dados do Google Analytics: ' . $e->getMessage(),
    'error' => $e->getMessage(),
    'property_id' => $propertyId,
  ]);
  error_log('Google Analytics API Error: ' . $e->getMessage());
}
?>

