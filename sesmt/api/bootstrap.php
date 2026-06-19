<?php

declare(strict_types=1);

function sesmt_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $candidates = [
        __DIR__ . '/config.local.php',
        dirname(__DIR__) . '/api/config.local.php',
    ];

    foreach ($candidates as $local) {
        if (is_file($local)) {
            $config = require $local;
            return $config;
        }
    }

    $fromEnv = sesmt_config_from_env();
    if ($fromEnv !== null) {
        $config = $fromEnv;
        return $config;
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Configure api/config.local.php na hospedagem (copie de config.example.php e preencha o banco).',
        'hint' => 'Após o deploy, envie manualmente api/config.local.php para a pasta api/ do subdomínio.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sesmt_config_from_env(): ?array
{
    $host = getenv('SESMT_DB_HOST') ?: '';
    $dbname = getenv('SESMT_DB_NAME') ?: '';
    $user = getenv('SESMT_DB_USER') ?: '';
    $pass = getenv('SESMT_DB_PASS');

    if ($host === '' || $dbname === '' || $user === '') {
        return null;
    }

    return [
        'db' => [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $user,
            'password' => $pass !== false ? $pass : '',
            'charset' => 'utf8mb4',
        ],
        'app' => [
            'token_ttl_hours' => (int) (getenv('SESMT_TOKEN_TTL') ?: 24),
            'session_name' => 'SESMTSESSID',
        ],
    ];
}

function sesmt_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = sesmt_config()['db'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['dbname'],
        $db['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function sesmt_cors(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
}

function sesmt_options_exit(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
                $headers[strtolower($key)] = $value;
            }
        }
        return $headers;
    }
}

function sesmt_bearer_token(): ?string
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$auth) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['auth_token'] ?? null;
    }
    return preg_replace('/^Bearer\s+/i', '', trim($auth)) ?: null;
}

function sesmt_ensure_auth_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auth_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            user_role VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_token (token),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function sesmt_resolve_user_from_token(PDO $pdo, string $token): ?array
{
    sesmt_ensure_auth_tables($pdo);

    $stmt = $pdo->prepare("
        SELECT t.user_id, t.user_role, t.expires_at,
               u.nome, u.username, u.ativo
        FROM auth_tokens t
        INNER JOIN usuarios u ON u.id = t.user_id
        WHERE t.token = :token
          AND (t.expires_at IS NULL OR t.expires_at > NOW())
          AND u.ativo = 1
    ");
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT p.nome
        FROM perfis p
        INNER JOIN usuario_perfis up ON up.perfil_id = p.id
        WHERE up.usuario_id = :uid
    ");
    $stmt->execute(['uid' => $row['user_id']]);
    $perfis = array_column($stmt->fetchAll(), 'nome');

    return [
        'id' => (int) $row['user_id'],
        'nome' => $row['nome'],
        'username' => $row['username'],
        'role' => in_array('root', $perfis, true) ? 'root' : ($perfis[0] ?? $row['user_role']),
        'perfis' => $perfis,
        'ativo' => (bool) $row['ativo'],
    ];
}

function sesmt_require_auth(): array
{
    $token = sesmt_bearer_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autenticado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $user = sesmt_resolve_user_from_token(sesmt_pdo(), $token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sessão inválida ou expirada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $user;
}

function sesmt_require_root(): array
{
    $user = sesmt_require_auth();
    if ($user['role'] !== 'root') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Apenas usuários root podem realizar esta ação.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $user;
}

function sesmt_json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function sesmt_user_perfis(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT p.nome, p.id
        FROM perfis p
        INNER JOIN usuario_perfis up ON up.perfil_id = p.id
        WHERE up.usuario_id = :uid
    ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

function sesmt_format_user(array $row, array $perfis): array
{
    $names = array_column($perfis, 'nome');
    return [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'username' => $row['username'],
        'ativo' => (bool) $row['ativo'],
        'perfis' => $names,
        'role' => in_array('root', $names, true) ? 'root' : ($names[0] ?? 'admin'),
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}
