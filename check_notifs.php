<?php
include_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE notifications");
    echo "<pre>";
    print_r($stmt->fetchAll());
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
