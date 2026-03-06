<?php
include 'includes/db.php';
$stmt = $pdo->query('DESCRIBE bookings');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('schema_dump.json', json_encode($columns, JSON_PRETTY_PRINT));
echo "Schema dumped to schema_dump.json\n";
?>
