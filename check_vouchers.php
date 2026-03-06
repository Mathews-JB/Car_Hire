<?php
include 'includes/db.php';
$stmt = $pdo->query('DESCRIBE vouchers');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
}
?>
