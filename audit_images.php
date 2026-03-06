<?php
include 'includes/db.php';
$stmt = $pdo->query('SELECT id, make, model, image_url FROM vehicles');
$output = "";
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= "ID:".$r['id']." | ".$r['make']." ".$r['model']." | IMG:".$r['image_url'].PHP_EOL;
}
file_put_contents('vehicle_audit.txt', $output);
?>
