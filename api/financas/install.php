<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();

if (($_GET['key'] ?? '') !== 'install') {
    fin_json(['success' => false, 'message' => 'Acesso negado.'], 403);
}

try {
    $svc = fin_load_service();
    $svc->ensureSchema();
    fin_json(['success' => true, 'message' => 'Tabelas criadas/verificadas.']);
} catch (Throwable $e) {
    fin_json(['success' => false, 'message' => $e->getMessage()], 500);
}
