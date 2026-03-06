<?php
include_once 'includes/db.php';

try {
    // Drop old table if exists (since it was just a placeholder schema in previous sessions)
    // and create the new robust version
    $pdo->exec("DROP TABLE IF EXISTS maintenance_logs");

    $sql = "CREATE TABLE maintenance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT NOT NULL,
        service_type VARCHAR(100) NOT NULL,
        service_date DATE NOT NULL,
        mileage_at_service INT NOT NULL,
        cost DECIMAL(10, 2) NOT NULL,
        description TEXT,
        next_service_km INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "Maintenance logs table standardized successfully.";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
