<?php
include_once 'includes/db.php';

try {
    // 1. Create vouchers table
    $sql_vouchers = "CREATE TABLE IF NOT EXISTS vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        discount_type ENUM('percentage', 'fixed') NOT NULL,
        discount_value DECIMAL(10, 2) NOT NULL,
        expiry_date DATE NOT NULL,
        min_booking_amount DECIMAL(10, 2) DEFAULT 0,
        usage_limit INT DEFAULT 100,
        used_count INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_vouchers);
    echo "Vouchers table created successfully.<br>";

    // 2. Add discount columns to bookings
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10, 2) DEFAULT 0 AFTER total_price");
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS voucher_id INT AFTER discount_amount");
    echo "Bookings table updated with discount columns.<br>";

    // 3. Add loyalty columns to users
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS loyalty_points INT DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS membership_tier ENUM('Bronze', 'Silver', 'Gold') DEFAULT 'Bronze'");
    echo "Users table updated with loyalty columns.<br>";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
