<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

sesmt_cors();
sesmt_options_exit();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = sesmt_pdo();

require_once __DIR__ . '/lib/schema_certificados.php';
sesmt_ensure_certificados_schema($pdo);

if ($method === 'GET' && isset($_GET['logo'])) {
    sesmt_require_auth();
    $row = $pdo->query('SELECT logo_path FROM empresa_config WHERE id = 1')->fetch();
    if (empty($row['logo_path']) || !is_file($row['logo_path'])) {
        http_response_code(404);
        exit;
    }
    $ext = strtolower(pathinfo($row['logo_path'], PATHINFO_EXTENSION));
    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
    header('Content-Type: ' . $mime);
    readfile($row['logo_path']);
    exit;
}

if ($method === 'GET') {
    sesmt_require_auth();
    $row = $pdo->query('SELECT nome_empresa, logo_path FROM empresa_config WHERE id = 1')->fetch();
    $logoUrl = null;
    if (!empty($row['logo_path']) && is_file($row['logo_path'])) {
        $logoUrl = '/api/empresa.php?logo=1';
    }
    echo json_encode([
        'success' => true,
        'empresa' => [
            'nome_empresa' => $row['nome_empresa'] ?? '',
            'logo_url' => $logoUrl,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    sesmt_require_auth();
    $nome = trim((string) ($_POST['nome_empresa'] ?? ''));
    if ($nome === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nome da empresa é obrigatório.']);
        exit;
    }

    $logoPath = null;
    $current = $pdo->query('SELECT logo_path FROM empresa_config WHERE id = 1')->fetch();

    if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Logo deve ser PNG ou JPG.']);
            exit;
        }
        $uploadDir = sesmt_uploads_dir();
        $filename = 'logo-empresa.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $dest = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar logo.']);
            exit;
        }
        $logoPath = $dest;
    } else {
        $logoPath = $current['logo_path'] ?? null;
    }

    $pdo->prepare('
        INSERT INTO empresa_config (id, nome_empresa, logo_path)
        VALUES (1, :nome, :logo)
        ON DUPLICATE KEY UPDATE nome_empresa = VALUES(nome_empresa), logo_path = VALUES(logo_path)
    ')->execute(['nome' => $nome, 'logo' => $logoPath]);

    echo json_encode(['success' => true, 'message' => 'Empresa atualizada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido.']);

function sesmt_uploads_dir(): string
{
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}
