<?php
include_once 'includes/db.php';
$stmt = $pdo->query("SELECT id, make, model, video_url FROM vehicles WHERE video_url IS NOT NULL");
foreach($stmt as $row) {
    echo "ID: " . $row['id'] . " | Name: " . $row['make'] . " " . $row['model'] . " | Video: " . $row['video_url'] . "\n";
}
?>
