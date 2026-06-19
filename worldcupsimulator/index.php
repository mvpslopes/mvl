<?php

declare(strict_types=1);

/**
 * Simulador de Copa do Mundo — 32 seleções
 * Estado persistido em $_SESSION | UI: Tailwind CSS (CDN)
 */

session_start();

// ---------------------------------------------------------------------------
// Dados base — 32 seleções (Ranking FIFA aproximado)
// ---------------------------------------------------------------------------

function wc_selecoes_base(): array
{
    return [
        ['nome' => 'Argentina',      'forca' => 93, 'iso' => 'ar'],
        ['nome' => 'França',         'forca' => 92, 'iso' => 'fr'],
        ['nome' => 'Brasil',          'forca' => 91, 'iso' => 'br'],
        ['nome' => 'Inglaterra',      'forca' => 90, 'iso' => 'gb-eng'],
        ['nome' => 'Bélgica',         'forca' => 88, 'iso' => 'be'],
        ['nome' => 'Portugal',        'forca' => 87, 'iso' => 'pt'],
        ['nome' => 'Holanda',         'forca' => 86, 'iso' => 'nl'],
        ['nome' => 'Espanha',         'forca' => 86, 'iso' => 'es'],
        ['nome' => 'Alemanha',        'forca' => 85, 'iso' => 'de'],
        ['nome' => 'Itália',          'forca' => 85, 'iso' => 'it'],
        ['nome' => 'Croácia',         'forca' => 84, 'iso' => 'hr'],
        ['nome' => 'Uruguai',         'forca' => 83, 'iso' => 'uy'],
        ['nome' => 'Colômbia',        'forca' => 82, 'iso' => 'co'],
        ['nome' => 'Marrocos',        'forca' => 82, 'iso' => 'ma'],
        ['nome' => 'México',          'forca' => 81, 'iso' => 'mx'],
        ['nome' => 'Estados Unidos',  'forca' => 80, 'iso' => 'us'],
        ['nome' => 'Japão',           'forca' => 79, 'iso' => 'jp'],
        ['nome' => 'Suíça',           'forca' => 79, 'iso' => 'ch'],
        ['nome' => 'Senegal',         'forca' => 78, 'iso' => 'sn'],
        ['nome' => 'Dinamarca',       'forca' => 78, 'iso' => 'dk'],
        ['nome' => 'Irã',             'forca' => 77, 'iso' => 'ir'],
        ['nome' => 'Coreia do Sul',   'forca' => 77, 'iso' => 'kr'],
        ['nome' => 'Austrália',       'forca' => 76, 'iso' => 'au'],
        ['nome' => 'Áustria',         'forca' => 76, 'iso' => 'at'],
        ['nome' => 'Turquia',         'forca' => 75, 'iso' => 'tr'],
        ['nome' => 'Equador',         'forca' => 75, 'iso' => 'ec'],
        ['nome' => 'Suécia',          'forca' => 74, 'iso' => 'se'],
        ['nome' => 'Polônia',         'forca' => 74, 'iso' => 'pl'],
        ['nome' => 'Ucrânia',         'forca' => 73, 'iso' => 'ua'],
        ['nome' => 'Sérvia',          'forca' => 73, 'iso' => 'rs'],
        ['nome' => 'País de Gales',   'forca' => 72, 'iso' => 'gb-wls'],
        ['nome' => 'Costa Rica',      'forca' => 71, 'iso' => 'cr'],
    ];
}

function wc_grupos_copa2026_mapa(): array
{
    return [
        'A' => ['México', 'Equador', 'Polônia', 'Costa Rica'],
        'B' => ['Estados Unidos', 'Uruguai', 'Sérvia', 'País de Gales'],
        'C' => ['Brasil', 'Marrocos', 'Croácia', 'Irã'],
        'D' => ['Argentina', 'Dinamarca', 'Japão', 'Ucrânia'],
        'E' => ['Alemanha', 'Senegal', 'Holanda', 'Turquia'],
        'F' => ['França', 'Colômbia', 'Suíça', 'Austrália'],
        'G' => ['Inglaterra', 'Bélgica', 'Coreia do Sul', 'Suécia'],
        'H' => ['Espanha', 'Portugal', 'Itália', 'Áustria'],
    ];
}

function wc_perfil_config(): array
{
    return wc_perfil_config_from($_SESSION['perfil'] ?? 'equilibrado');
}

function wc_perfil_config_from(string $perfil): array
{
    return match ($perfil) {
        'realista'  => ['zebra' => 6, 'gols' => 2.4, 'label' => 'Realista'],
        'caotico'   => ['zebra' => 18, 'gols' => 3.2, 'label' => 'Caótico'],
        default     => ['zebra' => 12, 'gols' => 2.8, 'label' => 'Equilibrado'],
    };
}

function wc_aplicar_sorteio(array $times, ?string $modo = null): array
{
    $grupos = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $modo = $modo ?? $_SESSION['modo_sorteio'] ?? 'aleatorio';
    $selecoes = [];

    if ($modo === 'cop2026') {
        $mapa = wc_grupos_copa2026_mapa();
        $porNome = [];
        foreach ($times as $t) {
            $porNome[$t['nome']] = $t;
        }
        foreach ($grupos as $letra) {
            $i = 1;
            foreach ($mapa[$letra] as $nome) {
                $t = $porNome[$nome] ?? ['nome' => $nome, 'forca' => 70, 'iso' => 'xx'];
                $selecoes[] = [
                    'id'    => $letra . $i,
                    'nome'  => $t['nome'],
                    'forca' => $t['forca'],
                    'iso'   => $t['iso'],
                    'grupo' => $letra,
                ];
                $i++;
            }
        }
        return $selecoes;
    }

    shuffle($times);
    $idx = 0;
    foreach ($grupos as $letra) {
        for ($i = 0; $i < 4; $i++) {
            $t = $times[$idx++];
            $selecoes[] = [
                'id'    => $letra . ($i + 1),
                'nome'  => $t['nome'],
                'forca' => $t['forca'],
                'iso'   => $t['iso'],
                'grupo' => $letra,
            ];
        }
    }
    return $selecoes;
}

function wc_inicializar_campeonato(): void
{
    $_SESSION['perfil'] = $_SESSION['perfil'] ?? 'equilibrado';
    $_SESSION['modo_sorteio'] = $_SESSION['modo_sorteio'] ?? 'aleatorio';
    $_SESSION['selecoes'] = wc_aplicar_sorteio(wc_selecoes_base());
    $_SESSION['etapa'] = 'inicio';
    $_SESSION['tabelas'] = [];
    $_SESSION['partidas_grupos'] = [];
    $_SESSION['classificados'] = [];
    $_SESSION['oitavas'] = [];
    $_SESSION['quartas'] = [];
    $_SESSION['semi'] = [];
    $_SESSION['final'] = null;
    $_SESSION['campeao'] = null;
    $_SESSION['historico_chave'] = [];
}

if (!isset($_SESSION['etapa'])) {
    wc_inicializar_campeonato();
}
$_SESSION['perfil'] = $_SESSION['perfil'] ?? 'equilibrado';
$_SESSION['modo_sorteio'] = $_SESSION['modo_sorteio'] ?? 'aleatorio';

// ---------------------------------------------------------------------------
// Histórico persistente de campeões (JSON no servidor)
// ---------------------------------------------------------------------------

function wc_historico_arquivo(): string
{
    return __DIR__ . '/data/campeoes.json';
}

function wc_carregar_historico_campeoes(): array
{
    $path = wc_historico_arquivo();
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function wc_salvar_historico_campeoes(array $lista): void
{
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(
        wc_historico_arquivo(),
        json_encode(array_slice($lista, 0, 1000), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function wc_placar_final_texto(array $g): string
{
    $placar = $g['casa'] . ' × ' . $g['fora'];
    if ($g['ap_casa'] !== null || $g['ap_fora'] !== null) {
        $placar .= ' (' . ($g['casa'] + ($g['ap_casa'] ?? 0)) . '×' . ($g['fora'] + ($g['ap_fora'] ?? 0)) . ' ap.)';
    }
    if ($g['pen_casa'] !== null) {
        $placar .= ' (' . $g['pen_casa'] . '-' . $g['pen_fora'] . ' pên.)';
    }
    return $placar;
}

function wc_build_campeao_entry(array $jogoFinal, int $edicao): array
{
    $campeao = $jogoFinal['vencedor'];
    $vice = ($jogoFinal['vencedor']['id'] ?? '') === ($jogoFinal['casa']['id'] ?? '')
        ? $jogoFinal['fora']
        : $jogoFinal['casa'];

    return [
        'edicao'        => $edicao,
        'nome'          => $campeao['nome'],
        'iso'           => $campeao['iso'],
        'grupo'         => $campeao['grupo'],
        'forca'         => $campeao['forca'],
        'vice_nome'     => $vice['nome'],
        'vice_iso'      => $vice['iso'],
        'final_placar'  => wc_placar_final_texto($jogoFinal['gols']),
        'registrado_em' => date('d/m/Y H:i'),
    ];
}

function wc_registrar_campeao(array $campeao, array $jogoFinal): void
{
    $historico = wc_carregar_historico_campeoes();
    $jogoFinal['vencedor'] = $campeao;
    array_unshift($historico, wc_build_campeao_entry($jogoFinal, count($historico) + 1));
    wc_salvar_historico_campeoes($historico);
}

function wc_render_historico_lista(?int $limite = null): string
{
    $historico = wc_carregar_historico_campeoes();
    if ($historico === []) {
        return '';
    }

    $itens = $limite !== null ? array_slice($historico, 0, $limite) : $historico;

    ob_start();
    ?>
    <ul class="divide-y divide-white/5">
        <?php foreach ($itens as $i => $h): ?>
        <li class="px-4 py-4 flex flex-wrap items-center gap-3 sm:gap-4 <?= $i === 0 ? 'bg-amber-950/20' : 'hover:bg-white/[0.02]' ?>">
            <span class="text-sm font-bold text-slate-500 w-8 shrink-0">#<?= (int) $h['edicao'] ?></span>
            <span class="shrink-0"><?= wc_flag($h['iso'], 40, 30) ?></span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-white text-lg"><?= htmlspecialchars($h['nome'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-sm text-slate-400 flex flex-wrap items-center gap-1.5">
                    Final <?= htmlspecialchars($h['final_placar'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-slate-600">·</span>
                    <span class="inline-flex items-center gap-1">
                        Vice <?= wc_flag($h['vice_iso'], 20, 15) ?>
                        <?= htmlspecialchars($h['vice_nome'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </p>
                <p class="text-xs text-slate-600 mt-0.5">Grupo <?= htmlspecialchars($h['grupo'], ENT_QUOTES, 'UTF-8') ?> · Força <?= (int) $h['forca'] ?></p>
            </div>
            <span class="text-xs text-slate-500 shrink-0 ml-auto"><?= htmlspecialchars($h['registrado_em'], ENT_QUOTES, 'UTF-8') ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php
    return (string) ob_get_clean();
}

function wc_apagar_historico_campeoes(): void
{
    wc_salvar_historico_campeoes([]);
}

function wc_contagem_campeoes(): int
{
    return count(wc_carregar_historico_campeoes());
}

function wc_ranking_from_entries(array $entries): array
{
    $rank = [];
    foreach ($entries as $h) {
        $nome = $h['nome'];
        if (!isset($rank[$nome])) {
            $rank[$nome] = ['nome' => $nome, 'iso' => $h['iso'], 'titulos' => 0, 'pct' => 0];
        }
        $rank[$nome]['titulos']++;
    }
    $total = count($entries) ?: 1;
    foreach ($rank as &$r) {
        $r['pct'] = round(($r['titulos'] / $total) * 100, 1);
    }
    unset($r);
    $lista = array_values($rank);
    usort($lista, static fn ($a, $b) => $b['titulos'] <=> $a['titulos']);
    return $lista;
}

function wc_render_lote_resultados(array $lote): string
{
    ob_start();
    ?>
    <section class="mb-8 space-y-4">
        <div class="bg-emerald-950/30 border border-emerald-500/30 rounded-2xl px-5 py-4">
            <h2 class="text-xl font-bold text-emerald-400">Simulação em lote concluída</h2>
            <p class="text-sm text-slate-400 mt-1">
                <?= (int) $lote['quantidade'] ?> edição(ões) · Perfil <?= htmlspecialchars($lote['perfil_label'], ENT_QUOTES, 'UTF-8') ?>
                · <?= $lote['modo'] === 'cop2026' ? 'Grupos Copa 2026' : 'Sorteio aleatório' ?>
            </p>
        </div>

        <div class="bg-pitch-800 rounded-2xl border border-white/10 overflow-hidden">
            <div class="px-4 py-3 border-b border-white/10">
                <h3 class="font-bold text-gold-400">Ranking deste lote</h3>
            </div>
            <ul class="divide-y divide-white/5">
                <?php foreach ($lote['ranking'] as $i => $r): ?>
                <li class="px-4 py-3 flex items-center gap-3">
                    <span class="text-slate-500 font-bold w-6"><?= $i + 1 ?>º</span>
                    <?= wc_flag($r['iso'], 28, 21) ?>
                    <span class="flex-1 font-semibold"><?= htmlspecialchars($r['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-gold-400 font-bold"><?= $r['titulos'] ?> títulos</span>
                    <span class="text-xs text-slate-500"><?= $r['pct'] ?>%</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="bg-pitch-800 rounded-2xl border border-white/10 overflow-hidden">
            <div class="px-4 py-3 border-b border-white/10 flex justify-between items-center">
                <h3 class="font-bold text-white">Campeões simulados</h3>
                <span class="text-xs text-slate-500"><?= count($lote['resultados']) ?> resultados</span>
            </div>
            <div class="max-h-[480px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="text-slate-500 text-xs sticky top-0 bg-pitch-800 border-b border-white/10">
                        <tr>
                            <th class="text-left py-2 pl-4">Ed.</th>
                            <th class="text-left py-2">Campeão</th>
                            <th class="text-left py-2 hidden sm:table-cell">Final</th>
                            <th class="text-left py-2 hidden md:table-cell">Vice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($lote['resultados'] as $h): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="py-2 pl-4 text-slate-500 font-mono text-xs">#<?= (int) $h['edicao'] ?></td>
                            <td class="py-2 pr-2"><?= wc_time_cell(['nome' => $h['nome'], 'iso' => $h['iso']], true) ?></td>
                            <td class="py-2 text-slate-400 hidden sm:table-cell"><?= htmlspecialchars($h['final_placar'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-2 hidden md:table-cell">
                                <span class="inline-flex items-center gap-1 text-slate-400">
                                    <?= wc_flag($h['vice_iso'], 18, 13) ?>
                                    <?= htmlspecialchars($h['vice_nome'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function wc_ranking_titulos(): array
{
    $rank = [];
    foreach (wc_carregar_historico_campeoes() as $h) {
        $nome = $h['nome'];
        if (!isset($rank[$nome])) {
            $rank[$nome] = [
                'nome'    => $nome,
                'iso'     => $h['iso'],
                'titulos' => 0,
            ];
        }
        $rank[$nome]['titulos']++;
    }
    $lista = array_values($rank);
    usort($lista, static fn ($a, $b) => $b['titulos'] <=> $a['titulos']);
    return $lista;
}

function wc_render_ranking_titulos(): string
{
    $rank = wc_ranking_titulos();
    if ($rank === []) {
        return '';
    }
    ob_start();
    ?>
    <section class="bg-pitch-800 rounded-2xl border border-white/10 overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-white/10">
            <h3 class="font-bold text-gold-400">Ranking de títulos</h3>
            <p class="text-xs text-slate-500">Seleções com mais conquistas no simulador</p>
        </div>
        <ul class="divide-y divide-white/5">
            <?php foreach ($rank as $i => $r): ?>
            <li class="px-4 py-3 flex items-center gap-3">
                <span class="text-lg font-black text-slate-600 w-6"><?= $i + 1 ?>º</span>
                <?= wc_flag($r['iso'], 32, 24) ?>
                <span class="font-semibold text-white flex-1"><?= htmlspecialchars($r['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="text-gold-400 font-bold"><?= $r['titulos'] ?>× 🏆</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
    return (string) ob_get_clean();
}

// ---------------------------------------------------------------------------
// Simulação de partidas
// ---------------------------------------------------------------------------

function wc_gols_tempo_regular(int $efCasa, int $efFora, float $fatorGols): array
{
    $total = $efCasa + $efFora;
    $baseCasa = ($efCasa / $total) * $fatorGols;
    $baseFora = ($efFora / $total) * $fatorGols;
    $golsCasa = max(0, (int) round($baseCasa + random_int(-1, 2)));
    $golsFora = max(0, (int) round($baseFora + random_int(-1, 2)));
    return [min($golsCasa, 6), min($golsFora, 6)];
}

/**
 * Simula uma partida com base na força + fator zebra (aleatoriedade).
 *
 * @return array{casa: int, fora: int, pen_casa: ?int, pen_fora: ?int, vencedor_id: string}
 */
function wc_simular_partida(array $timeCasa, array $timeFora, bool $mataMata = false, ?array $cfg = null): array
{
    $cfg = $cfg ?? wc_perfil_config();
    $zebraMax = $cfg['zebra'];
    $zebraCasa = random_int(-$zebraMax, $zebraMax);
    $zebraFora = random_int(-$zebraMax, $zebraMax);
    $efCasa = max(40, $timeCasa['forca'] + $zebraCasa);
    $efFora = max(40, $timeFora['forca'] + $zebraFora);

    [$golsCasa, $golsFora] = wc_gols_tempo_regular($efCasa, $efFora, $cfg['gols']);

    $apCasa = null;
    $apFora = null;
    $penCasa = null;
    $penFora = null;
    $vencedorId = $golsCasa > $golsFora ? $timeCasa['id'] : ($golsFora > $golsCasa ? $timeFora['id'] : '');

    if ($mataMata && $golsCasa === $golsFora) {
        [$apCasa, $apFora] = wc_gols_tempo_regular($efCasa, $efFora, $cfg['gols'] * 0.45);
        $apCasa = min($apCasa, 3);
        $apFora = min($apFora, 3);
        $totalCasa = $golsCasa + $apCasa;
        $totalFora = $golsFora + $apFora;
        if ($totalCasa > $totalFora) {
            $vencedorId = $timeCasa['id'];
        } elseif ($totalFora > $totalCasa) {
            $vencedorId = $timeFora['id'];
        } else {
            [$penCasa, $penFora, $vencedorId] = wc_simular_penaltis($timeCasa, $timeFora, $efCasa, $efFora);
        }
    }

    return [
        'casa'     => $golsCasa,
        'fora'     => $golsFora,
        'ap_casa'  => $apCasa,
        'ap_fora'  => $apFora,
        'pen_casa' => $penCasa,
        'pen_fora' => $penFora,
        'vencedor_id' => $vencedorId,
    ];
}

/** Disputa de pênaltis até haver vencedor. */
function wc_simular_penaltis(array $timeCasa, array $timeFora, int $efCasa, int $efFora): array
{
    $probCasa = $efCasa / ($efCasa + $efFora);
    $penCasa = 0;
    $penFora = 0;

    do {
        $penCasa = 0;
        $penFora = 0;
        for ($i = 0; $i < 5; $i++) {
            $penCasa += (random_int(1, 100) / 100) < $probCasa ? 1 : 0;
            $penFora += (random_int(1, 100) / 100) < (1 - $probCasa) ? 1 : 0;
        }
        while ($penCasa === $penFora) {
            $c = (random_int(1, 100) / 100) < $probCasa ? 1 : 0;
            $f = (random_int(1, 100) / 100) < (1 - $probCasa) ? 1 : 0;
            if ($c !== $f) {
                $penCasa += $c;
                $penFora += $f;
            }
        }
    } while ($penCasa === $penFora);

    $vencedorId = $penCasa > $penFora ? $timeCasa['id'] : $timeFora['id'];

    return [$penCasa, $penFora, $vencedorId];
}

function wc_buscar_time(string $id): ?array
{
    foreach ($_SESSION['selecoes'] as $t) {
        if ($t['id'] === $id) {
            return $t;
        }
    }
    return null;
}

function wc_buscar_por_grupo_pos(string $grupo, int $pos): ?array
{
    $tabela = $_SESSION['tabelas'][$grupo] ?? [];
    return $tabela[$pos - 1] ?? null;
}

// ---------------------------------------------------------------------------
// Fase de grupos — todos contra todos
// ---------------------------------------------------------------------------

function wc_simular_fase_grupos(): void
{
    [$partidas, $tabelas, $classificados] = wc_simular_fase_grupos_core($_SESSION['selecoes'], wc_perfil_config());
    $_SESSION['partidas_grupos'] = $partidas;
    $_SESSION['tabelas'] = $tabelas;
    $_SESSION['classificados'] = $classificados;
    $_SESSION['etapa'] = 'grupos_resultado';
}

/** @return array{0: array, 1: array, 2: array} */
function wc_simular_fase_grupos_core(array $selecoes, array $cfg): array
{
    $grupos = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $partidas = [];
    $stats = [];

    foreach ($selecoes as $t) {
        $stats[$t['id']] = [
            'time' => $t, 'pts' => 0, 'j' => 0, 'v' => 0, 'e' => 0, 'd' => 0,
            'gp' => 0, 'gc' => 0, 'sg' => 0,
        ];
    }

    foreach ($grupos as $grupo) {
        $doGrupo = array_values(array_filter($selecoes, static fn ($t) => $t['grupo'] === $grupo));
        for ($i = 0; $i < count($doGrupo); $i++) {
            for ($j = $i + 1; $j < count($doGrupo); $j++) {
                $casa = $doGrupo[$i];
                $fora = $doGrupo[$j];
                $r = wc_simular_partida($casa, $fora, false, $cfg);
                $partidas[] = ['grupo' => $grupo, 'casa' => $casa, 'fora' => $fora, 'gols' => $r];
                wc_atualizar_stats($stats, $casa['id'], $fora['id'], $r['casa'], $r['fora']);
            }
        }
    }

    $tabelas = wc_montar_tabelas($stats, $partidas);
    return [$partidas, $tabelas, wc_extrair_classificados_from($tabelas)];
}

function wc_atualizar_stats(array &$stats, string $idCasa, string $idFora, int $gc, int $gf): void
{
    $stats[$idCasa]['j']++;
    $stats[$idFora]['j']++;
    $stats[$idCasa]['gp'] += $gc;
    $stats[$idCasa]['gc'] += $gf;
    $stats[$idFora]['gp'] += $gf;
    $stats[$idFora]['gc'] += $gc;

    if ($gc > $gf) {
        $stats[$idCasa]['pts'] += 3;
        $stats[$idCasa]['v']++;
        $stats[$idFora]['d']++;
    } elseif ($gc < $gf) {
        $stats[$idFora]['pts'] += 3;
        $stats[$idFora]['v']++;
        $stats[$idCasa]['d']++;
    } else {
        $stats[$idCasa]['pts']++;
        $stats[$idFora]['pts']++;
        $stats[$idCasa]['e']++;
        $stats[$idFora]['e']++;
    }

    $stats[$idCasa]['sg'] = $stats[$idCasa]['gp'] - $stats[$idCasa]['gc'];
    $stats[$idFora]['sg'] = $stats[$idFora]['gp'] - $stats[$idFora]['gc'];
}

function wc_stats_h2h(string $timeId, array $tiedIds, array $partidas, string $grupo): array
{
    $pts = 0;
    $gp = 0;
    $gc = 0;
    foreach ($partidas as $p) {
        if ($p['grupo'] !== $grupo) {
            continue;
        }
        $casaId = $p['casa']['id'];
        $foraId = $p['fora']['id'];
        if (!in_array($casaId, $tiedIds, true) || !in_array($foraId, $tiedIds, true)) {
            continue;
        }
        $golsCasa = $p['gols']['casa'];
        $golsFora = $p['gols']['fora'];
        if ($timeId === $casaId) {
            $gp += $golsCasa;
            $gc += $golsFora;
            if ($golsCasa > $golsFora) {
                $pts += 3;
            } elseif ($golsCasa === $golsFora) {
                $pts += 1;
            }
        } elseif ($timeId === $foraId) {
            $gp += $golsFora;
            $gc += $golsCasa;
            if ($golsFora > $golsCasa) {
                $pts += 3;
            } elseif ($golsFora === $golsCasa) {
                $pts += 1;
            }
        }
    }
    return ['pts' => $pts, 'gp' => $gp, 'gc' => $gc, 'sg' => $gp - $gc];
}

function wc_comparar_classificacao(array $a, array $b, array $linhas, array $partidas, string $grupo): int
{
    if ($a['pts'] !== $b['pts']) {
        return $b['pts'] <=> $a['pts'];
    }

    $tiedPts = $a['pts'];
    $tiedIds = array_values(array_map(
        static fn ($l) => $l['time']['id'],
        array_filter($linhas, static fn ($l) => $l['pts'] === $tiedPts)
    ));

    if (count($tiedIds) >= 2
        && in_array($a['time']['id'], $tiedIds, true)
        && in_array($b['time']['id'], $tiedIds, true)) {
        $h2hA = wc_stats_h2h($a['time']['id'], $tiedIds, $partidas, $grupo);
        $h2hB = wc_stats_h2h($b['time']['id'], $tiedIds, $partidas, $grupo);
        if ($h2hA['pts'] !== $h2hB['pts']) {
            return $h2hB['pts'] <=> $h2hA['pts'];
        }
        if ($h2hA['sg'] !== $h2hB['sg']) {
            return $h2hB['sg'] <=> $h2hA['sg'];
        }
        if ($h2hA['gp'] !== $h2hB['gp']) {
            return $h2hB['gp'] <=> $h2hA['gp'];
        }
    }

    if ($a['sg'] !== $b['sg']) {
        return $b['sg'] <=> $a['sg'];
    }
    return $b['gp'] <=> $a['gp'];
}

function wc_montar_tabelas(array $stats, array $partidas): array
{
    $tabelas = [];
    foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $g) {
        $linhas = array_values(array_filter($stats, static fn ($s) => $s['time']['grupo'] === $g));
        usort($linhas, static function ($a, $b) use ($linhas, $partidas, $g) {
            return wc_comparar_classificacao($a, $b, $linhas, $partidas, $g);
        });
        $tabelas[$g] = array_map(static fn ($s) => array_merge($s['time'], [
            'pts' => $s['pts'], 'j' => $s['j'], 'v' => $s['v'], 'e' => $s['e'], 'd' => $s['d'],
            'gp' => $s['gp'], 'gc' => $s['gc'], 'sg' => $s['sg'],
        ]), $linhas);
    }
    return $tabelas;
}

function wc_extrair_classificados(): array
{
    return wc_extrair_classificados_from($_SESSION['tabelas']);
}

function wc_extrair_classificados_from(array $tabelas): array
{
    $out = [];
    foreach ($tabelas as $grupo => $linhas) {
        $out[$grupo . '1'] = $linhas[0];
        $out[$grupo . '2'] = $linhas[1];
    }
    return $out;
}

function wc_pares_oitavas(array $classificados): array
{
    $c = $classificados;
    return [
        [$c['A1'], $c['B2']],
        [$c['C1'], $c['D2']],
        [$c['E1'], $c['F2']],
        [$c['G1'], $c['H2']],
        [$c['B1'], $c['A2']],
        [$c['D1'], $c['C2']],
        [$c['F1'], $c['E2']],
        [$c['H1'], $c['G2']],
    ];
}

// ---------------------------------------------------------------------------
// Mata-mata — chaveamento padrão Copa (32 times)
// ---------------------------------------------------------------------------

function wc_gerar_oitavas(): array
{
    return wc_simular_rodada(wc_pares_oitavas($_SESSION['classificados']), 'oitavas');
}

function wc_time_por_id(string $id, array $selecoes): ?array
{
    foreach ($selecoes as $t) {
        if ($t['id'] === $id) {
            return $t;
        }
    }
    return null;
}

function wc_simular_rodada(array $pares, string $fase, ?array $cfg = null, ?array $selecoes = null): array
{
    $cfg = $cfg ?? wc_perfil_config();
    $jogos = [];
    foreach ($pares as [$casa, $fora]) {
        $r = wc_simular_partida($casa, $fora, true, $cfg);
        $vencedor = $selecoes !== null
            ? wc_time_por_id($r['vencedor_id'], $selecoes)
            : wc_buscar_time($r['vencedor_id']);
        $jogos[] = [
            'fase'     => $fase,
            'casa'     => $casa,
            'fora'     => $fora,
            'gols'     => $r,
            'vencedor' => $vencedor,
        ];
    }
    return $jogos;
}

function wc_simular_copa_completa(string $perfil, string $modo): array
{
    $cfg = wc_perfil_config_from($perfil);
    $selecoes = wc_aplicar_sorteio(wc_selecoes_base(), $modo);
    [, , $classificados] = wc_simular_fase_grupos_core($selecoes, $cfg);

    $oitavas = wc_simular_rodada(wc_pares_oitavas($classificados), 'oitavas', $cfg, $selecoes);
    $quartas = wc_simular_rodada(wc_montar_proxima_rodada(wc_vencedores($oitavas)), 'quartas', $cfg, $selecoes);
    $semi = wc_simular_rodada(wc_montar_proxima_rodada(wc_vencedores($quartas)), 'semi', $cfg, $selecoes);
    $final = wc_simular_rodada(
        wc_montar_proxima_rodada(wc_vencedores($semi)),
        'final',
        $cfg,
        $selecoes
    );

    return $final[0];
}

function wc_simular_lote(int $quantidade, string $perfil, string $modo): array
{
    $quantidade = max(1, min(500, $quantidade));
    set_time_limit(max(30, $quantidade * 2));

    $historico = wc_carregar_historico_campeoes();
    $baseEdicao = count($historico);
    $resultados = [];

    for ($i = 0; $i < $quantidade; $i++) {
        $jogoFinal = wc_simular_copa_completa($perfil, $modo);
        $resultados[] = wc_build_campeao_entry($jogoFinal, $baseEdicao + $i + 1);
    }

    $historico = array_merge(array_reverse($resultados), $historico);
    wc_salvar_historico_campeoes($historico);

    $cfg = wc_perfil_config_from($perfil);
    return [
        'quantidade'   => $quantidade,
        'perfil'       => $perfil,
        'perfil_label' => $cfg['label'],
        'modo'         => $modo,
        'resultados'   => $resultados,
        'ranking'      => wc_ranking_from_entries($resultados),
    ];
}

function wc_vencedores(array $jogos): array
{
    return array_map(static fn ($j) => $j['vencedor'], $jogos);
}

function wc_montar_proxima_rodada(array $vencedores): array
{
    $pares = [];
    for ($i = 0; $i < count($vencedores); $i += 2) {
        $pares[] = [$vencedores[$i], $vencedores[$i + 1]];
    }
    return $pares;
}

// ---------------------------------------------------------------------------
// Controle de ações (POST)
// ---------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'apagar_historico') {
        wc_apagar_historico_campeoes();
        unset($_SESSION['lote_ultimo']);
        header('Location: index.php?p=historico&apagado=1');
        exit;
    }

    if ($acao === 'reiniciar') {
        $perfil = $_SESSION['perfil'] ?? 'equilibrado';
        $modo = $_SESSION['modo_sorteio'] ?? 'aleatorio';
        wc_inicializar_campeonato();
        $_SESSION['perfil'] = $perfil;
        $_SESSION['modo_sorteio'] = $modo;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    if ($acao === 'simular_lote') {
        $perfis = ['realista', 'equilibrado', 'caotico'];
        $modos = ['aleatorio', 'cop2026'];
        $perfil = in_array($_POST['perfil'] ?? '', $perfis, true) ? $_POST['perfil'] : ($_SESSION['perfil'] ?? 'equilibrado');
        $modo = in_array($_POST['modo_sorteio'] ?? '', $modos, true) ? $_POST['modo_sorteio'] : ($_SESSION['modo_sorteio'] ?? 'aleatorio');
        $_SESSION['perfil'] = $perfil;
        $_SESSION['modo_sorteio'] = $modo;
        $qtd = (int) ($_POST['quantidade'] ?? 10);
        $_SESSION['lote_ultimo'] = wc_simular_lote($qtd, $perfil, $modo);
        header('Location: index.php?p=historico');
        exit;
    }

    if ($acao === 'config' && ($_SESSION['etapa'] ?? '') === 'inicio') {
        $perfis = ['realista', 'equilibrado', 'caotico'];
        $modos = ['aleatorio', 'cop2026'];
        if (in_array($_POST['perfil'] ?? '', $perfis, true)) {
            $_SESSION['perfil'] = $_POST['perfil'];
        }
        if (in_array($_POST['modo_sorteio'] ?? '', $modos, true)) {
            $_SESSION['modo_sorteio'] = $_POST['modo_sorteio'];
        }
        $_SESSION['selecoes'] = wc_aplicar_sorteio(wc_selecoes_base());
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    match ($_SESSION['etapa']) {
        'inicio' => (function () {
            if (isset($_POST['perfil']) && in_array($_POST['perfil'], ['realista', 'equilibrado', 'caotico'], true)) {
                $_SESSION['perfil'] = $_POST['perfil'];
            }
            if (isset($_POST['modo_sorteio']) && in_array($_POST['modo_sorteio'], ['aleatorio', 'cop2026'], true)) {
                $_SESSION['modo_sorteio'] = $_POST['modo_sorteio'];
                $_SESSION['selecoes'] = wc_aplicar_sorteio(wc_selecoes_base());
            }
            wc_simular_fase_grupos();
        })(),
        'grupos_resultado' => (function () {
            $_SESSION['oitavas'] = wc_gerar_oitavas();
            $_SESSION['historico_chave']['oitavas'] = $_SESSION['oitavas'];
            $_SESSION['etapa'] = 'oitavas';
        })(),
        'oitavas' => (function () {
            $v = wc_vencedores($_SESSION['oitavas']);
            $_SESSION['quartas'] = wc_simular_rodada(wc_montar_proxima_rodada($v), 'quartas');
            $_SESSION['historico_chave']['quartas'] = $_SESSION['quartas'];
            $_SESSION['etapa'] = 'quartas';
        })(),
        'quartas' => (function () {
            $v = wc_vencedores($_SESSION['quartas']);
            $_SESSION['semi'] = wc_simular_rodada(wc_montar_proxima_rodada($v), 'semi');
            $_SESSION['historico_chave']['semi'] = $_SESSION['semi'];
            $_SESSION['etapa'] = 'semi';
        })(),
        'semi' => (function () {
            $v = wc_vencedores($_SESSION['semi']);
            $final = wc_simular_rodada([[$v[0], $v[1]]], 'final');
            $_SESSION['final'] = $final[0];
            $_SESSION['historico_chave']['final'] = $_SESSION['final'];
            $_SESSION['campeao'] = $final[0]['vencedor'];
            wc_registrar_campeao($_SESSION['campeao'], $final[0]);
            $_SESSION['etapa'] = 'campeao';
        })(),
        default => null,
    };

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ---------------------------------------------------------------------------
// Helpers de UI
// ---------------------------------------------------------------------------

function wc_flag(string $iso, int $w = 24, int $h = 18): string
{
    $isoRaw = strtolower($iso);
    $isoSafe = htmlspecialchars($isoRaw, ENT_QUOTES, 'UTF-8');

    // Bandeiras locais (pasta flags/) — preferível na hospedagem
    $localPath = __DIR__ . '/flags/' . $isoRaw . '.png';
    if (is_file($localPath)) {
        $src = 'flags/' . $isoRaw . '.png';
    } else {
        // CDN flagcdn — tamanhos fixos 4:3 (24x16 não existe)
        $cdnSize = $w >= 48 ? '80x60' : '24x18';
        $src = 'https://flagcdn.com/' . $cdnSize . '/' . $isoSafe . '.png';
    }

    return sprintf(
        '<img src="%s" width="%d" height="%d" alt="" class="inline-block rounded-sm object-cover shadow-sm ring-1 ring-white/10" loading="lazy" referrerpolicy="no-referrer">',
        $src,
        $w,
        $h
    );
}

function wc_time_cell(array $time, bool $destaque = false): string
{
    $cls = $destaque ? 'font-semibold text-emerald-400' : 'text-slate-200';
    return sprintf(
        '<span class="inline-flex items-center gap-2 %s"><span class="shrink-0">%s</span><span>%s</span></span>',
        $cls,
        wc_flag($time['iso']),
        htmlspecialchars($time['nome'], ENT_QUOTES, 'UTF-8')
    );
}

function wc_placar_linha(array $jogo): string
{
    $g = $jogo['gols'];
    $txt = $g['casa'] . ' × ' . $g['fora'];
    if ($g['ap_casa'] !== null || $g['ap_fora'] !== null) {
        $txt .= ' <span class="text-xs text-slate-400">('
            . ($g['casa'] + ($g['ap_casa'] ?? 0)) . '×' . ($g['fora'] + ($g['ap_fora'] ?? 0))
            . ' ap.)</span>';
    }
    if ($g['pen_casa'] !== null) {
        $txt .= ' <span class="text-xs text-slate-500">(' . $g['pen_casa'] . '-' . $g['pen_fora'] . ' pên.)</span>';
    }
    return $txt;
}

function wc_render_card_jogo(array $j, bool $compacto = false): string
{
    $cls = $compacto ? 'p-2 text-xs' : 'p-4';
    ob_start();
    ?>
    <div class="<?= $cls ?> rounded-xl bg-pitch-700/50 border border-white/5 space-y-1">
        <div class="flex items-center justify-between gap-2">
            <div class="flex-1 min-w-0"><?= wc_time_cell($j['casa']) ?></div>
            <span class="font-black tabular-nums shrink-0"><?= wc_placar_linha($j) ?></span>
            <div class="flex-1 min-w-0 flex justify-end"><?= wc_time_cell($j['fora']) ?></div>
        </div>
        <?php if (!$compacto): ?>
        <p class="text-center text-xs text-emerald-500/80 pt-1 border-t border-white/5">
            ✓ <?= wc_time_cell($j['vencedor'], true) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function wc_render_chave_visual(): string
{
    $oitavas = $_SESSION['historico_chave']['oitavas'] ?? $_SESSION['oitavas'] ?? [];
    $quartas = $_SESSION['historico_chave']['quartas'] ?? $_SESSION['quartas'] ?? [];
    $semi = $_SESSION['historico_chave']['semi'] ?? $_SESSION['semi'] ?? [];
    $final = isset($_SESSION['final']) ? [$_SESSION['final']] : [];

    if ($oitavas === []) {
        return '';
    }

    ob_start();
    ?>
    <section class="bg-pitch-800 rounded-2xl border border-white/10 p-4 md:p-6 overflow-x-auto">
        <h2 class="text-lg font-bold text-emerald-400 mb-4">Chave do mata-mata</h2>
        <div class="flex gap-3 min-w-[720px]">
            <?php
            $colunas = [
                ['titulo' => 'Oitavas', 'jogos' => $oitavas],
                ['titulo' => 'Quartas', 'jogos' => $quartas],
                ['titulo' => 'Semi', 'jogos' => $semi],
                ['titulo' => 'Final', 'jogos' => $final],
            ];
            foreach ($colunas as $col):
                if ($col['jogos'] === []) {
                    continue;
                }
            ?>
            <div class="flex-1 min-w-[160px] flex flex-col gap-2">
                <p class="text-xs font-bold text-slate-500 uppercase text-center mb-1"><?= $col['titulo'] ?></p>
                <?php foreach ($col['jogos'] as $j): ?>
                <?= wc_render_card_jogo($j, true) ?>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function wc_botao_acao(): array
{
    return match ($_SESSION['etapa']) {
        'inicio'           => ['label' => 'Simular Fase de Grupos', 'acao' => 'avancar'],
        'grupos_resultado' => ['label' => 'Simular Oitavas de Final', 'acao' => 'avancar'],
        'oitavas', 'quartas', 'semi' => ['label' => 'Simular Próxima Fase', 'acao' => 'avancar'],
        'campeao'          => ['label' => 'Reiniciar Campeonato', 'acao' => 'reiniciar'],
        default            => ['label' => 'Continuar', 'acao' => 'avancar'],
    };
}

$etapa = $_SESSION['etapa'];
$btn = wc_botao_acao();
$pagina = ($_GET['p'] ?? 'jogo') === 'historico' ? 'historico' : 'jogo';
$historicoApagado = isset($_GET['apagado']);
$totalCampeoes = wc_contagem_campeoes();
$perfilAtual = $_SESSION['perfil'] ?? 'equilibrado';
$modoAtual = $_SESSION['modo_sorteio'] ?? 'aleatorio';
$perfilLabel = wc_perfil_config()['label'];

$titulos = [
    'inicio'           => 'Sorteio dos Grupos',
    'grupos_resultado' => 'Resultados — Fase de Grupos',
    'oitavas'          => 'Oitavas de Final',
    'quartas'          => 'Quartas de Final',
    'semi'             => 'Semifinais',
    'final'            => 'Final',
    'campeao'          => 'Campeão da Copa',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>World Cup Simulator — MVLopes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pitch: { 900: '#0a1628', 800: '#0f2137', 700: '#152a45' },
                        gold: { 400: '#fbbf24', 500: '#f59e0b' },
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6,  1) infinite',
                        'bounce-slow': 'bounce 2s infinite',
                    },
                },
            },
        };
    </script>
    <style>
        @keyframes crown-glow {
            0%, 100% { box-shadow: 0 0 30px rgba(251, 191, 36, 0.35); }
            50% { box-shadow: 0 0 60px rgba(251, 191, 36, 0.6); }
        }
        .champion-glow { animation: crown-glow 2.5s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen bg-pitch-900 text-slate-100 antialiased">

<!-- Header -->
<header class="border-b border-white/10 bg-pitch-800/90 backdrop-blur sticky top-0 z-20">
    <div class="max-w-6xl mx-auto px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-widest text-emerald-500/80 font-medium">MVLopes</p>
                <h1 class="text-xl md:text-2xl font-bold text-white">World Cup Simulator</h1>
            </div>
            <nav class="flex items-center gap-1 p-1 rounded-xl bg-black/30 border border-white/10">
                <a href="index.php"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $pagina === 'jogo' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                    Simulador
                </a>
                <a href="index.php?p=historico"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition <?= $pagina === 'historico' ? 'bg-gold-500 text-pitch-900 shadow font-bold' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                    🏆 Hall dos Campeões
                    <?php if ($totalCampeoes > 0): ?>
                    <span class="text-xs px-1.5 py-0.5 rounded-full <?= $pagina === 'historico' ? 'bg-pitch-900/30' : 'bg-gold-500/20 text-gold-400' ?>">
                        <?= $totalCampeoes ?>
                    </span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>
        <?php if ($pagina === 'jogo'): ?>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-400"><?= htmlspecialchars($titulos[$etapa] ?? 'Copa do Mundo', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="flex items-center gap-1.5 flex-wrap">
                <?php
                $fases = ['inicio', 'grupos_resultado', 'oitavas', 'quartas', 'semi', 'campeao'];
                $labels = ['Início', 'Grupos', 'Oitavas', 'Quartas', 'Semi', 'Campeão'];
                foreach ($fases as $i => $f):
                ?>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $f === $etapa ? 'bg-emerald-600/80 text-white' : 'bg-white/5 text-slate-500' ?>">
                    <?= $labels[$i] ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <p class="mt-2 text-sm text-slate-400">Histórico completo de todas as edições simuladas</p>
        <?php endif; ?>
    </div>
</header>

<?php if ($pagina === 'jogo'): ?>
<!-- Botão principal + opções (início) -->
<div class="max-w-6xl mx-auto px-4 py-5 space-y-4">
    <?php if ($etapa === 'inicio'): ?>
    <form method="post" class="bg-pitch-800 rounded-2xl border border-white/10 p-4 grid sm:grid-cols-2 gap-4">
        <input type="hidden" name="acao" value="config">
        <div>
            <label class="text-xs text-slate-500 uppercase tracking-wide">Perfil de simulação</label>
            <select name="perfil" class="mt-1 w-full rounded-lg bg-pitch-900 border border-white/10 px-3 py-2 text-sm">
                <option value="realista" <?= $perfilAtual === 'realista' ? 'selected' : '' ?>>Realista — menos zebras</option>
                <option value="equilibrado" <?= $perfilAtual === 'equilibrado' ? 'selected' : '' ?>>Equilibrado — padrão</option>
                <option value="caotico" <?= $perfilAtual === 'caotico' ? 'selected' : '' ?>>Caótico — muitas surpresas</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500 uppercase tracking-wide">Sorteio dos grupos</label>
            <select name="modo_sorteio" class="mt-1 w-full rounded-lg bg-pitch-900 border border-white/10 px-3 py-2 text-sm">
                <option value="aleatorio" <?= $modoAtual === 'aleatorio' ? 'selected' : '' ?>>Aleatório a cada copa</option>
                <option value="cop2026" <?= $modoAtual === 'cop2026' ? 'selected' : '' ?>>Copa 2026 (grupos fixos)</option>
            </select>
        </div>
        <div class="sm:col-span-2 flex justify-center">
            <button type="submit" class="px-5 py-2 rounded-lg bg-white/10 hover:bg-white/15 text-sm font-medium">
                Aplicar configurações
            </button>
        </div>
    </form>

    <form method="post" class="bg-pitch-800 rounded-2xl border border-amber-500/20 p-4 space-y-4">
        <input type="hidden" name="acao" value="simular_lote">
        <input type="hidden" name="perfil" value="<?= htmlspecialchars($perfilAtual, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="modo_sorteio" value="<?= htmlspecialchars($modoAtual, ENT_QUOTES, 'UTF-8') ?>">
        <div>
            <h3 class="font-bold text-amber-400">Simulação automática em lote</h3>
            <p class="text-xs text-slate-500 mt-1">Simula várias copas completas de uma vez e exibe todos os campeões no Hall.</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[140px]">
                <label class="text-xs text-slate-500 uppercase tracking-wide">Quantidade de edições</label>
                <input type="number" name="quantidade" min="1" max="500" value="100"
                    class="mt-1 w-full rounded-lg bg-pitch-900 border border-white/10 px-3 py-2 text-sm font-semibold" />
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ([10, 50, 100, 200] as $preset): ?>
                <button type="submit" name="quantidade" value="<?= $preset ?>"
                    class="px-3 py-2 rounded-lg bg-white/10 hover:bg-amber-500/20 text-sm">
                    <?= $preset ?>
                </button>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-pitch-900 font-bold text-sm">
                Simular edições
            </button>
        </div>
    </form>
    <?php endif; ?>
    <form method="post" class="flex flex-col items-center gap-2">
        <?php if ($etapa === 'inicio'): ?>
        <input type="hidden" name="perfil" value="<?= htmlspecialchars($perfilAtual, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="modo_sorteio" value="<?= htmlspecialchars($modoAtual, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <input type="hidden" name="acao" value="<?= htmlspecialchars($btn['acao'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit"
            class="px-8 py-3.5 rounded-xl font-bold text-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-900/40 transition transform hover:scale-[1.02] active:scale-[0.98] <?= $etapa === 'campeao' ? 'bg-amber-600 hover:bg-amber-500 shadow-amber-900/40' : '' ?>">
            <?= htmlspecialchars($btn['label'], ENT_QUOTES, 'UTF-8') ?>
        </button>
        <?php if ($etapa !== 'inicio' && $etapa !== 'campeao'): ?>
        <p class="text-xs text-slate-600">Perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </form>
</div>

<main class="max-w-6xl mx-auto px-4 pb-16 space-y-8">

<?php if ($etapa === 'inicio'): ?>
    <!-- Grupos sorteados -->
    <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach (['A','B','C','D','E','F','G','H'] as $g): ?>
        <div class="bg-pitch-800 rounded-xl border border-white/10 overflow-hidden">
            <div class="px-4 py-2 bg-emerald-900/40 border-b border-white/10 font-bold text-emerald-400">Grupo <?= $g ?></div>
            <ul class="divide-y divide-white/5">
                <?php foreach (array_filter($_SESSION['selecoes'], static fn($t) => $t['grupo'] === $g) as $t): ?>
                <li class="px-4 py-2.5 flex items-center justify-between text-sm">
                    <?= wc_time_cell($t) ?>
                    <span class="text-xs text-slate-500"><?= $t['forca'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </section>
    <p class="text-center text-slate-500 text-sm">
        32 seleções · Força FIFA ·
        <?= $modoAtual === 'cop2026' ? 'Grupos Copa 2026' : 'Sorteio aleatório' ?> ·
        Perfil <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>
    </p>

<?php elseif ($etapa === 'grupos_resultado'): ?>
    <!-- Tabelas de classificação -->
    <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($_SESSION['tabelas'] as $grupo => $linhas): ?>
        <div class="bg-pitch-800 rounded-xl border border-white/10 overflow-hidden">
            <div class="px-3 py-2 bg-emerald-900/40 border-b border-white/10 font-bold text-emerald-400 text-sm">Grupo <?= $grupo ?></div>
            <table class="w-full text-xs">
                <thead class="text-slate-500 border-b border-white/5">
                    <tr>
                        <th class="text-left py-2 pl-3">Seleção</th>
                        <th>Pts</th><th>J</th><th>SG</th><th>GP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linhas as $i => $row): ?>
                    <tr class="border-b border-white/5 last:border-0 <?= $i < 2 ? 'bg-emerald-950/30' : '' ?>">
                        <td class="py-2 pl-3 pr-1"><?= wc_time_cell($row, $i < 2) ?></td>
                        <td class="text-center font-bold"><?= $row['pts'] ?></td>
                        <td class="text-center text-slate-400"><?= $row['j'] ?></td>
                        <td class="text-center"><?= $row['sg'] >= 0 ? '+' . $row['sg'] : $row['sg'] ?></td>
                        <td class="text-center text-slate-400"><?= $row['gp'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- Jogos da fase de grupos -->
    <section class="bg-pitch-800 rounded-2xl border border-white/10 p-5">
        <h2 class="text-lg font-bold text-emerald-400 mb-4">Jogos da fase de grupos</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach (['A','B','C','D','E','F','G','H'] as $g): ?>
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase mb-2">Grupo <?= $g ?></p>
                <div class="space-y-2">
                    <?php foreach (array_filter($_SESSION['partidas_grupos'], static fn($p) => $p['grupo'] === $g) as $p): ?>
                    <div class="text-xs p-2 rounded-lg bg-pitch-700/40 border border-white/5">
                        <div class="flex justify-between items-center gap-1">
                            <span class="truncate"><?= wc_flag($p['casa']['iso'], 18, 13) ?> <?= htmlspecialchars($p['casa']['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="font-bold tabular-nums shrink-0"><?= $p['gols']['casa'] ?>×<?= $p['gols']['fora'] ?></span>
                            <span class="truncate text-right"><?= htmlspecialchars($p['fora']['nome'], ENT_QUOTES, 'UTF-8') ?> <?= wc_flag($p['fora']['iso'], 18, 13) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-slate-600 mt-4 text-center">Desempate: pontos → confronto direto → saldo → gols pró</p>
    </section>

<?php elseif (in_array($etapa, ['oitavas', 'quartas', 'semi'], true)): ?>
    <?php
    $jogosAtual = $_SESSION[$etapa] ?? [];
    $labelFase = ['oitavas' => 'Oitavas', 'quartas' => 'Quartas', 'semi' => 'Semifinais'][$etapa];
    ?>
    <section class="bg-pitch-800 rounded-2xl border border-white/10 p-6">
        <h2 class="text-lg font-bold text-emerald-400 mb-4"><?= $labelFase ?></h2>
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($jogosAtual as $j): ?>
            <?= wc_render_card_jogo($j) ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?= wc_render_chave_visual() ?>

<?php elseif ($etapa === 'campeao'): ?>
    <?php $camp = $_SESSION['campeao']; ?>
    <!-- Campeão -->
    <section class="text-center py-8">
        <div class="inline-block champion-glow rounded-3xl bg-gradient-to-b from-amber-900/40 to-pitch-800 border-2 border-gold-400/50 px-10 py-10 animate-pulse-slow">
            <p class="text-sm uppercase tracking-[0.3em] text-gold-400 mb-4">🏆 Campeão do Mundo</p>
            <div class="flex flex-col items-center gap-4">
                <?= wc_flag($camp['iso'], 80, 60) ?>
                <h2 class="text-4xl md:text-5xl font-black text-white"><?= htmlspecialchars($camp['nome'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-slate-400">Grupo <?= $camp['grupo'] ?> · Força <?= $camp['forca'] ?></p>
            </div>
        </div>
    </section>

    <?= wc_render_chave_visual() ?>

    <!-- Chaveamento completo (lista) -->
    <section class="space-y-6">
        <?php
        $fasesChave = [
            'oitavas' => 'Oitavas de Final',
            'quartas' => 'Quartas de Final',
            'semi'    => 'Semifinais',
            'final'   => 'Final',
        ];
        foreach ($fasesChave as $key => $titulo):
            $lista = $key === 'final'
                ? (isset($_SESSION['final']) ? [$_SESSION['final']] : [])
                : ($_SESSION['historico_chave'][$key] ?? $_SESSION[$key] ?? []);
            if (empty($lista)) continue;
        ?>
        <div class="bg-pitch-800 rounded-xl border border-white/10 p-5">
            <h3 class="font-bold text-slate-300 mb-3"><?= $titulo ?></h3>
            <div class="grid md:grid-cols-2 gap-2 text-sm">
                <?php foreach ($lista as $j): ?>
                <div class="flex items-center gap-2 p-3 rounded-lg bg-pitch-700/40">
                    <?= wc_time_cell($j['casa']) ?>
                    <span class="font-bold tabular-nums mx-2"><?= wc_placar_linha($j) ?></span>
                    <?= wc_time_cell($j['fora']) ?>
                    <span class="ml-auto text-emerald-500 text-xs">✓</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

</main>

<?php else: ?>
<!-- Página Hall dos Campeões -->
<main class="max-w-6xl mx-auto px-4 py-8 pb-16">
    <?php
    $historico = wc_carregar_historico_campeoes();
    $loteUltimo = $_SESSION['lote_ultimo'] ?? null;
    if ($loteUltimo) {
        echo wc_render_lote_resultados($loteUltimo);
        unset($_SESSION['lote_ultimo']);
    }
    ?>
    <?php if ($historicoApagado): ?>
    <p class="mb-6 text-sm text-emerald-400 bg-emerald-950/40 border border-emerald-500/30 rounded-xl px-4 py-3">
        Histórico de campeões apagado com sucesso.
    </p>
    <?php endif; ?>
    <?php if ($historico === [] && !$loteUltimo && !$historicoApagado): ?>
    <section class="text-center py-20 bg-pitch-800 rounded-2xl border border-white/10">
        <p class="text-5xl mb-4 opacity-40">🏆</p>
        <h2 class="text-xl font-bold text-white mb-2">Nenhum campeão ainda</h2>
        <p class="text-slate-500 text-sm mb-6">Simule uma copa completa para registrar o primeiro título.</p>
        <a href="index.php" class="inline-block px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold transition">
            Ir para o Simulador
        </a>
    </section>
    <?php elseif ($historico !== []): ?>
    <?= wc_render_ranking_titulos() ?>
    <section class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gold-400">Hall dos Campeões</h2>
            <p class="text-sm text-slate-500"><?= count($historico) ?> edição(ões) registrada(s) · mais recente primeiro</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="index.php" class="text-sm text-emerald-400 hover:text-emerald-300 font-medium">← Voltar ao simulador</a>
            <form method="post" class="inline"
                onsubmit="return confirm('Apagar todo o histórico de campeões? Esta ação não pode ser desfeita.');">
                <input type="hidden" name="acao" value="apagar_historico">
                <button type="submit"
                    class="text-sm px-4 py-2 rounded-lg border border-red-500/40 text-red-400 hover:bg-red-950/40 transition">
                    Apagar histórico
                </button>
            </form>
        </div>
    </section>
    <section class="bg-pitch-800 rounded-2xl border border-white/10 overflow-hidden">
        <?= wc_render_historico_lista() ?>
    </section>
    <?php endif; ?>
</main>
<?php endif; ?>

<footer class="text-center text-xs text-slate-600 py-6 border-t border-white/5">
    World Cup Simulator · Bandeiras via <a href="https://flagcdn.com" class="text-slate-500 hover:text-emerald-500">flagcdn.com</a>
    · Simulação PHP + <?= date('Y') ?> MVLopes
</footer>

</body>
</html>
