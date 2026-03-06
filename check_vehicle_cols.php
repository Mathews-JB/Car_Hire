<?php
include 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE vehicle_images");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = "COLUMNS IN vehicle_images TABLE:\n";
    foreach ($columns as $col) {
        $out .= sprintf("%-25s | %s\n", $col['Field'], $col['Type']);
    }
    file_put_contents('vehicle_images_cols.txt', $out);
    echo "Done. output in vehicle_images_cols.txt\n";
} catch (Exception $e) {
    file_put_contents('vehicle_images_cols.txt', "Error: " . $e->getMessage());
}
?>
