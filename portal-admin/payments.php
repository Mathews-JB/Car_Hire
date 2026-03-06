<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Filters
$status_filter   = $_GET['status']   ?? '';
$provider_filter = $_GET['provider'] ?? '';
$search          = trim($_GET['search'] ?? '');

// Build Query
$where = ["1=1"];
$params = [];

if ($status_filter) {
    $where[] = "p.status = ?";
    $params[] = $status_filter;
}
if ($provider_filter) {
    $where[] = "p.provider = ?";
    $params[] = $provider_filter;
}
if ($search) {
    $where[] = "(u.name LIKE ? OR p.transaction_id LIKE ? OR p.phone_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where);

$payments = [];
$db_error = '';
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               b.pickup_date, b.dropoff_date, b.pickup_location,
               u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
               v.make, v.model
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE $where_sql
        ORDER BY p.created_at DESC
    ");
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_error = "The <code>payments</code> table does not exist yet. Please run the database migration or complete a payment to auto-create it.";
}

// Summary Stats
$total_received = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'successful'), 'amount'));
$total_count    = count($payments);
$success_count  = count(array_filter($payments, fn($p) => $p['status'] === 'successful'));
$failed_count   = count(array_filter($payments, fn($p) => $p['status'] !== 'successful'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Transactions | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .pay-stat {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .pay-stat h4 { 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            color: rgba(255,255,255,0.5); 
            margin: 0 0 10px; 
            font-weight: 800;
        }
        .pay-stat .val { font-size: 1.6rem; font-weight: 800; color: white; line-height: 1; margin-bottom: 8px; }
        .pay-stat small { font-size: 0.75rem; opacity: 0.5; font-weight: 600; }
        
        .filters-bar { 
            display: flex; 
            gap: 12px; 
            flex-wrap: wrap; 
            margin-bottom: 30px; 
            align-items: center; 
            background: rgba(30, 41, 59, 0.4); 
            padding: 15px; 
            border-radius: 16px; 
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        @media (max-width: 768px) {
            .pay-stat { padding: 20px; text-align: center; }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filters-bar input, .filters-bar select, .filters-bar button { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Payment Transactions</h1>
                    <p class="text-secondary">All Lenco payment records — live gateway data.</p>
                </div>
                <div class="header-actions">
                    <a href="reports-financial.php" class="btn btn-outline"><i class="fas fa-chart-bar"></i> ROI Reports</a>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="summary-grid" style="margin-bottom: 30px;">
                <div class="summary-card status-pending-border" style="border-top: 3px solid #3b82f6;">
                    <h4>Transactions</h4>
                    <p><?php echo $total_count; ?></p>
                    <small>All time records</small>
                </div>
                <div class="summary-card status-success-border" style="border-top: 3px solid #10b981;">
                    <h4>Revenue</h4>
                    <p style="font-size: 1.1rem;">ZMW <?php echo number_format($total_received, 0); ?></p>
                    <small><?php echo $success_count; ?> successful</small>
                </div>
                <div class="summary-card status-success-border" style="border-top: 3px solid #10b981;">
                    <h4>Successful</h4>
                    <p style="color: #10b981;"><?php echo $success_count; ?></p>
                    <small>Payments confirmed</small>
                </div>
                <div class="summary-card status-cancelled-border" style="border-top: 3px solid #ef4444;">
                    <h4>Failed</h4>
                    <p style="color: #f87171;"><?php echo $failed_count; ?></p>
                    <small>Requires attention</small>
                </div>
            </div>

            <!-- DB Error Banner -->
            <?php if ($db_error): ?>
                <div class="status-pill status-cancelled" style="margin-bottom: 30px; width: 100%; text-transform: none; justify-content: flex-start; padding: 20px;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 15px; font-size: 1.2rem;"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 3px;">Database Setup Required</strong>
                        <span style="font-size: 0.85rem; opacity: 0.8;"><?php echo $db_error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <form method="GET" class="filters-bar">
                <input type="text" name="search" placeholder="Search customer, ID..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 150px;">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Status</option>
                    <option value="successful" <?php echo $status_filter === 'successful' ? 'selected' : ''; ?>>Successful</option>
                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding: 10px 15px;"><i class="fas fa-search"></i></button>
                <?php if ($search || $status_filter || $provider_filter): ?>
                    <a href="payments.php" class="btn btn-outline" style="padding: 10px 15px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="data-card">
                <?php if (count($payments) > 0): ?>
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th class="hide-mobile">#</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Provider</th>
                            <th>Amount</th>
                            <th>Transaction ID</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td data-label="Booking" class="font-bold hide-mobile">
                                <a href="booking-details.php?id=<?php echo $p['booking_id']; ?>" style="color: var(--primary-color);">#<?php echo $p['booking_id']; ?></a>
                            </td>
                            <td data-label="Customer">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($p['customer_name']); ?></div>
                                <small style="opacity: 0.5;"><?php echo htmlspecialchars($p['customer_email']); ?></small>
                            </td>
                            <td data-label="Vehicle"><?php echo htmlspecialchars($p['make'] . ' ' . $p['model']); ?></td>
                            <td data-label="Provider">
                                <span style="font-weight: 700;"><?php echo htmlspecialchars($p['provider']); ?></span>
                            </td>
                            <td data-label="Amount" class="font-bold" style="color: #10b981;">
                                ZMW <?php echo number_format($p['amount'], 2); ?>
                            </td>
                            <td data-label="Transaction ID">
                                <span class="txn-id" title="<?php echo htmlspecialchars($p['transaction_id']); ?>">
                                    <?php echo htmlspecialchars($p['transaction_id']); ?>
                                </span>
                            </td>
                            <td data-label="Mobile"><?php echo htmlspecialchars($p['phone_number'] ?: '—'); ?></td>
                            <td data-label="Status">
                                <?php
                                $s = strtolower($p['status']);
                                $pill_class = ($s === 'successful' || $s === 'completed') ? 'confirmed' : (($s === 'pending') ? 'pending' : 'cancelled');
                                ?>
                                <span class="status-pill status-<?php echo $pill_class; ?>" style="font-size: 0.7rem; padding: 4px 10px;">
                                    <?php echo strtoupper($p['status']); ?>
                                </span>
                            </td>
                            <td data-label="Date">
                                <div style="font-size: 0.85rem; color: white;"><?php echo date('d M Y', strtotime($p['created_at'])); ?></div>
                                <div style="font-size: 0.75rem; opacity: 0.5;"><?php echo date('H:i', strtotime($p['created_at'])); ?></div>
                            </td>
                            <td data-label="Action">
                                <a href="booking-details.php?id=<?php echo $p['booking_id']; ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: rgba(255,255,255,0.4);">
                        <i class="fas fa-receipt" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                        <p>No payment records found<?php echo ($search || $status_filter || $provider_filter) ? ' matching your filters.' : ' yet.'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
