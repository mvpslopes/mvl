<?php
/**
 * Diagnóstico detalhado do analytics.php
 * 
 * ⚠️ Lembre-se: Remova este arquivo após testar por questões de segurança!
 */

// Habilitar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Analytics API</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
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
        .warning { color: #ff9800; font-weight: bold; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border-left: 4px solid #2196F3;
            max-height: 500px;
            overflow-y: auto;
            font-size: 12px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico Detalhado - Analytics API</h1>
        
        <?php
        // 1. Verificar se o arquivo existe
        echo "<h2>1. Verificação de Arquivos</h2>";
        echo "<div class='test-section'>";
        
        $analyticsPath = __DIR__ . '/analytics.php';
        $configPath = __DIR__ . '/config.php';
        $credentialsPath = __DIR__ . '/credentials.json';
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        
        echo "<p>" . (file_exists($analyticsPath) ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . " analytics.php: <code>" . htmlspecialchars($analyticsPath) . "</code></p>";
        echo "<p>" . (file_exists($configPath) ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . " config.php: <code>" . htmlspecialchars($configPath) . "</code></p>";
        echo "<p>" . (file_exists($credentialsPath) ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . " credentials.json: <code>" . htmlspecialchars($credentialsPath) . "</code></p>";
        echo "<p>" . (file_exists($autoloadPath) ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . " vendor/autoload.php: <code>" . htmlspecialchars($autoloadPath) . "</code></p>";
        
        if (file_exists($analyticsPath)) {
            $fileSize = filesize($analyticsPath);
            $fileTime = filemtime($analyticsPath);
            echo "<p class='info'>📊 Tamanho: " . number_format($fileSize) . " bytes</p>";
            echo "<p class='info'>📅 Modificado: " . date('Y-m-d H:i:s', $fileTime) . "</p>";
            
            // Verificar sintaxe PHP
            $syntaxCheck = shell_exec("php -l " . escapeshellarg($analyticsPath) . " 2>&1");
            if (strpos($syntaxCheck, 'No syntax errors') !== false) {
                echo "<p class='ok'>✅ Sintaxe PHP válida</p>";
            } else {
                echo "<p class='erro'>❌ Erro de sintaxe:</p>";
                echo "<pre>" . htmlspecialchars($syntaxCheck) . "</pre>";
            }
        }
        
        echo "</div>";
        
        // 2. Buscar token
        echo "<h2>2. Buscar Token do Banco</h2>";
        echo "<div class='test-section'>";
        
        $host = 'localhost';
        $dbname = 'u179630068_mvl';
        $username = 'u179630068_mvlroot';
        $dbpassword = '/5ld=SX7j;W';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $dbpassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("
                SELECT t.*, u.email, u.nome 
                FROM auth_tokens t 
                INNER JOIN usuarios u ON t.user_id = u.id 
                WHERE t.expires_at > NOW() OR t.expires_at IS NULL
                ORDER BY t.created_at DESC 
                LIMIT 1
            ");
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tokenData) {
                $token = $tokenData['token'];
                echo "<p class='ok'>✅ Token encontrado</p>";
                echo "<p class='info'>Token: " . substr($token, 0, 30) . "...</p>";
            } else {
                echo "<p class='erro'>❌ Nenhum token válido encontrado</p>";
                exit;
            }
        } catch (PDOException $e) {
            echo "<p class='erro'>❌ Erro ao buscar token: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
        
        echo "</div>";
        
        // 3. Testar execução direta do analytics.php
        echo "<h2>3. Execução Direta do analytics.php</h2>";
        echo "<div class='test-section'>";
        
        // Configurar ambiente
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $_GET['days'] = 7;
        $_SERVER['HTTP_HOST'] = 'mvlopes.com.br';
        $_SERVER['REQUEST_URI'] = '/api/analytics.php?days=7';
        
        // Capturar output e erros
        ob_start();
        $errorOutput = '';
        
        // Redirecionar stderr para capturar erros
        $oldErrorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errorOutput) {
            $errorOutput .= "[$errno] $errstr in $errfile on line $errline\n";
            return false;
        });
        
        try {
            // Tentar incluir o arquivo
            if (file_exists($analyticsPath)) {
                echo "<p class='info'>🔄 Executando analytics.php...</p>";
                
                // Capturar qualquer output
                include $analyticsPath;
                
                $output = ob_get_clean();
                
                echo "<p class='ok'>✅ Execução concluída</p>";
                echo "<p class='info'>📋 Output capturado (" . strlen($output) . " bytes):</p>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                
                // Tentar decodificar JSON
                $jsonData = json_decode($output, true);
                if ($jsonData !== null) {
                    echo "<p class='ok'>✅ Resposta é JSON válido</p>";
                    echo "<pre>" . json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                } else {
                    echo "<p class='warning'>⚠️ Resposta não é JSON válido</p>";
                }
            } else {
                echo "<p class='erro'>❌ Arquivo analytics.php não encontrado</p>";
            }
        } catch (Throwable $e) {
            ob_end_clean();
            echo "<p class='erro'>❌ Exceção capturada:</p>";
            echo "<pre>";
            echo "Mensagem: " . htmlspecialchars($e->getMessage()) . "\n";
            echo "Arquivo: " . htmlspecialchars($e->getFile()) . "\n";
            echo "Linha: " . $e->getLine() . "\n";
            echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString());
            echo "</pre>";
        } catch (Error $e) {
            ob_end_clean();
            echo "<p class='erro'>❌ Erro fatal capturado:</p>";
            echo "<pre>";
            echo "Mensagem: " . htmlspecialchars($e->getMessage()) . "\n";
            echo "Arquivo: " . htmlspecialchars($e->getFile()) . "\n";
            echo "Linha: " . $e->getLine() . "\n";
            echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString());
            echo "</pre>";
        }
        
        // Restaurar error handler
        if ($oldErrorHandler !== null) {
            set_error_handler($oldErrorHandler);
        } else {
            restore_error_handler();
        }
        
        if (!empty($errorOutput)) {
            echo "<p class='warning'>⚠️ Erros capturados:</p>";
            echo "<pre>" . htmlspecialchars($errorOutput) . "</pre>";
        }
        
        echo "</div>";
        
        // 4. Verificar logs de erro do PHP
        echo "<h2>4. Verificar Logs de Erro</h2>";
        echo "<div class='test-section'>";
        
        $errorLogPath = ini_get('error_log');
        if ($errorLogPath && file_exists($errorLogPath)) {
            echo "<p class='info'>📋 Caminho do log: <code>" . htmlspecialchars($errorLogPath) . "</code></p>";
            
            // Ler últimas 50 linhas do log
            $logLines = file($errorLogPath);
            $recentLines = array_slice($logLines, -50);
            
            echo "<p class='info'>📋 Últimas 50 linhas do log:</p>";
            echo "<pre>" . htmlspecialchars(implode('', $recentLines)) . "</pre>";
        } else {
            echo "<p class='warning'>⚠️ Arquivo de log não encontrado ou não configurado</p>";
            echo "<p class='info'>ℹ️ Verifique a configuração do <code>error_log</code> no php.ini</p>";
        }
        
        echo "</div>";
        
        // 5. Verificar extensões PHP necessárias
        echo "<h2>5. Verificar Extensões PHP</h2>";
        echo "<div class='test-section'>";
        
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'openssl', 'mbstring'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            echo "<p>" . ($loaded ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . " $ext</p>";
        }
        
        echo "</div>";
        ?>
    </div>
</body>
</html>

