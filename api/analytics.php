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
  $useMockData = $config['api']['use_mock_data'] ?? true;
  
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

  // Retornar dados formatados
  $responseData = [
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
