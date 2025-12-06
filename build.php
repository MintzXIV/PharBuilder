<?php

// Command to run: php -d phar.readonly=0 build.php

$pluginName = "YOUR_PLUGIN_NAME";
$pharName = $pluginName . ".phar";
$buildDir = __DIR__;
$finalPharPath = $buildDir . "/" . $pharName;

$startTime = microtime(true);

echo "Building $pharName...\n";

if (file_exists($finalPharPath)) {
    echo "  - Deleting old PHAR...\n";
    @unlink($finalPharPath);
    clearstatcache();
}

try {
    $phar = new Phar($finalPharPath);
    $phar->setSignatureAlgorithm(Phar::SHA1);
    $phar->startBuffering();

    $rootFiles = [
        'plugin.yml',
        'composer.json',
        'composer.lock',
        'README.md',
        'LICENSE'
    ];

    foreach ($rootFiles as $file) {
        $path = $buildDir . "/" . $file;
        if (file_exists($path)) {
            $phar->addFile($path, $file);
            echo "  + Added $file\n";
        }
    }

    $srcDir = $buildDir . "/src";
    $srcCount = 0;
    
    if (is_dir($srcDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $realPath = $file->getRealPath();
                $relativePath = 'src/' . substr($realPath, strlen($srcDir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);

                $phar->addFile($realPath, $relativePath);
                $srcCount++;
            }
        }
        echo "  + Added $srcCount files from src/\n";
    }

    $resDir = $buildDir . "/resources";
    if (is_dir($resDir)) {
        $resCount = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $realPath = $file->getRealPath();
                $relativePath = 'resources/' . substr($realPath, strlen($resDir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);

                $phar->addFile($realPath, $relativePath);
                $resCount++;
            }
        }
        echo "  + Added $resCount files from resources/\n";
    }

    $libsDir = $buildDir . "/libs";
    if (is_dir($libsDir)) {
        $libCount = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($libsDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $realPath = $file->getRealPath();
                
                $relativePath = 'src/libs/' . substr($realPath, strlen($libsDir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);

                $phar->addFile($realPath, $relativePath);
                $libCount++;
            }
        }
        echo "  + Added $libCount files from libs/ (Mapped to src/libs/)\n";
    } else {
        echo "  ! No libs/ folder found. (If you use virions, make sure they are installed)\n";
    }

    $defaultStub = '<?php __HALT_COMPILER();';
    $phar->setStub($defaultStub);

    $phar->stopBuffering();

    $time = round(microtime(true) - $startTime, 3);
    echo "\n------------------------------------------------\n";
    echo "SUCCESS! Built $pharName in $time seconds.\n";
    echo "File size: " . round(filesize($finalPharPath) / 1024, 2) . " KB\n";
    echo "------------------------------------------------\n";

} catch (Exception $e) {
    echo "\nCRITICAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
