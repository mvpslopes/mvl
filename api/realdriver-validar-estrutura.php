<?php
/**
 * Script de Validação - Estrutura do Sistema Real Driver
 * 
 * Este script valida se todos os arquivos estão nos lugares corretos
 * Acesse: https://mvlopes.com.br/api/realdriver-validar-estrutura.php
 */

header('Content-Type: text/html; charset=utf-8');

// Cores para o output
$corSucesso = '#28a745';
$corErro = '#dc3545';
$corAviso = '#ffc107';
$corInfo = '#17a2b8';

// Base path
$basePath = __DIR__;
$apiPath = $basePath;

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação - Estrutura Real Driver</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
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
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin: 5px 0;
        }
        .sucesso { background: ' . $corSucesso . '; color: white; }
        .erro { background: ' . $corErro . '; color: white; }
        .aviso { background: ' . $corAviso . '; color: #333; }
        .info { background: ' . $corInfo . '; color: white; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .caminho {
            font-family: monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .resumo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .card {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: white;
        }
        .card-total { background: #6c757d; }
        .card-ok { background: ' . $corSucesso . '; }
        .card-erro { background: ' . $corErro . '; }
        .card-aviso { background: ' . $corAviso . '; color: #333; }
        .card h3 {
            margin: 0 0 10px 0;
            font-size: 2em;
        }
        .card p {
            margin: 0;
            font-size: 0.9em;
        }
        .acoes {
            margin-top: 30px;
            padding: 20px;
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .acoes h3 {
            margin-top: 0;
            color: #004085;
        }
        .acoes ul {
            line-height: 1.8;
        }
        .acoes code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Validação da Estrutura - Sistema Real Driver</h1>';

// Informações do servidor
echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
echo '<strong>📂 Caminho Base:</strong> <code>' . htmlspecialchars($apiPath) . '</code><br>';
echo '<strong>🌐 DOCUMENT_ROOT:</strong> <code>' . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . '</code>';
echo '</div>';

// Definir estrutura esperada
$estruturaEsperada = [
    // Arquivos PHP
    'PHP' => [
        $apiPath . '/realdriver.php',
        $apiPath . '/realdriver-api.php',
        $apiPath . '/realdriver-auth.php',
        $apiPath . '/realdriver-users.php',
        $apiPath . '/realdriver-permissions.php',
        $apiPath . '/realdriver-config.php',
        $apiPath . '/realdriver-login.html',
    ],
    // Arquivos JavaScript
    'JavaScript' => [
        $apiPath . '/realdriver-api-wrapper.js',
        $apiPath . '/realdriver-user-menu.js',
        $apiPath . '/realdriver-users-manager.js',
    ],
    // Arquivos do Frontend
    'Frontend' => [
        $apiPath . '/SistemasEmbarcados/realdriver/index.html',
        $apiPath . '/SistemasEmbarcados/realdriver/script.js',
        $apiPath . '/SistemasEmbarcados/realdriver/styles.css',
        $apiPath . '/SistemasEmbarcados/realdriver/logo.webp',
    ],
];

// Validar estrutura
$resultados = [];
$totalArquivos = 0;
$arquivosOk = 0;
$arquivosErro = 0;
$arquivosAviso = 0;

foreach ($estruturaEsperada as $categoria => $arquivos) {
    $resultados[$categoria] = [];
    
    foreach ($arquivos as $arquivo) {
        $totalArquivos++;
        $nomeArquivo = basename($arquivo);
        $caminhoRelativo = str_replace($apiPath . '/', '', $arquivo);
        
        $existe = file_exists($arquivo);
        $tamanho = $existe ? filesize($arquivo) : 0;
        $legivel = $existe ? is_readable($arquivo) : false;
        
        $status = 'ok';
        $mensagem = '';
        
        if (!$existe) {
            $status = 'erro';
            $mensagem = 'Arquivo não encontrado';
            $arquivosErro++;
        } elseif (!$legivel) {
            $status = 'erro';
            $mensagem = 'Arquivo sem permissão de leitura';
            $arquivosErro++;
        } elseif ($tamanho == 0) {
            $status = 'aviso';
            $mensagem = 'Arquivo vazio';
            $arquivosAviso++;
        } else {
            $arquivosOk++;
            
            // Verificar tamanho mínimo esperado
            $tamanhoMinimo = 0;
            if (strpos($nomeArquivo, '.php') !== false || strpos($nomeArquivo, '.html') !== false) {
                $tamanhoMinimo = 100; // 100 bytes mínimo para PHP/HTML
            } elseif (strpos($nomeArquivo, '.js') !== false) {
                $tamanhoMinimo = 500; // 500 bytes mínimo para JS
            } elseif (strpos($nomeArquivo, '.css') !== false) {
                $tamanhoMinimo = 200; // 200 bytes mínimo para CSS
            }
            
            if ($tamanhoMinimo > 0 && $tamanho < $tamanhoMinimo) {
                $status = 'aviso';
                $mensagem = 'Arquivo muito pequeno (pode estar incompleto)';
                $arquivosAviso++;
            } else {
                $mensagem = 'OK';
            }
        }
        
        $resultados[$categoria][] = [
            'arquivo' => $nomeArquivo,
            'caminho' => $caminhoRelativo,
            'caminhoCompleto' => $arquivo,
            'existe' => $existe,
            'tamanho' => $tamanho,
            'status' => $status,
            'mensagem' => $mensagem,
        ];
    }
}

// Exibir resumo
echo '<div class="resumo">';
echo '<div class="card card-total">';
echo '<h3>' . $totalArquivos . '</h3>';
echo '<p>Total de Arquivos</p>';
echo '</div>';

echo '<div class="card card-ok">';
echo '<h3>' . $arquivosOk . '</h3>';
echo '<p>Arquivos OK</p>';
echo '</div>';

echo '<div class="card card-erro">';
echo '<h3>' . $arquivosErro . '</h3>';
echo '<p>Arquivos Faltando</p>';
echo '</div>';

echo '<div class="card card-aviso">';
echo '<h3>' . $arquivosAviso . '</h3>';
echo '<p>Avisos</p>';
echo '</div>';
echo '</div>';

// Exibir resultados detalhados
foreach ($resultados as $categoria => $arquivos) {
    echo '<h2>' . $categoria . ' (' . count($arquivos) . ' arquivos)</h2>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Arquivo</th>';
    echo '<th>Caminho</th>';
    echo '<th>Tamanho</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($arquivos as $item) {
        $corStatus = $item['status'] === 'ok' ? $corSucesso : ($item['status'] === 'erro' ? $corErro : $corAviso);
        $classeStatus = $item['status'] === 'ok' ? 'sucesso' : ($item['status'] === 'erro' ? 'erro' : 'aviso');
        $icone = $item['status'] === 'ok' ? '✅' : ($item['status'] === 'erro' ? '❌' : '⚠️');
        
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($item['arquivo']) . '</strong></td>';
        echo '<td><span class="caminho">' . htmlspecialchars($item['caminho']) . '</span></td>';
        
        if ($item['existe']) {
            $tamanhoFormatado = $item['tamanho'] > 1024 
                ? number_format($item['tamanho'] / 1024, 2) . ' KB'
                : $item['tamanho'] . ' B';
            echo '<td>' . $tamanhoFormatado . '</td>';
        } else {
            echo '<td>-</td>';
        }
        
        echo '<td>';
        echo '<span class="status ' . $classeStatus . '">';
        echo $icone . ' ' . htmlspecialchars($item['mensagem']);
        echo '</span>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

// Verificar estrutura de pastas
echo '<h2>📁 Estrutura de Pastas</h2>';

$pastasEsperadas = [
    'SistemasEmbarcados' => $apiPath . '/SistemasEmbarcados',
    'SistemasEmbarcados/realdriver' => $apiPath . '/SistemasEmbarcados/realdriver',
];

echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>Pasta</th>';
echo '<th>Caminho</th>';
echo '<th>Status</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($pastasEsperadas as $nome => $caminho) {
    $existe = is_dir($caminho);
    $legivel = $existe ? is_readable($caminho) : false;
    
    $status = $existe && $legivel ? 'ok' : 'erro';
    $classeStatus = $status === 'ok' ? 'sucesso' : 'erro';
    $icone = $status === 'ok' ? '✅' : '❌';
    $mensagem = $status === 'ok' ? 'Pasta existe' : ($existe ? 'Sem permissão de leitura' : 'Pasta não encontrada');
    
    echo '<tr>';
    echo '<td><strong>' . htmlspecialchars($nome) . '</strong></td>';
    echo '<td><span class="caminho">' . htmlspecialchars(str_replace($apiPath . '/', '', $caminho)) . '</span></td>';
    echo '<td>';
    echo '<span class="status ' . $classeStatus . '">';
    echo $icone . ' ' . $mensagem;
    echo '</span>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// Ações recomendadas
if ($arquivosErro > 0 || $arquivosAviso > 0) {
    echo '<div class="acoes">';
    echo '<h3>🔧 Ações Recomendadas</h3>';
    echo '<ul>';
    
    if ($arquivosErro > 0) {
        echo '<li><strong>Arquivos faltando:</strong> Verifique quais arquivos estão marcados com ❌ e envie-os para o servidor.</li>';
    }
    
    if ($arquivosAviso > 0) {
        echo '<li><strong>Arquivos com aviso:</strong> Verifique os arquivos marcados com ⚠️ - eles podem estar vazios ou incompletos.</li>';
    }
    
    // Verificar se a pasta SistemasEmbarcados existe
    if (!is_dir($apiPath . '/SistemasEmbarcados')) {
        echo '<li><strong>Criar pasta:</strong> Crie a pasta <code>SistemasEmbarcados</code> dentro de <code>api/</code></li>';
    }
    
    if (!is_dir($apiPath . '/SistemasEmbarcados/realdriver')) {
        echo '<li><strong>Criar pasta:</strong> Crie a pasta <code>realdriver</code> dentro de <code>api/SistemasEmbarcados/</code></li>';
    }
    
    echo '</ul>';
    echo '</div>';
} else {
    echo '<div style="background: ' . $corSucesso . '; color: white; padding: 20px; border-radius: 5px; margin-top: 20px; text-align: center;">';
    echo '<h2 style="margin: 0;">✅ Estrutura Completa e Correta!</h2>';
    echo '<p style="margin: 10px 0 0 0;">Todos os arquivos estão nos lugares corretos.</p>';
    echo '</div>';
}

// Link para testar o sistema
echo '<div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 5px; text-align: center;">';
echo '<h3 style="margin-top: 0;">🚀 Próximos Passos</h3>';
echo '<p>';
echo '<a href="/api/realdriver-check-path.php" style="display: inline-block; margin: 5px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Verificar Caminhos</a> ';
echo '<a href="/api/realdriver.php" style="display: inline-block; margin: 5px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;">Testar Sistema</a>';
echo '</p>';
echo '</div>';

echo '</div></body></html>';
?>

