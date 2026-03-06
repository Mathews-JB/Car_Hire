<?php
include 'includes/db.php';

try {
    // 1. Add plate_number, latitude, longitude if they don't exist
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS plate_number VARCHAR(20) AFTER model");
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT -15.3875 AFTER status");
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT 28.3228 AFTER latitude");

    // 2. Update status enum
    // Note: ALTER TABLE on ENUM can be tricky in some SQL versions. 
    // We'll try to modify it to include new statuses.
    $pdo->exec("ALTER TABLE vehicles MODIFY COLUMN status ENUM('available', 'booked', 'hired', 'returned', 'maintenance') DEFAULT 'available'");

    // 3. Update existing data with mock plate numbers
    $vehicles = $pdo->query("SELECT id FROM vehicles")->fetchAll();
    foreach ($vehicles as $v) {
        $plate = "ZAB " . rand(1000, 9999) . " " . chr(rand(65, 90));
        $stmt = $pdo->prepare("UPDATE vehicles SET plate_number = ? WHERE id = ? AND (plate_number IS NULL OR plate_number = '')");
        $stmt->execute([$plate, $v['id']]);
    }

    echo "Migration successful!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
