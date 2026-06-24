<?php

declare(strict_types=1);

final class FinanceService
{
    public function __construct(private PDO $pdo) {}

    public function ensureSchema(): void
    {
        $sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('schema.sql não encontrado.');
        }
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt !== '') {
                $this->pdo->exec($stmt);
            }
        }
    }

    public function getConfig(): array
    {
        $this->ensureFinTables();
        $row = $this->pdo->query('SELECT saldo_referencia, data_referencia FROM fin_config WHERE id = 1')->fetch();
        if (!$row) {
            $this->pdo->exec("INSERT INTO fin_config (id, saldo_referencia, data_referencia) VALUES (1, 0, CURDATE())");
            return ['saldo_referencia' => 0.0, 'data_referencia' => date('Y-m-d')];
        }
        return [
            'saldo_referencia' => (float) $row['saldo_referencia'],
            'data_referencia' => $row['data_referencia'],
        ];
    }

    public function updateConfig(float $saldo, string $dataReferencia): array
    {
        $this->ensureFinTables();
        $this->pdo->prepare('
            INSERT INTO fin_config (id, saldo_referencia, data_referencia)
            VALUES (1, :s, :d)
            ON DUPLICATE KEY UPDATE saldo_referencia = :s2, data_referencia = :d2
        ')->execute([
            's' => $saldo,
            'd' => $dataReferencia,
            's2' => $saldo,
            'd2' => $dataReferencia,
        ]);
        return $this->getConfig();
    }

    /** @return list<array<string, mixed>> */
    public function listCategorias(?string $tipo = null): array
    {
        $this->ensureFinTables();
        $sql = 'SELECT * FROM fin_categorias WHERE ativa = 1';
        if ($tipo === 'receita' || $tipo === 'despesa') {
            $sql .= " AND (tipo = :t OR tipo = 'ambos')";
            $stmt = $this->pdo->prepare($sql . ' ORDER BY nome ASC');
            $stmt->execute(['t' => $tipo]);
        } else {
            $stmt = $this->pdo->query($sql . ' ORDER BY nome ASC');
        }
        return array_map(fn ($r) => $this->mapCategoria($r), $stmt->fetchAll());
    }

    public function getCategoria(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fin_categorias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Categoria não encontrada.');
        }
        return $this->mapCategoria($row);
    }

    public function createCategoria(array $data): array
    {
        $this->ensureFinTables();
        $stmt = $this->pdo->prepare('
            INSERT INTO fin_categorias (nome, tipo, cor, ativa) VALUES (:nome, :tipo, :cor, :ativa)
        ');
        $stmt->execute([
            'nome' => $data['nome'],
            'tipo' => $data['tipo'],
            'cor' => $data['cor'],
            'ativa' => !empty($data['ativa']) ? 1 : 0,
        ]);
        return $this->getCategoria((int) $this->pdo->lastInsertId());
    }

    public function updateCategoria(int $id, array $data): array
    {
        $stmt = $this->pdo->prepare('
            UPDATE fin_categorias SET nome = :nome, tipo = :tipo, cor = :cor, ativa = :ativa WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'nome' => $data['nome'],
            'tipo' => $data['tipo'],
            'cor' => $data['cor'],
            'ativa' => !empty($data['ativa']) ? 1 : 0,
        ]);
        return $this->getCategoria($id);
    }

    public function deleteCategoria(int $id): void
    {
        $this->pdo->prepare('UPDATE fin_lancamentos SET categoria_id = NULL WHERE categoria_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE fin_recorrencias SET categoria_id = NULL WHERE categoria_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM fin_categorias WHERE id = :id')->execute(['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listRecorrencias(): array
    {
        $this->ensureFinTables();
        $rows = $this->pdo->query('
            SELECT r.*, c.nome AS categoria_nome, c.cor AS categoria_cor
            FROM fin_recorrencias r
            LEFT JOIN fin_categorias c ON c.id = r.categoria_id
            ORDER BY r.ativa DESC, r.descricao ASC
        ')->fetchAll();
        return array_map(fn ($r) => $this->mapRecorrencia($r), $rows);
    }

    public function createRecorrencia(array $data): array
    {
        $this->ensureFinTables();
        $stmt = $this->pdo->prepare('
            INSERT INTO fin_recorrencias (tipo, descricao, valor, dia_vencimento, data_inicio, data_fim, categoria_id, ativa)
            VALUES (:tipo, :desc, :valor, :dia, :ini, :fim, :cat, :ativa)
        ');
        $stmt->execute([
            'tipo' => $data['tipo'],
            'desc' => $data['descricao'],
            'valor' => $data['valor'],
            'dia' => $data['dia_vencimento'],
            'ini' => $data['data_inicio'],
            'fim' => $data['data_fim'] ?? null,
            'cat' => $data['categoria_id'] ?? null,
            'ativa' => !empty($data['ativa']) ? 1 : 0,
        ]);
        return $this->getRecorrencia((int) $this->pdo->lastInsertId());
    }

    public function updateRecorrencia(int $id, array $data): array
    {
        $this->ensureFinTables();
        $stmt = $this->pdo->prepare('
            UPDATE fin_recorrencias SET
                tipo = :tipo, descricao = :desc, valor = :valor,
                dia_vencimento = :dia, data_inicio = :ini, data_fim = :fim,
                categoria_id = :cat, ativa = :ativa
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'tipo' => $data['tipo'],
            'desc' => $data['descricao'],
            'valor' => $data['valor'],
            'dia' => $data['dia_vencimento'],
            'ini' => $data['data_inicio'],
            'fim' => $data['data_fim'] ?? null,
            'cat' => $data['categoria_id'] ?? null,
            'ativa' => !empty($data['ativa']) ? 1 : 0,
        ]);
        if (array_key_exists('categoria_id', $data)) {
            $this->propagarCategoriaNaRecorrencia($id, $data['categoria_id']);
        }
        return $this->getRecorrencia($id);
    }

    public function deleteRecorrencia(int $id): array
    {
        $this->ensureFinTables();
        $this->getRecorrencia($id);

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM fin_lancamentos WHERE recorrencia_id = :id');
        $countStmt->execute(['id' => $id]);
        $lancamentosRemovidos = (int) $countStmt->fetchColumn();

        $this->pdo->prepare('DELETE FROM fin_lancamentos WHERE recorrencia_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM fin_recorrencias WHERE id = :id')->execute(['id' => $id]);

        return ['lancamentos_removidos' => $lancamentosRemovidos];
    }

    public function getRecorrencia(int $id): array
    {
        $stmt = $this->pdo->prepare('
            SELECT r.*, c.nome AS categoria_nome, c.cor AS categoria_cor
            FROM fin_recorrencias r
            LEFT JOIN fin_categorias c ON c.id = r.categoria_id
            WHERE r.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Recorrência não encontrada.');
        }
        return $this->mapRecorrencia($row);
    }

    /** @return list<array<string, mixed>> */
    public function lancamentosDoMes(string $mesReferencia): array
    {
        $this->ensureFinTables();
        $stored = $this->fetchLancamentosMes($mesReferencia);
        $byRec = [];
        foreach ($stored as $l) {
            if ($l['recorrencia_id']) {
                $byRec[(int) $l['recorrencia_id']] = $l;
            }
        }

        $merged = [];
        foreach ($stored as $l) {
            if (!$l['recorrencia_id']) {
                $merged[] = $l;
            }
        }

        $recs = $this->pdo->query('
            SELECT r.*, c.nome AS categoria_nome, c.cor AS categoria_cor
            FROM fin_recorrencias r
            LEFT JOIN fin_categorias c ON c.id = r.categoria_id
            WHERE r.ativa = 1
        ')->fetchAll();
        $addedRecIds = [];
        foreach ($recs as $rec) {
            $rid = (int) $rec['id'];
            if (!$this->recorrenciaAplicaAoMes($rec, $mesReferencia)) {
                continue;
            }
            if (isset($byRec[$rid])) {
                $merged[] = $byRec[$rid];
                $addedRecIds[$rid] = true;
            } else {
                $merged[] = $this->lancamentoProjetado($rec, $mesReferencia);
            }
        }

        // Lançamentos já registrados no mês cuja recorrência foi desativada/excluída
        foreach ($byRec as $rid => $l) {
            if (!isset($addedRecIds[$rid])) {
                $merged[] = $l;
            }
        }

        usort($merged, fn ($a, $b) => strcmp($a['data_vencimento'], $b['data_vencimento']));
        return $merged;
    }

    public function createLancamento(array $data): array
    {
        $this->ensureFinTables();
        $mes = $data['mes_referencia'] ?? substr($data['data_vencimento'], 0, 7);
        $status = $data['status'] ?? 'prevista';
        $stmt = $this->pdo->prepare('
            INSERT INTO fin_lancamentos (tipo, descricao, valor, valor_realizado, data_vencimento, data_efetivacao, mes_referencia, status, categoria_id, recorrencia_id)
            VALUES (:tipo, :desc, :valor, :vreal, :venc, :efet, :mes, :status, :cat, :rec)
        ');
        $stmt->execute([
            'tipo' => $data['tipo'],
            'desc' => $data['descricao'],
            'valor' => $data['valor'],
            'vreal' => $data['valor_realizado'] ?? null,
            'venc' => $data['data_vencimento'],
            'efet' => $data['data_efetivacao'] ?? null,
            'mes' => $mes,
            'status' => $status,
            'cat' => $data['categoria_id'] ?? null,
            'rec' => $data['recorrencia_id'] ?? null,
        ]);
        return $this->getLancamento((int) $this->pdo->lastInsertId());
    }

    public function materializarRecorrencia(int $recorrenciaId, string $mesReferencia, string $status): array
    {
        $rec = $this->getRecorrenciaRow($recorrenciaId);
        if (!$this->recorrenciaAplicaAoMes($rec, $mesReferencia)) {
            throw new InvalidArgumentException('Recorrência não se aplica a este mês.');
        }

        $existing = $this->pdo->prepare('
            SELECT id FROM fin_lancamentos WHERE recorrencia_id = :r AND mes_referencia = :m LIMIT 1
        ');
        $existing->execute(['r' => $recorrenciaId, 'm' => $mesReferencia]);
        $id = $existing->fetchColumn();

        $venc = $this->vencimentoNoMes($rec, $mesReferencia);
        $defaultStatus = $rec['tipo'] === 'receita' ? 'prevista' : 'prevista';

        if ($id) {
            return $this->getLancamento((int) $id);
        }

        return $this->createLancamento([
            'tipo' => $rec['tipo'],
            'descricao' => $rec['descricao'],
            'valor' => (float) $rec['valor'],
            'data_vencimento' => $venc,
            'mes_referencia' => $mesReferencia,
            'status' => $status ?: $defaultStatus,
            'recorrencia_id' => $recorrenciaId,
            'categoria_id' => !empty($rec['categoria_id']) ? (int) $rec['categoria_id'] : null,
        ]);
    }

    public function updateLancamento(int $id, array $data): array
    {
        $this->ensureFinTables();
        $current = $this->getLancamento($id);
        $fields = [];
        $params = ['id' => $id];

        foreach (['descricao', 'valor', 'valor_realizado', 'data_vencimento', 'data_efetivacao', 'status', 'tipo', 'categoria_id'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = :{$f}";
                $params[$f] = $data[$f];
            }
        }
        if (isset($data['data_vencimento'])) {
            $fields[] = 'mes_referencia = :mes';
            $params['mes'] = substr((string) $data['data_vencimento'], 0, 7);
        }
        if ($fields === []) {
            return $current;
        }
        $this->pdo->prepare('UPDATE fin_lancamentos SET ' . implode(', ', $fields) . ' WHERE id = :id')->execute($params);

        if (array_key_exists('categoria_id', $data) && !empty($current['recorrencia_id'])) {
            $this->propagarCategoriaNaRecorrencia((int) $current['recorrencia_id'], $data['categoria_id']);
        }

        return $this->getLancamento($id);
    }

    /** Propaga categoria da recorrência para todos os meses (lançamentos já materializados). */
    private function propagarCategoriaNaRecorrencia(int $recorrenciaId, mixed $categoriaId): void
    {
        $cat = $categoriaId !== null && $categoriaId !== '' ? (int) $categoriaId : null;
        $this->pdo->prepare('UPDATE fin_lancamentos SET categoria_id = :cat WHERE recorrencia_id = :rid')->execute([
            'cat' => $cat,
            'rid' => $recorrenciaId,
        ]);
    }

    public function deleteLancamento(int $id): void
    {
        $this->pdo->prepare('DELETE FROM fin_lancamentos WHERE id = :id')->execute(['id' => $id]);
    }

    public function getLancamento(int $id): array
    {
        $stmt = $this->pdo->prepare('
            SELECT l.*,
                c.nome AS categoria_nome,
                c.cor AS categoria_cor,
                r.categoria_id AS rec_categoria_id,
                cr.nome AS rec_categoria_nome,
                cr.cor AS rec_categoria_cor
            FROM fin_lancamentos l
            LEFT JOIN fin_recorrencias r ON r.id = l.recorrencia_id
            LEFT JOIN fin_categorias c ON c.id = l.categoria_id
            LEFT JOIN fin_categorias cr ON cr.id = r.categoria_id
            WHERE l.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Lançamento não encontrado.');
        }
        return $this->mapLancamento($row, false);
    }

    public function resumoAno(int $ano): array
    {
        return $this->resumoAnoComAcumulado($ano);
    }

    public function resumoMes(string $mesReferencia): array
    {
        $items = $this->lancamentosDoMes($mesReferencia);
        $totais = $this->calcularTotais($items);
        return array_merge(['mes' => $mesReferencia], $totais);
    }

    public function resumoMesComparado(string $mesReferencia): array
    {
        $atual = $this->resumoMes($mesReferencia);
        $antMes = $this->mesAnterior($mesReferencia);
        $anterior = $this->resumoMes($antMes);
        $acumulado = $this->saldoAcumuladoAteMes($mesReferencia);
        $config = $this->getConfig();
        $refYm = substr($config['data_referencia'], 0, 7);
        $acumuladoAnterior = $antMes < $refYm
            ? round((float) $config['saldo_referencia'], 2)
            : $this->saldoAcumuladoAteMes($antMes)['saldo_acumulado_previsto'];

        return array_merge($atual, [
            'saldo_acumulado_previsto' => $acumulado['saldo_acumulado_previsto'],
            'saldo_acumulado_previsto_anterior' => $acumuladoAnterior,
            'mes_anterior_ref' => $antMes,
            'mes_anterior' => $anterior,
            'variacao' => [
                'receitas_previstas' => round($atual['receitas_previstas'] - $anterior['receitas_previstas'], 2),
                'despesas_previstas' => round($atual['despesas_previstas'] - $anterior['despesas_previstas'], 2),
                'saldo_previsto' => round($atual['saldo_previsto'] - $anterior['saldo_previsto'], 2),
                'receitas_realizadas' => round($atual['receitas_realizadas'] - $anterior['receitas_realizadas'], 2),
                'despesas_realizadas' => round($atual['despesas_realizadas'] - $anterior['despesas_realizadas'], 2),
                'saldo_realizado' => round($atual['saldo_realizado'] - $anterior['saldo_realizado'], 2),
            ],
        ]);
    }

    /** Saldo acumulado até o fim do mês, a partir do saldo inicial configurado. */
    public function saldoAcumuladoAteMes(string $ym): array
    {
        $config = $this->getConfig();
        $refYm = substr($config['data_referencia'], 0, 7);
        $saldoAcumPrev = (float) $config['saldo_referencia'];
        $saldoAcumReal = (float) $config['saldo_referencia'];

        if ($ym >= $refYm) {
            foreach ($this->listarMesesEntre($refYm, $ym) as $mes) {
                $t = $this->calcularTotais($this->lancamentosDoMes($mes));
                $saldoAcumPrev += $t['saldo_previsto'];
                $saldoAcumReal += $t['saldo_realizado'];
            }
        }

        return [
            'saldo_acumulado_previsto' => round($saldoAcumPrev, 2),
            'saldo_acumulado_realizado' => round($saldoAcumReal, 2),
        ];
    }

    /** @param list<array<string, mixed>> $items */
    public function vencimentosProximos(array $items, string $mesReferencia): array
    {
        $this->ensureFinTables();
        $hoje = date('Y-m-d');
        $mesAtual = date('Y-m');
        if ($mesReferencia !== $mesAtual) {
            return [];
        }
        $limite = date('Y-m-d', strtotime($hoje . ' +7 days'));
        $out = [];
        $idsVistos = [];

        foreach ($items as $it) {
            if (($it['status'] ?? '') !== 'prevista') {
                continue;
            }
            $venc = $it['data_vencimento'];
            if ($venc <= $limite) {
                $out[] = $this->marcarAlertaVencimento($it, $hoje);
                if (!empty($it['id'])) {
                    $idsVistos[(int) $it['id']] = true;
                }
            }
        }

        foreach ($this->fetchVencidasAnteriores($hoje, $mesAtual, $idsVistos) as $it) {
            $out[] = $this->marcarAlertaVencimento($it, $hoje);
        }

        usort($out, function ($a, $b) {
            $va = ($a['alerta_vencimento'] ?? '') === 'vencida' ? 0 : 1;
            $vb = ($b['alerta_vencimento'] ?? '') === 'vencida' ? 0 : 1;
            if ($va !== $vb) {
                return $va - $vb;
            }

            return strcmp($a['data_vencimento'], $b['data_vencimento']);
        });

        return $out;
    }

    /** @param array<string, mixed> $it */
    private function marcarAlertaVencimento(array $it, string $hoje): array
    {
        return array_merge($it, [
            'alerta_vencimento' => $it['data_vencimento'] < $hoje ? 'vencida' : 'proxima',
        ]);
    }

    /**
     * @param array<int, true> $excludeIds
     * @return list<array<string, mixed>>
     */
    private function fetchVencidasAnteriores(string $hoje, string $mesAtual, array $excludeIds): array
    {
        $stmt = $this->pdo->prepare('
            SELECT l.*,
                c.nome AS categoria_nome,
                c.cor AS categoria_cor,
                r.categoria_id AS rec_categoria_id,
                cr.nome AS rec_categoria_nome,
                cr.cor AS rec_categoria_cor
            FROM fin_lancamentos l
            LEFT JOIN fin_recorrencias r ON r.id = l.recorrencia_id
            LEFT JOIN fin_categorias c ON c.id = l.categoria_id
            LEFT JOIN fin_categorias cr ON cr.id = r.categoria_id
            WHERE l.status = "prevista"
              AND l.data_vencimento < :hoje
              AND l.mes_referencia < :mes
            ORDER BY l.data_vencimento ASC
        ');
        $stmt->execute(['hoje' => $hoje, 'mes' => $mesAtual]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $id = (int) $row['id'];
            if (isset($excludeIds[$id])) {
                continue;
            }
            $out[] = $this->mapLancamento($row, false);
        }

        return $out;
    }

    public function resumoAnoDashboard(int $ano): array
    {
        $resumo = $this->resumoAnoComAcumulado($ano);
        $totais = [
            'receitas_previstas' => 0.0,
            'despesas_previstas' => 0.0,
            'saldo_previsto' => 0.0,
            'receitas_realizadas' => 0.0,
            'despesas_realizadas' => 0.0,
            'saldo_realizado' => 0.0,
        ];
        $melhor = null;
        $pior = null;
        foreach ($resumo['meses'] as $m) {
            foreach (array_keys($totais) as $k) {
                $totais[$k] += (float) ($m[$k] ?? 0);
            }
            $sp = (float) $m['saldo_previsto'];
            if ($melhor === null || $sp > $melhor['saldo_previsto']) {
                $melhor = $m;
            }
            if ($pior === null || $sp < $pior['saldo_previsto']) {
                $pior = $m;
            }
        }
        foreach ($totais as $k => $v) {
            $totais[$k] = round($v, 2);
        }
        return array_merge($resumo, [
            'totais_ano' => $totais,
            'melhor_mes' => $melhor,
            'pior_mes' => $pior,
            'por_categoria' => $this->resumoPorCategoriaAno($ano),
        ]);
    }

    /** @return array{receitas: list<array<string, mixed>>, despesas: list<array<string, mixed>>} */
    public function resumoPorCategoriaAno(int $ano): array
    {
        $this->ensureFinTables();
        $buckets = ['receita' => [], 'despesa' => []];

        for ($i = 1; $i <= 12; $i++) {
            $ym = sprintf('%04d-%02d', $ano, $i);
            foreach ($this->lancamentosDoMes($ym) as $it) {
                if ($it['status'] === 'cancelada') {
                    continue;
                }
                $tipo = $it['tipo'];
                $catId = $it['categoria_id'] ?? 0;
                $key = (string) $catId;
                if (!isset($buckets[$tipo][$key])) {
                    $buckets[$tipo][$key] = [
                        'categoria_id' => $it['categoria_id'],
                        'categoria_nome' => $it['categoria_nome'] ?? 'Sem categoria',
                        'categoria_cor' => $it['categoria_cor'] ?? '#94a3b8',
                        'previsto' => 0.0,
                        'realizado' => 0.0,
                    ];
                }
                $buckets[$tipo][$key]['previsto'] += (float) $it['valor'];
                if ($tipo === 'receita' && $it['status'] === 'recebida') {
                    $buckets[$tipo][$key]['realizado'] += $this->valorRealizadoItem($it);
                } elseif ($tipo === 'despesa' && $it['status'] === 'paga') {
                    $buckets[$tipo][$key]['realizado'] += $this->valorRealizadoItem($it);
                }
            }
        }

        $fmt = static function (array $list): array {
            $out = array_values($list);
            foreach ($out as &$row) {
                $row['previsto'] = round($row['previsto'], 2);
                $row['realizado'] = round($row['realizado'], 2);
            }
            unset($row);
            usort($out, fn ($a, $b) => $b['previsto'] <=> $a['previsto']);
            return $out;
        };

        return [
            'receitas' => $fmt($buckets['receita']),
            'despesas' => $fmt($buckets['despesa']),
        ];
    }

    public function projecao(string $de, string $ate): array
    {
        $config = $this->getConfig();
        $refYm = substr($config['data_referencia'], 0, 7);

        $saldoAcumPrev = (float) $config['saldo_referencia'];
        $saldoAcumReal = (float) $config['saldo_referencia'];

        if ($de > $refYm) {
            foreach ($this->listarMesesEntre($refYm, $this->mesAnterior($de)) as $ym) {
                $t = $this->calcularTotais($this->lancamentosDoMes($ym));
                $saldoAcumPrev += $t['saldo_previsto'];
                $saldoAcumReal += $t['saldo_realizado'];
            }
        }

        $linhas = [];
        foreach ($this->listarMesesEntre($de, $ate) as $ym) {
            $t = $this->calcularTotais($this->lancamentosDoMes($ym));
            $saldoAcumPrev += $t['saldo_previsto'];
            $saldoAcumReal += $t['saldo_realizado'];
            $linhas[] = array_merge(['mes' => $ym], $t, [
                'saldo_acumulado_previsto' => round($saldoAcumPrev, 2),
                'saldo_acumulado_realizado' => round($saldoAcumReal, 2),
            ]);
        }

        return [
            'de' => $de,
            'ate' => $ate,
            'config' => $config,
            'meses' => $linhas,
        ];
    }

    public function resumoAnoComAcumulado(int $ano): array
    {
        $de = sprintf('%04d-01', $ano);
        $ate = sprintf('%04d-12', $ano);
        $proj = $this->projecao($de, $ate);
        $byMes = [];
        foreach ($proj['meses'] as $m) {
            $byMes[$m['mes']] = $m;
        }

        $meses = [];
        for ($i = 1; $i <= 12; $i++) {
            $ym = sprintf('%04d-%02d', $ano, $i);
            $meses[] = $byMes[$ym] ?? array_merge($this->resumoMes($ym), [
                'mes' => $ym,
                'saldo_acumulado_previsto' => 0,
                'saldo_acumulado_realizado' => 0,
            ]);
        }

        return [
            'ano' => $ano,
            'config' => $proj['config'],
            'meses' => $meses,
        ];
    }

    private function ensureFinTables(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fin_config (
                id TINYINT UNSIGNED NOT NULL DEFAULT 1,
                saldo_referencia DECIMAL(14,2) NOT NULL DEFAULT 0,
                data_referencia DATE NOT NULL,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fin_recorrencias (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tipo ENUM('receita','despesa') NOT NULL,
                descricao VARCHAR(255) NOT NULL,
                valor DECIMAL(14,2) NOT NULL,
                dia_vencimento TINYINT UNSIGNED NOT NULL,
                data_inicio DATE NOT NULL,
                data_fim DATE NULL,
                ativa TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fin_lancamentos (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tipo ENUM('receita','despesa') NOT NULL,
                descricao VARCHAR(255) NOT NULL,
                valor DECIMAL(14,2) NOT NULL,
                data_vencimento DATE NOT NULL,
                mes_referencia CHAR(7) NOT NULL,
                status ENUM('prevista','recebida','paga','cancelada') NOT NULL DEFAULT 'prevista',
                recorrencia_id INT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_lanc_mes (mes_referencia)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM fin_config')->fetchColumn();
        if ($count === 0) {
            $this->pdo->exec("INSERT INTO fin_config (id, saldo_referencia, data_referencia) VALUES (1, 0, CURDATE())");
        }
        $this->ensureLancamentosValorRealizado();
        $this->ensureLancamentosExtras();
        $this->ensureFinCategorias();
        $done = true;
    }

    private function ensureLancamentosExtras(): void
    {
        if ($this->pdo->query("SHOW COLUMNS FROM fin_lancamentos LIKE 'data_efetivacao'")->fetchAll() === []) {
            $this->pdo->exec('ALTER TABLE fin_lancamentos ADD COLUMN data_efetivacao DATE NULL DEFAULT NULL AFTER data_vencimento');
        }
        if ($this->pdo->query("SHOW COLUMNS FROM fin_lancamentos LIKE 'categoria_id'")->fetchAll() === []) {
            $this->pdo->exec('ALTER TABLE fin_lancamentos ADD COLUMN categoria_id INT UNSIGNED NULL DEFAULT NULL AFTER status');
        }
        if ($this->pdo->query("SHOW COLUMNS FROM fin_recorrencias LIKE 'categoria_id'")->fetchAll() === []) {
            $this->pdo->exec('ALTER TABLE fin_recorrencias ADD COLUMN categoria_id INT UNSIGNED NULL DEFAULT NULL AFTER data_fim');
        }
    }

    private function ensureFinCategorias(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fin_categorias (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                nome VARCHAR(80) NOT NULL,
                tipo ENUM('receita','despesa','ambos') NOT NULL DEFAULT 'ambos',
                cor VARCHAR(7) NOT NULL DEFAULT '#6366f1',
                ativa TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_fin_cat_nome (nome)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM fin_categorias')->fetchColumn();
        if ($count > 0) {
            return;
        }
        $seed = dirname(__DIR__) . '/database/seed-categorias.sql';
        if (is_file($seed)) {
            $sql = file_get_contents($seed);
            if ($sql !== false) {
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if ($stmt !== '') {
                        $this->pdo->exec($stmt);
                    }
                }
            }
        }
    }

    private function mapCategoria(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'tipo' => $row['tipo'],
            'cor' => $row['cor'],
            'ativa' => (bool) $row['ativa'],
        ];
    }

    private function ensureLancamentosValorRealizado(): void
    {
        $cols = $this->pdo->query("SHOW COLUMNS FROM fin_lancamentos LIKE 'valor_realizado'")->fetchAll();
        if ($cols === []) {
            $this->pdo->exec('ALTER TABLE fin_lancamentos ADD COLUMN valor_realizado DECIMAL(14,2) NULL DEFAULT NULL AFTER valor');
        }
    }

    private function valorRealizadoItem(array $it): float
    {
        if (array_key_exists('valor_realizado', $it) && $it['valor_realizado'] !== null) {
            return (float) $it['valor_realizado'];
        }
        return (float) $it['valor'];
    }

    /** @return list<array<string, mixed>> */
    private function fetchLancamentosMes(string $mes): array
    {
        $stmt = $this->pdo->prepare('
            SELECT l.*,
                c.nome AS categoria_nome,
                c.cor AS categoria_cor,
                r.categoria_id AS rec_categoria_id,
                cr.nome AS rec_categoria_nome,
                cr.cor AS rec_categoria_cor
            FROM fin_lancamentos l
            LEFT JOIN fin_recorrencias r ON r.id = l.recorrencia_id
            LEFT JOIN fin_categorias c ON c.id = l.categoria_id
            LEFT JOIN fin_categorias cr ON cr.id = r.categoria_id
            WHERE l.mes_referencia = :m
            ORDER BY l.data_vencimento
        ');
        $stmt->execute(['m' => $mes]);
        return array_map(fn ($r) => $this->mapLancamento($r, false), $stmt->fetchAll());
    }

  private function calcularTotais(array $items): array
    {
        $recPrev = $despPrev = $recReal = $despReal = 0.0;
        foreach ($items as $it) {
            if ($it['status'] === 'cancelada') {
                continue;
            }
            $vPrev = (float) $it['valor'];
            if ($it['tipo'] === 'receita') {
                $recPrev += $vPrev;
                if ($it['status'] === 'recebida') {
                    $recReal += $this->valorRealizadoItem($it);
                }
            } else {
                $despPrev += $vPrev;
                if ($it['status'] === 'paga') {
                    $despReal += $this->valorRealizadoItem($it);
                }
            }
        }
        return [
            'receitas_previstas' => round($recPrev, 2),
            'despesas_previstas' => round($despPrev, 2),
            'saldo_previsto' => round($recPrev - $despPrev, 2),
            'receitas_realizadas' => round($recReal, 2),
            'despesas_realizadas' => round($despReal, 2),
            'saldo_realizado' => round($recReal - $despReal, 2),
        ];
    }

    private function recorrenciaAplicaAoMes(array $rec, string $ym): bool
    {
        if (!(int) ($rec['ativa'] ?? 0)) {
            return false;
        }
        $first = $ym . '-01';
        $last = date('Y-m-t', strtotime($first));
        if ($rec['data_inicio'] > $last) {
            return false;
        }
        if (!empty($rec['data_fim']) && $rec['data_fim'] < $first) {
            return false;
        }
        return true;
    }

    private function vencimentoNoMes(array $rec, string $ym): string
    {
        [$y, $m] = explode('-', $ym);
        $maxDay = (int) date('t', strtotime("{$y}-{$m}-01"));
        $day = min((int) $rec['dia_vencimento'], $maxDay);
        return sprintf('%s-%02d-%02d', $y, $m, $day);
    }

    private function lancamentoProjetado(array $rec, string $ym): array
    {
        return [
            'id' => null,
            'tipo' => $rec['tipo'],
            'descricao' => $rec['descricao'],
            'valor' => (float) $rec['valor'],
            'valor_realizado' => null,
            'data_vencimento' => $this->vencimentoNoMes($rec, $ym),
            'data_efetivacao' => null,
            'mes_referencia' => $ym,
            'status' => 'prevista',
            'categoria_id' => !empty($rec['categoria_id']) ? (int) $rec['categoria_id'] : null,
            'categoria_nome' => $rec['categoria_nome'] ?? null,
            'categoria_cor' => $rec['categoria_cor'] ?? null,
            'recorrencia_id' => (int) $rec['id'],
            'projetado' => true,
        ];
    }

    private function mapLancamento(array $row, bool $projetado): array
    {
        $cat = $this->resolverCategoria($row);

        return [
            'id' => (int) $row['id'],
            'tipo' => $row['tipo'],
            'descricao' => $row['descricao'],
            'valor' => (float) $row['valor'],
            'valor_realizado' => array_key_exists('valor_realizado', $row) && $row['valor_realizado'] !== null
                ? (float) $row['valor_realizado']
                : null,
            'data_vencimento' => $row['data_vencimento'],
            'data_efetivacao' => !empty($row['data_efetivacao']) ? $row['data_efetivacao'] : null,
            'mes_referencia' => $row['mes_referencia'],
            'status' => $row['status'],
            'categoria_id' => $cat['id'],
            'categoria_nome' => $cat['nome'],
            'categoria_cor' => $cat['cor'],
            'recorrencia_id' => $row['recorrencia_id'] ? (int) $row['recorrencia_id'] : null,
            'projetado' => $projetado,
        ];
    }

    /** @return array{id: int|null, nome: string|null, cor: string|null} */
    private function resolverCategoria(array $row): array
    {
        if (!empty($row['categoria_id'])) {
            return [
                'id' => (int) $row['categoria_id'],
                'nome' => $row['categoria_nome'] ?? null,
                'cor' => $row['categoria_cor'] ?? null,
            ];
        }
        if (!empty($row['rec_categoria_id'])) {
            return [
                'id' => (int) $row['rec_categoria_id'],
                'nome' => $row['rec_categoria_nome'] ?? null,
                'cor' => $row['rec_categoria_cor'] ?? null,
            ];
        }

        return ['id' => null, 'nome' => null, 'cor' => null];
    }

    private function mapRecorrencia(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'tipo' => $row['tipo'],
            'descricao' => $row['descricao'],
            'valor' => (float) $row['valor'],
            'dia_vencimento' => (int) $row['dia_vencimento'],
            'data_inicio' => $row['data_inicio'],
            'data_fim' => $row['data_fim'],
            'categoria_id' => !empty($row['categoria_id']) ? (int) $row['categoria_id'] : null,
            'categoria_nome' => $row['categoria_nome'] ?? null,
            'categoria_cor' => $row['categoria_cor'] ?? null,
            'ativa' => (bool) $row['ativa'],
        ];
    }

    private function getRecorrenciaRow(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fin_recorrencias WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Recorrência não encontrada.');
        }
        return $row;
    }

    /** @return list<string> */
    private function listarMesesEntre(string $de, string $ate): array
    {
        if ($de > $ate) {
            return [];
        }
        $out = [];
        $cur = $de;
        while ($cur <= $ate) {
            $out[] = $cur;
            $cur = $this->mesSeguinte($cur);
        }
        return $out;
    }

    private function mesSeguinte(string $ym): string
    {
        $d = new DateTimeImmutable($ym . '-01');
        return $d->modify('+1 month')->format('Y-m');
    }

    private function mesAnterior(string $ym): string
    {
        $d = new DateTimeImmutable($ym . '-01');
        return $d->modify('-1 month')->format('Y-m');
    }
}
