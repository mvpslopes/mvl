<?php
/**
 * Teste de Sessão - Verificar se a sessão está funcionando
 * 
 * Acesse: https://mvlopes.com.br/api/test-session.php
 * 
 * ⚠️ IMPORTANTE: Remova este arquivo após testar por questões de segurança
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste de Sessão</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .ok { color: green; font-weight: bold; }
        .erro { color: red; font-weight: bold; }
        .info { color: #666; background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Teste de Sessão PHP</h1>
        
        <?php
        session_start();
        
        echo "<h2>1. Informações da Sessão</h2>";
        echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
        echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "<span class='ok'>✅ Ativa</span>" : "<span class='erro'>❌ Inativa</span>") . "</p>";
        
        echo "<h2>2. Dados da Sessão</h2>";
        if (empty($_SESSION)) {
            echo "<p class='erro'>❌ Sessão vazia - nenhum dado encontrado</p>";
            echo "<p class='info'>Faça login primeiro em <a href='/login'>/login</a></p>";
        } else {
            echo "<p class='ok'>✅ Sessão contém dados:</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
        }
        
        echo "<h2>3. Verificação de Autenticação</h2>";
        $hasToken = isset($_SESSION['auth_token']);
        $hasRole = isset($_SESSION['user_role']);
        $role = $_SESSION['user_role'] ?? 'não definido';
        $isRoot = $role === 'root';
        
        echo "<p>Token presente: " . ($hasToken ? "<span class='ok'>✅ Sim</span>" : "<span class='erro'>❌ Não</span>") . "</p>";
        echo "<p>Role presente: " . ($hasRole ? "<span class='ok'>✅ Sim</span>" : "<span class='erro'>❌ Não</span>") . "</p>";
        echo "<p>Role atual: <strong>$role</strong></p>";
        echo "<p>É root: " . ($isRoot ? "<span class='ok'>✅ Sim</span>" : "<span class='erro'>❌ Não</span>") . "</p>";
        
        if ($hasToken && $isRoot) {
            echo "<p class='ok'><strong>✅ Autenticação OK - Você pode acessar o dashboard!</strong></p>";
        } else {
            echo "<p class='erro'><strong>❌ Autenticação falhou</strong></p>";
            if (!$hasToken) {
                echo "<p class='info'>Token não encontrado. Faça login novamente.</p>";
            }
            if (!$isRoot) {
                echo "<p class='info'>Role incorreto. Role atual: '$role', necessário: 'root'</p>";
            }
        }
        
        echo "<h2>4. Cookies</h2>";
        if (empty($_COOKIE)) {
            echo "<p class='info'>Nenhum cookie encontrado</p>";
        } else {
            echo "<pre>";
            print_r($_COOKIE);
            echo "</pre>";
        }
        
        echo "<h2>5. Headers da Requisição</h2>";
        echo "<pre>";
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[$name] = $value;
        }
        print_r($headers);
        echo "</pre>";
        ?>
        
        <hr>
        <p><small>⚠️ <strong>Lembre-se:</strong> Remova este arquivo após testar por questões de segurança!</small></p>
    </div>
</body>
</html>

