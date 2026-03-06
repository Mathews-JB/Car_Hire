<?php
include 'includes/db.php';
$stmt = $pdo->query('DESCRIBE users');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
