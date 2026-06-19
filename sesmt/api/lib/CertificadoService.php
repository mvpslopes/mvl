<?php

declare(strict_types=1);

require_once __DIR__ . '/PdfCertificado.php';
require_once __DIR__ . '/EmpresaLogo.php';

class CertificadoService
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validar(array $data, bool $rascunho): array
    {
        $nrTipoId = (int) ($data['nr_tipo_id'] ?? 0);
        $cargaHoraria = trim((string) ($data['carga_horaria'] ?? ''));
        $colaboradorNome = trim((string) ($data['colaborador_nome'] ?? ''));
        $colaboradorCpf = preg_replace('/\D/', '', (string) ($data['colaborador_cpf'] ?? ''));
        $dataCert = trim((string) ($data['data_certificado'] ?? ''));
        $cidade = trim((string) ($data['cidade'] ?? ''));
        $empresaNome = trim((string) ($data['empresa_nome'] ?? ''));
        $empresaId = !empty($data['empresa_id']) ? (int) $data['empresa_id'] : null;
        $empresaLogoBase64 = trim((string) ($data['empresa_logo_base64'] ?? ''));
        $conteudoMinistrado = trim((string) ($data['conteudo_ministrado'] ?? ''));
        $assinaturas = $data['assinaturas'] ?? [];

        if (!$rascunho) {
            if ($nrTipoId <= 0 || $cargaHoraria === '' || $colaboradorNome === '' || $colaboradorCpf === ''
                || $dataCert === '' || $cidade === '' || $empresaNome === '') {
                throw new InvalidArgumentException('Preencha todos os campos obrigatórios.');
            }
            if (!is_array($assinaturas) || count(array_filter($assinaturas, fn ($a) => !empty(trim($a['nome'] ?? '')))) === 0) {
                throw new InvalidArgumentException('Informe ao menos uma assinatura.');
            }
        }

        $assinaturas = is_array($assinaturas)
            ? array_slice(
                array_values(array_filter($assinaturas, fn ($a) => !empty(trim($a['nome'] ?? '')))),
                0,
                4
            )
            : [];

        return compact(
            'nrTipoId',
            'cargaHoraria',
            'colaboradorNome',
            'colaboradorCpf',
            'dataCert',
            'cidade',
            'empresaNome',
            'empresaId',
            'empresaLogoBase64',
            'conteudoMinistrado',
            'assinaturas'
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    /**
     * @param array<string, mixed>|null $certRow
     */
    public static function gerarPdf(PDO $pdo, array $payload, string $numero, ?array $certRow = null): string
    {
        $nr = null;
        if ($payload['nrTipoId'] > 0) {
            $stmt = $pdo->prepare('SELECT nome FROM nr_tipos WHERE id = :id');
            $stmt->execute(['id' => $payload['nrTipoId']]);
            $nr = $stmt->fetch();
        }
        $nomeTreinamento = $nr['nome'] ?? 'Treinamento NR';

        $logoAbs = sesmt_resolve_certificado_logo($pdo, $payload, $certRow);

        $dataFormatada = $payload['dataCert'] !== ''
            ? sesmt_formatar_data($payload['dataCert'])
            : '';

        $pdfDir = __DIR__ . '/../uploads/certificados/' . date('Y');
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }
        $pdfPath = $pdfDir . '/cert-' . $numero . '.pdf';

        PdfCertificado::gerar([
            'empresa_nome' => $payload['empresaNome'],
            'logo_abs_path' => $logoAbs,
            'nome_treinamento' => $nomeTreinamento,
            'carga_horaria' => sesmt_formatar_carga_horaria($payload['cargaHoraria']),
            'colaborador_nome' => $payload['colaboradorNome'],
            'colaborador_cpf' => $payload['colaboradorCpf'] !== ''
                ? sesmt_formatar_cpf($payload['colaboradorCpf'])
                : '',
            'cidade' => $payload['cidade'],
            'data_formatada' => $dataFormatada,
            'assinaturas' => $payload['assinaturas'],
            'conteudo_ministrado' => $payload['conteudoMinistrado'],
            'numero' => $numero,
        ], $pdfPath);

        return $pdfPath;
    }

    public static function formatarRegistro(array $row): array
    {
        $row['assinaturas'] = json_decode($row['assinaturas'] ?? '[]', true) ?: [];
        $row['status'] = $row['status'] ?? 'rascunho';
        $row['has_pdf'] = !empty($row['pdf_path']) && is_file($row['pdf_path']);
        return $row;
    }
}

function sesmt_proximo_numero_certificado(PDO $pdo): string
{
    $ano = date('Y');
    $stmt = $pdo->prepare('SELECT COUNT(*) + 1 AS seq FROM certificados WHERE YEAR(created_at) = :ano');
    $stmt->execute(['ano' => $ano]);
    $seq = (int) $stmt->fetchColumn();
    return sprintf('SESMT-%s-%05d', $ano, $seq);
}

function sesmt_formatar_data(string $isoDate): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $isoDate);
    if (!$dt) {
        return $isoDate;
    }
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];
    $m = (int) $dt->format('n');
    return $dt->format('d') . ' de ' . ($meses[$m] ?? '') . ' de ' . $dt->format('Y');
}

function sesmt_formatar_cpf(string $digits): string
{
    $d = str_pad(substr($digits, 0, 11), 11, '0', STR_PAD_LEFT);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $d);
}

function sesmt_formatar_carga_horaria(string $carga): string
{
    $carga = trim($carga);
    if ($carga === '') {
        return $carga;
    }
    if (preg_match('/\bhoras?\b/i', $carga)) {
        return $carga;
    }
    return $carga . ' horas';
}
