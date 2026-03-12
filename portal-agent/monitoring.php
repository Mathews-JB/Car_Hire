<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in as agent
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

// Fetch Hired Vehicles
$stmt = $pdo->query("
    SELECT v.*, b.id as booking_id, u.name as customer_name, b.pickup_date, b.dropoff_date
    FROM vehicles v
    JOIN bookings b ON v.id = b.vehicle_id
    JOIN users u ON b.user_id = u.id
    WHERE v.status = 'hired' AND b.status = 'confirmed'
    ORDER BY b.dropoff_date ASC
");
$hired_vehicles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Monitoring | Agent</title>
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
            <div class="dashboard-header">
                <div>
                    <h1>Hired Vehicles Monitoring</h1>
                    <p class="text-secondary">Track active trips and expected returns.</p>
                </div>
            </div>

            <div class="data-card">
                <?php if(count($hired_vehicles) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Plate</th>
                            <th>Customer</th>
                            <th>Return Date</th>
                            <th>Live Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($hired_vehicles as $v): ?>
                        <tr>
                            <td><strong><?php echo $v['make'] . ' ' . $v['model']; ?></strong></td>
                            <td><code><?php echo $v['plate_number']; ?></code></td>
                            <td><?php echo htmlspecialchars($v['customer_name']); ?></td>
                            <td><?php echo date('d M, H:i', strtotime($v['dropoff_date'])); ?></td>
                            <td>
                                <?php 
                                $is_overdue = strtotime($v['dropoff_date']) < time();
                                if($is_overdue): ?>
                                    <span class="status-pill status-cancelled">Overdue</span>
                                <?php else: ?>
                                    <span class="status-pill status-confirmed">On Trip</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:0.8rem; font-family: monospace; color:#3b82f6; font-weight:600;">
                                    <?php echo number_format($v['latitude'], 4); ?>, <?php echo number_format($v['longitude'], 4); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state" style="padding: 60px;">
                    <i class="fas fa-car-side" style="font-size: 3rem; opacity: 0.2; margin-bottom: 20px;"></i>
                    <p>No active trips at the moment.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

