<?php
/**
 * Audit Fix Migration: Repairs corrupted tables and missing columns
 */

require_once __DIR__ . '/includes/db.php';

$results = [];

// 1. Repair Contracts Table (Drop and Recreate to fix "Not found in engine")
try {
    $pdo->exec("DROP TABLE IF EXISTS contracts");
    $pdo->exec("CREATE TABLE contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        contract_pdf_path VARCHAR(255) NOT NULL,
        is_signed TINYINT(1) DEFAULT 0,
        signed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "✅ Rebuilt <code>contracts</code> table.";
} catch (PDOException $e) {
    $results[] = "❌ Failed rebuilding contracts: " . $e->getMessage();
}

// 2. Update Payments Table ENUM to include 'Lenco'
try {
    // Check if lenco exists in enum first to avoid downtime or errors
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'provider'");
    $col = $stmt->fetch();
    if ($col && !str_contains($col['Type'], 'Lenco')) {
        $pdo->exec("ALTER TABLE payments MODIFY COLUMN provider ENUM('MTN', 'Airtel', 'Zamtel', 'Lenco') NOT NULL");
        $results[] = "✅ Added 'Lenco' to <code>payments.provider</code> ENUM.";
    } else {
        $results[] = "⏭ 'Lenco' already exists in <code>payments.provider</code> ENUM.";
    }
} catch (PDOException $e) {
    $results[] = "❌ Failed updating payments ENUM: " . $e->getMessage();
}

// 3. Ensure other columns exist (from run_migration_whatsapp_ocr.php)
$checks = [
    ['bookings', 'reminder_sent_at', "DATETIME NULL DEFAULT NULL"],
    ['users', 'whatsapp_opt_in', "TINYINT(1) NOT NULL DEFAULT 1"],
    ['users', 'ocr_detected_nrc', "VARCHAR(20) NULL DEFAULT NULL"],
    ['users', 'ocr_detected_license', "VARCHAR(20) NULL DEFAULT NULL"]
];

foreach ($checks as $check) {
    list($table, $col, $def) = $check;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
            $results[] = "✅ Added missing column <code>$table.$col</code>.";
        } else {
            $results[] = "⏭ Column <code>$table.$col</code> already exists.";
        }
    } catch (PDOException $e) {
        $results[] = "❌ Error checking <code>$table.$col</code>: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Repair Migration | Car Hire</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; padding: 40px; }
        .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; max-width: 800px; margin: 0 auto; }
        .item { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        code { background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; color: #60a5fa; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🛠 Database Audit & Repair</h1>
        <p>Fixing structural issues and ensuring feature completeness.</p>
        <div style="margin-top: 20px;">
            <?php foreach ($results as $res): ?>
                <div class="item"><?php echo $res; ?></div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top: 30px;"><a href="portal-admin/dashboard.php" style="color: #10b981; font-weight: bold; text-decoration: none;">← Back to Dashboard</a></p>
    </div>
</body>
</html>
