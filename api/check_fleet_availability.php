<?php
include_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['model']) || !isset($_GET['date']) || !isset($_GET['days'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$model = $_GET['model'];
$pickup_date = $_GET['date'];
$days = (int)$_GET['days'];
$dropoff_date = date('Y-m-d', strtotime($pickup_date . ' + ' . $days . ' days'));

// 1. Get total cars of this model
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles 
                        WHERE (model = ? OR CONCAT(make, ' ', model) = ?) 
                        AND status != 'deleted'");
$stmt->execute([$model, $model]);
$total_cars = (int)$stmt->fetchColumn();

// 2. Get booked cars
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE (v.model = ? OR CONCAT(v.make, ' ', v.model) = ?)
    AND b.status NOT IN ('cancelled', 'completed')
    AND (
        (b.pickup_date < ? AND b.dropoff_date > ?)
    )
");
$stmt->execute([$model, $model, $dropoff_date, $pickup_date]);
$booked_cars = (int)$stmt->fetchColumn();

$available = $total_cars - $booked_cars;

echo json_encode([
    'model' => $model,
    'total' => $total_cars,
    'booked' => $booked_cars,
    'available' => $available >= 0 ? $available : 0
]);
