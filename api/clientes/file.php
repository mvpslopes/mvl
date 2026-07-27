<?php

declare(strict_types=1);

require_once __DIR__ . '/clientes.php';

clientes_options_exit();

$id = trim((string) ($_GET['id'] ?? ''));
if ($id === '') {
    clientes_cors();
    clientes_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
}

$item = clientes_find($id);
if (!$item) {
    clientes_cors();
    clientes_json(['success' => false, 'message' => 'Cliente não encontrado.'], 404);
}

$meta = $item['logo'] ?? null;
if (!is_array($meta)) {
    clientes_cors();
    clientes_json(['success' => false, 'message' => 'Logo não encontrada.'], 404);
}

$path = clientes_resolve_upload_path($meta);
if ($path === null) {
    clientes_cors();
    clientes_json(['success' => false, 'message' => 'Arquivo ausente no disco.'], 404);
}

$ext = strtolower((string) ($meta['ext'] ?? pathinfo($path, PATHINFO_EXTENSION)));
$mimes = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Access-Control-Allow-Origin: *');
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
