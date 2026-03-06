<?php
require_once 'includes/functions.php';
echo "getenv('GOOGLE_VISION_API_KEY'): " . (getenv('GOOGLE_VISION_API_KEY') ?: 'EMPTY') . "\n";
echo "\$_ENV['GOOGLE_VISION_API_KEY']: " . ($_ENV['GOOGLE_VISION_API_KEY'] ?? 'NOT SET') . "\n";
echo "app_config('GOOGLE_VISION_API_KEY'): " . (app_config('GOOGLE_VISION_API_KEY') ?: 'EMPTY') . "\n";
?>
