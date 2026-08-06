<?php

declare(strict_types=1);

final class GroqInsightService
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private string $apiKey,
        private string $model
    ) {
    }

    /**
     * @param list<array<string, mixed>> $ideias
     * @return array{insights: list<array{titulo: string, texto: string, por_que: string, tags: list<string>}>, pergunta: string}
     */
    public function gerar(array $ideias, string $perguntaGuia = ''): array
    {
        if (count($ideias) < 2) {
            throw new InvalidArgumentException('Selecione pelo menos duas ideias para combinar.');
        }

        $inputs = array_map(static function (array $ideia): array {
            $keywords = array_map(
                static fn (array $keyword): string => (string) ($keyword['nome'] ?? ''),
                is_array($ideia['keywords'] ?? null) ? $ideia['keywords'] : []
            );
            return [
                'texto' => mb_substr(trim((string) ($ideia['texto'] ?? '')), 0, 500),
                'palavras_chave' => array_values(array_filter($keywords)),
            ];
        }, array_slice($ideias, 0, 12));

        $system = <<<'PROMPT'
Você é um facilitador profissional de criatividade e inovação.
Seu trabalho é combinar observações reais sem inventar fatos, buscando conexões não óbvias, paradoxos, oportunidades e aplicações práticas.
Escreva em português do Brasil, de forma clara, concisa e sem clichês.

Retorne SOMENTE JSON válido neste formato:
{
  "insights": [
    {
      "titulo": "título curto",
      "texto": "insight acionável em até 500 caracteres",
      "por_que": "explicação objetiva da conexão",
      "tags": ["tag1", "tag2"]
    }
  ],
  "pergunta": "uma pergunta provocadora para aprofundar"
}

Gere exatamente 3 insights distintos:
1. uma conexão conceitual;
2. uma aplicação prática (produto, serviço, conteúdo ou processo);
3. uma provocação ou paradoxo.
Não use emojis. Não repita literalmente os inputs como se fossem um insight.
PROMPT;

        $userPayload = [
            'pergunta_guia' => $perguntaGuia !== '' ? mb_substr($perguntaGuia, 0, 300) : null,
            'inputs' => $inputs,
        ];

        $result = $this->chatJson(
            $system,
            'Combine estes inputs: ' . json_encode(
                $userPayload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            0.85,
            1800
        );

        return $this->sanitizeResult($result);
    }

    /**
     * @param list<string> $existentes
     * @return list<string>
     */
    public function sugerirTags(string $texto, array $existentes = []): array
    {
        $texto = trim($texto);
        if ($texto === '') {
            throw new InvalidArgumentException('Informe o texto da ideia para sugerir tags.');
        }

        $catalogo = [];
        foreach (array_slice($existentes, 0, 200) as $nome) {
            $clean = mb_substr(trim(strip_tags((string) $nome)), 0, 80);
            if ($clean === '') {
                continue;
            }
            $catalogo[mb_strtolower($clean, 'UTF-8')] = $clean;
        }
        $catalogo = array_values($catalogo);

        $system = <<<'PROMPT'
Você classifica observações curtas com palavras-chave úteis para agrupar ideias depois.
Escreva em português do Brasil. Não use emojis.

Regras:
- Gere entre 3 e 6 tags.
- Prefira reutilizar tags do catálogo existente quando fizer sentido (copie a grafia exata).
- Só invente tags novas se nenhuma existente cobrir bem o tema.
- Tags curtas: 1 a 3 palavras, concretas, sem hashtag (#).
- Evite genéricas demais como "ideia", "pensamento", "observação".

Retorne SOMENTE JSON válido:
{"tags":["tag1","tag2","tag3"]}
PROMPT;

        $userPayload = [
            'texto' => mb_substr($texto, 0, 500),
            'catalogo_existente' => $catalogo,
        ];

        $result = $this->chatJson(
            $system,
            'Sugira tags para esta observação: ' . json_encode(
                $userPayload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            0.35,
            400
        );

        return $this->sanitizeTags(is_array($result['tags'] ?? null) ? $result['tags'] : [], $catalogo);
    }

    /**
     * @return array<string, mixed>
     */
    private function chatJson(
        string $system,
        string $user,
        float $temperature,
        int $maxTokens
    ): array {
        if ($this->apiKey === '') {
            throw new RuntimeException('Groq não configurado no servidor.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extensão cURL não está disponível no servidor.');
        }

        $body = json_encode([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => $temperature,
            'max_completion_tokens' => $maxTokens,
            'response_format' => ['type' => 'json_object'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new RuntimeException('Não foi possível preparar a solicitação à IA.');
        }

        $curl = curl_init(self::ENDPOINT);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);

        $raw = curl_exec($curl);
        $curlError = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($raw === false) {
            throw new RuntimeException('Falha ao conectar ao Groq: ' . $curlError);
        }

        $response = json_decode((string) $raw, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($response)
                ? (string) ($response['error']['message'] ?? 'Erro retornado pelo Groq.')
                : 'Resposta inválida do Groq.';
            if ($status === 401) {
                $message = 'Chave Groq inválida ou revogada.';
            } elseif ($status === 429) {
                $message = 'Limite de uso do Groq atingido. Tente novamente em instantes.';
            }
            throw new RuntimeException($message);
        }

        $content = is_array($response)
            ? trim((string) ($response['choices'][0]['message']['content'] ?? ''))
            : '';
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        $result = json_decode($content, true);
        if (!is_array($result)) {
            throw new RuntimeException('A IA retornou um conteúdo que não pôde ser interpretado.');
        }

        return $result;
    }

    /**
     * @param list<mixed> $rawTags
     * @param list<string> $catalogo
     * @return list<string>
     */
    private function sanitizeTags(array $rawTags, array $catalogo): array
    {
        $byLower = [];
        foreach ($catalogo as $nome) {
            $byLower[mb_strtolower($nome, 'UTF-8')] = $nome;
        }

        $tags = [];
        foreach (array_slice($rawTags, 0, 8) as $tag) {
            $clean = mb_substr(trim(strip_tags((string) $tag)), 0, 80);
            $clean = ltrim($clean, "# \t");
            if ($clean === '') {
                continue;
            }
            $key = mb_strtolower($clean, 'UTF-8');
            if (isset($byLower[$key])) {
                $clean = $byLower[$key];
            }
            $tags[$key] = $clean;
        }

        $tags = array_values($tags);
        if ($tags === []) {
            throw new RuntimeException('A IA não gerou tags válidas.');
        }

        return $tags;
    }

    /**
     * @param array<string, mixed> $result
     * @return array{insights: list<array{titulo: string, texto: string, por_que: string, tags: list<string>}>, pergunta: string}
     */
    private function sanitizeResult(array $result): array
    {
        $insights = [];
        $rawInsights = is_array($result['insights'] ?? null) ? $result['insights'] : [];
        foreach (array_slice($rawInsights, 0, 3) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $texto = trim(strip_tags((string) ($item['texto'] ?? '')));
            if ($texto === '') {
                continue;
            }
            $tags = [];
            foreach ((is_array($item['tags'] ?? null) ? $item['tags'] : []) as $tag) {
                $clean = mb_substr(trim(strip_tags((string) $tag)), 0, 80);
                if ($clean !== '') {
                    $tags[mb_strtolower($clean, 'UTF-8')] = $clean;
                }
            }
            $insights[] = [
                'titulo' => mb_substr(trim(strip_tags((string) ($item['titulo'] ?? 'Insight'))), 0, 100),
                'texto' => mb_substr($texto, 0, 500),
                'por_que' => mb_substr(trim(strip_tags((string) ($item['por_que'] ?? ''))), 0, 500),
                'tags' => array_values($tags),
            ];
        }

        if ($insights === []) {
            throw new RuntimeException('A IA não gerou insights válidos.');
        }

        return [
            'insights' => $insights,
            'pergunta' => mb_substr(
                trim(strip_tags((string) ($result['pergunta'] ?? 'Que conexão você exploraria primeiro?'))),
                0,
                300
            ),
        ];
    }
}
