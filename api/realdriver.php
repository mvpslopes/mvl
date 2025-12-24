<?php
/**
 * Servidor do Sistema Real Driver
 * 
 * Este arquivo serve o sistema Real Driver integrado ao site.
 * Acesse: https://seusite.com.br/api/realdriver.php
 */

// Habilitar exibição de erros apenas para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros na tela, mas logar
ini_set('log_errors', 1);

// Caminho para os arquivos do sistema Real Driver
// Tenta vários caminhos possíveis (local e servidor)
// IMPORTANTE: Linux é case-sensitive, então testamos ambas as variações
$possiblePaths = [
    __DIR__ . '/SistemasEmbarcados/realdriver/',  // api/SistemasEmbarcados/realdriver/ (PRIORIDADE - com maiúscula)
    __DIR__ . '/sistemasembarcados/realdriver/',  // api/sistemasembarcados/realdriver/ (alternativo - minúscula)
    __DIR__ . '/realdriver/',  // api/realdriver/ (alternativo direto)
    __DIR__ . '/../SistemasEmbarcados/realdriver/',  // Local development
    $_SERVER['DOCUMENT_ROOT'] . '/SistemasEmbarcados/realdriver/',  // Servidor raiz
    dirname(__DIR__) . '/SistemasEmbarcados/realdriver/',  // Servidor alternativo
];

$realdriverPath = null;
foreach ($possiblePaths as $path) {
    if (is_dir($path)) {
        $realdriverPath = $path;
        break;
    }
}

// Verificar se a pasta existe
if (!$realdriverPath || !is_dir($realdriverPath)) {
    http_response_code(404);
    $sugeridoPath = __DIR__ . '/realdriver/';
    die('
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema Real Driver - Não Encontrado</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .box { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .box h2 { color: #856404; margin-top: 0; }
            code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
            ol { line-height: 1.8; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>❌ Sistema Real Driver não encontrado</h1>
        
        <div class="box">
            <h2>📁 Solução: Criar pasta e enviar arquivos</h2>
            <p><strong>Caminho esperado (prioridade):</strong> <code>' . htmlspecialchars(__DIR__ . '/SistemasEmbarcados/realdriver/') . '</code></p>
            
            <h3>Passo a Passo:</h3>
            <ol>
                <li>No <strong>File Manager</strong> da Hostinger, navegue até: <code>public_html/api/</code></li>
                <li>Crie uma nova pasta chamada <code>SistemasEmbarcados</code> (com S maiúsculo)</li>
                <li>Dentro de <code>SistemasEmbarcados</code>, crie outra pasta chamada <code>realdriver</code></li>
                <li>Envie os seguintes arquivos (da pasta local <code>SistemasEmbarcados/realdriver/</code>):
                    <ul>
                        <li><code>index.html</code></li>
                        <li><code>script.js</code></li>
                        <li><code>styles.css</code></li>
                        <li><code>logo.webp</code></li>
                    </ul>
                </li>
                <li>Após o upload, <a href="/api/realdriver-check-path.php">verifique aqui</a> se está correto</li>
            </ol>
            
            <h3>Estrutura final no servidor:</h3>
            <pre>public_html/
└── api/
    └── SistemasEmbarcados/  ← CRIE ESTA PASTA (com S maiúsculo)
        └── realdriver/      ← CRIE ESTA PASTA DENTRO
            ├── index.html   ← ENVIE ESTES ARQUIVOS
            ├── script.js
            ├── styles.css
            └── logo.webp</pre>
        </div>
        
        <p><a href="/api/realdriver-check-path.php">🔍 Verificar caminhos disponíveis</a></p>
    </body>
    </html>');
}

// Se for uma requisição para arquivo estático (CSS, JS, imagens)
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$queryString = parse_url($requestUri, PHP_URL_QUERY);
parse_str($queryString ?? '', $queryParams);

// Verificar se é requisição de arquivo estático via ?file=
if (isset($queryParams['file']) && !empty($queryParams['file'])) {
    $fileName = $queryParams['file'];
    
    // Remover qualquer caminho relativo perigoso
    $fileName = str_replace('..', '', $fileName);
    $fileName = ltrim($fileName, '/');
    
    // Construir caminho completo - tentar diferentes variações
    $filePath = $realdriverPath . $fileName;
    
    // Se não existe, tentar apenas o nome do arquivo
    if (!file_exists($filePath) || !is_file($filePath)) {
        $filePath = $realdriverPath . basename($fileName);
    }
    
    // Se ainda não existe, verificar se é logo.webp e tentar caminhos alternativos
    if ((!file_exists($filePath) || !is_file($filePath)) && basename($fileName) === 'logo.webp') {
        // Tentar caminhos alternativos para logo
        $altPaths = [
            $realdriverPath . 'logo.webp',
            __DIR__ . '/SistemasEmbarcados/realdriver/logo.webp',
            __DIR__ . '/sistemasembarcados/realdriver/logo.webp',
        ];
        foreach ($altPaths as $altPath) {
            if (file_exists($altPath) && is_file($altPath)) {
                $filePath = $altPath;
                break;
            }
        }
    }
    
    // Se o arquivo não existe, retornar 404
    if (!file_exists($filePath) || !is_file($filePath)) {
        http_response_code(404);
        die('Arquivo não encontrado: ' . htmlspecialchars($fileName) . ' (procurado em: ' . htmlspecialchars($realdriverPath) . ')');
    }
    
    // Continuar para servir o arquivo estático (código abaixo)
    $isStaticFile = true;
} else {
    // NÃO é requisição de arquivo estático - servir o index.html depois
    $isStaticFile = false;
    $filePath = null; // Marcar que não é arquivo estático
}

// Se for arquivo estático, servir agora
if ($isStaticFile && $filePath !== null && file_exists($filePath) && is_file($filePath)) {
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml'
    ];
    
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $mimeType);
    
    // Headers de cache para imagens e CSS
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'ico', 'svg', 'css'])) {
        header('Cache-Control: public, max-age=31536000'); // 1 ano
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
    
    // Se for script.js, modificar para usar API
    if (basename($filePath) === 'script.js') {
        $scriptContent = file_get_contents($filePath);
        // Adicionar código para aguardar o wrapper estar pronto
        // Usar heredoc para evitar problemas com aspas
        $apiPatch = <<<'JSPATCH'
// Aguardar o wrapper da API estar pronto antes de inicializar
(function() {
    function waitForAPI() {
        if (window.USE_API_MODE && window.RealDriverAPI) {
            console.log("✅ API Wrapper detectado, aplicando patches...");
            
            // API Mode Patch
            const originalLoadData = SisRealDriver.prototype.loadData;
            SisRealDriver.prototype.loadData = async function(forceRefresh) {
                forceRefresh = forceRefresh || false;
                console.log("📊 Carregando dados da API...", forceRefresh ? "(forçando refresh)" : "");
                try {
                    const apiData = await window.RealDriverAPI.loadAllData(forceRefresh);
                    this.data = apiData;
                    console.log("✅ Dados carregados da API:", {
                        motoristas: apiData.motoristas ? apiData.motoristas.length : 0,
                        veiculos: apiData.veiculos ? apiData.veiculos.length : 0,
                        diarias: apiData.diarias ? apiData.diarias.length : 0
                    });
                    return this.data;
                } catch (error) {
                    console.error("Erro ao carregar da API, usando fallback:", error);
                    originalLoadData.call(this);
                    return this.data;
                }
            };
            
            // Interceptar saveData
            const originalSaveData = SisRealDriver.prototype.saveData;
            SisRealDriver.prototype.saveData = function() {
                console.log("💾 Dados serão salvos via métodos individuais");
            };
            
            // Patch updateDashboard
            const originalUpdateDashboard = SisRealDriver.prototype.updateDashboard;
            SisRealDriver.prototype.updateDashboard = async function() {
                const hasData = this.data && (
                    (this.data.motoristas && this.data.motoristas.length > 0) ||
                    (this.data.veiculos && this.data.veiculos.length > 0)
                );
                
                if (!hasData) {
                    console.log("🔄 Dados vazios detectados, forçando reload...");
                    if (typeof this.loadData === 'function') {
                        await this.loadData(true);
                    }
                }
                
                originalUpdateDashboard.call(this);
            };
            
            // Forçar atualização após delay
            setTimeout(function() {
                try {
                    if (window.app && typeof window.app.updateDashboard === 'function') {
                        console.log("🔄 Forçando atualização inicial do dashboard...");
                        window.app.updateDashboard();
                    }
                } catch (e) {
                    console.error("Erro ao atualizar dashboard:", e);
                }
            }, 1500);
        } else {
            setTimeout(waitForAPI, 100);
        }
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", waitForAPI);
    } else {
        waitForAPI();
    }
})();
JSPATCH;
        echo $scriptContent . $apiPatch;
    } else {
        readfile($filePath);
    }
    exit;
}

// Verificar autenticação
require_once __DIR__ . '/realdriver-permissions.php';

$config = require __DIR__ . '/realdriver-config.php';
$db = $config['database'];
$prefix = $config['system']['table_prefix'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    // Mostrar erro detalhado apenas em desenvolvimento
    $errorMsg = 'Erro de conexão com banco de dados';
    if (isset($_GET['debug']) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        $errorMsg .= ': ' . $e->getMessage();
    }
    die($errorMsg . '<br><br><a href="/api/realdriver-test-connection.php">🔍 Testar Conexão</a>');
}

// Verificar token do localStorage via JavaScript ou sessão
$token = $_GET['token'] ?? null;
if (!$token) {
    session_start();
    $token = $_SESSION['auth_token'] ?? null;
}

// Se não tiver token, redirecionar para login
if (!$token) {
    // Verificar se é requisição AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Não autenticado', 'redirect' => '/api/realdriver-login.html']);
        exit;
    }
    // Redirecionar para login
    header('Location: /api/realdriver-login.html');
    exit;
}

// Verificar se token é válido
$user = verifyAuth($pdo, $prefix);
if (!$user) {
    header('Location: /api/realdriver-login.html');
    exit;
}

// Servir o index.html modificado
$indexPath = $realdriverPath . 'index.html';
if (!file_exists($indexPath)) {
    http_response_code(404);
    die('Arquivo index.html não encontrado');
}

$html = file_get_contents($indexPath);

// Corrigir caminhos dos arquivos estáticos (CSS, JS, imagens)
// Substituir caminhos relativos por caminhos absolutos via PHP
$baseUrl = '/api/realdriver.php';

// CSS
$html = preg_replace('/href=["\']styles\.css["\']/', 'href="' . $baseUrl . '?file=styles.css"', $html);

// Logo - substituições diretas primeiro (mais confiáveis)
$logoUrl = $baseUrl . '?file=logo.webp';
$html = str_replace('src="logo.webp"', 'src="' . $logoUrl . '"', $html);
$html = str_replace("src='logo.webp'", "src='" . $logoUrl . "'", $html);
$html = str_replace('src="./logo.webp"', 'src="' . $logoUrl . '"', $html);
$html = str_replace("src='./logo.webp'", "src='" . $logoUrl . "'", $html);

// Regex para outros formatos de logo
$html = preg_replace('/(src=["\'])logo\.(webp|ico|png|jpg)(["\'])/i', '$1' . $logoUrl . '$3', $html);

// Imagens em subpastas
$html = preg_replace('/src=["\']public\/([^"\']+)["\']/', 'src="' . $baseUrl . '?file=public/$1"', $html);

// Garantir que qualquer logo.webp restante seja substituído
$html = preg_replace('/(<img[^>]*src=["\'])([^"\']*logo\.webp)(["\'][^>]*>)/i', '$1' . $logoUrl . '$3', $html);

// Adicionar script wrapper ANTES do script.js
$userData = json_encode([
    'id' => $user['usuario_id'],
    'nome' => $user['nome'],
    'email' => $user['email'],
    'perfil' => $user['perfil_nome'],
    'permissoes' => $user['permissoes']
]);

$apiWrapper = '
<script>
// Configuração da API - DEVE SER CARREGADO ANTES DO script.js
window.REALDRIVER_API_URL = "/api/realdriver-api.php";
window.REALDRIVER_AUTH_URL = "/api/realdriver-auth.php";
window.REALDRIVER_USERS_URL = "/api/realdriver-users.php";
window.USE_API_MODE = true;
window.REALDRIVER_USER = ' . $userData . ';
window.REALDRIVER_TOKEN = "' . htmlspecialchars($token, ENT_QUOTES) . '";

// Salvar token no localStorage também para garantir
if (window.REALDRIVER_TOKEN) {
    localStorage.setItem("realdriver_token", window.REALDRIVER_TOKEN);
    var tokenPreview = window.REALDRIVER_TOKEN ? (window.REALDRIVER_TOKEN.substring(0, 10) + "...") : "Token ausente";
    console.log("✅ Token configurado:", tokenPreview);
} else {
    console.error("❌ Token não foi configurado!");
}
</script>
<script src="/api/realdriver-api-wrapper.js"></script>
<script src="/api/realdriver-user-menu.js"></script>
' . ($user['perfil_nome'] === 'root' ? '<script src="/api/realdriver-users-manager.js"></script>' : '') . '
';

// Injetar o wrapper ANTES do script.js
// Primeiro, substituir o caminho do script.js para usar a URL correta
$html = preg_replace('/src=["\']script\.js["\']/', 'src="' . $baseUrl . '?file=script.js"', $html);

// Depois, injetar o wrapper ANTES do script.js
$html = preg_replace('/(<script\s+src=["\'][^"\']*script\.js[^"\']*["\']><\/script>)/i', $apiWrapper . '$1', $html);

// Se não encontrou o script.js, adicionar antes do </body> como fallback
if (strpos($html, $apiWrapper) === false) {
    $html = str_replace('</body>', $apiWrapper . '</body>', $html);
}

header('Content-Type: text/html; charset=utf-8');
echo $html;

