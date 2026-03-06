<?php
include 'includes/db.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
file_put_contents('db_state_out.txt', "Tables in DB: " . implode(', ', $tables) . "\n\n");

foreach ($tables as $table) {
    try {
        $cols = $pdo->query("DESCRIBE `$table`")->fetchAll();
        $out = "Table: $table\n" . print_r($cols, true) . "\n";
        file_put_contents('db_state_out.txt', $out, FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents('db_state_out.txt', "Table: $table - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
echo "Done. check db_state_out.txt";
