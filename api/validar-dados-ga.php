<?php
/**
 * Script de Validação - Dados do Google Analytics
 * 
 * Este script mostra claramente quais dados vêm do Google Analytics
 * e quais são mockados/calculados no frontend.
 * 
 * ⚠️ Lembre-se: Remova este arquivo após validar por questões de segurança!
 */

header('Content-Type: text/html; charset=utf-8');

// Buscar token do banco
$host = 'localhost';
$dbname = 'u179630068_mvl';
$username = 'u179630068_mvlroot';
$dbpassword = '/5ld=SX7j;W';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
        SELECT t.token 
        FROM auth_tokens t 
        INNER JOIN usuarios u ON t.user_id = u.id 
        WHERE t.expires_at > NOW() OR t.expires_at IS NULL
        ORDER BY t.created_at DESC 
        LIMIT 1
    ");
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData) {
        die('Token não encontrado. Faça login primeiro.');
    }
    
    $token = $tokenData['token'];
} catch (PDOException $e) {
    die('Erro ao buscar token: ' . $e->getMessage());
}

// Fazer requisição à API
$url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/analytics.php?days=7';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
$config = require __DIR__ . '/config.php';
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
        .mock { color: #9e9e9e; font-style: italic; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border-left: 4px solid #2196F3;
            max-height: 400px;
            overflow-y: auto;
            font-size: 12px;
        }
        .data-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .data-item {
            padding: 8px;
            margin: 5px 0;
            border-left: 3px solid #4CAF50;
            background: #e8f5e9;
        }
        .data-item.mock {
            border-left-color: #9e9e9e;
            background: #f5f5f5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2196F3;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-real {
            background: #4CAF50;
            color: white;
        }
        .badge-mock {
            background: #9e9e9e;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Validação de Dados - Google Analytics</h1>
        
        <div class="data-section">
            <h2>📊 Status da API</h2>
            <p><strong>Status HTTP:</strong> <?php echo $httpCode === 200 ? '<span class="ok">✅ 200 OK</span>' : '<span class="erro">❌ ' . $httpCode . '</span>'; ?></p>
            <p><strong>Property ID:</strong> <code><?php echo htmlspecialchars($config['ga4']['property_id']); ?></code></p>
            <p><strong>Usando dados mockados:</strong> <?php echo $config['api']['use_mock_data'] ? '<span class="warning">⚠️ SIM</span>' : '<span class="ok">✅ NÃO (dados reais)</span>'; ?></p>
        </div>

        <?php if ($data && isset($data['success']) && $data['success']): ?>
        
        <div class="data-section">
            <h2>✅ Dados Reais do Google Analytics</h2>
            <p class="info">Estes dados vêm diretamente da API do Google Analytics:</p>
            
            <table>
                <tr>
                    <th>Métrica</th>
                    <th>Valor</th>
                    <th>Fonte</th>
                </tr>
                <tr>
                    <td><strong>Visitantes Únicos (totalUsers)</strong></td>
                    <td><?php echo number_format($data['data']['totalUsers'] ?? 0); ?></td>
                    <td><span class="badge badge-real">Google Analytics</span></td>
                </tr>
                <tr>
                    <td><strong>Total de Sessões (totalSessions)</strong></td>
                    <td><?php echo number_format($data['data']['totalSessions'] ?? 0); ?></td>
                    <td><span class="badge badge-real">Google Analytics</span></td>
                </tr>
                <tr>
                    <td><strong>Visualizações de Página (pageViews)</strong></td>
                    <td><?php echo number_format($data['data']['pageViews'] ?? 0); ?></td>
                    <td><span class="badge badge-real">Google Analytics</span></td>
                </tr>
                <tr>
                    <td><strong>Taxa de Rejeição (bounceRate)</strong></td>
                    <td><?php echo number_format($data['data']['bounceRate'] ?? 0, 1); ?>%</td>
                    <td><span class="badge badge-real">Google Analytics</span></td>
                </tr>
            </table>
        </div>

        <div class="data-section">
            <h2>📄 Páginas Mais Visitadas</h2>
            <p class="info">Dados reais do Google Analytics:</p>
            <table>
                <tr>
                    <th>Página</th>
                    <th>Visualizações</th>
                    <th>Fonte</th>
                </tr>
                <?php if (isset($data['data']['topPages'])): ?>
                    <?php foreach ($data['data']['topPages'] as $page): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($page['page']); ?></code></td>
                        <td><?php echo number_format($page['views']); ?></td>
                        <td><span class="badge badge-real">Google Analytics</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">Nenhuma página encontrada</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="data-section">
            <h2>🌐 Origem do Tráfego</h2>
            <p class="info">Dados reais do Google Analytics:</p>
            <table>
                <tr>
                    <th>Origem</th>
                    <th>Sessões</th>
                    <th>Fonte</th>
                </tr>
                <?php if (isset($data['data']['trafficSources'])): ?>
                    <?php foreach ($data['data']['trafficSources'] as $source): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($source['source']); ?></td>
                        <td><?php echo number_format($source['sessions']); ?></td>
                        <td><span class="badge badge-real">Google Analytics</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">Nenhuma origem encontrada</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="data-section">
            <h2>⚠️ Dados Mockados no Frontend</h2>
            <p class="warning">Estes dados são gerados aleatoriamente no Dashboard (frontend) e <strong>NÃO</strong> vêm do Google Analytics:</p>
            
            <ul>
                <li><span class="mock">Horários de Pico</span> - Gerado aleatoriamente</li>
                <li><span class="mock">Atividade por Dia da Semana</span> - Gerado aleatoriamente</li>
                <li><span class="mock">Visitantes ao Longo do Tempo</span> - Gerado aleatoriamente</li>
                <li><span class="mock">Dispositivos (desktop/mobile)</span> - Calculado baseado em porcentagens fixas</li>
                <li><span class="mock">Navegadores</span> - Calculado baseado em porcentagens fixas</li>
                <li><span class="mock">Sistemas Operacionais</span> - Calculado baseado em porcentagens fixas</li>
                <li><span class="mock">Páginas de Entrada</span> - Não implementado na API</li>
                <li><span class="mock">Páginas de Saída</span> - Não implementado na API</li>
                <li><span class="mock">Acessos por País</span> - Não implementado na API</li>
                <li><span class="mock">Acessos por Cidade</span> - Não implementado na API</li>
                <li><span class="mock">Visitantes Online Agora</span> - Não implementado na API</li>
            </ul>
        </div>

        <div class="data-section">
            <h2>🔍 Como Validar no Google Analytics</h2>
            <ol>
                <li>Acesse o <a href="https://analytics.google.com" target="_blank">Google Analytics</a></li>
                <li>Selecione a propriedade: <strong>Property ID: <?php echo htmlspecialchars($config['ga4']['property_id']); ?></strong></li>
                <li>Vá em <strong>Relatórios > Engajamento > Visão geral</strong></li>
                <li>Compare os valores:
                    <ul>
                        <li><strong>Usuários:</strong> Deve corresponder a "Visitantes Únicos"</li>
                        <li><strong>Sessões:</strong> Deve corresponder a "Total de Sessões"</li>
                        <li><strong>Visualizações de tela:</strong> Deve corresponder a "Visualizações"</li>
                        <li><strong>Taxa de rejeição:</strong> Deve corresponder a "Taxa de Saída"</li>
                    </ul>
                </li>
                <li>Para ver páginas mais visitadas: <strong>Relatórios > Engajamento > Páginas e telas</strong></li>
                <li>Para ver origem do tráfego: <strong>Relatórios > Aquisição > Visão geral</strong></li>
            </ol>
        </div>

        <div class="data-section">
            <h2>📋 Resposta Completa da API</h2>
            <pre><?php echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></pre>
        </div>

        <?php else: ?>
        
        <div class="data-section">
            <p class="erro">❌ Erro ao buscar dados da API</p>
            <pre><?php echo htmlspecialchars($response); ?></pre>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>

