<?php
include_once 'includes/db.php';
$vehicles = $pdo->query("SELECT id, make, model FROM vehicles LIMIT 5")->fetchAll();
echo json_encode($vehicles, JSON_PRETTY_PRINT);
?>
