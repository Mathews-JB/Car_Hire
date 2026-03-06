<?php
include_once 'includes/db.php';

try {
    // 1. Seed Brands
    $brands = ['Toyota', 'Ford', 'Volkswagen', 'Land Rover', 'Mercedes-Benz', 'BMW', 'Nissan', 'Mitsubishi', 'Audi', 'Jeep', 'Hyundai', 'Suzuki'];
    $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
    foreach ($brands as $brand) {
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM brands WHERE name = ?");
        $check->execute([$brand]);
        if (!$check->fetch()) {
            $stmt->execute([$brand]);
        }
    }

    // 2. Associate existing vehicles with brands
    $stmt = $pdo->query("SELECT id, make FROM vehicles");
    $updateStmt = $pdo->prepare("UPDATE vehicles SET brand_id = (SELECT id FROM brands WHERE name = ?) WHERE id = ?");
    
    while ($vehicle = $stmt->fetch()) {
        $updateStmt->execute([$vehicle['make'], $vehicle['id']]);
    }

    echo "Seeding/Association successful!";
} catch (PDOException $e) {
    echo "Seeding failed: " . $e->getMessage();
}
?>
