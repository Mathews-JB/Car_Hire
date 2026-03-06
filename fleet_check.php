<?php
include 'includes/db.php';
$stmt = $pdo->query("SELECT status, COUNT(*) as c FROM vehicles GROUP BY status");
while ($r = $stmt->fetch()) {
    echo "DBH_STATUS: [" . $r['status'] . "] COUNT: " . $r['c'] . "\n";
}
$stmt = $pdo->query("SELECT id, status FROM vehicles WHERE status != 'available'");
echo "NON-AVAILABLE CARS:\n";
while ($r = $stmt->fetch()) {
    echo "ID: " . $r['id'] . " STATUS: [" . $r['status'] . "]\n";
}
