<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch all bookings that could have a contract (confirmed, hired, or completed)
$stmt = $pdo->query("
    SELECT b.*, v.make, v.model, v.plate_number, u.name as customer_name, u.email as customer_email
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status IN ('confirmed', 'hired', 'completed')
    ORDER BY b.created_at DESC
");
$contracts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Legal Hub | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Digital Legal Hub</h1>
                    <p class="text-secondary">Automated Rental Agreements & Compliance Documents</p>
                </div>
                <div class="header-actions">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <button class="btn btn-outline" onclick="window.location.reload()"><i class="fas fa-sync"></i> Refresh</button>
                    <a href="reports.php" class="btn btn-primary"><i class="fas fa-file-export"></i> Export All</a>
                </div>
            </div>

            <div class="data-card mt-4">
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($contracts as $c): ?>
                        <tr>
                            <td data-label="Reference"><strong style="color:var(--accent-color); font-family:monospace;">#<?php echo str_pad($c['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            <td data-label="Customer">
                                <div class="font-bold"><?php echo htmlspecialchars($c['customer_name']); ?></div>
                                <div style="font-size: 0.75rem; opacity:0.6;"><?php echo htmlspecialchars($c['customer_email']); ?></div>
                            </td>
                            <td data-label="Vehicle">
                                <div class="font-bold"><?php echo $c['make'] . ' ' . $c['model']; ?></div>
                                <div style="font-size: 0.75rem; opacity:0.6;"><?php echo $c['plate_number']; ?></div>
                            </td>
                            <td data-label="Period">
                                <span style="font-size: 0.85rem;"><?php echo date('d M', strtotime($c['pickup_date'])); ?> - <?php echo date('d M, Y', strtotime($c['dropoff_date'])); ?></span>
                            </td>
                            <td data-label="Status">
                                <span class="status-pill status-<?php echo strtolower($c['status']); ?>">
                                    <?php echo ucfirst($c['status']); ?>
                                </span>
                            </td>
                            <td data-label="Actions">
                                <div style="display:flex; gap:8px; justify-content: flex-end;">
                                    <a href="contract-viewer.php?id=<?php echo $c['id']; ?>" class="btn btn-outline btn-sm" style="flex: 1; justify-content: center;">
                                        <i class="fas fa-eye"></i> <span class="hide-mobile">View</span>
                                    </a>
                                    <button onclick="downloadContract('<?php echo $c['id']; ?>')" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center;">
                                        <i class="fas fa-file-pdf"></i> <span class="hide-mobile">PDF</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($contracts)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:60px; opacity:0.5;">
                                <i class="fas fa-file-signature" style="font-size:3rem; display:block; margin-bottom:15px;"></i>
                                No active rental agreements found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- html2pdf Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script>
        function downloadContract(id) {
            window.location.href = 'contract-viewer.php?id=' + id + '&download=1';
        }
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

