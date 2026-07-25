<?php

declare(strict_types=1);

/**
 * Briefing MVL — config (sem banco: JSON + arquivos).
 */

const BRIEFING_MAX_UPLOAD_BYTES = 20 * 1024 * 1024; // 20 MB
const BRIEFING_ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'pdf'];
const BRIEFING_RATE_LIMIT_MAX = 8;
const BRIEFING_RATE_LIMIT_WINDOW = 3600; // 1 hora

function briefing_data_dir(): string
{
    return __DIR__ . '/dados';
}

function briefing_json_file(): string
{
    return briefing_data_dir() . '/briefings.json';
}

function briefing_uploads_dir(): string
{
    return briefing_data_dir() . '/briefings';
}

function briefing_rate_file(): string
{
    return briefing_data_dir() . '/rate_limit.json';
}
