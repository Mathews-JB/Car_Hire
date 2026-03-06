<?php
include 'includes/db.php';
$stmt = $pdo->query('SELECT * FROM bookings LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "Columns: " . implode(', ', array_keys($row)) . PHP_EOL;
} else {
    echo "No rows in bookings table to check columns." . PHP_EOL;
    // Fallback to DESCRIBE if empty
    $stmt = $pdo->query('DESCRIBE bookings');
    while($c = $stmt->fetch()) {
        echo $c['Field'] . ", ";
    }
}
?>
