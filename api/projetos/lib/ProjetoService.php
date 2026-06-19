<?php

declare(strict_types=1);

class ProjetoService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        $schema = dirname(__DIR__) . '/database/schema.sql';
        if (is_file($schema)) {
            $sql = file_get_contents($schema);
            if ($sql !== false) {
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if ($stmt !== '') {
                        $this->pdo->exec($stmt);
                    }
                }
            }
        }

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM proj_tipos')->fetchColumn();
        if ($count === 0) {
            $seed = dirname(__DIR__) . '/database/seed-tipos.sql';
            if (is_file($seed)) {
                $this->pdo->exec(file_get_contents($seed));
            }
        }
    }

    public function listTipos(): array
    {
        $this->ensureSchema();
        $rows = $this->pdo->query('SELECT * FROM proj_tipos ORDER BY ordem, nome')->fetchAll();
        return array_map(fn ($r) => $this->mapTipo($r), $rows);
    }

    public function semanaDemandas(string $isoWeek, ?string $statusFiltro = null): array
    {
        $this->ensureSchema();
        $range = proj_parse_iso_week($isoWeek);
        $demandas = $this->fetchDemandas(
            'd.data_prevista >= :de AND d.data_prevista <= :ate',
            ['de' => $range['de'], 'ate' => $range['ate']],
            $statusFiltro
        );

        return [
            'semana' => $range,
            'demandas' => $demandas,
            'por_dia' => $this->agruparPorDia($demandas, $range['de'], $range['ate']),
        ];
    }

    public function backlog(?string $statusFiltro = null): array
    {
        $this->ensureSchema();
        return $this->fetchDemandas('d.data_prevista IS NULL', [], $statusFiltro);
    }

    public function getDemanda(int $id): array
    {
        $this->ensureSchema();
        $row = $this->fetchDemandaRow($id);
        if (!$row) {
            throw new InvalidArgumentException('Demanda não encontrada.');
        }
        return $this->mapDemanda($row, true);
    }

    public function createDemanda(array $data): array
    {
        $this->ensureSchema();
        $this->assertTipo((int) $data['tipo_id']);

        $stmt = $this->pdo->prepare('
            INSERT INTO proj_demandas (titulo, descricao, tipo_id, data_prevista, status, prioridade)
            VALUES (:titulo, :descricao, :tipo_id, :data_prevista, :status, :prioridade)
        ');
        $stmt->execute([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'tipo_id' => (int) $data['tipo_id'],
            'data_prevista' => $data['data_prevista'] ?? null,
            'status' => $data['status'] ?? 'pendente',
            'prioridade' => $data['prioridade'] ?? 'media',
        ]);

        $id = (int) $this->pdo->lastInsertId();
        if (!empty($data['checklist']) && is_array($data['checklist'])) {
            $this->replaceChecklist($id, $data['checklist']);
        }

        return $this->getDemanda($id);
    }

    public function updateDemanda(int $id, array $data): array
    {
        $this->ensureSchema();
        $current = $this->getDemanda($id);

        $fields = [];
        $params = ['id' => $id];

        foreach (['titulo', 'descricao', 'tipo_id', 'data_prevista', 'status', 'prioridade'] as $f) {
            if (array_key_exists($f, $data)) {
                if ($f === 'tipo_id') {
                    $this->assertTipo((int) $data['tipo_id']);
                }
                if ($f === 'data_prevista' && $data['data_prevista'] === '') {
                    $data['data_prevista'] = null;
                }
                $fields[] = "{$f} = :{$f}";
                $params[$f] = $data[$f];
            }
        }

        if (isset($data['status'])) {
            if ($data['status'] === 'concluida') {
                $fields[] = 'concluida_em = NOW()';
            } elseif ($data['status'] !== 'concluida' && ($current['status'] ?? '') === 'concluida') {
                $fields[] = 'concluida_em = NULL';
            }
        }

        if ($fields !== []) {
            $this->pdo->prepare('UPDATE proj_demandas SET ' . implode(', ', $fields) . ' WHERE id = :id')->execute($params);
        }

        if (array_key_exists('checklist', $data) && is_array($data['checklist'])) {
            $this->replaceChecklist($id, $data['checklist']);
        }

        return $this->getDemanda($id);
    }

    public function deleteDemanda(int $id): void
    {
        $this->ensureSchema();
        $this->pdo->prepare('DELETE FROM proj_demandas WHERE id = :id')->execute(['id' => $id]);
    }

    public function updateChecklistItem(int $id, bool $concluido): array
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('SELECT demanda_id FROM proj_checklist WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Item não encontrado.');
        }

        $this->pdo->prepare('UPDATE proj_checklist SET concluido = :c WHERE id = :id')->execute([
            'c' => $concluido ? 1 : 0,
            'id' => $id,
        ]);

        $demandaId = (int) $row['demanda_id'];
        $this->syncDemandaStatusFromChecklist($demandaId);

        return $this->getDemanda($demandaId);
    }

    public function addChecklistItem(int $demandaId, string $texto): array
    {
        $this->ensureSchema();
        $this->getDemanda($demandaId);

        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 AS n FROM proj_checklist WHERE demanda_id = :d');
        $stmt->execute(['d' => $demandaId]);
        $ordem = (int) ($stmt->fetch()['n'] ?? 1);

        $this->pdo->prepare('
            INSERT INTO proj_checklist (demanda_id, texto, ordem) VALUES (:d, :t, :o)
        ')->execute(['d' => $demandaId, 't' => trim($texto), 'o' => $ordem]);

        return $this->getDemanda($demandaId);
    }

    public function deleteChecklistItem(int $id): array
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('SELECT demanda_id FROM proj_checklist WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Item não encontrado.');
        }

        $demandaId = (int) $row['demanda_id'];
        $this->pdo->prepare('DELETE FROM proj_checklist WHERE id = :id')->execute(['id' => $id]);
        $this->syncDemandaStatusFromChecklist($demandaId);

        return $this->getDemanda($demandaId);
    }

    private function syncDemandaStatusFromChecklist(int $demandaId): void
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) AS total, SUM(concluido) AS done FROM proj_checklist WHERE demanda_id = :d
        ');
        $stmt->execute(['d' => $demandaId]);
        $stats = $stmt->fetch();
        $total = (int) ($stats['total'] ?? 0);
        $done = (int) ($stats['done'] ?? 0);

        if ($total === 0) {
            return;
        }

        if ($done === $total) {
            $this->pdo->prepare("UPDATE proj_demandas SET status = 'concluida', concluida_em = NOW() WHERE id = :id AND status != 'cancelada'")
                ->execute(['id' => $demandaId]);
        } elseif ($done > 0) {
            $this->pdo->prepare("UPDATE proj_demandas SET status = 'em_andamento', concluida_em = NULL WHERE id = :id AND status NOT IN ('cancelada','concluida')")
                ->execute(['id' => $demandaId]);
            $this->pdo->prepare("UPDATE proj_demandas SET status = 'em_andamento', concluida_em = NULL WHERE id = :id AND status = 'concluida'")
                ->execute(['id' => $demandaId]);
        }
    }

    private function replaceChecklist(int $demandaId, array $items): void
    {
        $this->pdo->prepare('DELETE FROM proj_checklist WHERE demanda_id = :d')->execute(['d' => $demandaId]);
        $ordem = 0;
        $ins = $this->pdo->prepare('
            INSERT INTO proj_checklist (demanda_id, texto, concluido, ordem) VALUES (:d, :t, :c, :o)
        ');
        foreach ($items as $item) {
            if (is_string($item)) {
                $texto = trim($item);
                $concluido = 0;
            } elseif (is_array($item)) {
                $texto = trim((string) ($item['texto'] ?? ''));
                $concluido = !empty($item['concluido']) ? 1 : 0;
            } else {
                continue;
            }
            if ($texto === '') {
                continue;
            }
            $ins->execute(['d' => $demandaId, 't' => $texto, 'c' => $concluido, 'o' => $ordem++]);
        }
        $this->syncDemandaStatusFromChecklist($demandaId);
    }

    private function fetchDemandas(string $where, array $params, ?string $statusFiltro): array
    {
        $sql = '
            SELECT d.*, t.nome AS tipo_nome, t.cor AS tipo_cor
            FROM proj_demandas d
            INNER JOIN proj_tipos t ON t.id = d.tipo_id
            WHERE ' . $where;

        if ($statusFiltro === 'pendentes') {
            $sql .= " AND d.status IN ('pendente', 'em_andamento')";
        } elseif ($statusFiltro === 'concluidas') {
            $sql .= " AND d.status = 'concluida'";
        } elseif ($statusFiltro !== null && $statusFiltro !== '' && $statusFiltro !== 'todas') {
            $sql .= ' AND d.status = :status';
            $params['status'] = $statusFiltro;
        }

        $sql .= ' ORDER BY d.data_prevista ASC, FIELD(d.prioridade, \'alta\', \'media\', \'baixa\'), d.id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(fn ($r) => $this->mapDemanda($r, true), $rows);
    }

    private function fetchDemandaRow(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT d.*, t.nome AS tipo_nome, t.cor AS tipo_cor
            FROM proj_demandas d
            INNER JOIN proj_tipos t ON t.id = d.tipo_id
            WHERE d.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function mapDemanda(array $row, bool $withChecklist): array
    {
        $demanda = [
            'id' => (int) $row['id'],
            'titulo' => $row['titulo'],
            'descricao' => $row['descricao'],
            'tipo_id' => (int) $row['tipo_id'],
            'tipo_nome' => $row['tipo_nome'] ?? null,
            'tipo_cor' => $row['tipo_cor'] ?? null,
            'data_prevista' => $row['data_prevista'],
            'status' => $row['status'],
            'prioridade' => $row['prioridade'],
            'concluida_em' => $row['concluida_em'],
        ];

        if ($withChecklist) {
            $demanda['checklist'] = $this->checklistForDemanda((int) $row['id']);
            $demanda['checklist_total'] = count($demanda['checklist']);
            $demanda['checklist_concluidos'] = count(array_filter($demanda['checklist'], fn ($c) => $c['concluido']));
        }

        return $demanda;
    }

    private function checklistForDemanda(int $demandaId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM proj_checklist WHERE demanda_id = :d ORDER BY ordem, id
        ');
        $stmt->execute(['d' => $demandaId]);
        return array_map(fn ($r) => [
            'id' => (int) $r['id'],
            'texto' => $r['texto'],
            'concluido' => (bool) $r['concluido'],
            'ordem' => (int) $r['ordem'],
        ], $stmt->fetchAll());
    }

    private function mapTipo(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'cor' => $row['cor'],
            'ordem' => (int) $row['ordem'],
        ];
    }

    private function assertTipo(int $tipoId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM proj_tipos WHERE id = :id');
        $stmt->execute(['id' => $tipoId]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException('Tipo inválido.');
        }
    }

    private function agruparPorDia(array $demandas, string $de, string $ate): array
    {
        $dias = [];
        $cursor = new DateTimeImmutable($de);
        $fim = new DateTimeImmutable($ate);
        while ($cursor <= $fim) {
            $key = $cursor->format('Y-m-d');
            $dias[$key] = [];
            $cursor = $cursor->modify('+1 day');
        }

        foreach ($demandas as $d) {
            $key = $d['data_prevista'] ?? '';
            if ($key !== '' && isset($dias[$key])) {
                $dias[$key][] = $d;
            }
        }

        return $dias;
    }
}
