<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/schema_certificados.php';
require_once __DIR__ . '/lib/CertificadoService.php';
require_once __DIR__ . '/lib/EmpresaLogo.php';

sesmt_cors();
sesmt_options_exit();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = sesmt_pdo();
sesmt_ensure_certificados_schema($pdo);

// Download PDF
if ($method === 'GET' && isset($_GET['id'], $_GET['download'])) {
    sesmt_require_auth();
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare('SELECT pdf_path, numero FROM certificados WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row || empty($row['pdf_path']) || !is_file($row['pdf_path'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'PDF não encontrado. Gere ou regenere o certificado.']);
        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="certificado-' . ($row['numero'] ?? $id) . '.pdf"');
    readfile($row['pdf_path']);
    exit;
}

// Detalhe para edição
if ($method === 'GET' && isset($_GET['id'])) {
    sesmt_require_auth();
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM certificados WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Certificado não encontrado.']);
        exit;
    }
    $row = CertificadoService::formatarRegistro($row);
    echo json_encode(['success' => true, 'certificado' => $row], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lista
if ($method === 'GET') {
    sesmt_require_auth();
    $busca = trim((string) ($_GET['q'] ?? ''));
    $sql = '
        SELECT c.id, c.numero, c.status, c.nome_treinamento, c.colaborador_nome,
               c.colaborador_cpf, c.data_certificado, c.cidade, c.empresa_nome,
               c.pdf_path, c.created_at
        FROM certificados c
    ';
    $params = [];
    if ($busca !== '') {
        $sql .= ' WHERE c.colaborador_nome LIKE :q OR c.numero LIKE :q OR c.nome_treinamento LIKE :q';
        $params['q'] = '%' . $busca . '%';
    }
    $sql .= ' ORDER BY c.created_at DESC LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $lista = [];
    foreach ($stmt->fetchAll() as $row) {
        $lista[] = CertificadoService::formatarRegistro($row);
    }
    echo json_encode(['success' => true, 'certificados' => $lista], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $user = sesmt_require_auth();
    $data = sesmt_json_input();
    $gerarPdf = !isset($data['gerar_pdf']) || (bool) $data['gerar_pdf'];
    $rascunho = !$gerarPdf;

    try {
        $payload = CertificadoService::validar($data, $rascunho);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    $nrNome = '';
    if ($payload['nrTipoId'] > 0) {
        $stmt = $pdo->prepare('SELECT nome FROM nr_tipos WHERE id = :id');
        $stmt->execute(['id' => $payload['nrTipoId']]);
        $nr = $stmt->fetch();
        $nrNome = $nr['nome'] ?? '';
    }

    $numero = sesmt_proximo_numero_certificado($pdo);
    $pdfPath = null;
    $status = 'rascunho';

    $payload = sesmt_aplicar_salvar_empresa_cadastro($pdo, $data, $payload);

    if ($gerarPdf) {
        try {
            $pdfPath = CertificadoService::gerarPdf($pdo, $payload, $numero);
            $status = 'emitido';
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao gerar PDF: ' . $e->getMessage()]);
            exit;
        }
    }

    $cpfSalvo = $payload['colaboradorCpf'] !== '' ? sesmt_formatar_cpf($payload['colaboradorCpf']) : '';
    $empresaFields = sesmt_cert_empresa_fields($payload, null);

    $stmt = $pdo->prepare('
        INSERT INTO certificados (
            numero, nr_tipo_id, nome_treinamento, carga_horaria,
            colaborador_nome, colaborador_cpf, data_certificado, cidade,
            empresa_nome, empresa_id, empresa_logo_path,
            assinaturas, conteudo_ministrado, pdf_path, status, emitido_por
        ) VALUES (
            :numero, :nr_id, :nome_treinamento, :carga,
            :colab_nome, :colab_cpf, :data_cert, :cidade,
            :empresa, :empresa_id, :empresa_logo,
            :assinaturas, :conteudo, :pdf, :status, :uid
        )
    ');
    if ($payload['nrTipoId'] <= 0) {
        $payload['nrTipoId'] = (int) $pdo->query('SELECT id FROM nr_tipos WHERE ativo = 1 ORDER BY id ASC LIMIT 1')->fetchColumn();
    }

    $stmt->execute([
        'numero' => $numero,
        'nr_id' => $payload['nrTipoId'],
        'nome_treinamento' => $nrNome ?: 'Rascunho',
        'carga' => $payload['cargaHoraria'],
        'colab_nome' => $payload['colaboradorNome'] ?: '—',
        'colab_cpf' => $cpfSalvo ?: '—',
        'data_cert' => $payload['dataCert'] !== '' ? $payload['dataCert'] : date('Y-m-d'),
        'cidade' => $payload['cidade'] ?: '—',
        'empresa' => $payload['empresaNome'] ?: '—',
        'empresa_id' => $empresaFields['empresa_id'],
        'empresa_logo' => $empresaFields['empresa_logo_path'],
        'assinaturas' => json_encode($payload['assinaturas'], JSON_UNESCAPED_UNICODE),
        'conteudo' => $payload['conteudoMinistrado'] !== '' ? $payload['conteudoMinistrado'] : null,
        'pdf' => $pdfPath,
        'status' => $status,
        'uid' => $user['id'],
    ]);

    $id = (int) $pdo->lastInsertId();
    if (empty($empresaFields['empresa_id']) && $payload['empresaLogoBase64'] !== '') {
        $logoPath = sesmt_persist_cert_logo($payload['empresaLogoBase64'], $id, null);
        if ($logoPath) {
            $pdo->prepare('UPDATE certificados SET empresa_logo_path = :p WHERE id = :id')
                ->execute(['p' => $logoPath, 'id' => $id]);
        }
    }
    sesmt_responder_certificado($id, $gerarPdf ? 'Certificado gerado.' : 'Rascunho salvo.');
    exit;
}

if ($method === 'PUT') {
    sesmt_require_auth();
    $data = sesmt_json_input();
    $id = (int) ($data['id'] ?? 0);
    $gerarPdf = (bool) ($data['gerar_pdf'] ?? false);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM certificados WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $existente = $stmt->fetch();
    if (!$existente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Certificado não encontrado.']);
        exit;
    }

    $rascunho = !$gerarPdf;
    try {
        $payload = CertificadoService::validar($data, $rascunho);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    $payload = sesmt_aplicar_salvar_empresa_cadastro($pdo, $data, $payload);
    $empresaFields = sesmt_cert_empresa_fields($payload, $existente);

    $nrNome = $existente['nome_treinamento'];
    if ($payload['nrTipoId'] > 0) {
        $stmt = $pdo->prepare('SELECT nome FROM nr_tipos WHERE id = :id');
        $stmt->execute(['id' => $payload['nrTipoId']]);
        $nr = $stmt->fetch();
        if ($nr) {
            $nrNome = $nr['nome'];
        }
    }

    $numero = $existente['numero'];
    $pdfPath = $existente['pdf_path'];
    $status = $existente['status'];

    if ($gerarPdf) {
        try {
            if ($pdfPath && is_file($pdfPath)) {
                @unlink($pdfPath);
            }
            $certCtx = array_merge($existente, [
                'empresa_id' => $empresaFields['empresa_id'],
                'empresa_logo_path' => $empresaFields['empresa_logo_path'],
            ]);
            $pdfPath = CertificadoService::gerarPdf($pdo, $payload, $numero, $certCtx);
            $status = 'emitido';
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao gerar PDF: ' . $e->getMessage()]);
            exit;
        }
    }

    $cpfSalvo = $payload['colaboradorCpf'] !== ''
        ? sesmt_formatar_cpf($payload['colaboradorCpf'])
        : $existente['colaborador_cpf'];

    $pdo->prepare('
        UPDATE certificados SET
            nr_tipo_id = :nr_id,
            nome_treinamento = :nome_treinamento,
            carga_horaria = :carga,
            colaborador_nome = :colab_nome,
            colaborador_cpf = :colab_cpf,
            data_certificado = :data_cert,
            cidade = :cidade,
            empresa_nome = :empresa,
            empresa_id = :empresa_id,
            empresa_logo_path = :empresa_logo,
            assinaturas = :assinaturas,
            conteudo_ministrado = :conteudo,
            pdf_path = :pdf,
            status = :status
        WHERE id = :id
    ')->execute([
        'nr_id' => $payload['nrTipoId'] > 0 ? $payload['nrTipoId'] : (int) $existente['nr_tipo_id'],
        'nome_treinamento' => $nrNome,
        'carga' => $payload['cargaHoraria'] ?: $existente['carga_horaria'],
        'colab_nome' => $payload['colaboradorNome'] ?: $existente['colaborador_nome'],
        'colab_cpf' => $cpfSalvo,
        'data_cert' => $payload['dataCert'] !== '' ? $payload['dataCert'] : $existente['data_certificado'],
        'cidade' => $payload['cidade'] ?: $existente['cidade'],
        'empresa' => $payload['empresaNome'] ?: $existente['empresa_nome'],
        'empresa_id' => $empresaFields['empresa_id'],
        'empresa_logo' => $empresaFields['empresa_logo_path'],
        'assinaturas' => json_encode($payload['assinaturas'], JSON_UNESCAPED_UNICODE),
        'conteudo' => $payload['conteudoMinistrado'] !== '' ? $payload['conteudoMinistrado'] : null,
        'pdf' => $pdfPath,
        'status' => $status,
        'id' => $id,
    ]);

    sesmt_responder_certificado($id, $gerarPdf ? 'Certificado atualizado e PDF regerado.' : 'Alterações salvas.');
    exit;
}

if ($method === 'DELETE') {
    sesmt_require_auth();
    $data = sesmt_json_input();
    $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT pdf_path FROM certificados WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Certificado não encontrado.']);
        exit;
    }
    if (!empty($row['pdf_path']) && is_file($row['pdf_path'])) {
        @unlink($row['pdf_path']);
    }
    $pdo->prepare('DELETE FROM certificados WHERE id = :id')->execute(['id' => $id]);
    echo json_encode(['success' => true, 'message' => 'Certificado excluído.'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido.']);

/**
 * @param array<string, mixed> $data
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function sesmt_aplicar_salvar_empresa_cadastro(PDO $pdo, array $data, array $payload): array
{
    if (empty($data['salvar_empresa_cadastro']) || !empty($payload['empresaId']) || $payload['empresaNome'] === '') {
        return $payload;
    }

    $logoPath = null;
    if ($payload['empresaLogoBase64'] !== '') {
        $logoPath = sesmt_save_logo_from_base64(
            $payload['empresaLogoBase64'],
            'empresa-' . bin2hex(random_bytes(8)),
            true
        );
    }

    $pdo->prepare('INSERT INTO empresas (nome, logo_path) VALUES (:nome, :logo)')
        ->execute(['nome' => $payload['empresaNome'], 'logo' => $logoPath]);

    $payload['empresaId'] = (int) $pdo->lastInsertId();
    $payload['empresaLogoBase64'] = '';

    return $payload;
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed>|null $existente
 * @return array{empresa_id: ?int, empresa_logo_path: ?string}
 */
function sesmt_cert_empresa_fields(array $payload, ?array $existente): array
{
    $certId = (int) ($existente['id'] ?? 0);
    $oldLogo = $existente['empresa_logo_path'] ?? null;

    if (!empty($payload['empresaId'])) {
        sesmt_delete_file_if_exists($oldLogo);
        return ['empresa_id' => (int) $payload['empresaId'], 'empresa_logo_path' => null];
    }

    if ($payload['empresaLogoBase64'] !== '' && $certId > 0) {
        $path = sesmt_persist_cert_logo($payload['empresaLogoBase64'], $certId, $oldLogo);
        return ['empresa_id' => null, 'empresa_logo_path' => $path];
    }

    if ($payload['empresaLogoBase64'] !== '' && $certId <= 0) {
        return ['empresa_id' => null, 'empresa_logo_path' => null];
    }

    return [
        'empresa_id' => null,
        'empresa_logo_path' => $oldLogo,
    ];
}

function sesmt_responder_certificado(int $id, string $message): void
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM certificados WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    $cert = $row ? CertificadoService::formatarRegistro($row) : ['id' => $id];

    echo json_encode([
        'success' => true,
        'message' => $message,
        'certificado' => array_merge($cert, [
            'pdf_url' => !empty($cert['has_pdf']) ? '/api/certificados.php?id=' . $id . '&download=1' : null,
        ]),
    ], JSON_UNESCAPED_UNICODE);
}
