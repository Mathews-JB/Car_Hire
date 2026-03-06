<?php
include 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM bookings WHERE total_price < 0");
$neg = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(count($neg) > 0) {
    echo "Found negative prices:\n";
    print_r($neg);
} else {
    echo "No negative prices found.\n";
}

$stmt = $pdo->query("SELECT DATE(created_at) as d, status, total_price FROM bookings ORDER BY created_at ASC");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['created_at']} | {$row['status']} | {$row['total_price']}\n";
}
