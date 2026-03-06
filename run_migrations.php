<?php
include_once 'includes/db.php';

try {
    // 1. Add columns to users table
    $pdo->exec("ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS verification_status ENUM('none', 'pending', 'approved', 'declined') DEFAULT 'none',
        ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
        ADD COLUMN IF NOT EXISTS address TEXT,
        ADD COLUMN IF NOT EXISTS id_number VARCHAR(50),
        ADD COLUMN IF NOT EXISTS license_number VARCHAR(50),
        ADD COLUMN IF NOT EXISTS profile_image_path VARCHAR(255),
        ADD COLUMN IF NOT EXISTS license_image_path VARCHAR(255),
        ADD COLUMN IF NOT EXISTS nrc_image_path VARCHAR(255)");

    // 2. Create brands table
    $pdo->exec("CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        logo_url VARCHAR(255)
    )");

    // 3. Add brand_id to vehicles table
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS brand_id INT");
    
    // Check if foreign key exists is complex in MySQL, but adding it if not exists:
    try {
        $pdo->exec("ALTER TABLE vehicles ADD CONSTRAINT fk_vehicle_brand FOREIGN KEY (brand_id) REFERENCES brands(id)");
    } catch (Exception $e) {
        // Likely already exists
    }

    // 4. Create notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('fleet', 'promo', 'system') DEFAULT 'system',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Migration successful!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
