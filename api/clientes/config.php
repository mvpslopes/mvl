<?php

declare(strict_types=1);

/**
 * Clientes / parceiros do site — JSON + uploads (sem MySQL).
 */

const CLIENTES_MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // 5 MB
const CLIENTES_ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

function clientes_data_dir(): string
{
    return __DIR__ . '/dados';
}

function clientes_json_file(): string
{
    return clientes_data_dir() . '/clientes.json';
}

function clientes_uploads_dir(): string
{
    return clientes_data_dir() . '/logos';
}

function clientes_seed_dir(): string
{
    return __DIR__ . '/seed';
}

function clientes_seed_manifest_file(): string
{
    return clientes_seed_dir() . '/manifest.json';
}
