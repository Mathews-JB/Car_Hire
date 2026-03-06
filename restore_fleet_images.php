<?php
include 'includes/db.php';
try {
    // Clear and restore all vehicles with local images
    $pdo->exec("DELETE FROM vehicle_images");
    
    $stmt = $pdo->query("SELECT id, image_url, interior_image_url FROM vehicles");
    while ($v = $stmt->fetch()) {
        // Exterior
        if (!empty($v['image_url'])) {
            $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, 1, 'exterior')")
                ->execute([$v['id'], $v['image_url']]);
        }
        // Interior fallback
        if (!empty($v['interior_image_url'])) {
            $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, ?, 0, 'interior')")
                ->execute([$v['id'], $v['interior_image_url']]);
        } else {
            // Default interior for demo
            $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_url, is_primary, view_type) VALUES (?, 'public/images/cars/g63_interior_demo.jpg', 0, 'interior')")
                ->execute([$v['id']]);
        }
    }
    echo "Fleet images restored to local paths.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
