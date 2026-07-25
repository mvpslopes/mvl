<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function briefing_cors(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-HTTP-Method-Override');
    header('Content-Type: application/json; charset=utf-8');
}

function briefing_options_exit(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        briefing_cors();
        http_response_code(200);
        exit;
    }
}

function briefing_request_method(): string
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

function briefing_json(array $payload, int $code = 200): void
{
    briefing_cors();
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function briefing_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function briefing_ensure_dirs(): void
{
    $dirs = [briefing_data_dir(), briefing_uploads_dir()];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    $ht = briefing_uploads_dir() . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Require all denied\nDeny from all\n");
    }
    $json = briefing_json_file();
    if (!is_file($json)) {
        file_put_contents($json, "[]\n");
    }
}

function briefing_read_json(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function briefing_write_json(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Falha ao serializar JSON.');
    }
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Falha ao gravar JSON.');
    }
    rename($tmp, $path);
}

function briefing_sanitize_text(?string $value, int $max = 2000): string
{
    $v = trim((string) $value);
    $v = strip_tags($v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v) ?? $v;
    if (mb_strlen($v) > $max) {
        $v = mb_substr($v, 0, $max);
    }
    return $v;
}

function briefing_sanitize_hex(?string $value): string
{
    $v = trim((string) $value);
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) {
        return strtoupper($v);
    }
    return '';
}

function briefing_client_ip(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (str_contains($ip, ',')) {
        $ip = trim(explode(',', $ip)[0]);
    }
    return briefing_sanitize_text($ip, 64);
}

function briefing_rate_limit_ok(string $ip): bool
{
    briefing_ensure_dirs();
    $file = briefing_rate_file();
    $now = time();
    $data = briefing_read_json($file);
    $bucket = is_array($data[$ip] ?? null) ? $data[$ip] : [];
    $times = array_values(array_filter(
        array_map('intval', $bucket),
        static fn ($t) => $t > ($now - BRIEFING_RATE_LIMIT_WINDOW)
    ));
    if (count($times) >= BRIEFING_RATE_LIMIT_MAX) {
        $data[$ip] = $times;
        briefing_write_json($file, $data);
        return false;
    }
    $times[] = $now;
    $data[$ip] = $times;
    // limpa IPs antigos
    foreach ($data as $k => $list) {
        if (!is_array($list)) {
            unset($data[$k]);
            continue;
        }
        $filtered = array_values(array_filter($list, static fn ($t) => (int) $t > ($now - BRIEFING_RATE_LIMIT_WINDOW)));
        if ($filtered === []) {
            unset($data[$k]);
        } else {
            $data[$k] = $filtered;
        }
    }
    briefing_write_json($file, $data);
    return true;
}

function briefing_new_id(): string
{
    return bin2hex(random_bytes(8));
}

function briefing_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function briefing_pdo(): ?PDO
{
    static $pdo = null;
    static $tried = false;
    if ($tried) {
        return $pdo;
    }
    $tried = true;

    $candidates = [
        dirname(__DIR__) . '/config.local.php',
        dirname(__DIR__) . '/config.php',
    ];
    $cfg = null;
    foreach ($candidates as $path) {
        if (is_file($path)) {
            $loaded = require $path;
            if (is_array($loaded) && isset($loaded['db'])) {
                $cfg = $loaded;
                break;
            }
        }
    }
    if ($cfg === null) {
        // fallback alinhado ao painel MVL
        $cfg = [
            'db' => [
                'host' => 'localhost',
                'dbname' => 'u179630068_mvl',
                'username' => 'u179630068_mvlroot',
                'password' => '/5ld=SX7j;W',
                'charset' => 'utf8mb4',
            ],
        ];
    }

    try {
        $db = $cfg['db'];
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
    } catch (Throwable) {
        $pdo = null;
    }
    return $pdo;
}

function briefing_require_auth(): void
{
    $token = briefing_bearer_token();
    if (!$token) {
        briefing_json(['success' => false, 'message' => 'Não autenticado.'], 401);
    }
    $pdo = briefing_pdo();
    if (!$pdo) {
        briefing_json(['success' => false, 'message' => 'Auth indisponível.'], 500);
    }
    try {
        $stmt = $pdo->prepare('
            SELECT user_role FROM auth_tokens
            WHERE token = :t AND expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        if (!$row || ($row['user_role'] ?? '') !== 'root') {
            briefing_json(['success' => false, 'message' => 'Sessão expirada ou sem permissão.'], 401);
        }
    } catch (Throwable) {
        briefing_json(['success' => false, 'message' => 'Erro de autenticação.'], 500);
    }
}
