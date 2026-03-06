<?php
include_once 'includes/db.php';
$stmt = $pdo->query("SELECT id, make, model, video_url FROM vehicles");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
?>
