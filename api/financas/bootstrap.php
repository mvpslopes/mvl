<?php

declare(strict_types=1);

function fin_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $candidates = [
        __DIR__ . '/config.local.php',
        dirname(__DIR__) . '/config.local.php',
        dirname(__DIR__) . '/config.php',
    ];

    foreach ($candidates as $local) {
        if (is_file($local)) {
            $loaded = require $local;
            if (is_array($loaded) && isset($loaded['db'])) {
                $config = $loaded;
                return $config;
            }
        }
    }

    $config = fin_default_config();
    return $config;
}

/** Mesmo banco do painel MVL quando não há config.local.php */
function fin_default_config(): array
{
    return [
        'db' => [
            'host' => 'localhost',
            'dbname' => 'u179630068_mvl',
            'username' => 'u179630068_mvlroot',
            'password' => '/5ld=SX7j;W',
            'charset' => 'utf8mb4',
        ],
        'app' => [
            'token_ttl_hours' => 168,
            'session_name' => 'FINSESSID',
        ],
    ];
}

function fin_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = fin_config()['db'];
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

function fin_cors(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
}

function fin_options_exit(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        fin_cors();
        http_response_code(200);
        exit;
    }
}

function fin_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function fin_json(array $payload, int $code = 200): void
{
    fin_cors();
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function fin_require_auth(): void
{
    $token = fin_resolve_token();

    if ($token === null || $token === '') {
        fin_json(['success' => false, 'message' => 'Não autenticado.'], 401);
    }

    if (fin_validate_fin_token($token) || fin_validate_root_token($token)) {
        return;
    }

    fin_json(['success' => false, 'message' => 'Sessão expirada ou sem permissão.'], 401);
}

function fin_resolve_token(): ?string
{
    $token = fin_bearer_token();
    if ($token) {
        return $token;
    }

    if (session_status() === PHP_SESSION_NONE) {
        $config = fin_config();
        session_name($config['app']['session_name'] ?? 'FINSESSID');
        session_start();
    }

    return $_SESSION['auth_token'] ?? null;
}

function fin_validate_fin_token(string $token): bool
{
    try {
        $pdo = fin_pdo();
        fin_ensure_auth_table($pdo);
        $stmt = $pdo->prepare('SELECT token FROM fin_auth_tokens WHERE token = :t AND expires_at > NOW()');
        $stmt->execute(['t' => $token]);
        return (bool) $stmt->fetch();
    } catch (Throwable) {
        return false;
    }
}

function fin_validate_root_token(string $token): bool
{
    try {
        $pdo = fin_pdo();
        $stmt = $pdo->prepare('
            SELECT user_role FROM auth_tokens
            WHERE token = :t AND expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row && ($row['user_role'] ?? '') === 'root';
    } catch (Throwable) {
        return false;
    }
}

function fin_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function fin_ensure_auth_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fin_auth_tokens (
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (token),
            KEY idx_fin_token_exp (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function fin_create_token(PDO $pdo): string
{
    fin_ensure_auth_table($pdo);
    $pdo->exec('DELETE FROM fin_auth_tokens WHERE expires_at <= NOW()');

    $token = bin2hex(random_bytes(32));
    $ttl = (int) (fin_config()['app']['token_ttl_hours'] ?? 168);
    $expires = date('Y-m-d H:i:s', strtotime("+{$ttl} hours"));

    $pdo->prepare('INSERT INTO fin_auth_tokens (token, expires_at) VALUES (:t, :e)')->execute([
        't' => $token,
        'e' => $expires,
    ]);

    return $token;
}

function fin_invalidate_token(PDO $pdo, string $token): void
{
    fin_ensure_auth_table($pdo);
    $pdo->prepare('DELETE FROM fin_auth_tokens WHERE token = :t')->execute(['t' => $token]);
}

function fin_load_service(): FinanceService
{
    require_once __DIR__ . '/lib/FinanceService.php';
    return new FinanceService(fin_pdo());
}
