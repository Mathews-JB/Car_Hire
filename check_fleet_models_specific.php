<?php
include 'includes/db.php';
$models = ['Audi Q7 Quattro', 'Hyundai Santa Fe', 'Jeep Grand Cherokee', 'Ford Everest', 'Q7 Quattro', 'Santa Fe', 'Grand Cherokee', 'Everest'];
foreach ($models as $m) {
    $stmt = $pdo->prepare("SELECT make, model, status, COUNT(*) as c FROM vehicles WHERE model = ? GROUP BY status");
    $stmt->execute([$m]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($res) {
        echo "Check for '$m':\n";
        print_r($res);
    }
}
