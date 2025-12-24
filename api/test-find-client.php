<?php
/**
 * Script para encontrar o nome correto da classe Client do Google Analytics
 * 
 * Acesse: https://mvlopes.com.br/api/test-find-client.php
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
    <title>Encontrar Cliente - Google Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .ok { color: green; font-weight: bold; }
        .erro { color: red; font-weight: bold; }
        .info { color: #666; background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Encontrar Classe Client do Google Analytics</h1>
        
        <?php
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        
        if (!file_exists($autoloadPath)) {
            echo "<p class='erro'>❌ vendor/autoload.php não encontrado</p>";
            exit;
        }
        
        require_once $autoloadPath;
        
        echo "<h2>1. Buscar arquivo Client fisicamente</h2>";
        
        $analyticsPath = __DIR__ . '/vendor/google/analytics-data';
        
        if (is_dir($analyticsPath)) {
            echo "<p class='ok'>✅ Pasta encontrada: vendor/google/analytics-data</p>";
            
            // Buscar arquivos que contenham "Client" no nome
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($analyticsPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            $clientFiles = [];
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $filename = $file->getFilename();
                    if (stripos($filename, 'client') !== false || stripos($filename, 'analytics') !== false) {
                        $clientFiles[] = $file->getPathname();
                    }
                }
            }
            
            if (count($clientFiles) > 0) {
                echo "<p class='ok'>✅ Encontrados " . count($clientFiles) . " arquivos relacionados a Client</p>";
                echo "<pre>";
                foreach ($clientFiles as $file) {
                    $relativePath = str_replace(__DIR__ . '/', '', $file);
                    echo "$relativePath\n";
                    
                    // Tentar extrair o namespace e classe do arquivo
                    $content = file_get_contents($file);
                    if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
                        $namespace = $nsMatch[1];
                        if (preg_match('/class\s+(\w+)/', $content, $classMatch)) {
                            $className = $namespace . '\\' . $classMatch[1];
                            echo "  → Classe: $className\n";
                            if (class_exists($className)) {
                                echo "    ✅ Classe existe e pode ser usada!\n";
                            } else {
                                echo "    ❌ Classe não encontrada pelo autoload\n";
                            }
                        }
                    }
                    echo "\n";
                }
                echo "</pre>";
            } else {
                echo "<p class='info'>Nenhum arquivo com 'client' no nome encontrado.</p>";
            }
        } else {
            echo "<p class='erro'>❌ Pasta vendor/google/analytics-data não encontrada</p>";
        }
        
        echo "<h2>2. Tentar diferentes variações do nome da classe</h2>";
        
        $possibleClasses = [
            'Google\\Analytics\\Data\\V1beta\\BetaAnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\AnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\Client\\AnalyticsDataClient',
            'Google\\Analytics\\Data\\BetaAnalyticsDataClient',
            'Google\\Analytics\\Data\\AnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\BetaAnalyticsData\\BetaAnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\Services\\BetaAnalyticsDataClient',
        ];
        
        $found = false;
        foreach ($possibleClasses as $className) {
            if (class_exists($className)) {
                echo "<p class='ok'>✅ <strong>CLASSE ENCONTRADA:</strong> <code>$className</code></p>";
                $found = true;
                
                // Tentar instanciar
                try {
                    $reflection = new ReflectionClass($className);
                    echo "<p class='ok'>✅ Classe pode ser refletida</p>";
                    echo "<div class='info'>";
                    echo "<strong>Métodos disponíveis:</strong><br>";
                    $methods = $reflection->getMethods();
                    foreach (array_slice($methods, 0, 10) as $method) {
                        echo "- " . $method->getName() . "<br>";
                    }
                    if (count($methods) > 10) {
                        echo "... e mais " . (count($methods) - 10) . " métodos<br>";
                    }
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<p class='info'>Não foi possível refletir a classe: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                break;
            }
        }
        
        if (!$found) {
            echo "<p class='erro'>❌ Nenhuma das variações de classe foi encontrada</p>";
        }
        
        echo "<h2>3. Listar todas as classes do namespace Google\\Analytics\\Data</h2>";
        
        $declaredClasses = get_declared_classes();
        $analyticsClasses = array_filter($declaredClasses, function($class) {
            return strpos($class, 'Google\\Analytics\\Data') === 0;
        });
        
        if (count($analyticsClasses) > 0) {
            echo "<p class='ok'>✅ Encontradas " . count($analyticsClasses) . " classes declaradas</p>";
            echo "<pre>";
            foreach ($analyticsClasses as $class) {
                echo "$class\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='info'>Nenhuma classe foi declarada ainda. Isso pode significar que o autoload não está funcionando corretamente.</p>";
        }
        
        echo "<h2>4. Verificar estrutura de diretórios</h2>";
        
        $pathsToCheck = [
            'vendor/google/analytics-data/src',
            'vendor/google/analytics-data/src/V1beta',
            'vendor/google/analytics-data/src/V1beta/Client',
            'vendor/google/analytics-data/src/V1beta/Services',
        ];
        
        foreach ($pathsToCheck as $path) {
            $fullPath = __DIR__ . '/' . $path;
            if (is_dir($fullPath)) {
                echo "<p class='ok'>✅ Pasta existe: $path</p>";
                $files = scandir($fullPath);
                $phpFiles = array_filter($files, function($file) {
                    return pathinfo($file, PATHINFO_EXTENSION) === 'php';
                });
                if (count($phpFiles) > 0) {
                    echo "<p class='info'>Arquivos PHP encontrados: " . implode(', ', array_slice($phpFiles, 0, 5));
                    if (count($phpFiles) > 5) {
                        echo " ... e mais " . (count($phpFiles) - 5);
                    }
                    echo "</p>";
                }
            } else {
                echo "<p class='info'>ℹ️ Pasta não existe: $path</p>";
            }
        }
        ?>
        
        <h2>5. Solução Recomendada</h2>
        <div class="info">
            <p><strong>Se nenhuma classe foi encontrada, tente:</strong></p>
            <ol>
                <li>Execute no servidor: <code>composer dump-autoload</code></li>
                <li>Ou reinstale: <code>composer remove google/analytics-data && composer require google/analytics-data</code></li>
                <li>Verifique se a pasta <code>vendor/</code> foi enviada completamente para o servidor</li>
                <li>Verifique as permissões dos arquivos na pasta <code>vendor/</code></li>
            </ol>
        </div>
    </div>
</body>
</html>

