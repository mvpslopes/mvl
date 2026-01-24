<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Método não permitido']);
  exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['userId'], $data['newPassword'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
  exit;
}

$userId = (int)$data['userId'];
$newPassword = $data['newPassword'];

// Validar senha
if (strlen($newPassword) < 6) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']);
  exit;
}

// Verificar autenticação
$isAuthenticated = false;
$authMethod = 'none';

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

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'u179630068_mvl';
$username = 'u179630068_mvlroot';
$dbpassword = '/5ld=SX7j;W';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $dbpassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Se tem token, verificar no banco de dados
  if (!empty($token)) {
    try {
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
        // Se não encontrou no banco, verificar perfis do usuário diretamente
        if ($tokenData) {
          $userId = $tokenData['user_id'];
          // Verificar se o usuário tem perfil root
          $stmt = $pdo->prepare("
            SELECT p.nome
            FROM perfis p
            INNER JOIN usuario_perfis up ON p.id = up.perfil_id
            WHERE up.usuario_id = :user_id AND p.nome = 'root'
          ");
          $stmt->execute(['user_id' => $userId]);
          $rootProfile = $stmt->fetch(PDO::FETCH_ASSOC);
          
          if ($rootProfile) {
            $isAuthenticated = true;
            $authMethod = 'db_profile';
          }
        }
        
        // Se não encontrou no banco, tentar verificar na sessão (fallback)
        if (!$isAuthenticated && isset($_SESSION['auth_token']) && $_SESSION['auth_token'] === $token) {
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
  
  if (!$isAuthenticated) {
    http_response_code(403);
    echo json_encode([
      'success' => false, 
      'message' => 'Acesso negado. Apenas usuários root podem alterar senhas.',
      'debug' => [
        'auth_method' => $authMethod,
        'has_token' => !empty($token),
        'token_length' => $token ? strlen($token) : 0
      ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Verificar se o usuário existe
  $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id = :userId");
  $stmt->execute(['userId' => $userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    exit;
  }

  // Hash da nova senha
  $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

  // Atualizar senha
  $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = :passwordHash WHERE id = :userId");
  $stmt->execute([
    'passwordHash' => $passwordHash,
    'userId' => $userId
  ]);

  echo json_encode([
    'success' => true,
    'message' => 'Senha alterada com sucesso'
  ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
  error_log($e->getMessage());
}

