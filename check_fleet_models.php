<?php
include 'includes/db.php';
$stmt = $pdo->query("SELECT make, model, COUNT(*) as c FROM vehicles GROUP BY model");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
