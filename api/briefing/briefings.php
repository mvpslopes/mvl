<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/** @return list<array<string, mixed>> */
function briefings_all(): array
{
    briefing_ensure_dirs();
    $list = briefing_read_json(briefing_json_file());
    return array_values(array_filter($list, 'is_array'));
}

function briefings_save(array $list): void
{
    briefing_ensure_dirs();
    briefing_write_json(briefing_json_file(), array_values($list));
}

function briefings_find(string $id): ?array
{
    foreach (briefings_all() as $item) {
        if (($item['id'] ?? '') === $id) {
            return $item;
        }
    }
    return null;
}

function briefings_update(string $id, array $patch): ?array
{
    $list = briefings_all();
    foreach ($list as $i => $item) {
        if (($item['id'] ?? '') !== $id) {
            continue;
        }
        $merged = array_merge($item, $patch);
        $merged['id'] = $id;
        $list[$i] = $merged;
        briefings_save($list);
        return $merged;
    }
    return null;
}

function briefings_delete(string $id): bool
{
    $list = briefings_all();
    $next = [];
    $deleted = false;
    foreach ($list as $item) {
        if (($item['id'] ?? '') !== $id) {
            $next[] = $item;
            continue;
        }
        $deleted = true;
        foreach (['logo_file', 'brand_file'] as $key) {
            $meta = $item[$key] ?? null;
            if (!is_array($meta)) {
                continue;
            }
            $path = briefing_resolve_upload_path($meta);
            if ($path !== null && is_file($path)) {
                @unlink($path);
            }
        }
    }
    if (!$deleted) {
        return false;
    }
    briefings_save($next);
    return true;
}

function briefings_count_new(): int
{
    $n = 0;
    foreach (briefings_all() as $item) {
        if (($item['status'] ?? '') === 'new') {
            $n++;
        }
    }
    return $n;
}

/**
 * @return array{original: string, stored: string, ext: string, size: int, path: string}|null
 */
function briefing_store_upload(string $field, string $briefingId, string $prefix): ?array
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
    if ($size <= 0 || $size > BRIEFING_MAX_UPLOAD_BYTES) {
        throw new InvalidArgumentException('Arquivo excede o limite de 20 MB.');
    }

    $original = (string) ($file['name'] ?? 'arquivo');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, BRIEFING_ALLOWED_EXT, true)) {
        throw new InvalidArgumentException('Extensão não permitida. Use png, jpg, webp, svg ou pdf.');
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

    $dir = briefing_uploads_dir() . '/' . $briefingId;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $stored = $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $stored;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new InvalidArgumentException('Não foi possível salvar o arquivo.');
    }

    return [
        'original' => briefing_sanitize_text($original, 200),
        'stored' => $stored,
        'ext' => $ext,
        'size' => $size,
        'path' => 'briefings/' . $briefingId . '/' . $stored,
    ];
}

function briefings_whatsapp_url(array $b): string
{
    $phone = preg_replace('/\D+/', '', (string) ($b['phone'] ?? '')) ?? '';
    if (strlen($phone) >= 10 && !str_starts_with($phone, '55')) {
        $phone = '55' . $phone;
    }
    $name = (string) ($b['name'] ?? '');
    $type = (string) ($b['project_type'] ?? '');
    $domain = (string) ($b['domain'] ?? '');
    $goal = (string) ($b['goal'] ?? '');
    $lines = [
        "Olá {$name}, recebemos seu briefing MVL.",
        $type !== '' ? "Projeto: {$type}" : null,
        $goal !== '' ? "Objetivo: {$goal}" : null,
        $domain !== '' ? "Domínio desejado: {$domain}" : null,
        'Vamos alinhar os próximos passos?',
    ];
    $text = implode("\n", array_values(array_filter($lines)));
    if ($phone === '') {
        return 'https://wa.me/?text=' . rawurlencode($text);
    }
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
}

function briefing_resolve_upload_path(array $meta): ?string
{
    $rel = (string) ($meta['path'] ?? '');
    if ($rel === '' || str_contains($rel, '..')) {
        return null;
    }
    $full = briefing_data_dir() . '/' . $rel;
    if (!is_file($full)) {
        return null;
    }
    return $full;
}
