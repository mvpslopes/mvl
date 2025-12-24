<?php
/**
 * Script para encontrar todas as classes disponíveis no namespace Google\Analytics
 * 
 * Acesse: https://mvlopes.com.br/api/test-find-classes.php
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
    <title>Encontrar Classes - Google Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .ok { color: green; font-weight: bold; }
        .erro { color: red; font-weight: bold; }
        .info { color: #666; background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Encontrar Classes do Google Analytics</h1>
        
        <?php
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        
        if (!file_exists($autoloadPath)) {
            echo "<p class='erro'>❌ vendor/autoload.php não encontrado</p>";
            exit;
        }
        
        require_once $autoloadPath;
        
        echo "<h2>1. Buscar todas as classes no namespace Google\\Analytics</h2>";
        
        // Função para buscar classes recursivamente
        function findClassesInNamespace($namespace, $directory) {
            $classes = [];
            
            if (!is_dir($directory)) {
                return $classes;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    
                    // Buscar por namespace e class
                    if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
                        $fileNamespace = $nsMatch[1];
                        
                        // Buscar classes
                        if (preg_match_all('/^\s*(?:abstract\s+|final\s+)?class\s+(\w+)/m', $content, $classMatches)) {
                            foreach ($classMatches[1] as $className) {
                                $fullClassName = $fileNamespace . '\\' . $className;
                                if (strpos($fullClassName, $namespace) === 0) {
                                    $classes[] = [
                                        'class' => $fullClassName,
                                        'file' => $file->getPathname(),
                                        'short' => $className
                                    ];
                                }
                            }
                        }
                        
                        // Buscar interfaces
                        if (preg_match_all('/^\s*interface\s+(\w+)/m', $content, $interfaceMatches)) {
                            foreach ($interfaceMatches[1] as $interfaceName) {
                                $fullInterfaceName = $fileNamespace . '\\' . $interfaceName;
                                if (strpos($fullInterfaceName, $namespace) === 0) {
                                    $classes[] = [
                                        'class' => $fullInterfaceName,
                                        'file' => $file->getPathname(),
                                        'short' => $interfaceName,
                                        'type' => 'interface'
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            
            return $classes;
        }
        
        // Buscar na pasta vendor/google/analytics-data
        $analyticsPath = __DIR__ . '/vendor/google/analytics-data';
        
        if (is_dir($analyticsPath)) {
            echo "<p class='ok'>✅ Pasta encontrada: vendor/google/analytics-data</p>";
            
            $classes = findClassesInNamespace('Google\\Analytics', $analyticsPath);
            
            if (count($classes) > 0) {
                echo "<p class='ok'>✅ Encontradas " . count($classes) . " classes/interfaces</p>";
                
                // Agrupar por namespace
                $grouped = [];
                foreach ($classes as $class) {
                    $parts = explode('\\', $class['class']);
                    $namespace = implode('\\', array_slice($parts, 0, -1));
                    $grouped[$namespace][] = $class;
                }
                
                echo "<h2>2. Classes Encontradas (agrupadas por namespace)</h2>";
                
                foreach ($grouped as $namespace => $namespaceClasses) {
                    echo "<h3>Namespace: <code>$namespace</code></h3>";
                    echo "<table>";
                    echo "<tr><th>Classe</th><th>Tipo</th><th>Arquivo</th><th>Existe?</th></tr>";
                    
                    foreach ($namespaceClasses as $class) {
                        $exists = class_exists($class['class']) || interface_exists($class['class']);
                        $type = $class['type'] ?? 'class';
                        $status = $exists ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>";
                        $filePath = str_replace(__DIR__ . '/', '', $class['file']);
                        
                        echo "<tr>";
                        echo "<td><code>{$class['class']}</code></td>";
                        echo "<td>$type</td>";
                        echo "<td><small>$filePath</small></td>";
                        echo "<td>$status</td>";
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                }
                
                // Buscar especificamente por "Client" ou "Analytics"
                echo "<h2>3. Classes que podem ser o Cliente Principal</h2>";
                $clientClasses = array_filter($classes, function($class) {
                    $name = strtolower($class['short']);
                    return strpos($name, 'client') !== false || 
                           strpos($name, 'analytics') !== false ||
                           strpos($name, 'data') !== false;
                });
                
                if (count($clientClasses) > 0) {
                    echo "<table>";
                    echo "<tr><th>Classe</th><th>Tipo</th><th>Existe?</th></tr>";
                    foreach ($clientClasses as $class) {
                        $exists = class_exists($class['class']) || interface_exists($class['class']);
                        $type = $class['type'] ?? 'class';
                        $status = $exists ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>";
                        
                        echo "<tr>";
                        echo "<td><code>{$class['class']}</code></td>";
                        echo "<td>$type</td>";
                        echo "<td>$status</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='info'>Nenhuma classe com 'client', 'analytics' ou 'data' no nome encontrada.</p>";
                }
                
            } else {
                echo "<p class='erro'>❌ Nenhuma classe encontrada no namespace Google\\Analytics</p>";
            }
        } else {
            echo "<p class='erro'>❌ Pasta vendor/google/analytics-data não encontrada</p>";
        }
        
        // Tentar usar get_declared_classes
        echo "<h2>4. Classes Declaradas (filtradas por Google\\Analytics)</h2>";
        $declaredClasses = get_declared_classes();
        $analyticsClasses = array_filter($declaredClasses, function($class) {
            return strpos($class, 'Google\\Analytics') === 0;
        });
        
        if (count($analyticsClasses) > 0) {
            echo "<p class='ok'>✅ Encontradas " . count($analyticsClasses) . " classes declaradas</p>";
            echo "<pre>";
            foreach ($analyticsClasses as $class) {
                echo "$class\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='info'>Nenhuma classe do namespace Google\\Analytics foi declarada ainda.</p>";
        }
        
        // Tentar carregar manualmente
        echo "<h2>5. Tentar Carregar Classes Manualmente</h2>";
        $possibleClasses = [
            'Google\\Analytics\\Data\\V1beta\\BetaAnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\AnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient',
            'Google\\Analytics\\Data\\V1beta\\Client\\AnalyticsDataClient',
            'Google\\Analytics\\Data\\BetaAnalyticsDataClient',
            'Google\\Analytics\\Data\\AnalyticsDataClient',
        ];
        
        foreach ($possibleClasses as $className) {
            if (class_exists($className)) {
                echo "<p class='ok'>✅ Classe encontrada: <code>$className</code></p>";
            } else {
                echo "<p class='info'>ℹ️ Classe não encontrada: <code>$className</code></p>";
            }
        }
        ?>
        
        <h2>6. Verificar Autoload</h2>
        <?php
        $autoloader = require $autoloadPath;
        
        if (method_exists($autoloader, 'getClassMap')) {
            echo "<p class='ok'>✅ Autoloader tem método getClassMap</p>";
            // Tentar obter o class map (se disponível)
            try {
                $reflection = new ReflectionClass($autoloader);
                if ($reflection->hasMethod('getClassMap')) {
                    $classMap = $autoloader->getClassMap();
                    $analyticsInMap = array_filter($classMap, function($file, $class) {
                        return strpos($class, 'Google\\Analytics') === 0;
                    }, ARRAY_FILTER_USE_BOTH);
                    
                    if (count($analyticsInMap) > 0) {
                        echo "<p class='ok'>✅ Encontradas " . count($analyticsInMap) . " classes no class map</p>";
                        echo "<pre>";
                        foreach (array_slice($analyticsInMap, 0, 20) as $class => $file) {
                            echo "$class => $file\n";
                        }
                        if (count($analyticsInMap) > 20) {
                            echo "... e mais " . (count($analyticsInMap) - 20) . " classes\n";
                        }
                        echo "</pre>";
                    }
                }
            } catch (Exception $e) {
                echo "<p class='info'>Não foi possível acessar o class map: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        ?>
    </div>
</body>
</html>

