<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// --- Key Performance Indicators (KPIs) ---

// 1. Total Revenue (Completed Bookings)
$stmt = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'completed'");
$total_revenue = $stmt->fetchColumn() ?: 0;

// 2. Pending Actions (Bookings needing approval)
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$pending_bookings = $stmt->fetchColumn();

// 3. Active Rentals (Currently on the road)
$current_date = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND pickup_date <= ? AND dropoff_date >= ?");
$stmt->execute([$current_date, $current_date]);
$active_rentals = $stmt->fetchColumn();

// 4. Fleet Availability
$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'");
$available_vehicles = $stmt->fetchColumn();

// --- Detailed Data ---

// Recent Bookings
$stmt = $pdo->query("
    SELECT b.*, u.name as customer_name, v.make, v.model, v.image_url 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN vehicles v ON b.vehicle_id = v.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$recent_bookings = $stmt->fetchAll();

// Fleet Status Breakdown
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM vehicles GROUP BY status");
$fleet_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$fleet_status = [
    'available' => $fleet_raw['available'] ?? 0,
    'hired' => $fleet_raw['hired'] ?? 0,
    'maintenance' => $fleet_raw['maintenance'] ?? 0,
    'booked' => $fleet_raw['booked'] ?? 0
];

// --- Compliance Alerts (Expiring within 30 days) ---
$thirty_days_later = date('Y-m-d', strtotime('+30 days'));
$stmt = $pdo->prepare("
    SELECT id, make, model, image_url, license_plate, insurance_expiry, road_tax_expiry, fitness_expiry, current_mileage, service_due_km 
    FROM vehicles 
    WHERE insurance_expiry <= ? 
       OR road_tax_expiry <= ? 
       OR fitness_expiry <= ?
       OR current_mileage >= (service_due_km - 500)
    LIMIT 10
");
$stmt->execute([$thirty_days_later, $thirty_days_later, $thirty_days_later]);
$expiring_docs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css?v=2.3">
    <style>
        .admin-layout::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4); /* Extra dim for dashboard */
            z-index: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Business Overview</h1>
                    <p class="text-secondary">Real-time platform performance metrics.</p>
                </div>
                <?php include_once '../includes/toast_notifications.php'; ?>
                <?php if(isset($_SESSION['msg_unfrozen'])): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            showToast('<strong>Security Override Successful</strong><br><span style="font-size:0.8rem;">Your account has been unfrozen. Please update your password immediately.</span>', 'success', 8000);
                        });
                    </script>
                <?php unset($_SESSION['msg_unfrozen']); endif; ?>
                <div class="header-actions">
                    <a href="notifications.php" class="btn btn-outline" style="position: relative; width: 50px; height: 50px; padding: 0; border-radius: 50%;">
                        <i class="fas fa-bell" style="font-size: 1.2rem;"></i>
                        <?php if (isset($notif_count) && $notif_count > 0): ?>
                            <span style="position: absolute; top: 0; right: 0; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                                <?php echo $notif_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="fleet.php" class="btn btn-primary"><i class="fas fa-car"></i> Fleet Overview</a>
                    <a href="reports.php" class="btn btn-outline"><i class="fas fa-download"></i> Download Report</a>
                </div>
            </div>

            <!-- Quick Actions Bar -->
            <div style="display: flex; gap: 12px; margin-bottom: 25px; overflow-x: auto; padding-bottom: 5px;">
                <a href="bookings.php?status=pending" class="btn btn-primary" style="white-space: nowrap; flex-shrink: 0;"><i class="fas fa-clock"></i> Review Pending (<?php echo $pending_bookings; ?>)</a>
                <a href="fleet.php?action=add" class="btn btn-outline" style="white-space: nowrap; flex-shrink: 0;"><i class="fas fa-plus-circle"></i> Add Vehicle</a>
                <a href="monitoring.php" class="btn btn-outline" style="white-space: nowrap; flex-shrink: 0;"><i class="fas fa-map-marked-alt"></i> Live Map</a>
                <a href="reports-financial.php" class="btn btn-outline" style="white-space: nowrap; flex-shrink: 0;"><i class="fas fa-chart-line"></i> Reports</a>
                <a href="payments.php" class="btn btn-outline" style="white-space: nowrap; flex-shrink: 0;"><i class="fas fa-receipt"></i> Transactions</a>
            </div>

            <!-- Top KPIs -->
            <div class="summary-grid">
                <div class="summary-card status-pending-border">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4>Pending</h4>
                            <p><?php echo $pending_bookings; ?></p>
                            <small>Needs Review</small>
                        </div>
                        <i class="fas fa-hourglass-half" style="font-size: 2rem; opacity: 0.15;"></i>
                    </div>
                </div>
                <div class="summary-card status-active-border">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4>Active</h4>
                            <p><?php echo $active_rentals; ?></p>
                            <small>On Road</small>
                        </div>
                        <i class="fas fa-road" style="font-size: 2rem; opacity: 0.15;"></i>
                    </div>
                </div>
                <div class="summary-card status-success-border">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4>Available</h4>
                            <p><?php echo $available_vehicles; ?></p>
                            <small>Ready to Rent</small>
                        </div>
                        <i class="fas fa-car" style="font-size: 2rem; opacity: 0.15;"></i>
                    </div>
                </div>
                <div class="summary-card status-dark-border">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4>Revenue</h4>
                            <p class="revenue-text" style="font-size: 1.3rem;">ZMW <?php echo number_format($total_revenue, 0); ?></p>
                            <small>Total Earned</small>
                        </div>
                        <i class="fas fa-wallet" style="font-size: 2rem; opacity: 0.15;"></i>
                    </div>
                </div>
            </div>

            <!-- Fleet Status Quick View -->
            <div class="data-card" style="margin-bottom: 30px; padding: 25px;">
                <h4 style="margin-bottom: 20px; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7;">Fleet Status Overview</h4>
                <div class="grid-2-mobile" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="text-align: center; padding: 15px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <span style="display: block; font-size: 1.8rem; font-weight: 800; color: #10b981; margin-bottom: 5px;"><?php echo $fleet_status['available']; ?></span>
                        <span style="font-size: 0.7rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">Available</span>
                    </div>
                    <div style="text-align: center; padding: 15px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2);">
                        <span style="display: block; font-size: 1.8rem; font-weight: 800; color: #3b82f6; margin-bottom: 5px;"><?php echo $fleet_status['hired']; ?></span>
                        <span style="font-size: 0.7rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">On Rent</span>
                    </div>
                    <div style="text-align: center; padding: 15px; background: rgba(245, 158, 11, 0.1); border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.2);">
                        <span style="display: block; font-size: 1.8rem; font-weight: 800; color: #f59e0b; margin-bottom: 5px;"><?php echo $fleet_status['maintenance']; ?></span>
                        <span style="font-size: 0.7rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">Service</span>
                    </div>
                    <div style="text-align: center; padding: 15px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; border: 1px solid rgba(99, 102, 241, 0.2);">
                        <span style="display: block; font-size: 1.8rem; font-weight: 800; color: #6366f1; margin-bottom: 5px;"><?php echo $fleet_status['booked']; ?></span>
                        <span style="font-size: 0.7rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">Reserved</span>
                    </div>
                </div>
            </div>

            <!-- Compliance Alerts Section -->
            <?php if(count($expiring_docs) > 0): ?>
            <div class="data-card" style="margin-bottom: 30px; border: 1px solid rgba(239, 68, 68, 0.3); padding: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="color: #ef4444; margin: 0; font-size: 1rem;"><i class="fas fa-exclamation-triangle"></i> Compliance Alerts</h3>
                    <small style="color: rgba(255,255,255,0.4);">Immediate action required</small>
                </div>
                <table class="data-table admin-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Plate</th>
                            <th>Requirement</th>
                            <th>Deadline</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($expiring_docs as $doc): 
                            $fields = [
                                'Insurance' => $doc['insurance_expiry'],
                                'Road Tax' => $doc['road_tax_expiry'],
                                'Fitness' => $doc['fitness_expiry']
                            ];
                            
                            if ($doc['current_mileage'] >= ($doc['service_due_km'] - 500)):
                                $km_left = $doc['service_due_km'] - $doc['current_mileage'];
                        ?>
                        <tr>
                            <td data-label="Vehicle">
                                <div class="vehicle-mini">
                                    <img src="<?php echo !empty($doc['image_url']) ? '../' . $doc['image_url'] : 'https://via.placeholder.com/150?text=Car'; ?>" alt="Car">
                                    <div>
                                        <strong><?php echo $doc['make'] . ' ' . $doc['model']; ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Plate"><?php echo $doc['license_plate'] ?: 'N/A'; ?></td>
                            <td data-label="Requirement"><span class="status-pill status-maintenance" style="font-size: 0.65rem;">Maintenance</span></td>
                            <td data-label="Deadline"><?php echo number_format($doc['service_due_km']); ?> KM</td>
                            <td data-label="Status">
                                <strong style="color: <?php echo $km_left <= 0 ? '#ef4444' : '#f59e0b'; ?>; font-size: 0.8rem;">
                                    <?php echo $km_left <= 0 ? 'OVERDUE' : $km_left . ' KM'; ?>
                                </strong>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php
                            foreach($fields as $label => $expiry):
                                if($expiry && $expiry <= $thirty_days_later):
                                    $days_left = floor((strtotime($expiry) - time()) / (60 * 60 * 24));
                        ?>
                        <tr>
                            <td data-label="Vehicle">
                                <div class="vehicle-mini">
                                    <img src="<?php echo !empty($doc['image_url']) ? '../' . $doc['image_url'] : 'https://via.placeholder.com/150?text=Car'; ?>" alt="Car">
                                    <div>
                                        <strong><?php echo $doc['make'] . ' ' . $doc['model']; ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Plate"><?php echo $doc['license_plate'] ?: 'N/A'; ?></td>
                            <td data-label="Requirement"><span class="status-pill <?php echo $days_left < 7 ? 'status-cancelled' : 'status-pending'; ?>" style="font-size: 0.65rem;"><?php echo $label; ?></span></td>
                            <td data-label="Deadline"><?php echo date('d M', strtotime($expiry)); ?></td>
                            <td data-label="Status">
                                <strong style="color: <?php echo $days_left < 7 ? '#ef4444' : '#f59e0b'; ?>; font-size: 0.8rem;">
                                    <?php echo $days_left < 0 ? 'EXPIRED' : $days_left . ' Days'; ?>
                                </strong>
                            </td>
                        </tr>
                        <?php 
                                endif;
                            endforeach;
                        endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Recent Bookings Table -->
                <div class="table-container activity-container">
                    <div class="table-header">
                        <h3>Recent Bookings</h3>
                        <a href="bookings.php" class="view-all-link">View All</a>
                    </div>
                    
                    <?php if(count($recent_bookings) > 0): ?>
                    <table class="data-table admin-table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Customer</th>
                                <th>Pickup Date</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_bookings as $b): ?>
                            <tr>
                                <td data-label="Vehicle">
                                    <div class="vehicle-mini">
                                        <img src="<?php echo !empty($b['image_url']) ? '../' . $b['image_url'] : 'https://via.placeholder.com/150?text=Car'; ?>" alt="Car">
                                        <div>
                                            <strong><?php echo htmlspecialchars($b['make']); ?></strong>
                                            <small><?php echo htmlspecialchars($b['model']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                 <td data-label="Customer"><?php echo htmlspecialchars($b['customer_name']); ?></td>
                                <td data-label="Pickup Date"><?php echo date('d M, Y', strtotime($b['pickup_date'])); ?></td>
                                <td data-label="Status"><span class="status-pill status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span></td>
                                <td data-label="Amount" class="font-bold">ZMW <?php echo number_format($b['total_price'], 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No bookings found.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Links -->
                <div class="table-container">
                    <h4 style="margin-bottom: 15px; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7;">Quick Links</h4>
                    <div style="display: grid; gap: 10px;">
                        <a href="vouchers.php" class="btn btn-outline" style="justify-content: flex-start; width: 100%;"><i class="fas fa-ticket-alt"></i> Manage Vouchers</a>
                        <a href="brands.php" class="btn btn-outline" style="justify-content: flex-start; width: 100%;"><i class="fas fa-tags"></i> Vehicle Brands</a>
                        <a href="users.php" class="btn btn-outline" style="justify-content: flex-start; width: 100%;"><i class="fas fa-users"></i> User Management</a>
                        <a href="settings.php" class="btn btn-outline" style="justify-content: flex-start; width: 100%;"><i class="fas fa-cog"></i> System Settings</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
