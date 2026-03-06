<?php
include_once 'includes/db.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
