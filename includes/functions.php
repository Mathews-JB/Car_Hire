<?php
require_once __DIR__ . '/db.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Security Check: Immediate Logout for Frozen Accounts
 * This ensures that if an account is frozen, the user is kicked out on their next click.
 */
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT account_status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $status = $stmt->fetchColumn();

    if ($status === 'frozen') {
        session_unset();
        session_destroy();
        header("Location: " . app_config('APP_URL', '/') . "login.php?error=account_frozen_security");
        exit;
    }
}

/**
 * Robust config/env helper
 */
function app_config($key, $default = null) {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

/**
 * XSS Protection Helper
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Language Handling
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'bem', 'nya', 'ton'])) {
        $_SESSION['lang'] = $lang;
    }
}
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';

// Load language file
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
$translations = file_exists($lang_file) ? include $lang_file : [];

function __($key) {
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $key;
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        header("HTTP/1.1 403 Forbidden");
        die("Security Error: CSRF token validation failed. Possible cross-site request forgery detected. Please refresh the page and try again.");
    }
    return true;
}

/**
 * Robust Password Strength Validation
 */
function validate_password($password) {
    // Minimum 8 characters
    if (strlen($password) < 8) return "Password must be at least 8 characters long.";
    
    // At least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) return "Password must contain at least one uppercase letter.";
    
    // At least one number
    if (!preg_match('/[0-9]/', $password)) return "Password must contain at least one number.";
    
    // At least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return "Password must contain at least one special character.";
    
    return true;
}

/**
 * Rate Limiting: Track Login Attempts
 */
function track_login_attempt($pdo, $email, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($success) {
        // Clear attempts on successful login
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ? OR ip_address = ?");
        $stmt->execute([$email, $ip]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, attempt_time) VALUES (?, ?, NOW())");
        $stmt->execute([$email, $ip]);
    }
}

/**
 * Rate Limiting: Check if Locked Out (5 attempts in 15 mins)
 */
function is_login_locked($pdo, $email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts 
                           WHERE (email = ? OR ip_address = ?) 
                           AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$email, $ip]);
    $attempts = $stmt->fetchColumn();
    
    return $attempts >= 5;
}


/**
 * Handle Image Uploads
 */
function uploadImage($file, $folder = 'profiles') {
    $target_dir = "../public/images/" . $folder . "/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) return false;

    // Limit file size (5MB)
    if ($file["size"] > 5000000) return false;

    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "webp") {
        return false;
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return "public/images/" . $folder . "/" . $new_filename;
    }
    return false;
}

function getAvailableVehicles($pdo) {
    $stmt = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Validates a voucher code against booking details
 */
function validateVoucher($pdo, $code, $booking_amount, $user_email = null) {
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = TRUE AND expiry_date >= CURDATE()");
    $stmt->execute([$code]);
    $v = $stmt->fetch();

    if (!$v) return ['valid' => false, 'msg' => 'Invalid or expired promo code.'];
    if ($v['used_count'] >= $v['usage_limit']) return ['valid' => false, 'msg' => 'This voucher has reached its usage limit.'];
    if ($booking_amount < $v['min_booking_amount']) return ['valid' => false, 'msg' => 'Minimum booking amount for this code is ZMW ' . $v['min_booking_amount']];

    // Check for targeted voucher
    if (!empty($v['assigned_user_email'])) {
        if (empty($user_email) || strtolower(trim($v['assigned_user_email'])) !== strtolower(trim($user_email))) {
            return ['valid' => false, 'msg' => 'This exclusive voucher is linked to another account.'];
        }
    }

    return ['valid' => true, 'data' => $v];
}

/**
 * Updates user loyalty points and membership tier
 */
function updateUserLoyalty($pdo, $user_id) {
    // Count completed bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();

    $tier = 'Bronze';
    if ($count >= 10) $tier = 'Gold';
    elseif ($count >= 3) $tier = 'Silver';

    $stmt = $pdo->prepare("UPDATE users SET membership_tier = ?, loyalty_points = ? WHERE id = ?");
    return $stmt->execute([$tier, $count * 100, $user_id]); // 100 points per completed booking
}

/**
 * Global Notification System (Enhanced for SMS/WhatsApp)
 */
function createNotification($pdo, $user_id, $title, $message, $type = 'info', $channels = ['db', 'email']) {
    include_once 'notification_helper.php';
    $notifier = new NotificationService($pdo);
    return $notifier->send($user_id, $title, $message, $channels, $type);
}

function getUnreadNotifications($pdo, $user_id) {
    if (!$user_id) return [];
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}


function checkAvailability($pdo, $vehicle_id, $pickup_date, $dropoff_date) {
    // Check for any overlapping bookings that are not cancelled
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE vehicle_id = ? 
        AND status NOT IN ('cancelled', 'completed') 
        AND (
            (pickup_date < ? AND dropoff_date > ?) 
        )
    ");
    // Logic: 
    // New booking: Start(A) to End(B)
    // Existing booking: Start(C) to End(D)
    // Overlap exists if A < D AND B > C
    
    $stmt->execute([$vehicle_id, $dropoff_date, $pickup_date]);
    return $stmt->fetchColumn() == 0;
}
?>
