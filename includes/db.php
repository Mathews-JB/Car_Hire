<?php
// Core configuration and database connection
require_once 'env_loader.php';

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'car_hire';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a real app, log this error instead of showing it
    die("Database connection failed: Error loading database.");
}

// Global constants
define('SITE_NAME', 'Car Hire');
define('BASE_URL', '/Car_Higher/');
define('APP_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lenco Payment Gateway Configuration
// Set to 'true' for Production, 'false' for Sandbox
$is_live = getenv('LENCO_IS_LIVE') === 'true';
define('LENCO_IS_LIVE', $is_live);

if (LENCO_IS_LIVE) {
    define('LENCO_PUBLIC_KEY', getenv('LENCO_LIVE_PUBLIC_KEY')); 
    define('LENCO_SECRET_KEY', getenv('LENCO_LIVE_SECRET_KEY'));
    define('LENCO_API_BASE', 'https://api.lenco.co/access/v2/');
    define('LENCO_JS_URL', 'https://pay.lenco.co/js/v1/inline.js');
} else {
    define('LENCO_PUBLIC_KEY', getenv('LENCO_SANDBOX_PUBLIC_KEY')); 
    define('LENCO_SECRET_KEY', getenv('LENCO_SANDBOX_SECRET_KEY'));
    define('LENCO_API_BASE', 'https://sandbox.lenco.co/access/v2/');
    define('LENCO_JS_URL', 'https://pay.sandbox.lenco.co/js/v1/inline.js');
}
?>
