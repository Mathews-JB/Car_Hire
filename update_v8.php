<?php
include_once 'includes/db.php';

try {
    // 1. Create vehicle_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT NOT NULL,
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        speed FLOAT DEFAULT 0,
        bearing INT DEFAULT 0,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(vehicle_id),
        INDEX(timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Add telemetry columns to vehicles table if they don't exist
    $columns = [
        'last_lat' => "DECIMAL(10, 8)",
        'last_lng' => "DECIMAL(11, 8)",
        'current_speed' => "FLOAT DEFAULT 0",
        'bearing' => "INT DEFAULT 0",
        'tracking_status' => "ENUM('online', 'stopped', 'offline') DEFAULT 'online'"
    ];

    foreach ($columns as $col => $type) {
        $check = $pdo->query("SHOW COLUMNS FROM vehicles LIKE '$col'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE vehicles ADD COLUMN $col $type");
        }
    }

    echo "Successfully migrated database for Phase 8 Tracking.\n";
} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage();
}
?>
