<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

sesmt_cors();
sesmt_options_exit();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

sesmt_require_auth();

try {
    $pdo = sesmt_pdo();
    require_once __DIR__ . '/lib/schema_certificados.php';
    sesmt_ensure_certificados_schema($pdo);

    $stmt = $pdo->query('SELECT id, codigo, nome FROM nr_tipos WHERE ativo = 1 ORDER BY codigo');
    $tipos = $stmt->fetchAll();

    echo json_encode(['success' => true, 'tipos' => $tipos], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('SESMT nr-tipos: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar tipos de NR. Execute install.php ou database/certificados.sql no banco.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('SESMT nr-tipos: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor.'], JSON_UNESCAPED_UNICODE);
}
