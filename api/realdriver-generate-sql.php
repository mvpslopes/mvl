<?php
/**
 * Script para Gerar SQL a partir do Backup JSON
 * 
 * Este script lê o arquivo de backup JSON e gera comandos SQL INSERT
 * que podem ser executados diretamente no banco de dados.
 * 
 * Uso:
 * 1. Acesse: https://seusite.com.br/api/realdriver-generate-sql.php
 * 2. O script gerará um arquivo SQL que você pode baixar ou copiar
 * 3. Execute o SQL no phpMyAdmin ou via linha de comando
 */

header('Content-Type: text/html; charset=utf-8');

// Caminhos possíveis do backup
$possibleBackupPaths = [
    __DIR__ . '/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json',
    __DIR__ . '/../SistemasEmbarcados/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json',
    $_SERVER['DOCUMENT_ROOT'] . '/SistemasEmbarcados/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json',
];

$backupFile = null;
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

echo "<h1>📦 Gerador de SQL - Real Driver</h1>";
echo "<p>Lendo backup: <strong>" . htmlspecialchars(basename($backupFile)) . "</strong></p>";

// Ler e decodificar o JSON
$jsonContent = file_get_contents($backupFile);
$backupData = json_decode($jsonContent, true);

if (!$backupData || !isset($backupData['data'])) {
    die("<h2 style='color: red;'>❌ Erro: Formato de backup inválido!</h2>");
}

$data = $backupData['data'];

// Iniciar geração do SQL
$sql = "-- ============================================\n";
$sql .= "-- Script SQL gerado automaticamente\n";
$sql .= "-- Backup: " . basename($backupFile) . "\n";
$sql .= "-- Data: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- ============================================\n\n";
$sql .= "USE `u179630068_realdriver`;\n\n";
$sql .= "-- Desabilitar verificações temporariamente para inserção rápida\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$sql .= "SET AUTOCOMMIT = 0;\n\n";
$sql .= "START TRANSACTION;\n\n";

$stats = [
    'motoristas' => 0,
    'veiculos' => 0,
    'diarias' => 0,
    'manutencoes' => 0,
    'contratos' => 0,
    'financeiro' => 0
];

// Função para escapar strings SQL
function escapeSql($value) {
    if ($value === null) {
        return 'NULL';
    }
    if (is_numeric($value)) {
        return $value;
    }
    return "'" . addslashes($value) . "'";
}

// Migrar Motoristas
if (isset($data['motoristas']) && is_array($data['motoristas'])) {
    $sql .= "-- ============================================\n";
    $sql .= "-- MOTORISTAS\n";
    $sql .= "-- ============================================\n\n";
    
    foreach ($data['motoristas'] as $motorista) {
        $id = isset($motorista['id']) ? (int)$motorista['id'] : 'NULL';
        $nome = escapeSql($motorista['nome'] ?? '');
        $cpf = escapeSql($motorista['cpf'] ?? '');
        $cnh = escapeSql($motorista['cnh'] ?? '');
        $telefone = escapeSql($motorista['telefone'] ?? '');
        $endereco = escapeSql($motorista['endereco'] ?? '');
        $status = escapeSql($motorista['status'] ?? 'Ativo');
        
        $sql .= "INSERT INTO motoristas (id, nome, cpf, cnh, telefone, endereco, status) VALUES ";
        $sql .= "({$id}, {$nome}, {$cpf}, {$cnh}, {$telefone}, {$endereco}, {$status}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "nome = {$nome}, ";
        $sql .= "cpf = {$cpf}, ";
        $sql .= "cnh = {$cnh}, ";
        $sql .= "telefone = {$telefone}, ";
        $sql .= "endereco = {$endereco}, ";
        $sql .= "status = {$status};\n";
        
        $stats['motoristas']++;
    }
    $sql .= "\n";
}

// Migrar Veículos
if (isset($data['veiculos']) && is_array($data['veiculos'])) {
    $sql .= "-- ============================================\n";
    $sql .= "-- VEÍCULOS\n";
    $sql .= "-- ============================================\n\n";
    
    foreach ($data['veiculos'] as $veiculo) {
        $id = isset($veiculo['id']) ? (int)$veiculo['id'] : 'NULL';
        $modelo = escapeSql($veiculo['modelo'] ?? '');
        $marca = escapeSql($veiculo['marca'] ?? '');
        $placa = escapeSql($veiculo['placa'] ?? '');
        $ano = isset($veiculo['ano']) ? (int)$veiculo['ano'] : 'NULL';
        $cor = escapeSql($veiculo['cor'] ?? '');
        $motoristaId = isset($veiculo['motoristaId']) ? (int)$veiculo['motoristaId'] : 'NULL';
        $status = escapeSql($veiculo['status'] ?? 'Ativo');
        
        $sql .= "INSERT INTO veiculos (id, modelo, marca, placa, ano, cor, motorista_id, status) VALUES ";
        $sql .= "({$id}, {$modelo}, {$marca}, {$placa}, {$ano}, {$cor}, {$motoristaId}, {$status}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "modelo = {$modelo}, ";
        $sql .= "marca = {$marca}, ";
        $sql .= "ano = {$ano}, ";
        $sql .= "cor = {$cor}, ";
        $sql .= "motorista_id = {$motoristaId}, ";
        $sql .= "status = {$status};\n";
        
        $stats['veiculos']++;
    }
    $sql .= "\n";
}

// Migrar Diárias
if (isset($data['diarias']) && is_array($data['diarias'])) {
    $sql .= "-- ============================================\n";
    $sql .= "-- DIÁRIAS\n";
    $sql .= "-- ============================================\n\n";
    
    foreach ($data['diarias'] as $diaria) {
        $id = isset($diaria['id']) ? (int)$diaria['id'] : 'NULL';
        $motoristaId = isset($diaria['motoristaId']) ? (int)$diaria['motoristaId'] : 'NULL';
        $veiculoId = isset($diaria['veiculoId']) ? (int)$diaria['veiculoId'] : 'NULL';
        $data = escapeSql($diaria['data'] ?? date('Y-m-d'));
        $valor = isset($diaria['valor']) ? number_format($diaria['valor'], 2, '.', '') : '0.00';
        $status = escapeSql($diaria['status'] ?? 'Pendente');
        
        $sql .= "INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES ";
        $sql .= "({$id}, {$motoristaId}, {$veiculoId}, {$data}, {$valor}, {$status}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "motorista_id = {$motoristaId}, ";
        $sql .= "veiculo_id = {$veiculoId}, ";
        $sql .= "data = {$data}, ";
        $sql .= "valor = {$valor}, ";
        $sql .= "status = {$status};\n";
        
        $stats['diarias']++;
    }
    $sql .= "\n";
}

// Migrar Manutenções
if (isset($data['manutencoes']) && is_array($data['manutencoes'])) {
    $sql .= "-- ============================================\n";
    $sql .= "-- MANUTENÇÕES\n";
    $sql .= "-- ============================================\n\n";
    
    foreach ($data['manutencoes'] as $manutencao) {
        $id = isset($manutencao['id']) ? (int)$manutencao['id'] : 'NULL';
        $veiculoId = isset($manutencao['veiculoId']) ? (int)$manutencao['veiculoId'] : 'NULL';
        $tipo = escapeSql($manutencao['tipo'] ?? 'Preventiva');
        $data = escapeSql($manutencao['data'] ?? date('Y-m-d'));
        $valor = isset($manutencao['valor']) ? number_format($manutencao['valor'], 2, '.', '') : '0.00';
        $descricao = escapeSql($manutencao['descricao'] ?? '');
        
        $sql .= "INSERT INTO manutencoes (id, veiculo_id, tipo, data, valor, descricao) VALUES ";
        $sql .= "({$id}, {$veiculoId}, {$tipo}, {$data}, {$valor}, {$descricao}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "veiculo_id = {$veiculoId}, ";
        $sql .= "tipo = {$tipo}, ";
        $sql .= "data = {$data}, ";
        $sql .= "valor = {$valor}, ";
        $sql .= "descricao = {$descricao};\n";
        
        $stats['manutencoes']++;
    }
    $sql .= "\n";
}

// Migrar Contratos
if (isset($data['contratos']) && is_array($data['contratos'])) {
    $sql .= "-- ============================================\n";
    $sql .= "-- CONTRATOS\n";
    $sql .= "-- ============================================\n\n";
    
    foreach ($data['contratos'] as $contrato) {
        $id = isset($contrato['id']) ? (int)$contrato['id'] : 'NULL';
        $veiculoId = isset($contrato['veiculoId']) ? (int)$contrato['veiculoId'] : 'NULL';
        $motoristaId = isset($contrato['motoristaId']) ? (int)$contrato['motoristaId'] : 'NULL';
        $dataInicio = escapeSql($contrato['dataInicio'] ?? date('Y-m-d'));
        $dataVencimento = escapeSql($contrato['dataVencimento'] ?? date('Y-m-d', strtotime('+30 days')));
        $duracaoDias = isset($contrato['duracao']) ? (int)$contrato['duracao'] : 30;
        $valorMensal = isset($contrato['valorMensal']) ? number_format($contrato['valorMensal'], 2, '.', '') : '0.00';
        $observacoes = escapeSql($contrato['observacoes'] ?? '');
        $status = escapeSql($contrato['status'] ?? 'Ativo');
        
        $sql .= "INSERT INTO contratos (id, veiculo_id, motorista_id, data_inicio, data_vencimento, duracao_dias, valor_mensal, observacoes, status) VALUES ";
        $sql .= "({$id}, {$veiculoId}, {$motoristaId}, {$dataInicio}, {$dataVencimento}, {$duracaoDias}, {$valorMensal}, {$observacoes}, {$status}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "veiculo_id = {$veiculoId}, ";
        $sql .= "motorista_id = {$motoristaId}, ";
        $sql .= "data_inicio = {$dataInicio}, ";
        $sql .= "data_vencimento = {$dataVencimento}, ";
        $sql .= "duracao_dias = {$duracaoDias}, ";
        $sql .= "valor_mensal = {$valorMensal}, ";
        $sql .= "observacoes = {$observacoes}, ";
        $sql .= "status = {$status};\n";
        
        $stats['contratos']++;
    }
    $sql .= "\n";
}

// Migrar Financeiro
if (isset($data['financeiro']) && is_array($data['financeiro']) && count($data['financeiro']) > 0) {
    $sql .= "-- ============================================\n";
    $sql .= "-- FINANCEIRO\n";
    $sql .= "-- ============================================\n\n";
    
    foreach ($data['financeiro'] as $financeiro) {
        $id = isset($financeiro['id']) ? (int)$financeiro['id'] : 'NULL';
        $descricao = escapeSql($financeiro['descricao'] ?? '');
        $valor = isset($financeiro['valor']) ? number_format($financeiro['valor'], 2, '.', '') : '0.00';
        $data = escapeSql($financeiro['data'] ?? date('Y-m-d'));
        $tipo = escapeSql($financeiro['tipo'] ?? 'Despesa');
        $categoria = escapeSql($financeiro['categoria'] ?? 'Outros');
        
        $sql .= "INSERT INTO financeiro (id, descricao, valor, data, tipo, categoria) VALUES ";
        $sql .= "({$id}, {$descricao}, {$valor}, {$data}, {$tipo}, {$categoria}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "descricao = {$descricao}, ";
        $sql .= "valor = {$valor}, ";
        $sql .= "data = {$data}, ";
        $sql .= "tipo = {$tipo}, ";
        $sql .= "categoria = {$categoria};\n";
        
        $stats['financeiro']++;
    }
    $sql .= "\n";
}

// Finalizar transação
$sql .= "\n-- Finalizar transação\n";
$sql .= "COMMIT;\n\n";
$sql .= "-- Reabilitar verificações\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$sql .= "SET AUTOCOMMIT = 1;\n\n";
$sql .= "-- ============================================\n";
$sql .= "-- FIM DO SCRIPT\n";
$sql .= "-- ============================================\n";

// Salvar arquivo SQL
$sqlFileName = 'realdriver-import-' . date('Y-m-d_His') . '.sql';
$sqlFilePath = __DIR__ . '/' . $sqlFileName;
file_put_contents($sqlFilePath, $sql);

// Exibir resultado
echo "<h2>✅ SQL Gerado com Sucesso!</h2>";
echo "<h3>📊 Resumo:</h3>";
echo "<ul>";
foreach ($stats as $tipo => $quantidade) {
    echo "<li><strong>" . ucfirst($tipo) . ":</strong> {$quantidade} registros</li>";
}
echo "</ul>";

echo "<h3>📥 Download do Arquivo SQL:</h3>";
echo "<p><a href='/{$sqlFileName}' download style='display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>⬇️ Baixar Arquivo SQL</a></p>";

echo "<h3>📋 Visualizar SQL:</h3>";
echo "<textarea style='width: 100%; height: 400px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;' readonly>";
echo htmlspecialchars($sql);
echo "</textarea>";

echo "<h3>📝 Como Usar:</h3>";
echo "<ol>";
echo "<li><strong>Opção 1 - phpMyAdmin:</strong> Acesse o phpMyAdmin, selecione o banco <code>u179630068_realdriver</code>, vá na aba 'SQL' e cole o conteúdo acima ou faça upload do arquivo SQL.</li>";
echo "<li><strong>Opção 2 - Linha de Comando:</strong> Execute: <code>mysql -u usuario -p u179630068_realdriver < {$sqlFileName}</code></li>";
echo "<li><strong>Opção 3 - Copiar e Colar:</strong> Copie o conteúdo do textarea acima e cole no phpMyAdmin.</li>";
echo "</ol>";

echo "<p><strong>⚠️ Importante:</strong> O script usa <code>ON DUPLICATE KEY UPDATE</code>, então pode ser executado várias vezes sem duplicar dados.</p>";

