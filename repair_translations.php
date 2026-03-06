<?php
// Repair script for language files
$langs = ['bem', 'nya', 'ton'];
foreach ($langs as $lang) {
    $file = __DIR__ . "/includes/lang/{$lang}.php";
    if (file_exists($file)) {
        $data = include $file;
        $updated = false;
        foreach ($data as $key => $val) {
            // Remove the "[Translated to xxx]: " prefix
            if (preg_match('/^\[Translated to [a-z]+\]: /i', $val)) {
                $data[$key] = preg_replace('/^\[Translated to [a-z]+\]: /i', '', $val);
                $updated = true;
            }
        }
        if ($updated) {
            $content = "<?php\nreturn " . var_export($data, true) . ";\n";
            file_put_contents($file, $content);
            echo "Cleaned up $lang.php\n";
        } else {
            echo "$lang.php already clean.\n";
        }
    }
}
