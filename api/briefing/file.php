<?php

declare(strict_types=1);

require_once __DIR__ . '/briefings.php';

briefing_options_exit();
briefing_require_auth();

$id = trim((string) ($_GET['id'] ?? ''));
$which = trim((string) ($_GET['file'] ?? 'logo')); // logo | brand

if ($id === '') {
    briefing_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
}

$item = briefings_find($id);
if (!$item) {
    briefing_json(['success' => false, 'message' => 'Briefing não encontrado.'], 404);
}

$key = $which === 'brand' ? 'brand_file' : 'logo_file';
$meta = $item[$key] ?? null;
if (!is_array($meta)) {
    briefing_json(['success' => false, 'message' => 'Arquivo não encontrado.'], 404);
}

$path = briefing_resolve_upload_path($meta);
if ($path === null) {
    briefing_json(['success' => false, 'message' => 'Arquivo ausente no disco.'], 404);
}

$original = (string) ($meta['original'] ?? basename($path));
$ext = strtolower((string) ($meta['ext'] ?? pathinfo($path, PATHINFO_EXTENSION)));
$mimes = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'pdf' => 'application/pdf',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . rawurlencode($original) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
