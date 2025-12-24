<?php
/**
 * Teste direto do analytics.php com token real
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
    <title>Teste Direto - Analytics API</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
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
            max-height: 400px;
            overflow-y: auto;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste Direto - Analytics API</h1>
        
        <?php
        // Configurações do banco de dados
        $host = 'localhost';
        $dbname = 'u179630068_mvl';
        $username = 'u179630068_mvlroot';
        $dbpassword = '/5ld=SX7j;W';
        
        echo "<h2>1. Buscar Token do Banco de Dados</h2>";
        echo "<div class='test-section'>";
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $dbpassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Buscar token mais recente
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
                echo "<p class='ok'>✅ Token encontrado no banco</p>";
                echo "<p class='info'>👤 Usuário: " . htmlspecialchars($tokenData['nome']) . " (" . htmlspecialchars($tokenData['email']) . ")</p>";
                echo "<p class='info'>🔑 Token: " . substr($token, 0, 30) . "...</p>";
                echo "<p class='info'>📅 Expira em: " . htmlspecialchars($tokenData['expires_at']) . "</p>";
                echo "<p class='info'>🎭 Role: " . htmlspecialchars($tokenData['user_role']) . "</p>";
                
                echo "<h2>2. Testar Requisição ao analytics.php</h2>";
                echo "<div class='test-section'>";
                
                // Preparar contexto para simular requisição
                $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/analytics.php?days=7';
                
                echo "<p class='info'>🌐 URL: <code>" . htmlspecialchars($url) . "</code></p>";
                
                // Usar cURL para fazer a requisição
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headers = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);
                
                $error = curl_error($ch);
                curl_close($ch);
                
                echo "<p class='info'>📊 Status HTTP: <strong>$httpCode</strong></p>";
                
                if ($error) {
                    echo "<p class='erro'>❌ Erro cURL: " . htmlspecialchars($error) . "</p>";
                }
                
                if ($httpCode === 200) {
                    echo "<p class='ok'>✅ Requisição bem-sucedida!</p>";
                    $data = json_decode($body, true);
                    if ($data) {
                        echo "<p class='ok'>✅ Resposta JSON válida</p>";
                        echo "<pre>";
                        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        echo "</pre>";
                    } else {
                        echo "<p class='erro'>❌ Resposta não é JSON válido</p>";
                        echo "<pre>" . htmlspecialchars($body) . "</pre>";
                    }
                } else {
                    echo "<p class='erro'>❌ Erro HTTP $httpCode</p>";
                    echo "<p class='info'>📋 Headers da resposta:</p>";
                    echo "<pre>" . htmlspecialchars($headers) . "</pre>";
                    echo "<p class='info'>📋 Corpo da resposta:</p>";
                    echo "<pre>" . htmlspecialchars($body) . "</pre>";
                    
                    $errorData = json_decode($body, true);
                    if ($errorData) {
                        echo "<p class='info'>📋 Dados do erro:</p>";
                        echo "<pre>";
                        echo json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        echo "</pre>";
                    }
                }
                
                echo "</div>";
                
                echo "<h2>3. Testar com Requisição Interna (simulando analytics.php)</h2>";
                echo "<div class='test-section'>";
                
                // Simular o que o analytics.php faria
                $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
                $_GET['days'] = 7;
                
                // Capturar output do analytics.php
                ob_start();
                try {
                    // Incluir analytics.php e capturar sua saída
                    $oldGet = $_GET;
                    $oldServer = $_SERVER;
                    
                    $_GET = ['days' => 7];
                    $_SERVER['REQUEST_METHOD'] = 'GET';
                    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
                    
                    // Tentar incluir o arquivo
                    $analyticsPath = __DIR__ . '/analytics.php';
                    if (file_exists($analyticsPath)) {
                        echo "<p class='ok'>✅ Arquivo analytics.php encontrado</p>";
                        
                        // Fazer include e capturar output
                        ob_start();
                        include $analyticsPath;
                        $output = ob_get_clean();
                        
                        echo "<p class='info'>📋 Saída do analytics.php:</p>";
                        echo "<pre>" . htmlspecialchars($output) . "</pre>";
                        
                        $jsonData = json_decode($output, true);
                        if ($jsonData) {
                            if (isset($jsonData['success']) && $jsonData['success']) {
                                echo "<p class='ok'>✅ analytics.php retornou sucesso!</p>";
                            } else {
                                echo "<p class='erro'>❌ analytics.php retornou erro: " . ($jsonData['message'] ?? 'Erro desconhecido') . "</p>";
                            }
                        }
                    } else {
                        echo "<p class='erro'>❌ Arquivo analytics.php não encontrado em: " . htmlspecialchars($analyticsPath) . "</p>";
                    }
                    
                    $_GET = $oldGet;
                    $_SERVER = $oldServer;
                } catch (Exception $e) {
                    echo "<p class='erro'>❌ Erro ao executar analytics.php: " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p class='info'>Stack trace:</p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                }
                ob_end_clean();
                
                echo "</div>";
                
            } else {
                echo "<p class='erro'>❌ Nenhum token válido encontrado no banco</p>";
                echo "<p class='info'>ℹ️ Faça login primeiro para gerar um token</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='erro'>❌ Erro ao conectar ao banco: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "</div>";
        
        echo "<h2>4. Verificar Logs de Erro do PHP</h2>";
        echo "<div class='test-section'>";
        echo "<p class='info'>ℹ️ Verifique os logs de erro do PHP no servidor para mais detalhes sobre erros 500.</p>";
        echo "<p class='info'>ℹ️ Os logs geralmente estão em:</p>";
        echo "<ul>";
        echo "<li><code>/var/log/php_errors.log</code></li>";
        echo "<li><code>/var/log/apache2/error.log</code></li>";
        echo "<li><code>/var/log/nginx/error.log</code></li>";
        echo "<li>Ou verifique a configuração do <code>error_log</code> no php.ini</li>";
        echo "</ul>";
        echo "</div>";
        ?>
    </div>
</body>
</html>

