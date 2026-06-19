<?php

declare(strict_types=1);

/**
 * Cria tabelas do módulo de certificados (sem afetar usuários).
 * Acesse uma vez: /api/migrate-certificados.php
 */

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/schema_certificados.php';

sesmt_cors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use GET ou POST.']);
    exit;
}

try {
    $pdo = sesmt_pdo();
    sesmt_ensure_certificados_schema($pdo);
    $vigentes = (int) $pdo->query('SELECT COUNT(*) FROM nr_tipos WHERE ativo = 1')->fetchColumn();
    $total = (int) $pdo->query('SELECT COUNT(*) FROM nr_tipos')->fetchColumn();
    echo json_encode([
        'success' => true,
        'message' => 'Tabelas de certificados criadas. NRs vigentes no seletor.',
        'nr_tipos_vigentes' => $vigentes,
        'nr_tipos_total' => $total,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
