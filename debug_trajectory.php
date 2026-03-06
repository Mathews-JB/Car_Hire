<?php
include 'includes/db.php';
include 'includes/functions.php';

$days_data = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days_data[$date] = [
        'label' => date('d M', strtotime($date)),
        'realized' => 0,
        'projected' => 0
    ];
}

$start_date = date('Y-m-d', strtotime("-29 days"));
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as pay_date, 
           SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) as realized,
           SUM(CASE WHEN status IN ('confirmed', 'completed') THEN total_price ELSE 0 END) as projected
    FROM bookings 
    WHERE created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY pay_date ASC
");
$stmt->execute([$start_date]);

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($days_data[$row['pay_date']])) {
        $days_data[$row['pay_date']]['realized'] = (float)$row['realized'];
        $days_data[$row['pay_date']]['projected'] = (float)$row['projected'];
    }
}

$realized_data = [];
$projected_data = [];
$cum_realized = 0;
$cum_projected = 0;

foreach ($days_data as $date => $data) {
    $cum_realized += $data['realized'];
    $cum_projected += $data['projected'];
    $realized_data[$date] = $cum_realized;
    $projected_data[$date] = $cum_projected;
}

echo "Date | Daily Realized | Cum Realized | Daily Projected | Cum Projected\n";
echo "----------------------------------------------------------------------\n";
foreach ($days_data as $date => $data) {
    echo $date . " | " . $data['realized'] . " | " . $realized_data[$date] . " | " . $data['projected'] . " | " . $projected_data[$date] . "\n";
}
