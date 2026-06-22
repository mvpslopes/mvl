<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();
fin_require_auth();

$svc = fin_load_service();
$method = fin_request_method();

if ($method === 'GET') {
    $de = trim((string) ($_GET['de'] ?? date('Y-m')));
    $meses = max(1, min(24, (int) ($_GET['meses'] ?? 6)));

    if (!preg_match('/^\d{4}-\d{2}$/', $de)) {
        fin_json(['success' => false, 'message' => 'Parâmetro de inválido.'], 400);
    }

    $d = new DateTimeImmutable($de . '-01');
    $ate = $d->modify('+' . ($meses - 1) . ' months')->format('Y-m');

    fin_json(['success' => true, 'projecao' => $svc->projecao($de, $ate)]);
}

fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);
