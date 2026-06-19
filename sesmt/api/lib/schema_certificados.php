<?php

declare(strict_types=1);

function sesmt_ensure_certificados_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS nr_tipos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(20) NOT NULL UNIQUE,
            nome VARCHAR(255) NOT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS empresa_config (
            id INT PRIMARY KEY DEFAULT 1,
            nome_empresa VARCHAR(200) NOT NULL DEFAULT '',
            logo_path VARCHAR(255) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("INSERT IGNORE INTO empresa_config (id, nome_empresa) VALUES (1, '')");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS empresas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(200) NOT NULL,
            logo_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_empresas_nome (nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS certificados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero VARCHAR(40) NOT NULL UNIQUE,
            nr_tipo_id INT NOT NULL,
            nome_treinamento VARCHAR(120) NOT NULL,
            carga_horaria VARCHAR(40) NOT NULL,
            colaborador_nome VARCHAR(200) NOT NULL,
            colaborador_cpf VARCHAR(20) NOT NULL,
            data_certificado DATE NOT NULL,
            cidade VARCHAR(120) NOT NULL,
            empresa_nome VARCHAR(200) NOT NULL,
            assinaturas JSON NOT NULL,
            pdf_path VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
            emitido_por INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nr (nr_tipo_id),
            INDEX idx_emitido (emitido_por),
            INDEX idx_colaborador (colaborador_nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    try {
        $pdo->exec('ALTER TABLE nr_tipos MODIFY nome VARCHAR(255) NOT NULL');
    } catch (PDOException) {
        /* tabela nova ou já atualizada */
    }
    try {
        $pdo->exec('ALTER TABLE certificados ADD COLUMN conteudo_ministrado TEXT NULL AFTER assinaturas');
    } catch (PDOException) {
        /* coluna já existe */
    }
    try {
        $pdo->exec("ALTER TABLE certificados ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'emitido' AFTER conteudo_ministrado");
    } catch (PDOException) {
        /* coluna já existe */
    }
    try {
        $pdo->exec('ALTER TABLE certificados MODIFY pdf_path VARCHAR(255) NULL');
    } catch (PDOException) {
        /* ok */
    }
    try {
        $pdo->exec('ALTER TABLE certificados ADD COLUMN empresa_id INT NULL AFTER empresa_nome');
    } catch (PDOException) {
        /* coluna já existe */
    }
    try {
        $pdo->exec('ALTER TABLE certificados ADD COLUMN empresa_logo_path VARCHAR(255) NULL AFTER empresa_id');
    } catch (PDOException) {
        /* coluna já existe */
    }
    try {
        $pdo->exec('ALTER TABLE certificados ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    } catch (PDOException) {
        /* coluna já existe */
    }

    $countEmpresas = (int) $pdo->query('SELECT COUNT(*) FROM empresas')->fetchColumn();
    if ($countEmpresas === 0) {
        $legacy = $pdo->query('SELECT nome_empresa, logo_path FROM empresa_config WHERE id = 1')->fetch();
        if ($legacy && trim((string) $legacy['nome_empresa']) !== '') {
            $pdo->prepare('INSERT INTO empresas (nome, logo_path) VALUES (:nome, :logo)')
                ->execute([
                    'nome' => trim($legacy['nome_empresa']),
                    'logo' => $legacy['logo_path'] ?? null,
                ]);
        }
    }

    require_once __DIR__ . '/nr_list.php';
    sesmt_seed_nr_tipos($pdo);
}
