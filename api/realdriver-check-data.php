<?php
/**
 * Script de Diagnóstico - Verificar Dados no Banco
 * 
 * Este script verifica se os dados foram inseridos corretamente no banco de dados.
 */

require_once __DIR__ . '/realdriver-config.php';

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico - Dados Real Driver</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        h2 { color: #667eea; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f9f9f9; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .count { font-size: 24px; font-weight: bold; color: #667eea; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Diagnóstico - Dados Real Driver</h1>";

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'><strong>✅ Conexão com banco de dados estabelecida!</strong></div>";
    
    // Verificar cada tabela
    $tables = ['motoristas', 'veiculos', 'diarias', 'manutencoes', 'contratos', 'financeiro'];
    
    echo "<h2>📊 Resumo dos Dados</h2>";
    echo "<table>";
    echo "<tr><th>Tabela</th><th>Total de Registros</th><th>Status</th></tr>";
    
    $totalRecords = 0;
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$table}`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['total'];
            $totalRecords += $count;
            
            $status = $count > 0 ? "<span class='success'>✅ Dados encontrados</span>" : "<span class='warning'>⚠️ Tabela vazia</span>";
            
            echo "<tr>";
            echo "<td><strong>{$table}</strong></td>";
            echo "<td><span class='count'>{$count}</span></td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td><strong>{$table}</strong></td>";
            echo "<td>-</td>";
            echo "<td><span class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</span></td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<div class='info'><strong>Total geral de registros:</strong> <span class='count'>{$totalRecords}</span></div>";
    
    // Mostrar alguns exemplos de cada tabela
    echo "<h2>📋 Exemplos de Dados</h2>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$table}` ORDER BY id LIMIT 5");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($data) > 0) {
                echo "<h3>{$table} (mostrando até 5 registros)</h3>";
                echo "<table>";
                
                // Cabeçalho
                $firstRow = $data[0];
                echo "<tr>";
                foreach (array_keys($firstRow) as $key) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                
                // Dados
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        $displayValue = $value === null ? '<em>NULL</em>' : htmlspecialchars($value);
                        echo "<td>{$displayValue}</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Erro ao consultar {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Verificar se há problemas com foreign keys
    echo "<h2>🔗 Verificação de Foreign Keys</h2>";
    
    // Verificar diarias sem motorista ou veículo válido
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM diarias d 
            LEFT JOIN motoristas m ON d.motorista_id = m.id 
            LEFT JOIN veiculos v ON d.veiculo_id = v.id 
            WHERE m.id IS NULL OR v.id IS NULL
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $orphanDiarias = (int)$result['total'];
        
        if ($orphanDiarias > 0) {
            echo "<div class='warning'><strong>⚠️ Atenção:</strong> {$orphanDiarias} diária(s) com motorista ou veículo inválido!</div>";
        } else {
            echo "<div class='success'>✅ Todas as diárias têm motorista e veículo válidos</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Erro ao verificar foreign keys: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Verificar manutencoes sem veículo válido
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM manutencoes m 
            LEFT JOIN veiculos v ON m.veiculo_id = v.id 
            WHERE v.id IS NULL
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $orphanManutencoes = (int)$result['total'];
        
        if ($orphanManutencoes > 0) {
            echo "<div class='warning'><strong>⚠️ Atenção:</strong> {$orphanManutencoes} manutenção(ões) com veículo inválido!</div>";
        } else {
            echo "<div class='success'>✅ Todas as manutenções têm veículo válido</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Erro ao verificar foreign keys: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Testar a API
    echo "<h2>🌐 Teste da API</h2>";
    echo "<p><a href='realdriver-api.php?action=getAll' target='_blank'>Testar API (action=getAll)</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Erro de conexão:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";

