<?php
include 'includes/db.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in DB:\n";
print_r($tables);

foreach ($tables as $table) {
    echo "\nColumns in $table:\n";
    $cols = $pdo->query("DESCRIBE $table")->fetchAll();
    print_r($cols);
}
