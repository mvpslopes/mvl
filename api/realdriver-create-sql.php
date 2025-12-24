<?php
/**
 * Script para Criar Arquivo SQL Puro
 * 
 * Execute: php realdriver-create-sql.php
 * Isso criará o arquivo REALDRIVER_IMPORT.sql
 */

// Caminho do backup (usar caminho absoluto)
$backupFile = dirname(__DIR__) . '/SistemasEmbarcados/realdriver/backups/SisRealDriver_Backup_2025-12-24_12-27-38.json';

// Normalizar caminho para Windows
$backupFile = str_replace('\\', '/', $backupFile);

if (!file_exists($backupFile)) {
    die("ERRO: Arquivo de backup não encontrado em: $backupFile\n");
}

// Ler e decodificar o JSON
$jsonContent = file_get_contents($backupFile);
if ($jsonContent === false) {
    die("ERRO: Não foi possível ler o arquivo de backup: $backupFile\n");
}

$backupData = json_decode($jsonContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERRO ao decodificar JSON: " . json_last_error_msg() . "\n");
}

if (!$backupData || !isset($backupData['data'])) {
    die("ERRO: Formato de backup inválido! Estrutura esperada: {data: {...}}\n");
}

$data = $backupData['data'];

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
        $dataDiaria = escapeSql($diaria['data'] ?? date('Y-m-d'));
        $valor = isset($diaria['valor']) ? number_format($diaria['valor'], 2, '.', '') : '0.00';
        $status = escapeSql($diaria['status'] ?? 'Pendente');
        
        $sql .= "INSERT INTO diarias (id, motorista_id, veiculo_id, data, valor, status) VALUES ";
        $sql .= "({$id}, {$motoristaId}, {$veiculoId}, {$dataDiaria}, {$valor}, {$status}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "motorista_id = {$motoristaId}, ";
        $sql .= "veiculo_id = {$veiculoId}, ";
        $sql .= "data = {$dataDiaria}, ";
        $sql .= "valor = {$valor}, ";
        $sql .= "status = {$status};\n";
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
        $dataManutencao = escapeSql($manutencao['data'] ?? date('Y-m-d'));
        $valor = isset($manutencao['valor']) ? number_format($manutencao['valor'], 2, '.', '') : '0.00';
        $descricao = escapeSql($manutencao['descricao'] ?? '');
        
        $sql .= "INSERT INTO manutencoes (id, veiculo_id, tipo, data, valor, descricao) VALUES ";
        $sql .= "({$id}, {$veiculoId}, {$tipo}, {$dataManutencao}, {$valor}, {$descricao}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "veiculo_id = {$veiculoId}, ";
        $sql .= "tipo = {$tipo}, ";
        $sql .= "data = {$dataManutencao}, ";
        $sql .= "valor = {$valor}, ";
        $sql .= "descricao = {$descricao};\n";
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
        $dataFinanceiro = escapeSql($financeiro['data'] ?? date('Y-m-d'));
        $tipo = escapeSql($financeiro['tipo'] ?? 'Despesa');
        $categoria = escapeSql($financeiro['categoria'] ?? 'Outros');
        
        $sql .= "INSERT INTO financeiro (id, descricao, valor, data, tipo, categoria) VALUES ";
        $sql .= "({$id}, {$descricao}, {$valor}, {$dataFinanceiro}, {$tipo}, {$categoria}) ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "descricao = {$descricao}, ";
        $sql .= "valor = {$valor}, ";
        $sql .= "data = {$dataFinanceiro}, ";
        $sql .= "tipo = {$tipo}, ";
        $sql .= "categoria = {$categoria};\n";
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
$totalMotoristas = count($data['motoristas'] ?? []);
$totalVeiculos = count($data['veiculos'] ?? []);
$totalDiarias = count($data['diarias'] ?? []);
$totalManutencoes = count($data['manutencoes'] ?? []);
$totalContratos = count($data['contratos'] ?? []);
$totalFinanceiro = count($data['financeiro'] ?? []);

$sql .= "-- Total: {$totalMotoristas} motoristas, ";
$sql .= "{$totalVeiculos} veículos, ";
$sql .= "{$totalDiarias} diárias, ";
$sql .= "{$totalManutencoes} manutenções, ";
$sql .= "{$totalContratos} contratos, ";
$sql .= "{$totalFinanceiro} financeiro\n";
$sql .= "-- ============================================\n";

// Salvar arquivo SQL
$sqlFileName = __DIR__ . '/REALDRIVER_IMPORT.sql';
file_put_contents($sqlFileName, $sql);

$totalMotoristas = count($data['motoristas'] ?? []);
$totalVeiculos = count($data['veiculos'] ?? []);
$totalDiarias = count($data['diarias'] ?? []);
$totalManutencoes = count($data['manutencoes'] ?? []);
$totalContratos = count($data['contratos'] ?? []);
$totalFinanceiro = count($data['financeiro'] ?? []);

echo "✅ Arquivo SQL criado com sucesso!\n";
echo "📁 Arquivo: " . $sqlFileName . "\n";
echo "📊 Total de registros:\n";
echo "   - Motoristas: {$totalMotoristas}\n";
echo "   - Veículos: {$totalVeiculos}\n";
echo "   - Diárias: {$totalDiarias}\n";
echo "   - Manutenções: {$totalManutencoes}\n";
echo "   - Contratos: {$totalContratos}\n";
echo "   - Financeiro: {$totalFinanceiro}\n";
echo "\n🚀 Agora você pode executar este arquivo no phpMyAdmin!\n";

