<?php
include 'includes/db.php';
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = 7');
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
foreach($user as $k => $v) {
    if (strpos($k, 'path') !== false || strpos($k, '_image') !== false) {
        echo "$k: $v\n";
    }
}
?>
