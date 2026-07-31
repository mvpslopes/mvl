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
    $rawIds = is_array($data['ideia_ids'] ?? null) ? $data['ideia_ids'] : [];
    $ids = [];
    foreach ($rawIds as $rawId) {
        $id = (int) $rawId;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    $ids = array_values($ids);

    if (count($ids) < 2) {
        ideias_json(['success' => false, 'message' => 'Selecione pelo menos duas ideias.'], 400);
    }
    if (count($ids) > 12) {
        ideias_json(['success' => false, 'message' => 'Selecione no máximo doze ideias.'], 400);
    }

    $svc = ideias_load_service();
    $ideias = [];
    foreach ($ids as $id) {
        $ideias[] = $svc->getNota($id);
    }

    $config = ideias_groq_config();
    require_once __DIR__ . '/lib/GroqInsightService.php';
    $groq = new GroqInsightService($config['api_key'], $config['model']);
    $result = $groq->gerar(
        $ideias,
        mb_substr(trim((string) ($data['pergunta_guia'] ?? '')), 0, 300)
    );

    ideias_json([
        'success' => true,
        'model' => $config['model'],
        ...$result,
    ]);
} catch (InvalidArgumentException $e) {
    ideias_json(['success' => false, 'message' => $e->getMessage()], 400);
} catch (RuntimeException $e) {
    error_log('ideias groq: ' . $e->getMessage());
    ideias_json(['success' => false, 'message' => $e->getMessage()], 502);
} catch (Throwable $e) {
    error_log('ideias insights: ' . $e->getMessage());
    ideias_json(['success' => false, 'message' => 'Erro ao gerar insights.'], 500);
}
