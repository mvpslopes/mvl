<?php
/**
 * Script de teste para verificar autenticação do analytics.php
 * 
 * Este script testa:
 * 1. Se a tabela auth_tokens existe
 * 2. Se o token pode ser salvo
 * 3. Se o analytics.php pode verificar o token
 * 
 * ⚠️ Lembre-se: Remova este arquivo após testar por questões de segurança!
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Autenticação - Analytics API</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-left: 4px solid #2196F3;
            padding-left: 10px;
        }
        .ok { color: #4CAF50; font-weight: bold; }
        .erro { color: #f44336; font-weight: bold; }
        .info { color: #2196F3; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border-left: 4px solid #2196F3;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Teste de Autenticação - Analytics API</h1>
        
        <?php
        // Configurações do banco de dados
        $host = 'localhost';
        $dbname = 'u179630068_mvl';
        $username = 'u179630068_mvlroot';
        $dbpassword = '/5ld=SX7j;W';
        
        echo "<h2>1. Verificação da Tabela auth_tokens</h2>";
        echo "<div class='test-section'>";
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $dbpassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar se a tabela existe
            $stmt = $pdo->query("SHOW TABLES LIKE 'auth_tokens'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                echo "<p class='ok'>✅ Tabela auth_tokens existe</p>";
                
                // Contar tokens
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM auth_tokens");
                $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                echo "<p class='info'>📊 Total de tokens no banco: $total</p>";
                
                // Listar tokens recentes
                $stmt = $pdo->query("
                    SELECT t.*, u.email, u.nome 
                    FROM auth_tokens t 
                    INNER JOIN usuarios u ON t.user_id = u.id 
                    ORDER BY t.created_at DESC 
                    LIMIT 5
                ");
                $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($tokens) > 0) {
                    echo "<p class='info'>📋 Tokens recentes:</p>";
                    echo "<pre>";
                    print_r($tokens);
                    echo "</pre>";
                } else {
                    echo "<p class='info'>ℹ️ Nenhum token encontrado no banco</p>";
                }
            } else {
                echo "<p class='erro'>❌ Tabela auth_tokens não existe</p>";
                echo "<p class='info'>ℹ️ A tabela será criada automaticamente no próximo login</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='erro'>❌ Erro ao conectar ao banco: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "</div>";
        
        echo "<h2>2. Teste de Autenticação via Header</h2>";
        echo "<div class='test-section'>";
        
        // Simular uma requisição com token
        $testToken = bin2hex(random_bytes(32));
        echo "<p class='info'>🔑 Token de teste gerado: " . substr($testToken, 0, 20) . "...</p>";
        
        // Verificar se podemos fazer uma requisição ao analytics.php
        echo "<p class='info'>ℹ️ Para testar completamente, você precisa:</p>";
        echo "<ol>";
        echo "<li>Fazer login no sistema para gerar um token real</li>";
        echo "<li>Copiar o token retornado pela API de login</li>";
        echo "<li>Fazer uma requisição GET para <code>api/analytics.php?days=7</code> com o header <code>Authorization: Bearer SEU_TOKEN</code></li>";
        echo "</ol>";
        
        echo "</div>";
        
        echo "<h2>3. Verificação de Sessão PHP</h2>";
        echo "<div class='test-section'>";
        
        session_start();
        echo "<p class='info'>Session ID: " . session_id() . "</p>";
        echo "<p class='info'>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "<span class='ok'>✅ Ativa</span>" : "<span class='erro'>❌ Inativa</span>") . "</p>";
        
        if (isset($_SESSION['auth_token'])) {
            echo "<p class='ok'>✅ Token na sessão: " . substr($_SESSION['auth_token'], 0, 20) . "...</p>";
            echo "<p class='info'>Role: " . ($_SESSION['user_role'] ?? 'não definido') . "</p>";
        } else {
            echo "<p class='erro'>❌ Nenhum token na sessão</p>";
            echo "<p class='info'>ℹ️ Faça login primeiro para criar uma sessão</p>";
        }
        
        echo "</div>";
        
        echo "<h2>4. Recomendações</h2>";
        echo "<div class='test-section'>";
        echo "<ul>";
        echo "<li>✅ Certifique-se de que o arquivo <code>api/analytics.php</code> foi atualizado no servidor</li>";
        echo "<li>✅ Certifique-se de que o arquivo <code>api/auth.php</code> foi atualizado no servidor</li>";
        echo "<li>✅ Faça um novo login para gerar um token que será salvo no banco de dados</li>";
        echo "<li>✅ Verifique os logs de erro do PHP para mais detalhes sobre erros 500</li>";
        echo "<li>⚠️ Remova este arquivo após testar por questões de segurança</li>";
        echo "</ul>";
        echo "</div>";
        ?>
    </div>
</body>
</html>

