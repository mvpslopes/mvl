<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

proj_cors();
proj_options_exit();
proj_require_auth();

$svc = proj_load_service();

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        proj_json(['success' => true, 'tipos' => $svc->listTipos()]);
    }
} catch (Throwable $e) {
    error_log('projetos tipos: ' . $e->getMessage());
    proj_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

proj_json(['success' => false, 'message' => 'Método não permitido.'], 405);
