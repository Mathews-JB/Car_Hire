<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE support_messages");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
