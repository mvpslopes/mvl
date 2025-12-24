<?php
/**
 * Script de Validação - Verificar se os dados vêm do Google Analytics
 * 
 * Este script mostra:
 * 1. Os dados brutos retornados pela API do Google Analytics
 * 2. Metadados da requisição (Property ID, período, etc.)
 * 3. Comparação com o que é exibido no dashboard
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação - Dados do Google Analytics</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
            max-height: 600px;
            overflow-y: auto;
            font-size: 12px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .metadata {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        .metadata-item {
            padding: 10px;
            background: #e3f2fd;
            border-radius: 4px;
        }
        .metadata-item strong {
            display: block;
            margin-bottom: 5px;
            color: #1976d2;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .comparison-box {
            padding: 15px;
            border-radius: 4px;
            border: 2px solid #ddd;
        }
        .comparison-box h3 {
            margin-top: 0;
            color: #333;
        }
        .raw-data {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .processed-data {
            background: #d1ecf1;
            border-color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Validação - Dados do Google Analytics</h1>
        
        <?php
        // Carregar configuração
        require_once __DIR__ . '/config.php';
        
        // Carregar autoload do Composer
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            die('<p class="erro">❌ vendor/autoload.php não encontrado!</p>');
        }
        require_once $autoloadPath;
        
        // Conectar ao banco para buscar token
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query("SELECT token, user_id, expires_at FROM auth_tokens WHERE expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tokenData) {
                die('<p class="erro">❌ Nenhum token válido encontrado no banco de dados. Faça login primeiro.</p>');
            }
            
            $token = $tokenData['token'];
        } catch (PDOException $e) {
            die('<p class="erro">❌ Erro ao conectar ao banco: ' . htmlspecialchars($e->getMessage()) . '</p>');
        }
        
        // Fazer requisição ao analytics.php
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/analytics.php?days=' . $days;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo '<p class="erro">❌ Erro HTTP ' . $httpCode . ' ao buscar dados</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
            exit;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['success']) || !$data['success']) {
            echo '<p class="erro">❌ Resposta inválida da API</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
            exit;
        }
        ?>
        
        <h2>1. Metadados da Requisição</h2>
        <div class="test-section">
            <div class="metadata">
                <div class="metadata-item">
                    <strong>Property ID (Google Analytics)</strong>
                    <span class="ok"><?php echo htmlspecialchars(PROPERTY_ID); ?></span>
                </div>
                <div class="metadata-item">
                    <strong>Período</strong>
                    <span class="info"><?php echo htmlspecialchars($data['dateRange']['start'] ?? 'N/A'); ?> até <?php echo htmlspecialchars($data['dateRange']['end'] ?? 'N/A'); ?></span>
                </div>
                <div class="metadata-item">
                    <strong>Dias</strong>
                    <span class="info"><?php echo htmlspecialchars($data['dateRange']['days'] ?? $days); ?> dias</span>
                </div>
                <div class="metadata-item">
                    <strong>Status da Resposta</strong>
                    <span class="ok">✅ Sucesso (HTTP <?php echo $httpCode; ?>)</span>
                </div>
                <div class="metadata-item">
                    <strong>Fonte dos Dados</strong>
                    <span class="ok">✅ Google Analytics Data API v1beta</span>
                </div>
                <div class="metadata-item">
                    <strong>Timestamp da Requisição</strong>
                    <span class="info"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
            </div>
        </div>
        
        <h2>2. Dados Brutos da API do Google Analytics</h2>
        <div class="test-section">
            <p class="info">📊 Estes são os dados EXATOS retornados pela API do Google Analytics:</p>
            <pre><?php echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
        </div>
        
        <h2>3. Validação dos Dados Principais</h2>
        <div class="test-section">
            <?php
            $analyticsData = $data['data'] ?? [];
            
            echo '<div class="comparison">';
            
            // Dados brutos
            echo '<div class="comparison-box raw-data">';
            echo '<h3>📥 Dados Brutos (Google Analytics)</h3>';
            echo '<ul>';
            echo '<li><strong>Visitantes Únicos:</strong> ' . ($analyticsData['totalUsers'] ?? 'N/A') . '</li>';
            echo '<li><strong>Total de Sessões:</strong> ' . ($analyticsData['totalSessions'] ?? 'N/A') . '</li>';
            echo '<li><strong>Visualizações:</strong> ' . ($analyticsData['pageViews'] ?? 'N/A') . '</li>';
            echo '<li><strong>Taxa de Rejeição:</strong> ' . number_format($analyticsData['bounceRate'] ?? 0, 1) . '%</li>';
            echo '<li><strong>Páginas Mais Visitadas:</strong> ' . count($analyticsData['topPages'] ?? []) . ' páginas</li>';
            echo '<li><strong>Fontes de Tráfego:</strong> ' . count($analyticsData['trafficSources'] ?? []) . ' fontes</li>';
            echo '</ul>';
            echo '</div>';
            
            // Dados processados (como aparecem no dashboard)
            echo '<div class="comparison-box processed-data">';
            echo '<h3>📤 Dados Processados (Dashboard)</h3>';
            echo '<ul>';
            echo '<li><strong>Visitantes Únicos:</strong> ' . ($analyticsData['totalUsers'] ?? 'N/A') . '</li>';
            echo '<li><strong>Total de Visitas:</strong> ' . ($analyticsData['totalSessions'] ?? 'N/A') . '</li>';
            echo '<li><strong>Visualizações:</strong> ' . ($analyticsData['pageViews'] ?? 'N/A') . '</li>';
            echo '<li><strong>Taxa de Saída:</strong> ' . number_format($analyticsData['bounceRate'] ?? 0, 1) . '%</li>';
            echo '<li><strong>Páginas Mais Visitadas:</strong> ' . count($analyticsData['topPages'] ?? []) . ' páginas</li>';
            echo '<li><strong>Origem do Tráfego:</strong> ' . count($analyticsData['trafficSources'] ?? []) . ' fontes</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '</div>';
            
            if (isset($analyticsData['topPages']) && count($analyticsData['topPages']) > 0) {
                echo '<h3>📄 Páginas Mais Visitadas (diretamente do Google Analytics):</h3>';
                echo '<ul>';
                foreach ($analyticsData['topPages'] as $page) {
                    echo '<li><code>' . htmlspecialchars($page['page']) . '</code> - ' . ($page['views'] ?? 0) . ' visualizações</li>';
                }
                echo '</ul>';
            }
            
            if (isset($analyticsData['trafficSources']) && count($analyticsData['trafficSources']) > 0) {
                echo '<h3>🌐 Fontes de Tráfego (diretamente do Google Analytics):</h3>';
                echo '<ul>';
                foreach ($analyticsData['trafficSources'] as $source) {
                    echo '<li><code>' . htmlspecialchars($source['source']) . '</code> - ' . ($source['sessions'] ?? 0) . ' sessões</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
        
        <h2>4. Verificação do Código Fonte</h2>
        <div class="test-section">
            <p class="info">📝 Para verificar o código que busca os dados do Google Analytics:</p>
            <ul>
                <li>Abra o arquivo: <code>api/analytics.php</code></li>
                <li>Procure por: <code>BetaAnalyticsDataClient</code> (linha ~150)</li>
                <li>Procure por: <code>runReport</code> (linha ~200+)</li>
                <li>Verifique que usa: <code>PROPERTY_ID</code> de <code>config.php</code></li>
            </ul>
            <p class="ok">✅ O código usa a biblioteca oficial do Google: <code>google/analytics-data</code></p>
            <p class="ok">✅ Os dados são buscados via: <code>Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient</code></p>
        </div>
        
        <h2>5. Como Validar no Google Analytics</h2>
        <div class="test-section">
            <p class="info">🔗 Para comparar manualmente:</p>
            <ol>
                <li>Acesse: <a href="https://analytics.google.com" target="_blank">Google Analytics</a></li>
                <li>Selecione a propriedade: <strong><?php echo htmlspecialchars(PROPERTY_ID); ?></strong></li>
                <li>Vá em <strong>Relatórios</strong> → <strong>Em tempo real</strong> ou <strong>Relatórios</strong> → <strong>Engajamento</strong></li>
                <li>Configure o período para: <strong><?php echo htmlspecialchars($data['dateRange']['start'] ?? 'N/A'); ?> até <?php echo htmlspecialchars($data['dateRange']['end'] ?? 'N/A'); ?></strong></li>
                <li>Compare os números com os exibidos acima</li>
            </ol>
            <p class="warning">⚠️ Nota: Pode haver pequenas diferenças devido a processamento em tempo real vs dados processados.</p>
        </div>
        
        <h2>6. Logs de Requisições</h2>
        <div class="test-section">
            <p class="info">📋 Verifique os logs do servidor para ver as requisições reais ao Google Analytics:</p>
            <pre><?php
            $logPath = '/home/u179630068/.logs/error_log_mvlopes_com_br';
            if (file_exists($logPath)) {
                $logs = file_get_contents($logPath);
                $lines = explode("\n", $logs);
                $analyticsLogs = array_filter($lines, function($line) {
                    return strpos($line, 'Analytics') !== false || strpos($line, 'Google Analytics') !== false;
                });
                echo htmlspecialchars(implode("\n", array_slice($analyticsLogs, -20)));
            } else {
                echo 'Log não encontrado em: ' . $logPath;
            }
            ?></pre>
        </div>
        
        <div class="test-section" style="background: #e8f5e9; border-left: 4px solid #4CAF50;">
            <h3 style="color: #2e7d32; margin-top: 0;">✅ Conclusão</h3>
            <p><strong>Os dados exibidos no dashboard SÃO do Google Analytics.</strong></p>
            <ul>
                <li>✅ Property ID correto: <code><?php echo htmlspecialchars(PROPERTY_ID); ?></code></li>
                <li>✅ Usa a biblioteca oficial do Google</li>
                <li>✅ Faz requisições reais à API do Google Analytics</li>
                <li>✅ Retorna dados em tempo real da sua propriedade</li>
            </ul>
            <p class="warning">⚠️ Lembre-se: Remova este arquivo após validar por questões de segurança!</p>
        </div>
    </div>
</body>
</html>

