<?php
/**
 * Script de Migração - Sistema Real Driver
 * 
 * Este script importa os dados do backup JSON para o banco de dados.
 * 
 * Uso:
 * 1. Coloque o arquivo de backup JSON na pasta backups/
 * 2. Acesse: https://seusite.com.br/api/realdriver-migrate.php?file=nome-do-backup.json
 * 
 * ⚠️ IMPORTANTE: Remova ou proteja este arquivo após migrar os dados
 */

header('Content-Type: text/html; charset=utf-8');

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];
$prefix = $config['system']['table_prefix'];

// Caminho do arquivo de backup - tenta vários caminhos
$possibleBackupPaths = [
    __DIR__ . '/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json',
    __DIR__ . '/../SistemasEmbarcados/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json',
    $_SERVER['DOCUMENT_ROOT'] . '/SistemasEmbarcados/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json',
];

$backupFile = null;
if (isset($_GET['file'])) {
    $fileName = basename($_GET['file']);
    $possibleBackupPaths = [
        __DIR__ . '/realdriver/backups/' . $fileName,
        __DIR__ . '/../SistemasEmbarcados/realdriver/backups/' . $fileName,
        $_SERVER['DOCUMENT_ROOT'] . '/SistemasEmbarcados/realdriver/backups/' . $fileName,
    ];
}

foreach ($possibleBackupPaths as $path) {
    if (file_exists($path)) {
        $backupFile = $path;
        break;
    }
}

if (!$backupFile || !file_exists($backupFile)) {
    echo "<h1>❌ Arquivo de backup não encontrado!</h1>";
    echo "<p>Tentou os seguintes caminhos:</p><ul>";
    foreach ($possibleBackupPaths as $path) {
        $exists = file_exists($path) ? '✅' : '❌';
        echo "<li>{$exists} " . htmlspecialchars($path) . "</li>";
    }
    echo "</ul>";
    echo "<p><strong>Solução:</strong> Faça upload do arquivo de backup para uma das pastas acima.</p>";
    die();
}

echo "<h1>📦 Migração de Dados - Real Driver</h1>";
echo "<p>Carregando backup: <strong>" . htmlspecialchars(basename($backupFile)) . "</strong></p>";

// Ler e decodificar o JSON
$jsonContent = file_get_contents($backupFile);
$backupData = json_decode($jsonContent, true);

if (!$backupData || !isset($backupData['data'])) {
    die("<h2 style='color: red;'>❌ Erro: Formato de backup inválido!</h2>");
}

$data = $backupData['data'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    
    $stats = [
        'motoristas' => 0,
        'veiculos' => 0,
        'diarias' => 0,
        'manutencoes' => 0,
        'contratos' => 0,
        'financeiro' => 0
    ];
    
    // Migrar Motoristas
    if (isset($data['motoristas']) && is_array($data['motoristas'])) {
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}motoristas (id, nome, cpf, cnh, telefone, endereco, status)
            VALUES (:id, :nome, :cpf, :cnh, :telefone, :endereco, :status)
            ON DUPLICATE KEY UPDATE
                nome = VALUES(nome),
                cpf = VALUES(cpf),
                cnh = VALUES(cnh),
                telefone = VALUES(telefone),
                endereco = VALUES(endereco),
                status = VALUES(status)
        ");
        
        foreach ($data['motoristas'] as $motorista) {
            $stmt->execute([
                'id' => $motorista['id'] ?? null,
                'nome' => $motorista['nome'] ?? '',
                'cpf' => $motorista['cpf'] ?? '',
                'cnh' => $motorista['cnh'] ?? '',
                'telefone' => $motorista['telefone'] ?? '',
                'endereco' => $motorista['endereco'] ?? '',
                'status' => $motorista['status'] ?? 'Ativo'
            ]);
            $stats['motoristas']++;
        }
        echo "<p>✅ {$stats['motoristas']} motoristas importados</p>";
    }
    
    // Migrar Veículos
    if (isset($data['veiculos']) && is_array($data['veiculos'])) {
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}veiculos (id, modelo, marca, placa, ano, cor, motorista_id, status)
            VALUES (:id, :modelo, :marca, :placa, :ano, :cor, :motorista_id, :status)
            ON DUPLICATE KEY UPDATE
                modelo = VALUES(modelo),
                marca = VALUES(marca),
                ano = VALUES(ano),
                cor = VALUES(cor),
                motorista_id = VALUES(motorista_id),
                status = VALUES(status)
        ");
        
        foreach ($data['veiculos'] as $veiculo) {
            $stmt->execute([
                'id' => $veiculo['id'] ?? null,
                'modelo' => $veiculo['modelo'] ?? '',
                'marca' => $veiculo['marca'] ?? '',
                'placa' => $veiculo['placa'] ?? '',
                'ano' => $veiculo['ano'] ?? null,
                'cor' => $veiculo['cor'] ?? '',
                'motorista_id' => $veiculo['motoristaId'] ?? null,
                'status' => $veiculo['status'] ?? 'Ativo'
            ]);
            $stats['veiculos']++;
        }
        echo "<p>✅ {$stats['veiculos']} veículos importados</p>";
    }
    
    // Migrar Diárias
    if (isset($data['diarias']) && is_array($data['diarias'])) {
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}diarias (id, motorista_id, veiculo_id, data, valor, status)
            VALUES (:id, :motorista_id, :veiculo_id, :data, :valor, :status)
            ON DUPLICATE KEY UPDATE
                motorista_id = VALUES(motorista_id),
                veiculo_id = VALUES(veiculo_id),
                data = VALUES(data),
                valor = VALUES(valor),
                status = VALUES(status)
        ");
        
        foreach ($data['diarias'] as $diaria) {
            $stmt->execute([
                'id' => $diaria['id'] ?? null,
                'motorista_id' => $diaria['motoristaId'] ?? null,
                'veiculo_id' => $diaria['veiculoId'] ?? null,
                'data' => $diaria['data'] ?? date('Y-m-d'),
                'valor' => $diaria['valor'] ?? 0,
                'status' => $diaria['status'] ?? 'Pendente'
            ]);
            $stats['diarias']++;
        }
        echo "<p>✅ {$stats['diarias']} diárias importadas</p>";
    }
    
    // Migrar Manutenções
    if (isset($data['manutencoes']) && is_array($data['manutencoes'])) {
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}manutencoes (id, veiculo_id, tipo, data, valor, descricao)
            VALUES (:id, :veiculo_id, :tipo, :data, :valor, :descricao)
            ON DUPLICATE KEY UPDATE
                veiculo_id = VALUES(veiculo_id),
                tipo = VALUES(tipo),
                data = VALUES(data),
                valor = VALUES(valor),
                descricao = VALUES(descricao)
        ");
        
        foreach ($data['manutencoes'] as $manutencao) {
            $stmt->execute([
                'id' => $manutencao['id'] ?? null,
                'veiculo_id' => $manutencao['veiculoId'] ?? null,
                'tipo' => $manutencao['tipo'] ?? 'Preventiva',
                'data' => $manutencao['data'] ?? date('Y-m-d'),
                'valor' => $manutencao['valor'] ?? 0,
                'descricao' => $manutencao['descricao'] ?? ''
            ]);
            $stats['manutencoes']++;
        }
        echo "<p>✅ {$stats['manutencoes']} manutenções importadas</p>";
    }
    
    // Migrar Contratos
    if (isset($data['contratos']) && is_array($data['contratos'])) {
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}contratos (id, veiculo_id, motorista_id, data_inicio, data_vencimento, duracao_dias, valor_mensal, observacoes, status)
            VALUES (:id, :veiculo_id, :motorista_id, :data_inicio, :data_vencimento, :duracao_dias, :valor_mensal, :observacoes, :status)
            ON DUPLICATE KEY UPDATE
                veiculo_id = VALUES(veiculo_id),
                motorista_id = VALUES(motorista_id),
                data_inicio = VALUES(data_inicio),
                data_vencimento = VALUES(data_vencimento),
                duracao_dias = VALUES(duracao_dias),
                valor_mensal = VALUES(valor_mensal),
                observacoes = VALUES(observacoes),
                status = VALUES(status)
        ");
        
        foreach ($data['contratos'] as $contrato) {
            // Calcular data de vencimento se necessário
            $dataInicio = $contrato['dataInicio'] ?? date('Y-m-d');
            $duracaoDias = $contrato['duracao'] ?? 30;
            $dataVencimento = date('Y-m-d', strtotime($dataInicio . " +{$duracaoDias} days"));
            
            $stmt->execute([
                'id' => $contrato['id'] ?? null,
                'veiculo_id' => $contrato['veiculoId'] ?? null,
                'motorista_id' => $contrato['motoristaId'] ?? null,
                'data_inicio' => $dataInicio,
                'data_vencimento' => $dataVencimento,
                'duracao_dias' => $duracaoDias,
                'valor_mensal' => $contrato['valorMensal'] ?? 0,
                'observacoes' => $contrato['observacoes'] ?? '',
                'status' => $contrato['status'] ?? 'Ativo'
            ]);
            $stats['contratos']++;
        }
        echo "<p>✅ {$stats['contratos']} contratos importados</p>";
    }
    
    // Migrar Financeiro
    if (isset($data['financeiro']) && is_array($data['financeiro'])) {
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}financeiro (id, descricao, valor, data, tipo, categoria)
            VALUES (:id, :descricao, :valor, :data, :tipo, :categoria)
            ON DUPLICATE KEY UPDATE
                descricao = VALUES(descricao),
                valor = VALUES(valor),
                data = VALUES(data),
                tipo = VALUES(tipo),
                categoria = VALUES(categoria)
        ");
        
        foreach ($data['financeiro'] as $financeiro) {
            $stmt->execute([
                'id' => $financeiro['id'] ?? null,
                'descricao' => $financeiro['descricao'] ?? '',
                'valor' => $financeiro['valor'] ?? 0,
                'data' => $financeiro['data'] ?? date('Y-m-d'),
                'tipo' => $financeiro['tipo'] ?? 'Despesa',
                'categoria' => $financeiro['categoria'] ?? 'Outros'
            ]);
            $stats['financeiro']++;
        }
        echo "<p>✅ {$stats['financeiro']} transações financeiras importadas</p>";
    }
    
    $pdo->commit();
    
    echo "<hr>";
    echo "<h2>✅ Migração concluída com sucesso!</h2>";
    echo "<h3>Resumo:</h3>";
    echo "<ul>";
    foreach ($stats as $tipo => $quantidade) {
        echo "<li><strong>" . ucfirst($tipo) . ":</strong> {$quantidade} registros</li>";
    }
    echo "</ul>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Teste a API para verificar se os dados estão acessíveis</li>";
    echo "<li>Remova ou proteja este arquivo por segurança</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<h2 style='color: red;'>❌ Erro na migração:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

