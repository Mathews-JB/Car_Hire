<?php
$lines = file('.env');
foreach ($lines as $line) {
    if (strpos($line, 'GOOGLE_VISION_API_KEY') !== false) {
        echo "Line: [" . $line . "]\n";
        echo "Hex: " . bin2hex($line) . "\n";
    }
}
?>
