<?php
include_once 'includes/db.php';

try {
    // Drop existing table to ensure proper schema
    $pdo->exec("DROP TABLE IF EXISTS notifications");
    
    // Recreate with full correct schema
    $pdo->exec("CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'danger', 'security') DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "Notifications table RECREATED successfully with all columns.";
} catch (PDOException $e) {
    echo "Error recreation table: " . $e->getMessage();
}
?>
