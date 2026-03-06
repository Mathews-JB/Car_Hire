<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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
    <title>Manage Bookings | Car Hire Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Reservation Management</h1>
                    <p class="text-secondary">Track and manage vehicle bookings and rental schedules.</p>
                </div>
                <div class="header-actions">
                    <a href="reports.php?type=bookings" class="btn btn-outline"><i class="fas fa-file-export"></i> Export CSV</a>
                </div>
            </div>

            <div class="filters-container" style="margin-bottom: 30px; overflow-x: auto; padding-bottom: 10px; -webkit-overflow-scrolling: touch;">
                <div class="filters" style="display: flex; gap: 10px; min-width: max-content;">
                    <a href="bookings.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
                    <a href="bookings.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="bookings.php?status=confirmed" class="filter-btn <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">Active</a>
                    <a href="bookings.php?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
                    <a href="bookings.php?status=cancelled" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                </div>
            </div>

            <div class="data-card">
                <?php if (count($reservations) > 0): ?>
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Pickup Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reservations as $r): ?>
                        <tr>
                            <td data-label="ID" class="font-bold">#<?php echo $r['id']; ?></td>
                            <td data-label="Customer">
                                <?php echo htmlspecialchars($r['customer_name']); ?>
                            </td>
                            <td data-label="Vehicle">
                                <?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?>
                            </td>
                            <td data-label="Pickup Date"><?php echo date('d M Y', strtotime($r['pickup_date'])); ?></td>
                            <td data-label="Total" class="font-bold">ZMW <?php echo number_format($r['total_price'], 0); ?></td>
                            <td data-label="Status">
                                <span class="status-pill status-<?php echo $r['status']; ?>">
                                    <?php echo $r['status']; ?>
                                </span>
                            </td>
                            <td data-label="Action">
                                <div style="display: flex; gap: 8px;">
                                    <a href="booking-details.php?id=<?php echo $r['id']; ?>" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.85rem;">Manage</a>
                                    <a href="contract-viewer.php?id=<?php echo $r['id']; ?>" target="_blank" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.85rem; border-color: var(--accent-color); color: var(--accent-color);"><i class="fas fa-file-contract"></i> Contract</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--secondary-color);">
                        <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                        <p>No reservations found with this status.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
