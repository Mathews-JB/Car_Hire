<?php
include_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE support_messages ADD COLUMN IF NOT EXISTS quote_amount DECIMAL(10,2) DEFAULT NULL");
    $pdo->exec("ALTER TABLE support_messages ADD COLUMN IF NOT EXISTS quote_status ENUM('none', 'sent', 'paid', 'approved') DEFAULT 'none'");
    echo "Migration Done!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
