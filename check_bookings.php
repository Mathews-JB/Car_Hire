<?php
include 'includes/db.php';
$stmt = $pdo->query('DESCRIBE bookings');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $row) {
    echo $row['Field'] . "\n";
}
?>
