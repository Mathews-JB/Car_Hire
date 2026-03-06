<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
$data = [
    'getenv' => getenv('GOOGLE_VISION_API_KEY'),
    '$_ENV' => $_ENV['GOOGLE_VISION_API_KEY'] ?? 'MISSING',
    '$_SERVER' => $_SERVER['GOOGLE_VISION_API_KEY'] ?? 'MISSING',
    'app_config' => app_config('GOOGLE_VISION_API_KEY'),
    'path' => __DIR__,
    'env_exists' => file_exists(__DIR__ . '/.env'),
    'env_readable' => is_readable(__DIR__ . '/.env')
];
file_put_contents('debug_web.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Debug data written to debug_web.json";
?>
