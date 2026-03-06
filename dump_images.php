<?php
include_once 'includes/db.php';
$stmt = $pdo->query("SELECT id, make, model, image_url FROM vehicles");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | " . $row['make'] . " " . $row['model'] . " | URL: " . $row['image_url'] . "\n";
}
?>
