<?php

declare(strict_types=1);

function sesmt_empresas_upload_dir(): string
{
    $dir = __DIR__ . '/../uploads/empresas';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function sesmt_cert_logos_upload_dir(): string
{
    $dir = __DIR__ . '/../uploads/cert-logos';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * @return array{path: string, ext: string}|null
 */
function sesmt_process_logo_upload(array $file): ?array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
        throw new InvalidArgumentException('Logo deve ser PNG ou JPG.');
    }
    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
    $filename = 'empresa-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = sesmt_empresas_upload_dir() . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Falha ao salvar logo.');
    }
    return ['path' => $dest, 'ext' => $ext];
}

function sesmt_logo_mime(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return $ext === 'png' ? 'image/png' : 'image/jpeg';
}

function sesmt_serve_logo_file(string $path): void
{
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . sesmt_logo_mime($path));
    readfile($path);
    exit;
}

function sesmt_save_logo_from_base64(string $dataUrl, string $destBasename, bool $empresaDir = false): ?string
{
    if (!preg_match('#^data:image/(png|jpeg|jpg);base64,(.+)$#i', $dataUrl, $m)) {
        return null;
    }
    $ext = strtolower($m[1]) === 'png' ? 'png' : 'jpg';
    $binary = base64_decode($m[2], true);
    if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
        return null;
    }
    $baseDir = $empresaDir ? sesmt_empresas_upload_dir() : sesmt_cert_logos_upload_dir();
    $dest = $baseDir . '/' . $destBasename . '.' . $ext;
    if (file_put_contents($dest, $binary) === false) {
        return null;
    }
    return $dest;
}

function sesmt_delete_file_if_exists(?string $path): void
{
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

/**
 * @param array<string, mixed> $payload
 */
function sesmt_resolve_certificado_logo(PDO $pdo, array $payload, ?array $certRow = null): ?string
{
    $empresaId = (int) ($payload['empresaId'] ?? 0);
    if ($empresaId > 0) {
        $stmt = $pdo->prepare('SELECT logo_path FROM empresas WHERE id = :id');
        $stmt->execute(['id' => $empresaId]);
        $row = $stmt->fetch();
        if (!empty($row['logo_path']) && is_file($row['logo_path'])) {
            return $row['logo_path'];
        }
    }

    $base64 = trim((string) ($payload['empresaLogoBase64'] ?? ''));
    if ($base64 !== '') {
        $tmp = sesmt_save_logo_from_base64($base64, 'tmp-' . bin2hex(random_bytes(6)));
        if ($tmp) {
            return $tmp;
        }
    }

    if ($certRow && !empty($certRow['empresa_logo_path']) && is_file($certRow['empresa_logo_path'])) {
        return $certRow['empresa_logo_path'];
    }

    return null;
}

/**
 * Persiste logo manual vinculado ao certificado (para regerar PDF depois).
 */
function sesmt_persist_cert_logo(?string $base64, int $certId, ?string $oldPath): ?string
{
    if ($base64 === null || trim($base64) === '') {
        return $oldPath;
    }
    sesmt_delete_file_if_exists($oldPath);
    return sesmt_save_logo_from_base64($base64, 'cert-' . $certId);
}
