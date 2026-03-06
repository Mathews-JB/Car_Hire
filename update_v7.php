<?php
include_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS color VARCHAR(50) AFTER vin");
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS engine_no VARCHAR(100) AFTER color");
    echo "Missing columns 'color' and 'engine_no' added successfully.<br>";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
