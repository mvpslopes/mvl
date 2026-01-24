<?php
// Iniciar output buffering para evitar problemas com headers
ob_start();

// Habilitar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros na resposta JSON
ini_set('log_errors', 1);

// Handler de erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        // Limpar qualquer output anterior
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Verificar se headers já foram enviados
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        
        $errorResponse = [
            'success' => false,
            'message' => 'Erro fatal no servidor',
            'error' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ];
        
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
        error_log('Fatal error in analytics.php: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
        exit;
    }
});

// Limpar qualquer output anterior antes de enviar headers
if (ob_get_level() > 0) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Verificar autenticação
$isAuthenticated = false;
$authMethod = 'none';

try {
  // Função compatível para obter headers
  if (!function_exists('getallheaders')) {
    function getallheaders() {
      $headers = [];
      foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
          $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
          $headers[$headerName] = $value;
          $headers[strtolower($headerName)] = $value;
        }
      }
      return $headers;
    }
  }
  
  // Tentar autenticação via token no header
  $headers = getallheaders();
  $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
  $token = null;
  
  if ($authHeader) {
    // Formato: "Bearer <token>" ou apenas "<token>"
    $token = preg_replace('/^Bearer\s+/i', '', $authHeader);
  }
  
  // Iniciar sessão apenas uma vez
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  
  // Se não tem token no header, tentar via sessão
  if (empty($token)) {
    $token = $_SESSION['auth_token'] ?? null;
    if ($token) {
      $authMethod = 'session';
    }
  } else {
    $authMethod = 'header';
  }
  
  // Se tem token, verificar no banco de dados
  if (!empty($token)) {
    try {
      // Configurações do banco de dados
      $host = 'localhost';
      $dbname = 'u179630068_mvl';
      $username = 'u179630068_mvlroot';
      $dbpassword = '/5ld=SX7j;W';
      
      $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $dbpassword);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      
      // Verificar se a tabela existe antes de criar
      $tableExists = false;
      try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'auth_tokens'");
        $tableExists = $stmt->rowCount() > 0;
      } catch (PDOException $e) {
        // Ignorar erro de verificação
      }
      
      // Criar tabela apenas se não existir
      if (!$tableExists) {
        try {
          $pdo->exec("
            CREATE TABLE IF NOT EXISTS auth_tokens (
              id INT AUTO_INCREMENT PRIMARY KEY,
              token VARCHAR(64) UNIQUE NOT NULL,
              user_id INT NOT NULL,
              user_role VARCHAR(50) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              expires_at TIMESTAMP NULL,
              INDEX idx_token (token),
              INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          ");
        } catch (PDOException $e) {
          // Se falhar ao criar, continuar (pode já existir)
          error_log('Aviso ao criar tabela auth_tokens: ' . $e->getMessage());
        }
      }
      
      // Buscar token no banco
      $stmt = $pdo->prepare("
        SELECT t.user_id, t.user_role, t.expires_at, u.ativo
        FROM auth_tokens t
        INNER JOIN usuarios u ON t.user_id = u.id
        WHERE t.token = :token
        AND (t.expires_at IS NULL OR t.expires_at > NOW())
        AND u.ativo = 1
      ");
      $stmt->execute(['token' => $token]);
      $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($tokenData && $tokenData['user_role'] === 'root') {
        $isAuthenticated = true;
        $authMethod .= '_db';
      } else {
        // Se não encontrou no banco, tentar verificar na sessão (fallback)
        if (isset($_SESSION['auth_token']) && $_SESSION['auth_token'] === $token) {
          if (($_SESSION['user_role'] ?? '') === 'root') {
            $isAuthenticated = true;
            $authMethod = 'session_fallback';
          }
        }
      }
    } catch (PDOException $e) {
      // Se erro no banco, tentar sessão como fallback
      error_log('Erro ao verificar token no banco: ' . $e->getMessage());
      if (isset($_SESSION['auth_token']) && $_SESSION['auth_token'] === $token) {
        if (($_SESSION['user_role'] ?? '') === 'root') {
          $isAuthenticated = true;
          $authMethod = 'session_fallback';
        }
      }
    } catch (Exception $e) {
      // Capturar qualquer outra exceção
      error_log('Erro inesperado na autenticação: ' . $e->getMessage());
      // Tentar sessão como fallback
      if (isset($_SESSION['auth_token']) && $_SESSION['auth_token'] === $token) {
        if (($_SESSION['user_role'] ?? '') === 'root') {
          $isAuthenticated = true;
          $authMethod = 'session_fallback';
        }
      }
    }
  }
  
  // Log para debug
  error_log('Analytics API - Auth check: ' . json_encode([
    'authenticated' => $isAuthenticated,
    'method' => $authMethod,
    'has_token' => !empty($token),
    'token_length' => $token ? strlen($token) : 0
  ]));
  
  if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
      'success' => false, 
      'message' => 'Não autorizado. Faça login novamente.',
      'debug' => [
        'auth_method' => $authMethod,
        'has_token' => !empty($token),
        'headers_received' => !empty($authHeader)
      ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Erro ao verificar autenticação: ' . $e->getMessage(),
    'error' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
  ], JSON_UNESCAPED_UNICODE);
  error_log('Erro na verificação de autenticação: ' . $e->getMessage());
  error_log('Stack trace: ' . $e->getTraceAsString());
  exit;
}

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

// Carregar configurações
try {
  $config = require __DIR__ . '/config.php';
  $propertyId = $config['ga4']['property_id'] ?? null;
  $useMockData = $config['api']['use_mock_data'] ?? false;
  
  if (!$propertyId) {
    throw new Exception('Property ID não configurado em config.php');
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Erro ao carregar configurações: ' . $e->getMessage()
  ]);
  error_log('Erro ao carregar config: ' . $e->getMessage());
  exit;
}

// Log para debug
error_log("API Analytics - use_mock_data: " . ($useMockData ? 'true' : 'false'));
error_log("API Analytics - Property ID: " . $propertyId);

// Se ainda estiver usando dados mockados, retornar mock
if ($useMockData === true) {
  // Gerar horários de pico (24 horas)
  $peakHours = [];
  for ($i = 0; $i < 24; $i++) {
    $peakHours[] = [
      'hour' => str_pad($i, 2, '0', STR_PAD_LEFT) . ':00',
      'value' => rand(1, 12)
    ];
  }

  // Gerar atividade por dia da semana
  $activityByDay = [
    ['day' => 'Sunday', 'value' => rand(5, 20)],
    ['day' => 'Monday', 'value' => rand(10, 25)],
    ['day' => 'Tuesday', 'value' => rand(8, 22)],
    ['day' => 'Wednesday', 'value' => rand(9, 23)],
    ['day' => 'Thursday', 'value' => rand(7, 20)],
    ['day' => 'Friday', 'value' => rand(12, 28)],
    ['day' => 'Saturday', 'value' => rand(6, 18)],
  ];

  // Gerar visitantes ao longo do tempo
  $visitorsOverTime = [];
  for ($i = $days; $i >= 0; $i--) {
    $date = date('d/m/Y', strtotime("-$i days"));
    $visitorsOverTime[] = [
      'date' => $date,
      'value' => rand(5, 25)
    ];
  }

  // Gerar dispositivos
  $totalSessions = rand(150, 1200);
  $desktopSessions = floor($totalSessions * 0.35);
  $mobileSessions = $totalSessions - $desktopSessions;
  $devices = [
    ['device' => 'desktop', 'sessions' => $desktopSessions, 'percentage' => round(($desktopSessions / $totalSessions) * 100, 1)],
    ['device' => 'mobile', 'sessions' => $mobileSessions, 'percentage' => round(($mobileSessions / $totalSessions) * 100, 1)],
  ];

  // Gerar navegadores
  $browsers = [
    ['browser' => 'Chrome', 'sessions' => floor($totalSessions * 0.68)],
    ['browser' => 'Safari', 'sessions' => floor($totalSessions * 0.24)],
    ['browser' => 'Firefox', 'sessions' => floor($totalSessions * 0.04)],
    ['browser' => 'Edge', 'sessions' => floor($totalSessions * 0.02)],
    ['browser' => 'Unknown', 'sessions' => floor($totalSessions * 0.02)],
  ];

  // Gerar sistemas operacionais
  $operatingSystems = [
    ['os' => 'Linux', 'sessions' => floor($totalSessions * 0.54)],
    ['os' => 'macOS', 'sessions' => floor($totalSessions * 0.30)],
    ['os' => 'Windows', 'sessions' => floor($totalSessions * 0.12)],
    ['os' => 'Unknown', 'sessions' => floor($totalSessions * 0.04)],
  ];

  // Gerar países
  $countries = [
    ['country' => 'Brazil', 'sessions' => floor($totalSessions * 0.62), 'views' => floor($totalSessions * 1.5)],
    ['country' => 'France', 'sessions' => floor($totalSessions * 0.10), 'views' => floor($totalSessions * 0.10)],
    ['country' => 'United States', 'sessions' => floor($totalSessions * 0.08), 'views' => floor($totalSessions * 0.08)],
    ['country' => 'Luxembourg', 'sessions' => floor($totalSessions * 0.02), 'views' => floor($totalSessions * 0.02)],
    ['country' => 'Spain', 'sessions' => floor($totalSessions * 0.02), 'views' => floor($totalSessions * 0.02)],
    ['country' => 'Poland', 'sessions' => floor($totalSessions * 0.02), 'views' => floor($totalSessions * 0.02)],
    ['country' => 'Canada', 'sessions' => floor($totalSessions * 0.02), 'views' => floor($totalSessions * 0.02)],
  ];

  // Gerar cidades
  $cities = [
    ['city' => 'Conselheiro Lafaiete(Brazil)', 'sessions' => 6, 'views' => 129],
    ['city' => 'Paris(France)', 'sessions' => 5, 'views' => 5],
    ['city' => 'Belo Horizonte(Brazil)', 'sessions' => 3, 'views' => 3],
    ['city' => 'Petrópolis(Brazil)', 'sessions' => 2, 'views' => 2],
    ['city' => 'Rio de Janeiro(Brazil)', 'sessions' => 2, 'views' => 2],
    ['city' => 'Bragança Paulista(Brazil)', 'sessions' => 2, 'views' => 2],
    ['city' => 'Ouro Branco(Brazil)', 'sessions' => 2, 'views' => 3],
    ['city' => 'Mountain View(United States)', 'sessions' => 2, 'views' => 2],
    ['city' => 'Burnaby(Canada)', 'sessions' => 2, 'views' => 2],
    ['city' => 'Prineville(United States)', 'sessions' => 2, 'views' => 2],
  ];

  // Gerar páginas de entrada e saída
  $topPages = [
    ['page' => '/', 'views' => rand(100, 500), 'avgTime' => '5m 37s'],
    ['page' => '/servicos', 'views' => rand(50, 300), 'avgTime' => '3m 20s'],
    ['page' => '/projetos', 'views' => rand(30, 200), 'avgTime' => '4m 15s'],
    ['page' => '/contato', 'views' => rand(20, 150), 'avgTime' => '2m 10s'],
  ];

  $entryPages = array_map(function($page) {
    return ['page' => $page['page'], 'entries' => floor($page['views'] * 1.1)];
  }, $topPages);

  $exitPages = array_map(function($page) {
    return ['page' => $page['page'], 'exits' => floor($page['views'] * 0.9)];
  }, $topPages);

  $totalUsers = rand(100, 1000);
  $pageViews = rand(200, 2000);
  $bounceRate = round(rand(30, 70) + (rand(0, 99) / 100), 1);
  $avgTimeOnPage = rand(2, 8) . 'm ' . rand(10, 59) . 's';
  $avgSessionDuration = rand(0, 5) . 'm ' . rand(0, 59) . 's';
  $totalClicks = floor($totalSessions * 0.1);
  $conversionRate = round(($totalClicks / $totalSessions) * 100, 1);
  $pagesPerSession = round($pageViews / $totalSessions, 1);

  $mockData = [
    'totalUsers' => $totalUsers,
    'totalSessions' => $totalSessions,
    'pageViews' => $pageViews,
    'bounceRate' => $bounceRate,
    'totalClicks' => $totalClicks,
    'avgTimeOnPage' => $avgTimeOnPage,
    'avgSessionDuration' => $avgSessionDuration,
    'conversionRate' => $conversionRate,
    'pagesPerSession' => $pagesPerSession,
    'onlineNow' => rand(1, 5),
    'topPages' => $topPages,
    'trafficSources' => [
      ['source' => 'Redes Sociais', 'sessions' => rand(20, 200)],
      ['source' => 'Outros', 'sessions' => rand(10, 100)],
      ['source' => 'Buscadores', 'sessions' => rand(5, 50)],
    ],
    'peakHours' => $peakHours,
    'activityByDay' => $activityByDay,
    'visitorsOverTime' => $visitorsOverTime,
    'devices' => $devices,
    'browsers' => $browsers,
    'operatingSystems' => $operatingSystems,
    'entryPages' => $entryPages,
    'exitPages' => $exitPages,
    'countries' => $countries,
    'cities' => $cities,
  ];

  echo json_encode([
    'success' => true,
    'data' => $mockData,
    'note' => 'Dados mockados. Configure use_mock_data => false em config.php',
  ], JSON_UNESCAPED_UNICODE);
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
  
  // Verificar se o arquivo de credenciais existe
  $credentialsPath = $config['ga4']['credentials_path'] ?? __DIR__ . '/credentials.json';
  if (!file_exists($credentialsPath)) {
    throw new Exception('Arquivo credentials.json não encontrado em: ' . $credentialsPath);
  }

  // Criar cliente
  $client = new \Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient([
    'credentials' => $credentialsPath,
  ]);

  // Calcular datas
  $startDate = date('Y-m-d', strtotime("-$days days"));
  $endDate = date('Y-m-d');

  // Buscar métricas gerais
  $dateRange = new \Google\Analytics\Data\V1beta\DateRange();
  $dateRange->setStartDate($startDate);
  $dateRange->setEndDate($endDate);
  
  $metrics = [
    (new \Google\Analytics\Data\V1beta\Metric())->setName('activeUsers'),
    (new \Google\Analytics\Data\V1beta\Metric())->setName('sessions'),
    (new \Google\Analytics\Data\V1beta\Metric())->setName('screenPageViews'),
    (new \Google\Analytics\Data\V1beta\Metric())->setName('bounceRate'),
  ];
  
  $request = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $request->setProperty("properties/$propertyId");
  $request->setDateRanges([$dateRange]);
  $request->setMetrics($metrics);
  
  // Log da requisição ao Google Analytics
  error_log("Google Analytics API - Fazendo requisição ao Property ID: $propertyId");
  error_log("Google Analytics API - Período: $startDate até $endDate ($days dias)");
  
  $response = $client->runReport($request);
  
  // Log da resposta
  error_log("Google Analytics API - Resposta recebida com sucesso");
  error_log("Google Analytics API - Número de linhas: " . count($response->getRows()));

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
  
  // Log dos dados processados
  error_log("Google Analytics API - Dados processados: Users=$totalUsers, Sessions=$totalSessions, PageViews=$pageViews, BounceRate=$bounceRate");

  // Buscar páginas mais visitadas
  $pagesDateRange = new \Google\Analytics\Data\V1beta\DateRange();
  $pagesDateRange->setStartDate($startDate);
  $pagesDateRange->setEndDate($endDate);
  
  $pageDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $pageDimension->setName('pagePath');
  
  $pageMetric = new \Google\Analytics\Data\V1beta\Metric();
  $pageMetric->setName('screenPageViews');
  
  $pagesRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
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
  $trafficDateRange = new \Google\Analytics\Data\V1beta\DateRange();
  $trafficDateRange->setStartDate($startDate);
  $trafficDateRange->setEndDate($endDate);
  
  $trafficDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $trafficDimension->setName('sessionSource');
  
  $trafficMetric = new \Google\Analytics\Data\V1beta\Metric();
  $trafficMetric->setName('sessions');
  
  $trafficRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
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

  // Inicializar todas as variáveis com arrays vazios
  $peakHours = [];
  $activityByDay = [];
  $visitorsOverTime = [];
  $devices = [];
  $browsers = [];
  $operatingSystems = [];
  $countries = [];
  $cities = [];
  $entryPages = [];
  $exitPages = [];

  // Buscar horários de pico (hora do dia)
  // Usar dateHour que combina data e hora
  $hourDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $hourDimension->setName('dateHour');
  $hourMetric = new \Google\Analytics\Data\V1beta\Metric();
  $hourMetric->setName('sessions');
  
  $hourRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $hourRequest->setProperty("properties/$propertyId");
  $hourRequest->setDateRanges([$dateRange]);
  $hourRequest->setDimensions([$hourDimension]);
  $hourRequest->setMetrics([$hourMetric]);
  
  try {
    $hourResponse = $client->runReport($hourRequest);
    $peakHours = [];
    $hourData = [];
    if ($hourResponse->getRows()) {
      foreach ($hourResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $dateHourStr = $dimensionValues[0]->getValue();
          // Formato: YYYYMMDDHH (ex: 2025012514 para 14:00 do dia 25/01/2025)
          // Extrair apenas a hora (últimos 2 dígitos antes dos últimos 2)
          if (strlen($dateHourStr) >= 10) {
            $hour = (int)substr($dateHourStr, -2);
            $hourData[$hour] = ($hourData[$hour] ?? 0) + (int)$metricValues[0]->getValue();
          }
        }
      }
    }
    // Preencher todas as 24 horas
    for ($i = 0; $i < 24; $i++) {
      $peakHours[] = [
        'hour' => str_pad($i, 2, '0', STR_PAD_LEFT) . ':00',
        'value' => $hourData[$i] ?? 0
      ];
    }
  } catch (Exception $e) {
    $peakHours = [];
    error_log('Erro ao buscar horários de pico: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    // Se falhar, retornar array vazio - o frontend mostrará "Nenhum dado disponível"
  }

  // Buscar atividade por dia da semana
  $dayDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $dayDimension->setName('dayOfWeek');
  $dayMetric = new \Google\Analytics\Data\V1beta\Metric();
  $dayMetric->setName('sessions');
  
  $dayRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $dayRequest->setProperty("properties/$propertyId");
  $dayRequest->setDateRanges([$dateRange]);
  $dayRequest->setDimensions([$dayDimension]);
  $dayRequest->setMetrics([$dayMetric]);
  
  try {
    $dayResponse = $client->runReport($dayRequest);
    $activityByDay = [];
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $dayData = [];
    if ($dayResponse->getRows()) {
      foreach ($dayResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $dayIndex = (int)$dimensionValues[0]->getValue();
          $dayData[$dayIndex] = (int)$metricValues[0]->getValue();
        }
      }
    }
    foreach ($dayNames as $index => $dayName) {
      $activityByDay[] = [
        'day' => $dayName,
        'value' => $dayData[$index] ?? 0
      ];
    }
  } catch (Exception $e) {
    $activityByDay = [];
    error_log('Erro ao buscar atividade por dia: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
  }

  // Buscar visitantes ao longo do tempo
  $dateDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $dateDimension->setName('date');
  $dateMetric = new \Google\Analytics\Data\V1beta\Metric();
  $dateMetric->setName('sessions');
  
  $dateRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $dateRequest->setProperty("properties/$propertyId");
  $dateRequest->setDateRanges([$dateRange]);
  $dateRequest->setDimensions([$dateDimension]);
  $dateRequest->setMetrics([$dateMetric]);
  
  try {
    $dateResponse = $client->runReport($dateRequest);
    $visitorsOverTime = [];
    if ($dateResponse->getRows()) {
      foreach ($dateResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $dateStr = $dimensionValues[0]->getValue();
          // Converter formato YYYYMMDD para DD/MM/YYYY
          if (strlen($dateStr) >= 8) {
            $year = substr($dateStr, 0, 4);
            $month = substr($dateStr, 4, 2);
            $day = substr($dateStr, 6, 2);
            $visitorsOverTime[] = [
              'date' => "$day/$month/$year",
              'value' => (int)$metricValues[0]->getValue()
            ];
          }
        }
      }
    }
  } catch (Exception $e) {
    $visitorsOverTime = [];
    error_log('Erro ao buscar visitantes ao longo do tempo: ' . $e->getMessage());
  }

  // Buscar dispositivos
  $deviceDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $deviceDimension->setName('deviceCategory');
  $deviceMetric = new \Google\Analytics\Data\V1beta\Metric();
  $deviceMetric->setName('sessions');
  
  $deviceRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $deviceRequest->setProperty("properties/$propertyId");
  $deviceRequest->setDateRanges([$dateRange]);
  $deviceRequest->setDimensions([$deviceDimension]);
  $deviceRequest->setMetrics([$deviceMetric]);
  
  try {
    $deviceResponse = $client->runReport($deviceRequest);
    $devices = [];
    if ($deviceResponse->getRows()) {
      foreach ($deviceResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $device = strtolower($dimensionValues[0]->getValue());
          $sessions = (int)$metricValues[0]->getValue();
          $devices[] = [
            'device' => $device,
            'sessions' => $sessions,
            'percentage' => $totalSessions > 0 ? round(($sessions / $totalSessions) * 100, 1) : 0
          ];
        }
      }
    }
  } catch (Exception $e) {
    $devices = [];
    error_log('Erro ao buscar dispositivos: ' . $e->getMessage());
  }

  // Buscar navegadores
  $browserDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $browserDimension->setName('browser');
  $browserMetric = new \Google\Analytics\Data\V1beta\Metric();
  $browserMetric->setName('sessions');
  
  $browserRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $browserRequest->setProperty("properties/$propertyId");
  $browserRequest->setDateRanges([$dateRange]);
  $browserRequest->setDimensions([$browserDimension]);
  $browserRequest->setMetrics([$browserMetric]);
  $browserRequest->setLimit(10);
  
  try {
    $browserResponse = $client->runReport($browserRequest);
    $browsers = [];
    if ($browserResponse->getRows()) {
      foreach ($browserResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $browsers[] = [
            'browser' => $dimensionValues[0]->getValue(),
            'sessions' => (int)$metricValues[0]->getValue()
          ];
        }
      }
    }
  } catch (Exception $e) {
    $browsers = [];
    error_log('Erro ao buscar navegadores: ' . $e->getMessage());
  }

  // Buscar sistemas operacionais
  $osDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $osDimension->setName('operatingSystem');
  $osMetric = new \Google\Analytics\Data\V1beta\Metric();
  $osMetric->setName('sessions');
  
  $osRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $osRequest->setProperty("properties/$propertyId");
  $osRequest->setDateRanges([$dateRange]);
  $osRequest->setDimensions([$osDimension]);
  $osRequest->setMetrics([$osMetric]);
  $osRequest->setLimit(10);
  
  try {
    $osResponse = $client->runReport($osRequest);
    $operatingSystems = [];
    if ($osResponse->getRows()) {
      foreach ($osResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $operatingSystems[] = [
            'os' => $dimensionValues[0]->getValue(),
            'sessions' => (int)$metricValues[0]->getValue()
          ];
        }
      }
    }
  } catch (Exception $e) {
    $operatingSystems = [];
    error_log('Erro ao buscar sistemas operacionais: ' . $e->getMessage());
  }

  // Buscar países
  $countryDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $countryDimension->setName('country');
  $countryMetric = new \Google\Analytics\Data\V1beta\Metric();
  $countryMetric->setName('sessions');
  $countryViewsMetric = new \Google\Analytics\Data\V1beta\Metric();
  $countryViewsMetric->setName('screenPageViews');
  
  $countryRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $countryRequest->setProperty("properties/$propertyId");
  $countryRequest->setDateRanges([$dateRange]);
  $countryRequest->setDimensions([$countryDimension]);
  $countryRequest->setMetrics([$countryMetric, $countryViewsMetric]);
  $countryRequest->setLimit(20);
  
  try {
    $countryResponse = $client->runReport($countryRequest);
    $countries = [];
    if ($countryResponse->getRows()) {
      foreach ($countryResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) >= 2) {
          $countries[] = [
            'country' => $dimensionValues[0]->getValue(),
            'sessions' => (int)$metricValues[0]->getValue(),
            'views' => (int)$metricValues[1]->getValue()
          ];
        }
      }
    }
  } catch (Exception $e) {
    $countries = [];
    error_log('Erro ao buscar países: ' . $e->getMessage());
  }

  // Buscar cidades
  $cityDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $cityDimension->setName('city');
  $cityCountryDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $cityCountryDimension->setName('country');
  $cityMetric = new \Google\Analytics\Data\V1beta\Metric();
  $cityMetric->setName('sessions');
  $cityViewsMetric = new \Google\Analytics\Data\V1beta\Metric();
  $cityViewsMetric->setName('screenPageViews');
  
  $cityRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $cityRequest->setProperty("properties/$propertyId");
  $cityRequest->setDateRanges([$dateRange]);
  $cityRequest->setDimensions([$cityDimension, $cityCountryDimension]);
  $cityRequest->setMetrics([$cityMetric, $cityViewsMetric]);
  $cityRequest->setLimit(20);
  
  try {
    $cityResponse = $client->runReport($cityRequest);
    $cities = [];
    if ($cityResponse->getRows()) {
      foreach ($cityResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) >= 2 && count($metricValues) >= 2) {
          $cityName = $dimensionValues[0]->getValue();
          $countryName = $dimensionValues[1]->getValue();
          $cities[] = [
            'city' => "$cityName($countryName)",
            'sessions' => (int)$metricValues[0]->getValue(),
            'views' => (int)$metricValues[1]->getValue()
          ];
        }
      }
    }
  } catch (Exception $e) {
    $cities = [];
    error_log('Erro ao buscar cidades: ' . $e->getMessage());
  }

  // Buscar páginas de entrada
  $entryDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $entryDimension->setName('landingPage');
  $entryMetric = new \Google\Analytics\Data\V1beta\Metric();
  $entryMetric->setName('sessions');
  
  $entryRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $entryRequest->setProperty("properties/$propertyId");
  $entryRequest->setDateRanges([$dateRange]);
  $entryRequest->setDimensions([$entryDimension]);
  $entryRequest->setMetrics([$entryMetric]);
  $entryRequest->setLimit(10);
  
  try {
    $entryResponse = $client->runReport($entryRequest);
    $entryPages = [];
    if ($entryResponse->getRows()) {
      foreach ($entryResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $entryPages[] = [
            'page' => $dimensionValues[0]->getValue(),
            'entries' => (int)$metricValues[0]->getValue()
          ];
        }
      }
    }
  } catch (Exception $e) {
    $entryPages = [];
    error_log('Erro ao buscar páginas de entrada: ' . $e->getMessage());
  }

  // Buscar páginas de saída
  $exitDimension = new \Google\Analytics\Data\V1beta\Dimension();
  $exitDimension->setName('exitPage');
  $exitMetric = new \Google\Analytics\Data\V1beta\Metric();
  $exitMetric->setName('sessions');
  
  $exitRequest = new \Google\Analytics\Data\V1beta\RunReportRequest();
  $exitRequest->setProperty("properties/$propertyId");
  $exitRequest->setDateRanges([$dateRange]);
  $exitRequest->setDimensions([$exitDimension]);
  $exitRequest->setMetrics([$exitMetric]);
  $exitRequest->setLimit(10);
  
  try {
    $exitResponse = $client->runReport($exitRequest);
    $exitPages = [];
    if ($exitResponse->getRows()) {
      foreach ($exitResponse->getRows() as $row) {
        $dimensionValues = $row->getDimensionValues();
        $metricValues = $row->getMetricValues();
        if (count($dimensionValues) > 0 && count($metricValues) > 0) {
          $exitPages[] = [
            'page' => $dimensionValues[0]->getValue(),
            'exits' => (int)$metricValues[0]->getValue()
          ];
        }
      }
    }
  } catch (Exception $e) {
    $exitPages = [];
    error_log('Erro ao buscar páginas de saída: ' . $e->getMessage());
  }

  // Calcular métricas adicionais
  $avgTimeOnPage = '0s';
  $avgSessionDuration = '0s';
  $totalClicks = 0;
  $conversionRate = 0;
  $pagesPerSession = $totalSessions > 0 ? round($pageViews / $totalSessions, 1) : 0;
  $onlineNow = 0;

  // Retornar dados formatados
  $responseData = [
    'success' => true,
    'data' => [
      'totalUsers' => $totalUsers,
      'totalSessions' => $totalSessions,
      'pageViews' => $pageViews,
      'bounceRate' => round($bounceRate * 100, 1), // Converter para porcentagem
      'totalClicks' => $totalClicks,
      'avgTimeOnPage' => $avgTimeOnPage,
      'avgSessionDuration' => $avgSessionDuration,
      'conversionRate' => $conversionRate,
      'pagesPerSession' => $pagesPerSession,
      'onlineNow' => $onlineNow,
      'topPages' => $topPages,
      'trafficSources' => $trafficSources,
      'peakHours' => $peakHours,
      'activityByDay' => $activityByDay,
      'visitorsOverTime' => $visitorsOverTime,
      'devices' => $devices,
      'browsers' => $browsers,
      'operatingSystems' => $operatingSystems,
      'entryPages' => $entryPages,
      'exitPages' => $exitPages,
      'countries' => $countries,
      'cities' => $cities,
    ],
    'dateRange' => [
      'start' => $startDate,
      'end' => $endDate,
      'days' => $days,
    ],
  ];
  
  // Adicionar informações de validação se solicitado
  if (isset($_GET['validate']) && $_GET['validate'] === 'true') {
    $responseData['validation'] = [
      'source' => 'Google Analytics Data API v1beta',
      'property_id' => $propertyId,
      'api_client' => 'Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient',
      'data_source' => 'Real-time Google Analytics data',
      'mock_data' => false,
      'timestamp' => date('Y-m-d H:i:s'),
      'requests_made' => [
        'main_metrics' => 'activeUsers, sessions, screenPageViews, bounceRate',
        'top_pages' => 'pagePath dimension with screenPageViews metric',
        'traffic_sources' => 'sessionSource dimension with sessions metric',
      ],
    ];
  }
  
  echo json_encode($responseData, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
  http_response_code(500);
  $errorMessage = $e->getMessage();
  $errorTrace = $e->getTraceAsString();
  
  error_log('Google Analytics API Error: ' . $errorMessage);
  error_log('Stack trace: ' . $errorTrace);
  
  echo json_encode([
    'success' => false,
    'message' => 'Erro ao buscar dados do Google Analytics: ' . $errorMessage,
    'error' => $errorMessage,
    'property_id' => $propertyId ?? 'não definido',
    'file' => $e->getFile(),
    'line' => $e->getLine(),
  ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
  http_response_code(500);
  $errorMessage = $e->getMessage();
  $errorTrace = $e->getTraceAsString();
  
  error_log('Google Analytics API Fatal Error: ' . $errorMessage);
  error_log('Stack trace: ' . $errorTrace);
  
  echo json_encode([
    'success' => false,
    'message' => 'Erro fatal ao buscar dados: ' . $errorMessage,
    'error' => $errorMessage,
    'file' => $e->getFile(),
    'line' => $e->getLine(),
  ], JSON_UNESCAPED_UNICODE);
}

// Enviar output buffer
if (ob_get_level() > 0) {
    ob_end_flush();
}
