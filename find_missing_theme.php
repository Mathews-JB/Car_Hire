<?php
$dirs = ['portal-admin', 'portal-agent', 'portal-customer'];
$missing = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;
        
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'theme.css') === false) {
            $missing[] = $file->getPathname();
        }
    }
}

echo implode("\n", $missing);
