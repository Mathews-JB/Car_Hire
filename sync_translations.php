<?php
/**
 * Translation Automation Script
 * Uses the Zambian Language Translation API to keep language files in sync.
 */

require_once __DIR__ . '/api/translate.php';

function syncTranslations() {
    $baseLangFile = __DIR__ . '/includes/lang/en.php';
    $targetLangs = ['bem', 'nya', 'ton'];
    
    if (!file_exists($baseLangFile)) {
        die("Base language file not found.\n");
    }

    $enStrings = include $baseLangFile;
    $translator = new ZambianTranslator();

    foreach ($targetLangs as $lang) {
        $langFile = __DIR__ . "/includes/lang/{$lang}.php";
        $currentStrings = file_exists($langFile) ? include $langFile : [];
        $updated = false;

        echo "Syncing $lang...\n";

        foreach ($enStrings as $key => $value) {
            $isMock = isset($currentStrings[$key]) && strpos($currentStrings[$key], '[Translated to') !== false;
            
            if (!isset($currentStrings[$key]) || $isMock) {
                echo ($isMock ? "Repairing" : "Translating") . " '$key' to $lang...\n";
                $result = $translator->translate($value, $lang);
                
                if ($result['status'] === 'success') {
                    $currentStrings[$key] = $result['translated_text'];
                    $updated = true;
                }
            }
        }

        if ($updated) {
            $content = "<?php\nreturn " . var_export($currentStrings, true) . ";\n";
            file_put_contents($langFile, $content);
            echo "Successfully updated $lang.php\n";
        } else {
            echo "No new strings to translate for $lang.\n";
        }
    }
}

// Security: Only allow running via CLI or admin session (placeholder check)
if (php_sapi_name() === 'cli') {
    syncTranslations();
} else {
    echo "This script must be run from the command line or authorized admin panel.";
}
