<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

sesmt_cors();
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = sesmt_pdo();
    $pdo->query('SELECT 1');
    echo json_encode([
        'success' => true,
        'message' => 'API e banco de dados OK.',
        'config' => true,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Falha na conexão: ' . $e->getMessage(),
        'config' => is_file(__DIR__ . '/config.local.php'),
    ], JSON_UNESCAPED_UNICODE);
}
