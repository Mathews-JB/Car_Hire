<?php
include 'includes/db.php';
$days_data = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days_data[$date] = ['real' => 0, 'proj' => 0];
}
$start = date('Y-m-d', strtotime("-29 days"));
$stmt = $pdo->prepare("SELECT DATE(created_at) as d, SUM(CASE WHEN status='completed' THEN total_price ELSE 0 END) as r, SUM(CASE WHEN status IN ('confirmed','completed') THEN total_price ELSE 0 END) as p FROM bookings WHERE created_at >= ? GROUP BY DATE(created_at)");
$stmt->execute([$start]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if(isset($days_data[$row['d']])) {
        $days_data[$row['d']]['real'] = (float)$row['r'];
        $days_data[$row['d']]['proj'] = (float)$row['p'];
    }
}
$cr = 0; $cp = 0;
foreach($days_data as $date => $val) {
    $cr += $val['real'];
    $cp += $val['proj'];
    echo "$date | R: {$val['real']} | CR: $cr | P: {$val['proj']} | CP: $cp\n";
}
