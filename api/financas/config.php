<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();
fin_require_auth();

$svc = fin_load_service();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    fin_json(['success' => true, 'config' => $svc->getConfig()]);
}

if ($method === 'PUT') {
    $data = fin_json_input();
    $saldo = isset($data['saldo_referencia']) ? (float) $data['saldo_referencia'] : 0;
    $dataRef = trim((string) ($data['data_referencia'] ?? date('Y-m-d')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataRef)) {
        fin_json(['success' => false, 'message' => 'Data de referência inválida.'], 400);
    }
    $config = $svc->updateConfig($saldo, $dataRef);
    fin_json(['success' => true, 'config' => $config]);
}

fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);
