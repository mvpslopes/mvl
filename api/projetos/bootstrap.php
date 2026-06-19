<?php

declare(strict_types=1);

function proj_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $candidates = [
        __DIR__ . '/config.local.php',
        dirname(__DIR__) . '/config.local.php',
        dirname(__DIR__, 2) . '/api/config.local.php',
        dirname(__DIR__, 2) . '/api/config.php',
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

    $config = proj_default_config();
    return $config;
}

function proj_default_config(): array
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
            'session_name' => 'PROJSESSID',
        ],
    ];
}

function proj_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = proj_config()['db'];
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

function proj_cors(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
}

function proj_options_exit(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        proj_cors();
        http_response_code(200);
        exit;
    }
}

function proj_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function proj_json(array $payload, int $code = 200): void
{
    proj_cors();
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function proj_require_auth(): void
{
    $token = proj_resolve_token();

    if ($token === null || $token === '') {
        proj_json(['success' => false, 'message' => 'Não autenticado.'], 401);
    }

    if (proj_validate_root_token($token)) {
        return;
    }

    proj_json(['success' => false, 'message' => 'Sessão expirada ou sem permissão.'], 401);
}

function proj_resolve_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }

    if (session_status() === PHP_SESSION_NONE) {
        $config = proj_config();
        session_name($config['app']['session_name'] ?? 'PROJSESSID');
        session_start();
    }

    return $_SESSION['auth_token'] ?? null;
}

function proj_validate_root_token(string $token): bool
{
    try {
        $pdo = proj_pdo();
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

function proj_load_service(): ProjetoService
{
    require_once __DIR__ . '/lib/ProjetoService.php';
    return new ProjetoService(proj_pdo());
}

function proj_parse_iso_week(string $iso): array
{
    if (!preg_match('/^(\d{4})-W(\d{2})$/', $iso, $m)) {
        throw new InvalidArgumentException('Semana inválida. Use AAAA-Wnn.');
    }
    $dto = new DateTimeImmutable();
    $dto = $dto->setISODate((int) $m[1], (int) $m[2]);
    $de = $dto->format('Y-m-d');
    $ate = $dto->modify('+6 days')->format('Y-m-d');

    return ['iso' => $iso, 'de' => $de, 'ate' => $ate];
}

function proj_current_iso_week(): string
{
    return (new DateTimeImmutable())->format('o-\WW');
}
