<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

ideias_cors();
ideias_options_exit();

if (($_GET['key'] ?? '') !== 'install') {
    ideias_json(['success' => false, 'message' => 'Acesso negado.'], 403);
}

try {
    $svc = ideias_load_service();
    $svc->ensureSchema();
    ideias_json(['success' => true, 'message' => 'Tabelas de ideias criadas/verificadas.']);
} catch (Throwable $e) {
    ideias_json(['success' => false, 'message' => $e->getMessage()], 500);
}
