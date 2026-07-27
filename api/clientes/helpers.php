<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function clientes_cors(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-HTTP-Method-Override');
    header('Content-Type: application/json; charset=utf-8');
}

function clientes_options_exit(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        clientes_cors();
        http_response_code(200);
        exit;
    }
}

function clientes_request_method(): string
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

function clientes_json(array $payload, int $code = 200): void
{
    clientes_cors();
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function clientes_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function clientes_ensure_dirs(): void
{
    $dirs = [clientes_data_dir(), clientes_uploads_dir()];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Sem permissão para criar pasta de dados dos clientes.');
            }
        }
    }
    if (!is_writable(clientes_data_dir())) {
        throw new RuntimeException('Pasta api/clientes/dados sem permissão de escrita.');
    }
    $htData = clientes_data_dir() . '/.htaccess';
    if (!is_file($htData)) {
        file_put_contents(
            $htData,
            "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n"
        );
    }
    $ht = clientes_uploads_dir() . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Require all denied\nDeny from all\n");
    }
    $json = clientes_json_file();
    if (!is_file($json)) {
        file_put_contents($json, "[]\n");
    }
}

/**
 * Importa logos padrão do site (seed/) quando a lista está vazia.
 * A pasta seed/ vai no deploy; dados/ permanece só no servidor.
 *
 * @return int quantidade importada
 */
function clientes_seed_if_empty(bool $force = false): int
{
    clientes_ensure_dirs();
    $existing = clientes_read_json(clientes_json_file());
    $hasItems = is_array($existing) && array_is_list($existing) && $existing !== [];
    if ($hasItems && !$force) {
        return 0;
    }

    $manifestPath = clientes_seed_manifest_file();
    if (!is_file($manifestPath)) {
        return 0;
    }
    $manifest = clientes_read_json($manifestPath);
    if ($manifest === [] || !array_is_list($manifest)) {
        return 0;
    }

    if ($force && $hasItems) {
        foreach ($existing as $old) {
            if (is_array($old)) {
                clientes_delete_files(is_array($old['logo'] ?? null) ? $old['logo'] : null);
            }
        }
    }

    $list = [];
    $order = 0;
    foreach ($manifest as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = clientes_sanitize_text((string) ($row['name'] ?? ''), 120);
        $file = basename((string) ($row['file'] ?? ''));
        if ($name === '' || $file === '' || str_contains($file, '..')) {
            continue;
        }
        $src = clientes_seed_dir() . '/logos/' . $file;
        if (!is_file($src)) {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, CLIENTES_ALLOWED_EXT, true)) {
            continue;
        }

        $id = clientes_new_id();
        $dir = clientes_uploads_dir() . '/' . $id;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $stored = 'logo_seed.' . $ext;
        $dest = $dir . '/' . $stored;
        if (!copy($src, $dest)) {
            continue;
        }

        $list[] = [
            'id' => $id,
            'name' => $name,
            'url' => clientes_sanitize_url((string) ($row['url'] ?? '')),
            'bg_color' => clientes_sanitize_hex((string) ($row['bg_color'] ?? '#FFFFFF')),
            'sort_order' => $order++,
            'logo' => [
                'original' => $file,
                'stored' => $stored,
                'ext' => $ext,
                'size' => (int) filesize($dest),
                'path' => 'logos/' . $id . '/' . $stored,
            ],
            'updated_at' => gmdate('c'),
        ];
    }

    if ($list === []) {
        return 0;
    }
    clientes_write_json(clientes_json_file(), $list);
    return count($list);
}

function clientes_read_json(string $path): array
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

function clientes_write_json(string $path, array $data): void
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

function clientes_sanitize_text(?string $value, int $max = 500): string
{
    $v = trim((string) $value);
    $v = strip_tags($v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v) ?? $v;
    if (mb_strlen($v) > $max) {
        $v = mb_substr($v, 0, $max);
    }
    return $v;
}

function clientes_sanitize_hex(?string $value, string $default = '#FFFFFF'): string
{
    $v = trim((string) $value);
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) {
        return strtoupper($v);
    }
    if (preg_match('/^[0-9A-Fa-f]{6}$/', $v)) {
        return '#' . strtoupper($v);
    }
    return $default;
}

function clientes_sanitize_url(?string $value): string
{
    $v = clientes_sanitize_text($value, 500);
    if ($v === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $v)) {
        $v = 'https://' . $v;
    }
    if (filter_var($v, FILTER_VALIDATE_URL) === false) {
        return '';
    }
    return $v;
}

function clientes_new_id(): string
{
    return bin2hex(random_bytes(8));
}

function clientes_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function clientes_pdo(): ?PDO
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

function clientes_require_auth(): void
{
    $token = clientes_bearer_token();
    if (!$token) {
        clientes_json(['success' => false, 'message' => 'Não autenticado.'], 401);
    }
    $pdo = clientes_pdo();
    if (!$pdo) {
        clientes_json(['success' => false, 'message' => 'Auth indisponível.'], 500);
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
            clientes_json(['success' => false, 'message' => 'Sessão expirada ou sem permissão.'], 401);
        }
    } catch (Throwable) {
        clientes_json(['success' => false, 'message' => 'Erro de autenticação.'], 500);
    }
}
