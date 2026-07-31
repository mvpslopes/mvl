<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

ideias_cors();
ideias_options_exit();
ideias_require_auth();

$svc = ideias_load_service();
$method = ideias_request_method();

try {
    if ($method === 'GET') {
        ideias_json([
            'success' => true,
            'keywords' => $svc->listKeywords(),
        ]);
    }
} catch (Throwable $e) {
    error_log('ideias keywords: ' . $e->getMessage());
    ideias_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

ideias_json(['success' => false, 'message' => 'Método não permitido.'], 405);
