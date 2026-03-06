<?php
include 'includes/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 7");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
file_put_contents('db_dump.txt', print_r(array_keys($user), true));
echo "Dumped to db_dump.txt\n";
?>
