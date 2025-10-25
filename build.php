<?php

// This script is used to build the plugin into a PHAR file. (Dependencies are not included in the PHAR file)
// php -d phar.readonly=0 build.php

$finalPharFile = __DIR__ . '/YOUR_PLUGIN_NAME.phar';

$startTime = microtime(true);

if (file_exists($finalPharFile)) {
    @unlink($finalPharFile);
    clearstatcache();
}

try {
    $phar = new Phar($finalPharFile);
    $phar->setStub('<?php __HALT_COMPILER();');
    $phar->startBuffering();
    
    $pluginYmlPath = __DIR__ . '/plugin.yml';
    if (file_exists($pluginYmlPath)) {
        $phar->addFromString('plugin.yml', file_get_contents($pluginYmlPath));
        echo "Added plugin.yml\n";
    } else {
        echo "Warning: plugin.yml not found!\n";
    }
    
    $composerJsonPath = __DIR__ . '/composer.json';
    if (file_exists($composerJsonPath)) {
        $phar->addFromString('composer.json', file_get_contents($composerJsonPath));
        echo "Added composer.json\n";
    } else {
        echo "Warning: composer.json not found!\n";
    }

    $composerLockPath = __DIR__ . '/composer.lock';
    if (file_exists($composerLockPath)) {
        $phar->addFromString('composer.lock', file_get_contents($composerLockPath));
        echo "Added composer.lock\n";
    }

    $srcDir = __DIR__ . '/src';
    if (is_dir($srcDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $fileCount = 0;
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $realPath = $file->getRealPath();
                $relativePath = 'src/' . substr($realPath, strlen($srcDir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                
                $phar->addFromString($relativePath, file_get_contents($realPath));
                echo "Added: $relativePath\n";
                $fileCount++;
            }
        }
        echo "Added $fileCount PHP files from src/\n";
    }
    
    $phar->stopBuffering();
    
    echo "\nBuilt EmeraldEconomy.phar successfully!\n\nTotal time: " . (microtime(true) - $startTime) . " seconds\n\n";
    
    $verify = new Phar($finalPharFile);
    $totalCount = 0;
    echo "Files in PHAR:\n";
    foreach (new RecursiveIteratorIterator($verify) as $file) {
        if ($totalCount++ < 15) {
            echo "  " . $file->getFileName() . "\n";
        }
    }
    if ($totalCount > 15) {
        echo "  ... and " . ($totalCount - 15) . " more files\n";
    }
    echo "\nTotal files in PHAR: $totalCount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
