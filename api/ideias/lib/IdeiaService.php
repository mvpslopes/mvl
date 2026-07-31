<?php

declare(strict_types=1);

class IdeiaService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        $schema = dirname(__DIR__) . '/database/schema.sql';
        if (!is_file($schema)) {
            return;
        }
        $sql = file_get_contents($schema);
        if ($sql === false) {
            return;
        }
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt === '') {
                continue;
            }
            $clean = trim(preg_replace('/^--.*$/m', '', $stmt) ?? $stmt);
            if ($clean !== '') {
                $this->pdo->exec($clean);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listNotas(?string $q = null, ?string $mes = null, ?string $keyword = null, ?string $status = null): array
    {
        $this->ensureSchema();

        $where = ['1=1'];
        $params = [];

        if ($q !== null && $q !== '') {
            $where[] = 'i.texto LIKE :q';
            $params['q'] = '%' . $q . '%';
        }
        if ($mes !== null && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $where[] = "DATE_FORMAT(i.created_at, '%Y-%m') = :mes";
            $params['mes'] = $mes;
        }
        if ($status !== null && in_array($status, ['raw', 'usado'], true)) {
            $where[] = 'i.status = :status';
            $params['status'] = $status;
        }
        if ($keyword !== null && $keyword !== '') {
            $where[] = 'EXISTS (
                SELECT 1 FROM ideias_ideia_keyword iik
                INNER JOIN ideias_keywords k ON k.id = iik.keyword_id
                WHERE iik.ideia_id = i.id AND (k.slug = :kw OR k.nome = :kw2)
            )';
            $params['kw'] = ideias_slugify($keyword);
            $params['kw2'] = $keyword;
        }

        $sql = '
            SELECT i.*
            FROM ideias i
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY i.created_at DESC, i.id DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $kwMap = $this->keywordsForIdeias($ids);

        return array_map(function ($r) use ($kwMap) {
            $id = (int) $r['id'];
            return [
                'id' => $id,
                'texto' => (string) $r['texto'],
                'fonte' => $r['fonte'] !== null ? (string) $r['fonte'] : null,
                'favorito' => (bool) (int) $r['favorito'],
                'status' => (string) $r['status'],
                'created_at' => (string) $r['created_at'],
                'updated_at' => $r['updated_at'] !== null ? (string) $r['updated_at'] : null,
                'keywords' => $kwMap[$id] ?? [],
            ];
        }, $rows);
    }

    /**
     * Agrupa notas por palavra-chave (ordem alfabética).
     *
     * @return list<array{keyword: array{id:int,nome:string,slug:string}, ideias: list<array<string,mixed>>}>
     */
    public function groupByKeyword(?string $mes = null, ?string $q = null): array
    {
        $this->ensureSchema();

        $where = ['1=1'];
        $params = [];
        if ($mes !== null && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $where[] = "DATE_FORMAT(i.created_at, '%Y-%m') = :mes";
            $params['mes'] = $mes;
        }
        if ($q !== null && $q !== '') {
            $where[] = 'i.texto LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        $sql = '
            SELECT
                k.id AS keyword_id,
                k.nome AS keyword_nome,
                k.slug AS keyword_slug,
                i.id AS ideia_id,
                i.texto,
                i.fonte,
                i.favorito,
                i.status,
                i.created_at,
                i.updated_at
            FROM ideias_keywords k
            INNER JOIN ideias_ideia_keyword iik ON iik.keyword_id = k.id
            INNER JOIN ideias i ON i.id = iik.ideia_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY k.nome ASC, i.created_at DESC, i.id DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $groups = [];
        $ideiaIds = [];
        foreach ($rows as $row) {
            $kid = (int) $row['keyword_id'];
            if (!isset($groups[$kid])) {
                $groups[$kid] = [
                    'keyword' => [
                        'id' => $kid,
                        'nome' => (string) $row['keyword_nome'],
                        'slug' => (string) $row['keyword_slug'],
                    ],
                    'ideias' => [],
                ];
            }
            $ideiaIds[] = (int) $row['ideia_id'];
            $groups[$kid]['ideias'][] = [
                'id' => (int) $row['ideia_id'],
                'texto' => (string) $row['texto'],
                'fonte' => $row['fonte'] !== null ? (string) $row['fonte'] : null,
                'favorito' => (bool) (int) $row['favorito'],
                'status' => (string) $row['status'],
                'created_at' => (string) $row['created_at'],
                'updated_at' => $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
                'keywords' => [], // preenchido abaixo
            ];
        }

        $ideiaIds = array_values(array_unique($ideiaIds));
        $kwMap = $this->keywordsForIdeias($ideiaIds);
        foreach ($groups as &$g) {
            foreach ($g['ideias'] as &$ideia) {
                $ideia['keywords'] = $kwMap[$ideia['id']] ?? [];
            }
            unset($ideia);
        }
        unset($g);

        return array_values($groups);
    }

    /**
     * @return list<array{id:int,nome:string,slug:string,count:int}>
     */
    public function listKeywords(): array
    {
        $this->ensureSchema();
        $rows = $this->pdo->query('
            SELECT k.id, k.nome, k.slug, COUNT(iik.ideia_id) AS count
            FROM ideias_keywords k
            LEFT JOIN ideias_ideia_keyword iik ON iik.keyword_id = k.id
            GROUP BY k.id, k.nome, k.slug
            ORDER BY k.nome ASC
        ')->fetchAll();

        return array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'nome' => (string) $r['nome'],
            'slug' => (string) $r['slug'],
            'count' => (int) $r['count'],
        ], $rows);
    }

    public function getNota(int $id): array
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('SELECT * FROM ideias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Ideia não encontrada.');
        }
        return $this->mapNota($row);
    }

    public function createNota(array $data): array
    {
        $this->ensureSchema();
        $texto = trim((string) ($data['texto'] ?? ''));
        if ($texto === '') {
            throw new InvalidArgumentException('Texto obrigatório.');
        }
        if (mb_strlen($texto) > 500) {
            throw new InvalidArgumentException('Texto máximo de 500 caracteres.');
        }

        $fonte = trim((string) ($data['fonte'] ?? ''));
        $fonte = $fonte !== '' ? mb_substr($fonte, 0, 120) : null;
        $favorito = !empty($data['favorito']) ? 1 : 0;
        $status = (($data['status'] ?? 'raw') === 'usado') ? 'usado' : 'raw';
        $keywords = $this->normalizeKeywords($data['keywords'] ?? []);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO ideias (texto, fonte, favorito, status)
                VALUES (:texto, :fonte, :favorito, :status)
            ');
            $stmt->execute([
                'texto' => $texto,
                'fonte' => $fonte,
                'favorito' => $favorito,
                'status' => $status,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->syncKeywords($id, $keywords);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->getNota($id);
    }

    public function updateNota(int $id, array $data): array
    {
        $this->ensureSchema();
        $current = $this->getNota($id);

        $fields = [];
        $params = ['id' => $id];

        if (array_key_exists('texto', $data)) {
            $texto = trim((string) $data['texto']);
            if ($texto === '') {
                throw new InvalidArgumentException('Texto obrigatório.');
            }
            if (mb_strlen($texto) > 500) {
                throw new InvalidArgumentException('Texto máximo de 500 caracteres.');
            }
            $fields[] = 'texto = :texto';
            $params['texto'] = $texto;
        }
        if (array_key_exists('fonte', $data)) {
            $fonte = trim((string) ($data['fonte'] ?? ''));
            $fields[] = 'fonte = :fonte';
            $params['fonte'] = $fonte !== '' ? mb_substr($fonte, 0, 120) : null;
        }
        if (array_key_exists('favorito', $data)) {
            $fields[] = 'favorito = :favorito';
            $params['favorito'] = !empty($data['favorito']) ? 1 : 0;
        }
        if (array_key_exists('status', $data)) {
            $fields[] = 'status = :status';
            $params['status'] = ($data['status'] === 'usado') ? 'usado' : 'raw';
        }

        $this->pdo->beginTransaction();
        try {
            if ($fields !== []) {
                $sql = 'UPDATE ideias SET ' . implode(', ', $fields) . ' WHERE id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }
            if (array_key_exists('keywords', $data)) {
                $this->syncKeywords($id, $this->normalizeKeywords($data['keywords']));
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        unset($current);
        return $this->getNota($id);
    }

    public function deleteNota(int $id): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('DELETE FROM ideias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Ideia não encontrada.');
        }
        $this->pruneOrphanKeywords();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapNota(array $row): array
    {
        $id = (int) $row['id'];
        $kwMap = $this->keywordsForIdeias([$id]);
        return [
            'id' => $id,
            'texto' => (string) $row['texto'],
            'fonte' => $row['fonte'] !== null ? (string) $row['fonte'] : null,
            'favorito' => (bool) (int) $row['favorito'],
            'status' => (string) $row['status'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
            'keywords' => $kwMap[$id] ?? [],
        ];
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<array{id:int,nome:string,slug:string}>>
     */
    private function keywordsForIdeias(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("
            SELECT iik.ideia_id, k.id, k.nome, k.slug
            FROM ideias_ideia_keyword iik
            INNER JOIN ideias_keywords k ON k.id = iik.keyword_id
            WHERE iik.ideia_id IN ($placeholders)
            ORDER BY k.nome ASC
        ");
        $stmt->execute(array_values($ids));
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $iid = (int) $r['ideia_id'];
            $map[$iid][] = [
                'id' => (int) $r['id'],
                'nome' => (string) $r['nome'],
                'slug' => (string) $r['slug'],
            ];
        }
        return $map;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function normalizeKeywords(mixed $raw): array
    {
        if (!is_array($raw)) {
            if (is_string($raw) && trim($raw) !== '') {
                $raw = preg_split('/[,;]+/', $raw) ?: [];
            } else {
                return [];
            }
        }
        $out = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $nome = trim((string) ($item['nome'] ?? $item['name'] ?? ''));
            } else {
                $nome = trim((string) $item);
            }
            $nome = preg_replace('/\s+/', ' ', $nome) ?? $nome;
            if ($nome === '') {
                continue;
            }
            $nome = mb_substr($nome, 0, 80);
            $key = mb_strtolower($nome, 'UTF-8');
            $out[$key] = $nome;
        }
        return array_values($out);
    }

    /**
     * @param list<string> $keywords
     */
    private function syncKeywords(int $ideiaId, array $keywords): void
    {
        $del = $this->pdo->prepare('DELETE FROM ideias_ideia_keyword WHERE ideia_id = :id');
        $del->execute(['id' => $ideiaId]);

        if ($keywords === []) {
            $this->pruneOrphanKeywords();
            return;
        }

        $find = $this->pdo->prepare('SELECT id FROM ideias_keywords WHERE slug = :slug LIMIT 1');
        $insertKw = $this->pdo->prepare('INSERT INTO ideias_keywords (nome, slug) VALUES (:nome, :slug)');
        $link = $this->pdo->prepare('
            INSERT IGNORE INTO ideias_ideia_keyword (ideia_id, keyword_id)
            VALUES (:ideia_id, :keyword_id)
        ');

        foreach ($keywords as $nome) {
            $slug = ideias_slugify($nome);
            $find->execute(['slug' => $slug]);
            $existing = $find->fetch();
            if ($existing) {
                $kid = (int) $existing['id'];
            } else {
                $insertKw->execute(['nome' => $nome, 'slug' => $slug]);
                $kid = (int) $this->pdo->lastInsertId();
            }
            $link->execute(['ideia_id' => $ideiaId, 'keyword_id' => $kid]);
        }
        $this->pruneOrphanKeywords();
    }

    private function pruneOrphanKeywords(): void
    {
        $this->pdo->exec('
            DELETE k FROM ideias_keywords k
            LEFT JOIN ideias_ideia_keyword iik ON iik.keyword_id = k.id
            WHERE iik.keyword_id IS NULL
        ');
    }
}
