<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT b.*, u.name as customer_name, v.make, v.model 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN vehicles v ON b.vehicle_id = v.id";

if ($status_filter) {
    $sql .= " WHERE b.status = :status";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($status_filter) {
    $stmt->execute(['status' => $status_filter]);
} else {
    $stmt->execute();
}
$reservations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
</head>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>

    <div class="agent-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>

        <main class="main-content">
            <h1>Reservation Management</h1>
            <p style="margin-bottom: 30px;">View and manage all customer bookings.</p>

            <div class="filters">
                <a href="reservations.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
                <a href="reservations.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="reservations.php?status=confirmed" class="filter-btn <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                <a href="reservations.php?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
            </div>

            <div class="data-card">
                <table class="data-table admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee;">
                            <th style="padding: 15px; text-align: left;">Booking ID</th>
                            <th style="padding: 15px; text-align: left;">Customer</th>
                            <th style="padding: 15px; text-align: left;">Vehicle</th>
                            <th style="padding: 15px; text-align: left;">Pickup Date</th>
                            <th style="padding: 15px; text-align: left;">Status</th>
                            <th style="padding: 15px; text-align: left;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reservations as $r): ?>
                        <tr>
                            <td data-label="Booking ID">#<?php echo $r['id']; ?></td>
                            <td data-label="Customer"><?php echo htmlspecialchars($r['customer_name']); ?></td>
                            <td data-label="Vehicle"><?php echo $r['make'] . ' ' . $r['model']; ?></td>
                            <td data-label="Pickup Date"><?php echo date('d M Y', strtotime($r['pickup_date'])); ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo $r['status']; ?>">
                                    <?php echo $r['status']; ?>
                                </span>
                            </td>
                            <td data-label="Action">
                                <a href="reservation-details.php?id=<?php echo $r['id']; ?>" class="btn btn-primary" style="padding: 5px 15px; font-size: 0.8rem;">Manage</a>
                            </td>
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

