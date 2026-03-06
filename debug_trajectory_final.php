<?php
include 'includes/db.php';
$days_data = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days_data[$date] = ['r' => 0, 'p' => 0];
}
$start = date('Y-m-d', strtotime("-29 days"));
$stmt = $pdo->prepare("SELECT DATE(created_at) as d, 
                       SUM(CASE WHEN status='completed' THEN total_price ELSE 0 END) as r, 
                       SUM(CASE WHEN status IN ('confirmed','completed') THEN total_price ELSE 0 END) as p 
                       FROM bookings 
                       WHERE created_at >= ? 
                       GROUP BY DATE(created_at)
                       ORDER BY d ASC");
$stmt->execute([$start]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if(isset($days_data[$row['d']])) {
        $days_data[$row['d']]['r'] = (float)$row['r'];
        $days_data[$row['d']]['p'] = (float)$row['p'];
    }
}
$cr = 0; $cp = 0;
$out = "";
foreach($days_data as $date => $val) {
    if ($val['r'] != 0 || $val['p'] != 0) {
        $cr += $val['r'];
        $cp += $val['p'];
        $out .= "$date | Daily R: {$val['r']} | Daily P: {$val['p']} | Cum R: $cr | Cum P: $cp\n";
    } else {
        // Still add even if 0 to show the progression
        $cr += $val['r'];
        $cp += $val['p'];
        $out .= "$date | Zero Activity | Cum R: $cr | Cum P: $cp\n";
    }
}
file_put_contents('trajectory_log.txt', $out);
echo "Logged to trajectory_log.txt\n";
