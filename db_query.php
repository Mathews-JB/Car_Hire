<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, make, model FROM vehicles");
while ($row = $stmt->fetch()) {
    echo "ID: " . $row['id'] . " | " . $row['make'] . " " . $row['model'] . "\n";
}
?>
