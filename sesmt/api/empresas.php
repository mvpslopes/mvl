<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/schema_certificados.php';
require_once __DIR__ . '/lib/EmpresaLogo.php';

sesmt_cors();
sesmt_options_exit();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = sesmt_pdo();
sesmt_ensure_certificados_schema($pdo);

if ($method === 'GET' && isset($_GET['logo'], $_GET['id'])) {
    sesmt_require_auth();
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare('SELECT logo_path FROM empresas WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (empty($row['logo_path'])) {
        http_response_code(404);
        exit;
    }
    sesmt_serve_logo_file($row['logo_path']);
}

if ($method === 'GET' && isset($_GET['id'])) {
    sesmt_require_auth();
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare('SELECT id, nome, logo_path, created_at, updated_at FROM empresas WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Empresa não encontrada.']);
        exit;
    }
    echo json_encode(['success' => true, 'empresa' => sesmt_format_empresa($row)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET') {
    sesmt_require_auth();
    $rows = $pdo->query('SELECT id, nome, logo_path, created_at, updated_at FROM empresas ORDER BY nome ASC')->fetchAll();
    $lista = array_map('sesmt_format_empresa', $rows);
    echo json_encode(['success' => true, 'empresas' => $lista], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    sesmt_require_auth();
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim((string) ($_POST['nome'] ?? ''));

    if ($nome === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nome da empresa é obrigatório.']);
        exit;
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT logo_path FROM empresas WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetch();
            if (!$current) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Empresa não encontrada.']);
                exit;
            }

            $logoPath = $current['logo_path'];
            if (!empty($_FILES['logo'])) {
                $uploaded = sesmt_process_logo_upload($_FILES['logo']);
                if ($uploaded) {
                    sesmt_delete_file_if_exists($logoPath);
                    $logoPath = $uploaded['path'];
                }
            }

            $pdo->prepare('UPDATE empresas SET nome = :nome, logo_path = :logo WHERE id = :id')
                ->execute(['nome' => $nome, 'logo' => $logoPath, 'id' => $id]);

            $stmt = $pdo->prepare('SELECT id, nome, logo_path, created_at, updated_at FROM empresas WHERE id = :id');
            $stmt->execute(['id' => $id]);
            echo json_encode([
                'success' => true,
                'message' => 'Empresa atualizada.',
                'empresa' => sesmt_format_empresa($stmt->fetch()),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $logoPath = null;
        if (!empty($_FILES['logo'])) {
            $uploaded = sesmt_process_logo_upload($_FILES['logo']);
            $logoPath = $uploaded['path'] ?? null;
        }

        $pdo->prepare('INSERT INTO empresas (nome, logo_path) VALUES (:nome, :logo)')
            ->execute(['nome' => $nome, 'logo' => $logoPath]);

        $newId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, nome, logo_path, created_at, updated_at FROM empresas WHERE id = :id');
        $stmt->execute(['id' => $newId]);
        echo json_encode([
            'success' => true,
            'message' => 'Empresa cadastrada.',
            'empresa' => sesmt_format_empresa($stmt->fetch()),
        ], JSON_UNESCAPED_UNICODE);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    sesmt_require_auth();
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT logo_path FROM empresas WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Empresa não encontrada.']);
        exit;
    }
    $pdo->prepare('DELETE FROM empresas WHERE id = :id')->execute(['id' => $id]);
    sesmt_delete_file_if_exists($row['logo_path'] ?? null);
    echo json_encode(['success' => true, 'message' => 'Empresa excluída.'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido.']);

/** @param array<string, mixed>|false $row */
function sesmt_format_empresa(array|false $row): array
{
    if (!$row) {
        return [];
    }
    $hasLogo = !empty($row['logo_path']) && is_file($row['logo_path']);
    return [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'logo_url' => $hasLogo ? '/api/empresas.php?id=' . (int) $row['id'] . '&logo=1' : null,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}
