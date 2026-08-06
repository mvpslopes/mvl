<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

ideias_cors();
ideias_options_exit();
ideias_require_auth();

if (ideias_request_method() !== 'POST') {
    ideias_json(['success' => false, 'message' => 'Método não permitido.'], 405);
}

try {
    $data = ideias_json_input();
    $texto = mb_substr(trim((string) ($data['texto'] ?? '')), 0, 500);
    if ($texto === '') {
        ideias_json(['success' => false, 'message' => 'Informe o texto da ideia.'], 400);
    }

    $existentes = [];
    $rawExistentes = is_array($data['existentes'] ?? null) ? $data['existentes'] : [];
    foreach ($rawExistentes as $nome) {
        $clean = mb_substr(trim((string) $nome), 0, 80);
        if ($clean !== '') {
            $existentes[] = $clean;
        }
    }

    // Se o frontend não mandar catálogo, usa o do banco.
    if ($existentes === []) {
        $svc = ideias_load_service();
        foreach ($svc->listKeywords() as $kw) {
            $nome = trim((string) ($kw['nome'] ?? ''));
            if ($nome !== '') {
                $existentes[] = $nome;
            }
        }
    }

    $config = ideias_groq_config();
    require_once __DIR__ . '/lib/GroqInsightService.php';
    $groq = new GroqInsightService($config['api_key'], $config['model']);
    $tags = $groq->sugerirTags($texto, $existentes);

    ideias_json([
        'success' => true,
        'tags' => $tags,
        'model' => $config['model'],
    ]);
} catch (InvalidArgumentException $e) {
    ideias_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (RuntimeException $e) {
    error_log('ideias sugerir-tags groq: ' . $e->getMessage());
    ideias_json(['success' => false, 'message' => $e->getMessage()], 502);
} catch (Throwable $e) {
    error_log('ideias sugerir-tags: ' . $e->getMessage());
    ideias_json(['success' => false, 'message' => 'Erro ao sugerir tags.'], 500);
}
