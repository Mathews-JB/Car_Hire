<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, make, model, price_per_day, status FROM vehicles");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | {$row['make']} {$row['model']} | Price: {$row['price_per_day']} | Status: {$row['status']}\n";
}
?>
