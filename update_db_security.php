<?php
include_once 'includes/db.php';

try {
    // Add recovery_key column if it doesn't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS recovery_key VARCHAR(255) DEFAULT NULL");
    
    // Add account_status column if it doesn't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS account_status ENUM('active', 'frozen') DEFAULT 'active'");
    
    echo "Database updated successfully.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
