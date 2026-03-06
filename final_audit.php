<?php
require 'includes/db.php';
echo "--- Database vs Filesystem Verification ---\n";
$stmt = $pdo->query("SELECT v.id, v.make, v.model, vi.image_url FROM vehicles v JOIN vehicle_images vi ON v.id = vi.vehicle_id WHERE vi.is_primary = 1");
while ($row = $stmt->fetch()) {
    $exists = file_exists($row['image_url']) ? "EXISTS" : "MISSING";
    echo "ID: {$row['id']} | {$row['make']} {$row['model']} | URL: {$row['image_url']} | Status: {$exists}\n";
}
echo "\n--- Grid & Styling Audit ---\n";
// Add any additional logic if needed
?>
