<?php

declare(strict_types=1);

function ideias_config(): array
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

    $config = [
        'db' => [
            'host' => 'localhost',
            'dbname' => 'u179630068_mvl',
            'username' => 'u179630068_mvlroot',
            'password' => '/5ld=SX7j;W',
            'charset' => 'utf8mb4',
        ],
    ];
    return $config;
}

function ideias_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = ideias_config()['db'];
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

/**
 * Configuração privada da IA. A chave nunca deve ser enviada ao frontend.
 *
 * @return array{api_key: string, model: string}
 */
function ideias_groq_config(): array
{
    $apiKey = trim((string) (getenv('GROQ_API_KEY') ?: ''));
    $model = trim((string) (getenv('GROQ_MODEL') ?: ''));

    $localPath = __DIR__ . '/config.local.php';
    if (is_file($localPath)) {
        $local = require $localPath;
        if (is_array($local) && is_array($local['groq'] ?? null)) {
            $groq = $local['groq'];
            if ($apiKey === '') {
                $apiKey = trim((string) ($groq['api_key'] ?? ''));
            }
            if ($model === '') {
                $model = trim((string) ($groq['model'] ?? ''));
            }
        }
    }

    return [
        'api_key' => $apiKey,
        'model' => $model !== '' ? $model : 'openai/gpt-oss-120b',
    ];
}

function ideias_cors(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-HTTP-Method-Override, X-Method-Override');
    header('Content-Type: application/json; charset=utf-8');
}

function ideias_options_exit(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        ideias_cors();
        http_response_code(200);
        exit;
    }
}

function ideias_request_method(): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== 'POST') {
        return $method;
    }
    $override = strtoupper(trim((string) (
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']
        ?? $_SERVER['HTTP_X_METHOD_OVERRIDE']
        ?? ''
    )));
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        return $override;
    }
    return $method;
}

function ideias_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function ideias_json(array $payload, int $code = 200): void
{
    ideias_cors();
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ideias_require_auth(): void
{
    $token = ideias_resolve_token();
    if ($token === null || $token === '') {
        ideias_json(['success' => false, 'message' => 'Não autenticado.'], 401);
    }
    if (!ideias_validate_root_token($token)) {
        ideias_json(['success' => false, 'message' => 'Sessão expirada ou sem permissão.'], 401);
    }
}

function ideias_resolve_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function ideias_validate_root_token(string $token): bool
{
    try {
        $pdo = ideias_pdo();
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

function ideias_load_service(): IdeiaService
{
    require_once __DIR__ . '/lib/IdeiaService.php';
    return new IdeiaService(ideias_pdo());
}

function ideias_slugify(string $nome): string
{
    $s = mb_strtolower(trim($nome), 'UTF-8');
    $map = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s) ?? $s;
    $s = trim($s, '-');
    if ($s === '') {
        $s = 'tag';
    }
    if (mb_strlen($s) > 80) {
        $s = mb_substr($s, 0, 80);
    }
    return $s;
}
