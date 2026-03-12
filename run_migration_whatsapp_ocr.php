<?php
/**
 * Migration: Add columns for WhatsApp & OCR features
 * Run once: http://localhost/Car_Higher/run_migration_whatsapp_ocr.php
 */

require_once __DIR__ . '/includes/db.php';

$migrations = [];
$errors     = [];

// 1. Add reminder_sent_at to bookings table
try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN reminder_sent_at DATETIME NULL DEFAULT NULL COMMENT 'Timestamp of last WhatsApp payment reminder sent'");
    $migrations[] = "✅ Added <code>bookings.reminder_sent_at</code>";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭ <code>bookings.reminder_sent_at</code> already exists";
    } else {
        $errors[] = "❌ bookings.reminder_sent_at: " . $e->getMessage();
    }
}

// 2. Add whatsapp_opt_in to users table (for GDPR compliance)
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN whatsapp_opt_in TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = user consents to WhatsApp notifications'");
    $migrations[] = "✅ Added <code>users.whatsapp_opt_in</code>";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭ <code>users.whatsapp_opt_in</code> already exists";
    } else {
        $errors[] = "❌ users.whatsapp_opt_in: " . $e->getMessage();
    }
}

// 3. Add ocr_detected_nrc and ocr_detected_license to users table
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN ocr_detected_nrc VARCHAR(20) NULL DEFAULT NULL COMMENT 'NRC number auto-detected by OCR'");
    $migrations[] = "✅ Added <code>users.ocr_detected_nrc</code>";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭ <code>users.ocr_detected_nrc</code> already exists";
    } else {
        $errors[] = "❌ users.ocr_detected_nrc: " . $e->getMessage();
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN ocr_detected_license VARCHAR(20) NULL DEFAULT NULL COMMENT 'License number auto-detected by OCR'");
    $migrations[] = "✅ Added <code>users.ocr_detected_license</code>";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭ <code>users.ocr_detected_license</code> already exists";
    } else {
        $errors[] = "❌ users.ocr_detected_license: " . $e->getMessage();
    }
}

// 4. Create logs directory
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
    $migrations[] = "✅ Created <code>logs/</code> directory";
} else {
    $migrations[] = "⏭ <code>logs/</code> directory already exists";
}

// Create .htaccess to protect logs
$htaccess = $log_dir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
    $migrations[] = "✅ Created <code>logs/.htaccess</code> (protected from web access)";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WhatsApp & OCR Migration | Car Hire</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; padding: 40px; }
        h1 { color: #25D366; margin-bottom: 5px; }
        p  { color: rgba(255,255,255,0.5); margin-bottom: 30px; }
        .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; max-width: 700px; }
        .item { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        .item:last-child { border-bottom: none; }
        code { background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 4px; color: #93c5fd; font-size: 0.85rem; }
        .error { color: #fca5a5; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #25D366; color: #000; border-radius: 8px; text-decoration: none; font-weight: 700; }
    </style>
    <!-- Theme System -->
    <link rel="stylesheet" href="public/css/theme.css?v=4.0">
    <script src="public/js/theme-switcher.js?v=4.0"></script>
</head>
<body>
    <h1>🚀 WhatsApp & OCR Migration</h1>
    <p>Setting up database columns and directories for the new features.</p>

    <div class="card">
        <?php foreach ($migrations as $m): ?>
            <div class="item"><?php echo $m; ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $e): ?>
            <div class="item error"><?php echo $e; ?></div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($errors)): ?>
        <p style="color:#25D366; margin-top:20px;">✅ All migrations completed successfully!</p>
    <?php else: ?>
        <p style="color:#fca5a5; margin-top:20px;">⚠️ Some migrations failed. Check errors above.</p>
    <?php endif; ?>

    <a href="portal-admin/whatsapp-ocr.php" class="btn">→ Go to WhatsApp & OCR Admin Panel</a>
</body>
</html>
