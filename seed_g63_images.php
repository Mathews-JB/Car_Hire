<?php
include 'includes/db.php';
try {
    // Mercedes G63 IDs (ID 6 in the user's case)
    $vehicle_id = 6;
    
    // Clear old images for this vehicle
    $pdo->prepare("DELETE FROM vehicle_images WHERE vehicle_id = ?")->execute([$vehicle_id]);
    
    $demo_images = [
        ['url' => 'https://images.unsplash.com/photo-1520031444823-951486ec75ce?q=80&w=2000&auto=format&fit=crop', 'type' => 'exterior', 'primary' => 1],
        ['url' => 'https://images.unsplash.com/photo-1541185933-ef5d8ed016c2?q=80&w=2000&auto=format&fit=crop', 'type' => 'interior', 'primary' => 0],
        ['url' => 'https://images.unsplash.com/photo-1621259182978-f09e5e2ca1ec?q=80&w=2000&auto=format&fit=crop', 'type' => 'exterior', 'primary' => 0],
        ['url' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?q=80&w=2000&auto=format&fit=crop', 'type' => 'dashboard', 'primary' => 0]
    ];
    
    foreach ($demo_images as $img) {
        $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, ?, ?)")
            ->execute([$vehicle_id, $img['url'], $img['primary'], $img['type']]);
    }
    
    echo "G63 Demo images added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
