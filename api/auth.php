<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

if (!$data || !isset($data['email'], $data['password'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
  exit;
}

$email = trim($data['email']);
$password = $data['password'];

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'u179630068_mvl';
$username = 'u179630068_mvlroot';
$dbpassword = '/5ld=SX7j;W';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $dbpassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Buscar usuário
  $stmt = $pdo->prepare("
    SELECT u.id, u.nome, u.email, u.senha_hash, u.ativo,
           GROUP_CONCAT(p.nome) as perfis
    FROM usuarios u
    LEFT JOIN usuario_perfis up ON u.id = up.usuario_id
    LEFT JOIN perfis p ON up.perfil_id = p.id
    WHERE u.email = :email
    GROUP BY u.id
  ");
  
  $stmt->execute(['email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Credenciais inválidas']);
    exit;
  }

  if (!$user['ativo']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuário inativo']);
    exit;
  }

  // Verificar senha
  if (!password_verify($password, $user['senha_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Credenciais inválidas']);
    exit;
  }

  // Gerar token simples (em produção, use JWT)
  $token = bin2hex(random_bytes(32));
  
  // Processar perfis - garantir que sempre tenha um array válido
  $perfisString = $user['perfis'] ?? '';
  $perfis = !empty($perfisString) ? explode(',', $perfisString) : [];
  $perfis = array_filter(array_map('trim', $perfis)); // Remove espaços e valores vazios
  $perfis = array_values($perfis); // Reindexa o array
  
  $isRoot = in_array('root', $perfis);

  // Determinar role - sempre garantir que tenha um valor
  $userRole = 'root'; // Padrão: root para permitir acesso ao dashboard
  if (!empty($perfis)) {
    $userRole = $isRoot ? 'root' : $perfis[0];
  }
  
  // Garantir que role não seja vazio
  if (empty($userRole)) {
    $userRole = 'root';
  }

  // Salvar token na sessão
  session_start();
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['user_email'] = $user['email'];
  $_SESSION['user_role'] = $userRole;
  $_SESSION['auth_token'] = $token;

  // Salvar token no banco de dados para persistência entre requisições
  try {
    // Criar tabela se não existir
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
    
    // Remover tokens antigos do mesmo usuário (opcional - manter apenas o último)
    $pdo->prepare("DELETE FROM auth_tokens WHERE user_id = :user_id")->execute(['user_id' => $user['id']]);
    
    // Salvar novo token (expira em 24 horas)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $stmt = $pdo->prepare("
      INSERT INTO auth_tokens (token, user_id, user_role, expires_at)
      VALUES (:token, :user_id, :user_role, :expires_at)
      ON DUPLICATE KEY UPDATE 
        user_id = VALUES(user_id),
        user_role = VALUES(user_role),
        expires_at = VALUES(expires_at),
        created_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
      'token' => $token,
      'user_id' => $user['id'],
      'user_role' => $userRole,
      'expires_at' => $expiresAt
    ]);
  } catch (PDOException $e) {
    // Log erro mas não falha o login
    error_log('Erro ao salvar token no banco: ' . $e->getMessage());
  }

  // Preparar resposta - garantir que todos os campos estejam presentes e não vazios
  $response = [
    'success' => true,
    'token' => $token,
    'name' => $user['nome'] ?? 'Usuário',
    'email' => $user['email'] ?? '',
    'role' => $userRole
  ];

  // Validação final - garantir que role não seja null, vazio ou false
  if (empty($response['role']) || $response['role'] === null || $response['role'] === false) {
    $response['role'] = 'root';
    error_log('AVISO: Role estava vazio, definido como root por padrão');
  }

  // Log para debug (remover em produção)
  error_log('Auth response: ' . json_encode($response));
  error_log('User perfis original: ' . ($user['perfis'] ?? 'null'));
  error_log('UserRole final: ' . $userRole);

  // Garantir que o header está definido
  header('Content-Type: application/json; charset=utf-8');
  
  echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
  error_log($e->getMessage());
}

