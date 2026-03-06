<?php
include_once 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 1; // Default to 1 for testing if not logged in

echo "<h1>Debug Info</h1>";
echo "User ID: $user_id<br>";

// Check User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "User Role: " . ($user['role'] ?? 'N/A') . "<br>";

// Check Bookings
$stmt = $pdo->prepare("SELECT id, status, pickup_date FROM bookings WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Recent Bookings:</h3>";
if (count($bookings) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Status</th><th>Date</th></tr>";
    foreach ($bookings as $b) {
        echo "<tr><td>{$b['id']}</td><td>{$b['status']}</td><td>{$b['pickup_date']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No bookings found for this user.";
}

// Force Confirm the latest one?
if (isset($_GET['force_confirm'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    echo "<br><b>UPDATED: Latest booking forced to 'confirmed'. Refresh dashboard now.</b>";
}

echo "<br><br><a href='debug_status.php?force_confirm=1'>Force Confirm Latest Booking</a>";
echo "<br><a href='portal-customer/dashboard.php'>Go to Dashboard</a>";
?>
