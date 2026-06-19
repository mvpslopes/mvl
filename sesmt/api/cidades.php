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

$q = trim((string) ($_GET['q'] ?? ''));
$limit = min(30, max(5, (int) ($_GET['limit'] ?? 20)));

$nomes = sesmt_carregar_cidades_mg();

if ($q !== '') {
    $qLower = mb_strtolower($q, 'UTF-8');
    $nomes = array_values(array_filter($nomes, function ($nome) use ($qLower) {
        return str_contains(mb_strtolower($nome, 'UTF-8'), $qLower);
    }));
}

$nomes = array_slice($nomes, 0, $limit);

echo json_encode([
    'success' => true,
    'cidades' => $nomes,
], JSON_UNESCAPED_UNICODE);

function sesmt_carregar_cidades_mg(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cacheFile = __DIR__ . '/data/cidades-mg-nomes.json';
    if (is_file($cacheFile)) {
        $decoded = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($decoded)) {
            $cache = $decoded;
            return $cache;
        }
    }

    $ibgeFile = __DIR__ . '/data/cidades-mg-ibge.json';
    if (is_file($ibgeFile)) {
        $raw = json_decode((string) file_get_contents($ibgeFile), true);
        if (is_array($raw)) {
            $nomes = array_map(fn ($m) => $m['nome'] ?? '', $raw);
            $nomes = array_values(array_filter($nomes));
            sort($nomes, SORT_LOCALE_STRING);
            if (!is_dir(__DIR__ . '/data')) {
                mkdir(__DIR__ . '/data', 0755, true);
            }
            file_put_contents($cacheFile, json_encode($nomes, JSON_UNESCAPED_UNICODE));
            $cache = $nomes;
            return $cache;
        }
    }

    $cache = [
        'Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim',
        'Montes Claros', 'Ribeirão das Neves', 'Uberaba', 'Governador Valadares',
        'Ipatinga', 'Sete Lagoas', 'Divinópolis', 'Santa Luzia', 'Poços de Caldas',
        'Patos de Minas', 'Teófilo Otoni', 'Barbacena', 'Varginha', 'Araguari',
    ];
    return $cache;
}
