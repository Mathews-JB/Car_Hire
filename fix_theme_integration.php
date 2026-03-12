<?php
$dirs = ['portal-admin', 'portal-agent', 'portal-customer'];
$files_to_fix = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isDir() || pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;
        $files_to_fix[] = $file->getPathname();
    }
}

foreach ($files_to_fix as $file) {
    $content = file_get_contents($file);
    $changed = false;

    // 1. Ensure theme tags in head
    if (strpos($content, 'theme.css') === false && strpos($content, '</head>') !== false) {
        $theme_tags = "\n    <!-- Theme System -->\n    <link rel=\"stylesheet\" href=\"../public/css/theme.css?v=4.0\">\n    <script src=\"../public/js/theme-switcher.js?v=4.0\"></script>\n";
        $content = str_replace('</head>', $theme_tags . '</head>', $content);
        $changed = true;
    }

    // 2. Ensure mobile_header at start of body
    if (strpos($content, 'mobile_header.php') === false && strpos($content, '<body>') !== false) {
        $content = str_replace('<body>', "<body>\n    <?php include_once '../includes/mobile_header.php'; ?>", $content);
        $changed = true;
    }

    // 3. Ensure theme_switcher in desktop header (if .header-actions exists)
    if (strpos($content, 'theme_switcher.php') === false && strpos($content, 'class="header-actions"') !== false) {
        $content = str_replace('class="header-actions">', "class=\"header-actions\">\n                    <?php include_once '../includes/theme_switcher.php'; ?>", $content);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
    }
}
