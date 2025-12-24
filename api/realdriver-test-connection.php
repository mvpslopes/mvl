<?php
/**
 * Script de Teste de Conexão - Real Driver
 * 
 * Este script testa a conexão com o banco de dados
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Teste de Conexão - Real Driver</h1>";

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];

echo "<h2>Configurações:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . htmlspecialchars($db['host']) . "</li>";
echo "<li><strong>Database:</strong> " . htmlspecialchars($db['dbname']) . "</li>";
echo "<li><strong>Username:</strong> " . htmlspecialchars($db['username']) . "</li>";
echo "<li><strong>Password:</strong> " . (strlen($db['password']) > 0 ? '***' : 'VAZIA') . "</li>";
echo "</ul>";

echo "<h2>Testando Conexão...</h2>";

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green; font-size: 18px;'>✅ <strong>Conexão bem-sucedida!</strong></p>";
    
    // Verificar se as tabelas existem
    echo "<h2>Verificando Tabelas:</h2>";
    $tables = [
        'motoristas',
        'veiculos',
        'diarias',
        'manutencoes',
        'contratos',
        'financeiro',
        'perfis',
        'usuarios',
        'auth_tokens'
    ];
    
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        $exists = in_array($table, $existingTables);
        $color = $exists ? 'green' : 'red';
        $icon = $exists ? '✅' : '❌';
        echo "<li style='color: {$color};'>{$icon} <strong>{$table}</strong> - " . ($exists ? 'Existe' : 'NÃO EXISTE') . "</li>";
    }
    echo "</ul>";
    
    // Verificar usuários
    echo "<h2>Verificando Usuários:</h2>";
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.nome, u.email, p.nome as perfil 
            FROM usuarios u 
            INNER JOIN perfis p ON u.perfil_id = p.id
        ");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($usuarios) > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Perfil</th></tr>";
            foreach ($usuarios as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['nome']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['perfil']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhum usuário encontrado. Execute o script SQL para criar os usuários.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erro ao verificar usuários: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-size: 18px;'>❌ <strong>Erro de conexão:</strong></p>";
    echo "<pre style='background: #fee; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    
    echo "<h2>Possíveis Causas:</h2>";
    echo "<ul>";
    echo "<li>Banco de dados não existe: <code>{$db['dbname']}</code></li>";
    echo "<li>Credenciais incorretas (usuário/senha)</li>";
    echo "<li>Host incorreto: <code>{$db['host']}</code></li>";
    echo "<li>Usuário não tem permissão para acessar o banco</li>";
    echo "</ul>";
    
    echo "<h2>Soluções:</h2>";
    echo "<ol>";
    echo "<li>Verifique se o banco de dados <strong>{$db['dbname']}</strong> foi criado</li>";
    echo "<li>Verifique se o usuário <strong>{$db['username']}</strong> tem acesso ao banco</li>";
    echo "<li>Verifique se a senha está correta</li>";
    echo "<li>Verifique se o host está correto (geralmente 'localhost')</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><a href='realdriver.php'>← Voltar para o sistema</a></p>";

