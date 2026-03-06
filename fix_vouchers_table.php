<?php
include 'includes/db.php';

$queries = [
    "ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage' AFTER code",
    "ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS discount_value DECIMAL(10,2) DEFAULT 0.00 AFTER discount_type",
    "ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS expiry_date DATE AFTER discount_value",
    "ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS min_booking_amount DECIMAL(10,2) DEFAULT 0.00 AFTER expiry_date",
    "ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS usage_limit INT DEFAULT 100 AFTER min_booking_amount",
    "ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS used_count INT DEFAULT 0 AFTER usage_limit",
    "ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER used_count"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Executed: $sql\n";
    } catch (Exception $e) {
        echo "Error on query ($sql): " . $e->getMessage() . "\n";
    }
}
?>
