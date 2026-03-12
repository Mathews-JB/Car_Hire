<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in as agent
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

// Stats for dashboard
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$pending_bookings = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
$active_rentals = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'maintenance'");
$in_maintenance = $stmt->fetchColumn();

// Recent Bookings
$stmt = $pdo->query("SELECT b.*, u.name as customer_name, v.make, v.model 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id 
                     JOIN vehicles v ON b.vehicle_id = v.id 
                     ORDER BY b.created_at DESC LIMIT 5");
$recent_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="agent-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>

        <main class="main-content">
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 60px;">
                <h1>Agent Dashboard</h1>
                <div class="agent-info" style="display: flex; align-items: center; gap: 20px;">
                    <!-- Theme Switcher -->
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                </div>
            </header>

            <div class="stats-grid" style="padding-top: 70px;">
                <div class="stat-card">
                    <h3><?php echo $pending_bookings; ?></h3>
                    <p>Pending Bookings</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $active_rentals; ?></h3>
                    <p>Active Rentals</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $in_maintenance; ?></h3>
                    <p>Vehicles in Repair</p>
                </div>
            </div>

            <div class="data-card">
                <h3>Recent Incoming Reservations</h3>
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Pickup Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_bookings as $b): ?>
                        <tr>
                            <td data-label="ID">#<?php echo $b['id']; ?></td>
                            <td data-label="Customer"><?php echo htmlspecialchars($b['customer_name']); ?></td>
                            <td data-label="Vehicle"><?php echo $b['make'] . ' ' . $b['model']; ?></td>
                            <td data-label="Pickup Date"><?php echo date('d M Y', strtotime($b['pickup_date'])); ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo $b['status']; ?>">
                                    <?php echo $b['status']; ?>
                                </span>
                            </td>
                            <td data-label="Action"><a href="reservation-details.php?id=<?php echo $b['id']; ?>" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.8rem;">Review</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
