<?php
include_once 'includes/db.php';

try {
    // Create handovers table
    $sql_handovers = "CREATE TABLE IF NOT EXISTS vehicle_inspections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        inspection_type ENUM('pickup', 'return') NOT NULL,
        inspector_id INT NOT NULL,
        mileage INT NOT NULL,
        fuel_level VARCHAR(20) NOT NULL,
        body_condition TEXT,
        interior_condition TEXT,
        tire_condition TEXT,
        spare_tire_exists BOOLEAN DEFAULT TRUE,
        jack_tool_exists BOOLEAN DEFAULT TRUE,
        photos_json TEXT,
        signature_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (inspector_id) REFERENCES users(id)
    )";
    
    $pdo->exec($sql_handovers);
    echo "Inspections table created successfully.<br>";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
