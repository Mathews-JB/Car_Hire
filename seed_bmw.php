<?php
include 'includes/db.php';
try {
    $v_id = 7; // BMW
    $pdo->prepare("DELETE FROM vehicle_images WHERE vehicle_id = ?")->execute([$v_id]);
    
    $images = [
        ['url' => 'public/images/cars/bmw_m4.jpg', 'type' => 'exterior', 'primary' => 1],
        ['url' => 'public/images/cars/bmw_interior_demo.jpg', 'type' => 'interior', 'primary' => 0]
    ];
    
    foreach ($images as $img) {
        $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, ?, ?)")
            ->execute([$v_id, $img['url'], $img['primary'], $img['type']]);
    }
    echo "BMW Demo images added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
