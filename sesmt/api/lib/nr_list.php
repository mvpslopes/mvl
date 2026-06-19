<?php

declare(strict_types=1);

/**
 * Normas Regulamentadoras — lista oficial MTE (NR-1 a NR-38).
 * NR-2 e NR-27 revogadas (ativo = 0 no seed).
 *
 * @return array<int, array{codigo: string, nome: string, ativo: int}>
 */
function sesmt_nr_list(): array
{
    return [
        ['codigo' => 'NR-1', 'nome' => 'NR-1 — Disposições Gerais e Gerenciamento de Riscos Ocupacionais', 'ativo' => 1],
        ['codigo' => 'NR-2', 'nome' => 'NR-2 — Inspeção Prévia (Revogada)', 'ativo' => 0],
        ['codigo' => 'NR-3', 'nome' => 'NR-3 — Embargo ou Interdição', 'ativo' => 1],
        ['codigo' => 'NR-4', 'nome' => 'NR-4 — SESMT', 'ativo' => 1],
        ['codigo' => 'NR-5', 'nome' => 'NR-5 — CIPA', 'ativo' => 1],
        ['codigo' => 'NR-6', 'nome' => 'NR-6 — Equipamento de Proteção Individual (EPI)', 'ativo' => 1],
        ['codigo' => 'NR-7', 'nome' => 'NR-7 — PCMSO', 'ativo' => 1],
        ['codigo' => 'NR-8', 'nome' => 'NR-8 — Edificações', 'ativo' => 1],
        ['codigo' => 'NR-9', 'nome' => 'NR-9 — Avaliação e Controle das Exposições Ocupacionais', 'ativo' => 1],
        ['codigo' => 'NR-10', 'nome' => 'NR-10 — Segurança em Instalações e Serviços em Eletricidade', 'ativo' => 1],
        ['codigo' => 'NR-11', 'nome' => 'NR-11 — Transporte, Movimentação, Armazenagem e Manuseio de Materiais', 'ativo' => 1],
        ['codigo' => 'NR-12', 'nome' => 'NR-12 — Segurança no Trabalho em Máquinas e Equipamentos', 'ativo' => 1],
        ['codigo' => 'NR-13', 'nome' => 'NR-13 — Caldeiras, Vasos de Pressão e Tubulações', 'ativo' => 1],
        ['codigo' => 'NR-14', 'nome' => 'NR-14 — Fornos', 'ativo' => 1],
        ['codigo' => 'NR-15', 'nome' => 'NR-15 — Atividades e Operações Insalubres', 'ativo' => 1],
        ['codigo' => 'NR-16', 'nome' => 'NR-16 — Atividades e Operações Perigosas', 'ativo' => 1],
        ['codigo' => 'NR-17', 'nome' => 'NR-17 — Ergonomia', 'ativo' => 1],
        ['codigo' => 'NR-18', 'nome' => 'NR-18 — Condições e Meio Ambiente na Indústria da Construção', 'ativo' => 1],
        ['codigo' => 'NR-19', 'nome' => 'NR-19 — Explosivos', 'ativo' => 1],
        ['codigo' => 'NR-20', 'nome' => 'NR-20 — Segurança com Inflamáveis e Combustíveis', 'ativo' => 1],
        ['codigo' => 'NR-21', 'nome' => 'NR-21 — Trabalhos a Céu Aberto', 'ativo' => 1],
        ['codigo' => 'NR-22', 'nome' => 'NR-22 — Segurança e Saúde Ocupacional na Mineração', 'ativo' => 1],
        ['codigo' => 'NR-23', 'nome' => 'NR-23 — Proteção Contra Incêndios', 'ativo' => 1],
        ['codigo' => 'NR-24', 'nome' => 'NR-24 — Condições Sanitárias e de Conforto nos Locais de Trabalho', 'ativo' => 1],
        ['codigo' => 'NR-25', 'nome' => 'NR-25 — Resíduos Industriais', 'ativo' => 1],
        ['codigo' => 'NR-26', 'nome' => 'NR-26 — Sinalização de Segurança', 'ativo' => 1],
        ['codigo' => 'NR-27', 'nome' => 'NR-27 — Registro Profissional do Técnico de Segurança (Revogada)', 'ativo' => 0],
        ['codigo' => 'NR-28', 'nome' => 'NR-28 — Fiscalização e Penalidades', 'ativo' => 1],
        ['codigo' => 'NR-29', 'nome' => 'NR-29 — Segurança e Saúde no Trabalho Portuário', 'ativo' => 1],
        ['codigo' => 'NR-30', 'nome' => 'NR-30 — Segurança e Saúde no Trabalho Aquaviário', 'ativo' => 1],
        ['codigo' => 'NR-31', 'nome' => 'NR-31 — Agricultura, Pecuária, Silvicultura e Aquicultura', 'ativo' => 1],
        ['codigo' => 'NR-32', 'nome' => 'NR-32 — Segurança e Saúde em Serviços de Saúde', 'ativo' => 1],
        ['codigo' => 'NR-33', 'nome' => 'NR-33 — Segurança e Saúde em Espaços Confinados', 'ativo' => 1],
        ['codigo' => 'NR-34', 'nome' => 'NR-34 — Construção, Reparação e Desmonte Naval', 'ativo' => 1],
        ['codigo' => 'NR-35', 'nome' => 'NR-35 — Trabalho em Altura', 'ativo' => 1],
        ['codigo' => 'NR-36', 'nome' => 'NR-36 — Abate e Processamento de Carnes e Derivados', 'ativo' => 1],
        ['codigo' => 'NR-37', 'nome' => 'NR-37 — Segurança e Saúde em Plataformas de Petróleo', 'ativo' => 1],
        ['codigo' => 'NR-38', 'nome' => 'NR-38 — Limpeza Urbana e Manejo de Resíduos Sólidos', 'ativo' => 1],
    ];
}

function sesmt_seed_nr_tipos(PDO $pdo): void
{
    $stmt = $pdo->prepare('
        INSERT INTO nr_tipos (codigo, nome, ativo)
        VALUES (:codigo, :nome, :ativo)
        ON DUPLICATE KEY UPDATE nome = VALUES(nome), ativo = VALUES(ativo)
    ');

    foreach (sesmt_nr_list() as $nr) {
        $stmt->execute([
            'codigo' => $nr['codigo'],
            'nome' => $nr['nome'],
            'ativo' => $nr['ativo'],
        ]);
    }
}
