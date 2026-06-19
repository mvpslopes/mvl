<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();
fin_require_auth();

$svc = fin_load_service();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        $lista = $svc->listCategorias($tipo !== '' ? $tipo : null);
        fin_json(['success' => true, 'categorias' => $lista]);
    }

    if ($method === 'POST') {
        $data = fin_json_input();
        $cat = $svc->createCategoria(fin_validate_categoria($data));
        fin_json(['success' => true, 'categoria' => $cat], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = fin_json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            fin_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $current = $svc->getCategoria($id);
        $merged = array_merge($current, fin_validate_categoria($data, false));
        $cat = $svc->updateCategoria($id, $merged);
        fin_json(['success' => true, 'categoria' => $cat]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? fin_json_input()['id'] ?? 0);
        if ($id <= 0) {
            fin_json(['success' => false, 'message' => 'ID obrigatório.'], 400);
        }
        $svc->deleteCategoria($id);
        fin_json(['success' => true]);
    }
} catch (InvalidArgumentException $e) {
    fin_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('financas categorias: ' . $e->getMessage());
    fin_json(['success' => false, 'message' => 'Erro no servidor.'], 500);
}

fin_json(['success' => false, 'message' => 'Método não permitido.'], 405);

function fin_validate_categoria(array $data, bool $requireNome = true): array
{
    $nome = trim((string) ($data['nome'] ?? ''));
    if ($requireNome && $nome === '') {
        throw new InvalidArgumentException('Nome obrigatório.');
    }
    $tipo = $data['tipo'] ?? 'ambos';
    if (!in_array($tipo, ['receita', 'despesa', 'ambos'], true)) {
        throw new InvalidArgumentException('Tipo inválido.');
    }
    $cor = trim((string) ($data['cor'] ?? '#6366f1'));
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
        $cor = '#6366f1';
    }

    return [
        'nome' => $nome,
        'tipo' => $tipo,
        'cor' => $cor,
        'ativa' => array_key_exists('ativa', $data) ? (bool) $data['ativa'] : true,
    ];
}
