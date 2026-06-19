<?php

declare(strict_types=1);

class PdfCertificado
{
    /**
     * @param array<string, mixed> $dados
     */
    public static function gerar(array $dados, string $outputPath): void
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException(
                'Biblioteca PDF não instalada. Execute: cd api && composer install'
            );
        }

        require_once $autoload;

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML(self::renderFrente($dados));
        $mpdf->AddPage();
        $mpdf->WriteHTML(self::renderVerso($dados));
        $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);
    }

    /**
     * @param array<string, mixed> $d
     */
    private static function renderFrente(array $d): string
    {
        $logoHtml = self::logoHtml($d);
        $assinaturas = $d['assinaturas'] ?? [];
        $blocos = '';
        foreach ($assinaturas as $a) {
            if (empty($a['nome'])) {
                continue;
            }
            $registro = trim(($a['registro_tipo'] ?? '') . ' ' . ($a['registro'] ?? ''));
            $blocos .= '
            <td class="sig-cell">
              <div class="sig-line"></div>
              <p class="sig-name">' . self::esc($a['nome']) . '</p>
              <p class="sig-role">' . self::esc($a['funcao'] ?? '') . '</p>
              ' . ($registro !== '' ? '<p class="sig-reg">' . self::esc($registro) . '</p>' : '') . '
            </td>';
        }

        $cols = max(count(array_filter($assinaturas, fn ($a) => !empty($a['nome']))), 1);
        $width = (int) floor(100 / min($cols, 4));

        $colaborador = self::esc($d['colaborador_nome'] ?? '');
        $empresa = self::esc($d['empresa_nome'] ?? '');
        $curso = self::esc($d['nome_treinamento'] ?? '');
        $dataExtenso = self::esc($d['data_formatada'] ?? '');
        $carga = self::esc($d['carga_horaria'] ?? '');
        $cidade = self::esc($d['cidade'] ?? '');

        return self::styles($width) . '
<div class="wrap">
  <div class="inner">
    <div class="header">' . $logoHtml . '<p class="titulo">Certificado</p></div>
    <p class="corpo">
      Certificamos que <strong>' . $colaborador . '</strong> da empresa <strong>' . $empresa . '</strong>
      participou com aproveitamento do curso de <strong>' . $curso . '</strong> no dia
      <strong>' . $dataExtenso . '</strong> com carga horária de <strong>' . $carga . '</strong>,
      tendo obtido o aproveitamento necessário.
    </p>
    <p class="data-extenso">' . $cidade . ', ' . $dataExtenso . '</p>
    <table class="sig-table"><tr>' . $blocos . '</tr></table>
    <p class="numero">Registro: ' . self::esc($d['numero'] ?? '') . '</p>
  </div>
</div>';
    }

    /**
     * @param array<string, mixed> $d
     */
    private static function renderVerso(array $d): string
    {
        $logoHtml = self::logoHtml($d);
        $conteudo = trim((string) ($d['conteudo_ministrado'] ?? ''));
        $conteudoHtml = $conteudo !== ''
            ? nl2br(self::esc($conteudo))
            : '<em>Não informado.</em>';

        $instrutores = self::filtrarInstrutores($d['assinaturas'] ?? []);
        $listaInstrutores = '';
        foreach ($instrutores as $inst) {
            $linha = self::esc($inst['nome']);
            $funcao = trim((string) ($inst['funcao'] ?? ''));
            if ($funcao !== '') {
                $linha .= ' — ' . self::esc($funcao);
            }
            $listaInstrutores .= '<li>' . $linha . '</li>';
        }
        if ($listaInstrutores === '') {
            $listaInstrutores = '<li><em>Nenhum instrutor informado.</em></li>';
        }

        return self::styles(50) . '
<div class="wrap">
  <div class="inner">
    <div class="header">' . $logoHtml . '</div>
    <h2 class="titulo-verso">Conteúdo Ministrado</h2>
    <div class="conteudo-bloco">' . $conteudoHtml . '</div>
    <h3 class="subtitulo-verso">Instrutores</h3>
    <ul class="lista-instrutores">' . $listaInstrutores . '</ul>
    <p class="numero">Registro: ' . self::esc($d['numero'] ?? '') . '</p>
  </div>
</div>';
    }

    /**
     * @param array<string, mixed> $d
     */
    private static function logoHtml(array $d): string
    {
        if (!empty($d['logo_abs_path']) && is_file($d['logo_abs_path'])) {
            return '<img src="' . $d['logo_abs_path'] . '" class="logo" alt="Logo" />';
        }
        return '';
    }

    /**
     * @param array<int, array<string, mixed>> $assinaturas
     * @return array<int, array<string, mixed>>
     */
    private static function filtrarInstrutores(array $assinaturas): array
    {
        $instrutores = [];
        foreach ($assinaturas as $a) {
            if (empty(trim((string) ($a['nome'] ?? '')))) {
                continue;
            }
            $funcao = mb_strtolower((string) ($a['funcao'] ?? ''), 'UTF-8');
            $tipo = strtoupper((string) ($a['registro_tipo'] ?? ''));

            $ehRT = str_contains($funcao, 'responsável')
                || str_contains($funcao, 'responsavel')
                || ($tipo === 'CREA' && (str_contains($funcao, 'técnico') || str_contains($funcao, 'tecnico')));

            $ehInstrutor = str_contains($funcao, 'instrutor') || $tipo === 'CRM';

            if ($ehInstrutor && !$ehRT) {
                $instrutores[] = $a;
            }
        }

        if ($instrutores !== []) {
            return $instrutores;
        }

        foreach ($assinaturas as $a) {
            if (empty(trim((string) ($a['nome'] ?? '')))) {
                continue;
            }
            $funcao = mb_strtolower((string) ($a['funcao'] ?? ''), 'UTF-8');
            if (!str_contains($funcao, 'responsável') && !str_contains($funcao, 'responsavel')) {
                $instrutores[] = $a;
            }
        }

        return $instrutores;
    }

    private static function styles(int $sigWidth): string
    {
        return '<style>
  body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; font-size: 12pt; }
  .wrap { border: 3px solid #2D4F3C; padding: 18px 22px; height: 100%; box-sizing: border-box; }
  .inner { padding: 28px 32px; min-height: 480px; position: relative; }
  .header { text-align: center; margin-bottom: 20px; }
  .logo { max-height: 56px; max-width: 160px; margin-bottom: 10px; }
  .titulo { font-size: 20pt; font-weight: bold; color: #1052E0; margin: 8px 0 0; letter-spacing: 2px; text-transform: uppercase; }
  .titulo-verso { font-size: 16pt; font-weight: bold; color: #2D4F3C; text-align: center; margin: 12px 0 20px; text-transform: uppercase; }
  .subtitulo-verso { font-size: 12pt; font-weight: bold; color: #1052E0; margin: 28px 0 12px; }
  .corpo { text-align: justify; line-height: 1.85; font-size: 13pt; margin: 24px 8px 40px; }
  .corpo strong { color: #2D4F3C; font-weight: bold; }
  .conteudo-bloco { text-align: justify; line-height: 1.75; font-size: 11.5pt; margin: 0 8px; padding: 16px 20px; background: #f8faf8; border-radius: 8px; min-height: 200px; }
  .data-extenso { text-align: center; font-size: 12pt; color: #333; margin: 32px 0 48px; }
  .lista-instrutores { margin: 0 24px; padding-left: 20px; font-size: 11.5pt; line-height: 1.8; }
  .lista-instrutores li { margin-bottom: 6px; }
  .sig-table { width: 100%; margin-top: 24px; border-collapse: collapse; }
  .sig-cell { width: ' . $sigWidth . '%; vertical-align: top; text-align: center; padding: 0 12px; }
  .sig-line { border-top: 1px solid #333; margin: 48px 8px 8px; height: 1px; }
  .sig-name { font-weight: bold; font-size: 10pt; margin: 0; }
  .sig-role { font-size: 9pt; margin: 3px 0 0; color: #444; }
  .sig-reg { font-size: 9pt; margin: 2px 0 0; color: #1052E0; }
  .numero { position: absolute; bottom: 10px; right: 14px; font-size: 8pt; color: #888; }
</style>';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
