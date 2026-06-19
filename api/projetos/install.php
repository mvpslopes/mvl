<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

proj_cors();
proj_options_exit();

if (($_GET['key'] ?? '') !== 'install') {
    proj_json(['success' => false, 'message' => 'Acesso negado.'], 403);
}

try {
    $svc = proj_load_service();
    $svc->ensureSchema();
    proj_json(['success' => true, 'message' => 'Tabelas criadas/verificadas.']);
} catch (Throwable $e) {
    proj_json(['success' => false, 'message' => $e->getMessage()], 500);
}
