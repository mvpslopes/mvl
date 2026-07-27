<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * @return list<array<string, mixed>>
 */
function clientes_all(): array
{
    clientes_ensure_dirs();
    clientes_seed_if_empty(false);
    $list = clientes_read_json(clientes_json_file());
    if ($list === [] || !array_is_list($list)) {
        return [];
    }
    usort($list, static function ($a, $b) {
        $oa = (int) ($a['sort_order'] ?? 0);
        $ob = (int) ($b['sort_order'] ?? 0);
        if ($oa === $ob) {
            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        }
        return $oa <=> $ob;
    });
    return $list;
}

function clientes_save_all(array $list): void
{
    clientes_ensure_dirs();
    clientes_write_json(clientes_json_file(), array_values($list));
}

function clientes_find(string $id): ?array
{
    foreach (clientes_all() as $item) {
        if (($item['id'] ?? '') === $id) {
            return $item;
        }
    }
    return null;
}

/**
 * @return array{original: string, stored: string, ext: string, size: int, path: string}|null
 */
function clientes_store_upload(string $field, string $clientId): ?array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Falha no upload do arquivo.');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > CLIENTES_MAX_UPLOAD_BYTES) {
        throw new InvalidArgumentException('Arquivo excede o limite de 5 MB.');
    }

    $original = (string) ($file['name'] ?? 'logo');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, CLIENTES_ALLOWED_EXT, true)) {
        throw new InvalidArgumentException('Extensão não permitida. Use png, jpg, webp ou svg.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new InvalidArgumentException('Upload inválido.');
    }

    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new InvalidArgumentException('Imagem inválida.');
        }
    }

    $dir = clientes_uploads_dir() . '/' . $clientId;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $stored = 'logo_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $stored;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new InvalidArgumentException('Não foi possível salvar o arquivo.');
    }

    return [
        'original' => clientes_sanitize_text($original, 200),
        'stored' => $stored,
        'ext' => $ext,
        'size' => $size,
        'path' => 'logos/' . $clientId . '/' . $stored,
    ];
}

function clientes_resolve_upload_path(array $meta): ?string
{
    $rel = (string) ($meta['path'] ?? '');
    if ($rel === '' || str_contains($rel, '..')) {
        return null;
    }
    $full = clientes_data_dir() . '/' . $rel;
    if (!is_file($full)) {
        return null;
    }
    return $full;
}

function clientes_delete_files(?array $meta): void
{
    if (!is_array($meta)) {
        return;
    }
    $path = clientes_resolve_upload_path($meta);
    if ($path !== null && is_file($path)) {
        @unlink($path);
    }
    $rel = (string) ($meta['path'] ?? '');
    if ($rel !== '' && !str_contains($rel, '..')) {
        $dir = dirname(clientes_data_dir() . '/' . $rel);
        if (is_dir($dir)) {
            $left = @scandir($dir);
            if (is_array($left) && count(array_diff($left, ['.', '..'])) === 0) {
                @rmdir($dir);
            }
        }
    }
}

/**
 * Payload público (site) ou admin.
 *
 * @param array<string, mixed> $item
 * @return array<string, mixed>
 */
function clientes_public_item(array $item): array
{
    $id = (string) ($item['id'] ?? '');
    $hasLogo = is_array($item['logo'] ?? null);
    return [
        'id' => $id,
        'name' => (string) ($item['name'] ?? ''),
        'url' => (string) ($item['url'] ?? ''),
        'bg_color' => clientes_sanitize_hex((string) ($item['bg_color'] ?? '#FFFFFF')),
        'sort_order' => (int) ($item['sort_order'] ?? 0),
        'logo_url' => $hasLogo && $id !== ''
            ? '/api/clientes/file.php?id=' . rawurlencode($id)
            : null,
    ];
}

/**
 * @param array<string, mixed> $item
 * @return array<string, mixed>
 */
function clientes_admin_item(array $item): array
{
    $pub = clientes_public_item($item);
    $pub['logo'] = is_array($item['logo'] ?? null) ? $item['logo'] : null;
    $pub['updated_at'] = (string) ($item['updated_at'] ?? '');
    return $pub;
}

/**
 * @return array<string, mixed>
 */
function clientes_create(array $fields, ?array $logoMeta, ?string $forcedId = null): array
{
    $list = clientes_all();
    $id = $forcedId !== null && $forcedId !== '' ? $forcedId : clientes_new_id();
    $maxOrder = -1;
    foreach ($list as $c) {
        $maxOrder = max($maxOrder, (int) ($c['sort_order'] ?? 0));
    }
    $item = [
        'id' => $id,
        'name' => clientes_sanitize_text((string) ($fields['name'] ?? ''), 120),
        'url' => clientes_sanitize_url((string) ($fields['url'] ?? '')),
        'bg_color' => clientes_sanitize_hex((string) ($fields['bg_color'] ?? '#FFFFFF')),
        'sort_order' => isset($fields['sort_order']) ? (int) $fields['sort_order'] : $maxOrder + 1,
        'logo' => $logoMeta,
        'updated_at' => gmdate('c'),
    ];
    if ($item['name'] === '') {
        throw new InvalidArgumentException('Nome é obrigatório.');
    }
    if ($logoMeta === null) {
        throw new InvalidArgumentException('Envie a logo do cliente.');
    }
    $list[] = $item;
    clientes_save_all($list);
    return $item;
}

/**
 * @return array<string, mixed>|null
 */
function clientes_update(string $id, array $fields, ?array $logoMeta = null, bool $replaceLogo = false): ?array
{
    $list = clientes_all();
    $found = null;
    foreach ($list as $i => $item) {
        if (($item['id'] ?? '') !== $id) {
            continue;
        }
        if (array_key_exists('name', $fields)) {
            $name = clientes_sanitize_text((string) $fields['name'], 120);
            if ($name === '') {
                throw new InvalidArgumentException('Nome é obrigatório.');
            }
            $item['name'] = $name;
        }
        if (array_key_exists('url', $fields)) {
            $item['url'] = clientes_sanitize_url((string) $fields['url']);
        }
        if (array_key_exists('bg_color', $fields)) {
            $item['bg_color'] = clientes_sanitize_hex((string) $fields['bg_color']);
        }
        if (array_key_exists('sort_order', $fields)) {
            $item['sort_order'] = (int) $fields['sort_order'];
        }
        if ($replaceLogo && $logoMeta !== null) {
            clientes_delete_files(is_array($item['logo'] ?? null) ? $item['logo'] : null);
            $item['logo'] = $logoMeta;
        }
        $item['updated_at'] = gmdate('c');
        $list[$i] = $item;
        $found = $item;
        break;
    }
    if ($found === null) {
        return null;
    }
    clientes_save_all($list);
    return $found;
}

function clientes_delete(string $id): bool
{
    $list = clientes_all();
    $next = [];
    $deleted = false;
    foreach ($list as $item) {
        if (($item['id'] ?? '') === $id) {
            clientes_delete_files(is_array($item['logo'] ?? null) ? $item['logo'] : null);
            $deleted = true;
            continue;
        }
        $next[] = $item;
    }
    if ($deleted) {
        clientes_save_all($next);
    }
    return $deleted;
}

/**
 * Reordena pela lista de IDs.
 *
 * @param list<string> $ids
 */
function clientes_reorder(array $ids): void
{
    $list = clientes_all();
    $map = [];
    foreach ($list as $item) {
        $map[(string) ($item['id'] ?? '')] = $item;
    }
    $ordered = [];
    $order = 0;
    foreach ($ids as $id) {
        $id = (string) $id;
        if (!isset($map[$id])) {
            continue;
        }
        $item = $map[$id];
        $item['sort_order'] = $order++;
        $item['updated_at'] = gmdate('c');
        $ordered[] = $item;
        unset($map[$id]);
    }
    foreach ($map as $item) {
        $item['sort_order'] = $order++;
        $ordered[] = $item;
    }
    clientes_save_all($ordered);
}
