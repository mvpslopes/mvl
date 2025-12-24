<?php
/**
 * Script de Criação do Banco de Dados - Sistema Real Driver
 * 
 * Execute este arquivo uma vez para criar todas as tabelas necessárias.
 * Acesse via navegador: https://seusite.com.br/api/realdriver-db-setup.php
 * 
 * ⚠️ IMPORTANTE: Remova ou proteja este arquivo após criar as tabelas
 */

header('Content-Type: text/html; charset=utf-8');

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];
$prefix = $config['system']['table_prefix'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🚗 Configuração do Banco de Dados - Real Driver</h1>";
    echo "<p>Banco de dados: <strong>{$db['dbname']}</strong></p>";
    echo "<p>Iniciando criação das tabelas...</p>";
    
    // Tabela de Motoristas
    $tableName = ($prefix ? $prefix : '') . 'motoristas';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            cpf VARCHAR(20) NOT NULL,
            cnh VARCHAR(50) NOT NULL,
            telefone VARCHAR(20),
            endereco TEXT,
            status VARCHAR(20) DEFAULT 'Ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_cpf (cpf)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Veículos
    $tableName = ($prefix ? $prefix : '') . 'veiculos';
    $motoristasTable = ($prefix ? $prefix : '') . 'motoristas';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            modelo VARCHAR(255) NOT NULL,
            marca VARCHAR(100) NOT NULL,
            placa VARCHAR(10) NOT NULL UNIQUE,
            ano INT,
            cor VARCHAR(50),
            motorista_id INT,
            status VARCHAR(20) DEFAULT 'Ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_motorista (motorista_id),
            INDEX idx_placa (placa),
            FOREIGN KEY (motorista_id) REFERENCES {$motoristasTable}(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Diárias
    $tableName = ($prefix ? $prefix : '') . 'diarias';
    $motoristasTable = ($prefix ? $prefix : '') . 'motoristas';
    $veiculosTable = ($prefix ? $prefix : '') . 'veiculos';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            motorista_id INT NOT NULL,
            veiculo_id INT NOT NULL,
            data DATE NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'Pendente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_motorista (motorista_id),
            INDEX idx_veiculo (veiculo_id),
            INDEX idx_data (data),
            INDEX idx_status (status),
            FOREIGN KEY (motorista_id) REFERENCES {$motoristasTable}(id) ON DELETE CASCADE,
            FOREIGN KEY (veiculo_id) REFERENCES {$veiculosTable}(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Manutenções
    $tableName = ($prefix ? $prefix : '') . 'manutencoes';
    $veiculosTable = ($prefix ? $prefix : '') . 'veiculos';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            veiculo_id INT NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            data DATE NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            descricao TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_veiculo (veiculo_id),
            INDEX idx_tipo (tipo),
            INDEX idx_data (data),
            FOREIGN KEY (veiculo_id) REFERENCES {$veiculosTable}(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Contratos
    $tableName = ($prefix ? $prefix : '') . 'contratos';
    $veiculosTable = ($prefix ? $prefix : '') . 'veiculos';
    $motoristasTable = ($prefix ? $prefix : '') . 'motoristas';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            veiculo_id INT NOT NULL,
            motorista_id INT NOT NULL,
            data_inicio DATE NOT NULL,
            data_vencimento DATE NOT NULL,
            duracao_dias INT NOT NULL,
            valor_mensal DECIMAL(10,2) NOT NULL,
            observacoes TEXT,
            status VARCHAR(20) DEFAULT 'Ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_veiculo (veiculo_id),
            INDEX idx_motorista (motorista_id),
            INDEX idx_status (status),
            INDEX idx_vencimento (data_vencimento),
            FOREIGN KEY (veiculo_id) REFERENCES {$veiculosTable}(id) ON DELETE CASCADE,
            FOREIGN KEY (motorista_id) REFERENCES {$motoristasTable}(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Financeiro
    $tableName = ($prefix ? $prefix : '') . 'financeiro';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            descricao VARCHAR(255) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            data DATE NOT NULL,
            tipo VARCHAR(20) NOT NULL,
            categoria VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tipo (tipo),
            INDEX idx_categoria (categoria),
            INDEX idx_data (data)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Perfis
    $tableName = ($prefix ? $prefix : '') . 'perfis';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(50) NOT NULL UNIQUE,
            descricao VARCHAR(255),
            permissoes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_nome (nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Usuários
    $tableName = ($prefix ? $prefix : '') . 'usuarios';
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            senha_hash VARCHAR(255) NOT NULL,
            perfil_id INT NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            ultimo_acesso TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_perfil (perfil_id),
            INDEX idx_ativo (ativo),
            FOREIGN KEY (perfil_id) REFERENCES {$perfisTable}(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Tabela de Tokens de Autenticação
    $tableName = ($prefix ? $prefix : '') . 'auth_tokens';
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) UNIQUE NOT NULL,
            usuario_id INT NOT NULL,
            perfil_nome VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_token (token),
            INDEX idx_usuario (usuario_id),
            INDEX idx_expires (expires_at),
            FOREIGN KEY (usuario_id) REFERENCES {$usuariosTable}(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabela <strong>{$tableName}</strong> criada</p>";
    
    // Inserir perfis padrão
    $perfisTable = ($prefix ? $prefix : '') . 'perfis';
    $perfis = [
        ['nome' => 'root', 'descricao' => 'Acesso total - pode criar usuários e fazer toda gestão', 'permissoes' => json_encode(['create_users', 'manage_users', 'read', 'write', 'delete', 'update'])],
        ['nome' => 'admin', 'descricao' => 'Pode lançar dados, deletar, atualizar e trocar própria senha', 'permissoes' => json_encode(['read', 'write', 'delete', 'update', 'change_own_password'])],
        ['nome' => 'user', 'descricao' => 'Apenas consultar dados', 'permissoes' => json_encode(['read'])]
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO {$perfisTable} (nome, descricao, permissoes)
        VALUES (:nome, :descricao, :permissoes)
    ");
    
    foreach ($perfis as $perfil) {
        $stmt->execute($perfil);
    }
    echo "<p>✅ Perfis padrão criados (root, admin, user)</p>";
    
    // Criar usuários pré-configurados
    $usuariosTable = ($prefix ? $prefix : '') . 'usuarios';
    $rootPerfilId = $pdo->query("SELECT id FROM {$perfisTable} WHERE nome = 'root'")->fetchColumn();
    $adminPerfilId = $pdo->query("SELECT id FROM {$perfisTable} WHERE nome = 'admin'")->fetchColumn();
    
    if ($rootPerfilId && $adminPerfilId) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO {$usuariosTable} (nome, email, senha_hash, perfil_id)
            VALUES (:nome, :email, :senha_hash, :perfil_id)
        ");
        
        // Usuário Root: marcus@mvlopes.com.br
        $stmt->execute([
            'nome' => 'Marcus Lopes',
            'email' => 'marcus@mvlopes.com.br',
            'senha_hash' => '$2a$12$KMd5H7XYnRYatJHkQc8hAO/T.ZmsPbZEKGediy9zIMvW5RaCaj4mG',
            'perfil_id' => $rootPerfilId
        ]);
        echo "<p>✅ Usuário <strong>Root</strong> criado: marcus@mvlopes.com.br</p>";
        
        // Usuário Admin: francisco@mvlopes.com.br
        $stmt->execute([
            'nome' => 'Francisco',
            'email' => 'francisco@mvlopes.com.br',
            'senha_hash' => '$2a$12$ZrKwKffwm6eevB8w6MR5M.9/4ZYQqFtWPprbagjhDwATMalOWgnT',
            'perfil_id' => $adminPerfilId
        ]);
        echo "<p>✅ Usuário <strong>Admin</strong> criado: francisco@mvlopes.com.br</p>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Todas as tabelas foram criadas com sucesso!</h2>";
    echo "<h3>📊 Tabelas criadas:</h3>";
    echo "<ul>";
    echo "<li><strong>motoristas</strong> - Cadastro de motoristas</li>";
    echo "<li><strong>veiculos</strong> - Cadastro de veículos</li>";
    echo "<li><strong>diarias</strong> - Controle de diárias</li>";
    echo "<li><strong>manutencoes</strong> - Registro de manutenções</li>";
    echo "<li><strong>contratos</strong> - Contratos de locação</li>";
    echo "<li><strong>financeiro</strong> - Controle financeiro</li>";
    echo "<li><strong>perfis</strong> - Perfis de acesso (root, admin, user)</li>";
    echo "<li><strong>usuarios</strong> - Usuários do sistema</li>";
    echo "<li><strong>auth_tokens</strong> - Tokens de autenticação</li>";
    echo "</ul>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Execute o script de migração para importar os dados do backup: <a href='realdriver-migrate.php'>realdriver-migrate.php</a></li>";
    echo "<li>Remova ou proteja este arquivo por segurança</li>";
    echo "<li>Acesse o sistema em: <a href='realdriver.php'>realdriver.php</a></li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erro ao criar tabelas:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p><strong>Verifique:</strong></p>";
    echo "<ul>";
    echo "<li>Se o banco de dados foi criado corretamente</li>";
    echo "<li>Se as credenciais em <code>realdriver-config.php</code> estão corretas</li>";
    echo "<li>Se o usuário tem permissões para criar tabelas</li>";
    echo "</ul>";
}
