<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ── Export Logic ─────────────────────────────────────────────────────────────
$type = $_GET['type'] ?? 'bookings';

if ($type === 'fleet') {
    // Export Fleet Data
    $filename = "Car_hire_Fleet_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Vehicle ID', 'Make', 'Model', 'Year', 'Plate Number', 'VIN', 'Fuel', 'Trans', 'Mileage', 'Status', 'Daily Rate (ZMW)']);

    $stmt = $pdo->query("SELECT id, make, model, year, plate_number, vin, fuel_type, transmission, current_mileage, status, price_per_day FROM vehicles ORDER BY make ASC");
    while ($v = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $v['id'], $v['make'], $v['model'], $v['year'], $v['plate_number'], $v['vin'], $v['fuel_type'], $v['transmission'], $v['current_mileage'], ucfirst($v['status']), number_format($v['price_per_day'], 0)
        ]);
    }
} else {
    // Export Bookings Data (Default)
    $stmt = $pdo->query("
        SELECT b.id, u.name as customer_name, v.make, v.model, b.pickup_date, b.dropoff_date, b.total_price, b.status, b.created_at
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN vehicles v ON b.vehicle_id = v.id 
        ORDER BY b.created_at DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Car_hire_Bookings_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Booking ID', 'Customer Name', 'Vehicle', 'Pickup Date', 'Drop-off Date', 'Total Price (ZMW)', 'Status', 'Date Created']);

    foreach ($bookings as $booking) {
        fputcsv($output, [
            $booking['id'],
            $booking['customer_name'],
            $booking['make'] . ' ' . $booking['model'],
            $booking['pickup_date'],
            $booking['dropoff_date'],
            number_format($booking['total_price'], 2),
            ucfirst($booking['status']),
            $booking['created_at']
        ]);
    }
}

fclose($output);
exit;
