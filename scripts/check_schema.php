<?php
include 'includes/db.php';
echo "VEHICLES TABLE:\n";
foreach($pdo->query('DESCRIBE vehicles')->fetchAll() as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ")\n";
}
echo "\nBOOKINGS TABLE:\n";
foreach($pdo->query('DESCRIBE bookings')->fetchAll() as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ")\n";
}
?>
