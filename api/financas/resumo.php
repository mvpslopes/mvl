<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();
fin_require_auth();

$svc = fin_load_service();
$method = fin_request_method();

if ($method === 'GET') {
    $ano = (int) ($_GET['ano'] ?? date('Y'));
    fin_json(['success' => true, 'resumo' => $svc->resumoAnoDashboard($ano)]);
}

fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);
