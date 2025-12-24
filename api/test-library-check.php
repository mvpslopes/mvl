<?php
/**
 * Verificação detalhada da biblioteca Google Analytics
 * 
 * Acesse: https://mvlopes.com.br/api/test-library-check.php
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
    <title>Verificação da Biblioteca - Google Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .ok { color: green; font-weight: bold; }
        .erro { color: red; font-weight: bold; }
        .info { color: #666; background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação da Biblioteca Google Analytics</h1>
        
        <h2>1. Verificação do Composer</h2>
        <?php
        $composerJson = __DIR__ . '/composer.json';
        $composerLock = __DIR__ . '/composer.lock';
        
        if (file_exists($composerJson)) {
            echo "<p class='ok'>✅ composer.json encontrado</p>";
            $composerData = json_decode(file_get_contents($composerJson), true);
            if (isset($composerData['require']['google/analytics-data'])) {
                $version = $composerData['require']['google/analytics-data'];
                echo "<p class='ok'>✅ google/analytics-data está no composer.json (versão requerida: $version)</p>";
            } else {
                echo "<p class='erro'>❌ google/analytics-data NÃO está no composer.json</p>";
            }
        } else {
            echo "<p class='erro'>❌ composer.json não encontrado</p>";
        }
        
        if (file_exists($composerLock)) {
            echo "<p class='ok'>✅ composer.lock encontrado</p>";
            $lockData = json_decode(file_get_contents($composerLock), true);
            if (isset($lockData['packages'])) {
                $found = false;
                foreach ($lockData['packages'] as $package) {
                    if ($package['name'] === 'google/analytics-data') {
                        $found = true;
                        echo "<p class='ok'>✅ google/analytics-data instalado (versão: {$package['version']})</p>";
                        break;
                    }
                }
                if (!$found) {
                    echo "<p class='erro'>❌ google/analytics-data NÃO encontrado no composer.lock</p>";
                }
            }
        } else {
            echo "<p class='warning'>⚠️ composer.lock não encontrado (pode ser normal se não foi commitado)</p>";
        }
        ?>
        
        <h2>2. Verificação do Autoload</h2>
        <?php
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            echo "<p class='ok'>✅ vendor/autoload.php encontrado</p>";
            
            try {
                require_once $autoloadPath;
                echo "<p class='ok'>✅ Autoload carregado com sucesso</p>";
            } catch (Exception $e) {
                echo "<p class='erro'>❌ Erro ao carregar autoload: " . htmlspecialchars($e->getMessage()) . "</p>";
            } catch (Error $e) {
                echo "<p class='erro'>❌ Erro fatal ao carregar autoload: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='erro'>❌ vendor/autoload.php NÃO encontrado</p>";
            echo "<p class='info'>Execute: <code>composer install</code> ou <code>composer require google/analytics-data</code></p>";
        }
        ?>
        
        <h2>3. Verificação de Classes</h2>
        <?php
        if (file_exists($autoloadPath)) {
        $classesToCheck = [
            'Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient' => 'Cliente principal do Google Analytics',
            'Google\\Analytics\\Data\\V1beta\\DateRange' => 'Classe para definir períodos de data',
            'Google\\Analytics\\Data\\V1beta\\Metric' => 'Classe para métricas',
            'Google\\Analytics\\Data\\V1beta\\Dimension' => 'Classe para dimensões',
        ];
            
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'><th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Classe</th><th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Status</th><th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Descrição</th></tr>";
            
            foreach ($classesToCheck as $className => $description) {
                $exists = class_exists($className);
                $status = $exists ? "<span class='ok'>✅ Encontrada</span>" : "<span class='erro'>❌ Não encontrada</span>";
                echo "<tr><td style='padding: 10px; border: 1px solid #ddd;'><code>$className</code></td><td style='padding: 10px; border: 1px solid #ddd;'>$status</td><td style='padding: 10px; border: 1px solid #ddd;'>$description</td></tr>";
            }
            
            echo "</table>";
        }
        ?>
        
        <h2>4. Verificação da Estrutura de Pastas</h2>
        <?php
        $vendorPath = __DIR__ . '/vendor';
        $googlePath = $vendorPath . '/google/analytics-data';
        
        if (is_dir($vendorPath)) {
            echo "<p class='ok'>✅ Pasta vendor/ existe</p>";
            
            if (is_dir($googlePath)) {
                echo "<p class='ok'>✅ Pasta vendor/google/analytics-data existe</p>";
                
                // Listar alguns arquivos importantes
        $importantFiles = [
            'src/V1beta/Client/BetaAnalyticsDataClient.php',
            'composer.json',
        ];
                
                foreach ($importantFiles as $file) {
                    $fullPath = $googlePath . '/' . $file;
                    if (file_exists($fullPath)) {
                        echo "<p class='ok'>✅ Arquivo encontrado: $file</p>";
                    } else {
                        echo "<p class='erro'>❌ Arquivo NÃO encontrado: $file</p>";
                    }
                }
            } else {
                echo "<p class='erro'>❌ Pasta vendor/google/analytics-data NÃO existe</p>";
            }
        } else {
            echo "<p class='erro'>❌ Pasta vendor/ NÃO existe</p>";
        }
        ?>
        
        <h2>5. Tentativa de Instanciação</h2>
        <?php
        if (file_exists($autoloadPath)) {
            try {
                // Tentar usar o namespace completo sem use statement
                $className = 'Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient';
                
                if (class_exists($className)) {
                    echo "<p class='ok'>✅ Classe existe, tentando instanciar...</p>";
                    
                    // Verificar se credentials.json existe
                    $credentialsPath = __DIR__ . '/credentials.json';
                    if (file_exists($credentialsPath)) {
                        try {
                            $reflection = new ReflectionClass($className);
                            echo "<p class='ok'>✅ Classe pode ser refletida</p>";
                            echo "<p class='info'>Métodos disponíveis: " . count($reflection->getMethods()) . "</p>";
                        } catch (Exception $e) {
                            echo "<p class='erro'>❌ Erro ao refletir classe: " . htmlspecialchars($e->getMessage()) . "</p>";
                        }
                    } else {
                        echo "<p class='warning'>⚠️ credentials.json não encontrado, não é possível testar instanciação completa</p>";
                    }
                } else {
                    echo "<p class='erro'>❌ Classe não existe mesmo após carregar autoload</p>";
                }
            } catch (Exception $e) {
                echo "<p class='erro'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            } catch (Error $e) {
                echo "<p class='erro'>❌ Erro fatal: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        ?>
        
        <h2>6. Informações do Sistema</h2>
        <div class="info">
            <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
            <strong>Composer:</strong> <?php 
                $composerPhar = __DIR__ . '/composer.phar';
                if (file_exists($composerPhar)) {
                    echo "✅ composer.phar encontrado";
                } else {
                    echo "❌ composer.phar não encontrado";
                }
            ?><br>
            <strong>Diretório atual:</strong> <?php echo __DIR__; ?><br>
            <strong>Include path:</strong> <?php echo get_include_path(); ?>
        </div>
        
        <hr>
        <h2>📋 Recomendações</h2>
        <?php
        $needsReinstall = false;
        
        if (!file_exists($autoloadPath)) {
            echo "<p class='erro'>❌ Execute: <code>composer install</code> ou <code>composer require google/analytics-data</code></p>";
            $needsReinstall = true;
        } else {
            $className = 'Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient';
            if (!class_exists($className)) {
                echo "<p class='erro'>❌ A biblioteca parece estar instalada, mas as classes não estão disponíveis.</p>";
                echo "<p class='info'>Tente executar: <code>composer dump-autoload</code></p>";
                echo "<p class='info'>Ou reinstale: <code>composer remove google/analytics-data && composer require google/analytics-data</code></p>";
                $needsReinstall = true;
            }
        }
        
        if (!$needsReinstall) {
            echo "<p class='ok'>✅ Tudo parece estar correto! A biblioteca está instalada e as classes estão disponíveis.</p>";
        }
        ?>
    </div>
</body>
</html>

