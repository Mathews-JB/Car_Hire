<?php
include 'includes/db.php';
$stmt = $pdo->query('DESCRIBE users');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('users_schema_dump.json', json_encode($columns, JSON_PRETTY_PRINT));
?>
