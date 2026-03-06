<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Handle Demo Mode
if (isset($_GET['demo']) && $_GET['demo'] == 'true') {
    // Generate Dummy Data for Testing
    $booking = [
        'id' => rand(1000, 9999),
        'created_at' => date('Y-m-d H:i:s'),
        'pickup_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'dropoff_date' => date('Y-m-d H:i:s', strtotime('+4 days')),
        'total_price' => 4500.00,
        'status' => 'confirmed'
    ];
    $user = ['name' => 'Demo Customer', 'email' => 'demo@example.com', 'phone' => '+260 97 000 000'];
    $vehicle = ['make' => 'Toyota', 'model' => 'Hilux', 'license_plate' => 'ABC 123'];
    $addons = [['name' => 'GPS Navigation', 'price' => 150]];
} else {
    // Real Booking Logic
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login with return URL
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: ../login.php");
        exit;
    }

    $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    
    // Fetch Booking
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Invoice not found or access denied.");
    }

    // Fetch User
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$booking['user_id']]);
    $user = $stmt->fetch();

    // Fetch Vehicle
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$booking['vehicle_id']]);
    $vehicle = $stmt->fetch();

    // Fetch Addons
    $stmt = $pdo->prepare("SELECT a.name, a.price_per_day AS price FROM booking_add_ons ba JOIN add_ons a ON ba.add_on_id = a.id WHERE ba.booking_id = ?");
    $stmt->execute([$booking_id]);
    $addons = $stmt->fetchAll();
}

// Calculations
$days = ceil((strtotime($booking['dropoff_date']) - strtotime($booking['pickup_date'])) / (60 * 60 * 24));
if ($days < 1) $days = 1;
$base_daily = ($booking['total_price'] - array_sum(array_column($addons, 'price'))) / $days; // Approx
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $booking['id']; ?> | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1e293b; background: #525659; margin: 0; padding: 40px 0; min-height: 100vh; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: white; padding: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 50px; border-bottom: 2px solid #f1f5f9; padding-bottom: 30px; }
        .logo { font-size: 24px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: -1px; }
        .invoice-meta { text-align: right; }
        .invoice-title { font-size: 32px; font-weight: 700; color: #0284c7; margin: 0; }
        .meta-item { margin-top: 5px; color: #64748b; font-size: 14px; }
        
        .client-info { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .info-block h3 { font-size: 14px; text-transform: uppercase; color: #94a3b8; margin-bottom: 10px; letter-spacing: 1px; }
        .info-block p { margin: 0; font-weight: 600; font-size: 16px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
        tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .total-row td { padding-top: 20px; font-size: 18px; font-weight: 700; color: #0f172a; border-top: 2px solid #0f172a; }

        .footer { text-align: center; color: #94a3b8; font-size: 12px; margin-top: 60px; padding-top: 30px; border-top: 1px solid #f1f5f9; }
        
        @media print {
            body { background: white !important; padding: 0 !important; }
            .invoice-container { box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
            .no-print { display: none !important; }
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            body {
                padding: 15px 0;
                background: #f1f5f9; /* Lighter background for mobile web view */
            }
            .invoice-container {
                padding: 30px 15px;
                margin: 0 10px;
                border-radius: 12px;
            }
            .invoice-header {
                flex-direction: column;
                gap: 25px;
                text-align: left;
            }
            .invoice-meta {
                text-align: left;
            }
            .invoice-title {
                font-size: 28px;
            }
            .client-info {
                flex-direction: column;
                gap: 25px;
            }
            .info-block[style*="text-align: right"] {
                text-align: left !important;
            }
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin-bottom: 30px;
            }
            table {
                min-width: 500px;
                margin-bottom: 0;
            }
            .total-row td {
                font-size: 16px;
            }
            .no-print {
                padding: 0 15px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .no-print a {
                margin-left: 0 !important;
                background: rgba(0,0,0,0.1);
                color: #1e293b !important;
                padding: 10px;
                border-radius: 6px;
                font-weight: 600;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Download / Print PDF</button>
        <a href="dashboard.php" style="margin-left: 15px; color: white; text-decoration: none;">Back to Dashboard</a>
    </div>

    <div class="invoice-container">
        <div class="invoice-header">
            <div>
                <div class="logo">Car Hire.</div>
                <div style="color: #64748b; font-size: 14px; margin-top: 10px;">
                    123 Business Road<br>Lusaka, Zambia<br>+260 97 123 4567
                </div>
            </div>
            <div class="invoice-meta">
                <h1 class="invoice-title">INVOICE</h1>
                <div class="meta-item">Invoice #: INV-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
                <div class="meta-item">Date: <?php echo date('d M, Y', strtotime($booking['created_at'])); ?></div>
                <div class="meta-item">Status: <span style="color: #10b981; font-weight: bold; text-transform: uppercase;"><?php echo $booking['status']; ?></span></div>
            </div>
        </div>

        <div class="client-info">
            <div class="info-block">
                <h3>Bill To</h3>
                <p><?php echo htmlspecialchars($user['name']); ?></p>
                <p style="font-weight: 400; font-size: 14px; color: #64748b; margin-top: 5px;"><?php echo htmlspecialchars($user['email']); ?></p>
                <p style="font-weight: 400; font-size: 14px; color: #64748b;"><?php echo htmlspecialchars($user['phone']); ?></p>
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Vehicle Details</h3>
                <p><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></p>
                <p style="font-weight: 400; font-size: 14px; color: #64748b; margin-top: 5px;">Plate: <?php echo htmlspecialchars($vehicle['license_plate']); ?></p>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Vehicle Rental</strong><br>
                            <span style="font-size: 12px; color: #64748b;">
                                <?php echo date('d M Y', strtotime($booking['pickup_date'])); ?> to <?php echo date('d M Y', strtotime($booking['dropoff_date'])); ?>
                            </span>
                        </td>
                        <td class="text-right">ZMW <?php echo number_format($base_daily, 2); ?></td>
                        <td class="text-right"><?php echo $days; ?> Days</td>
                        <td class="text-right">ZMW <?php echo number_format($base_daily * $days, 2); ?></td>
                    </tr>
                    <?php foreach($addons as $addon): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($addon['name']); ?></td>
                        <td class="text-right">ZMW <?php echo number_format($addon['price'], 2); ?></td>
                        <td class="text-right">1</td>
                        <td class="text-right">ZMW <?php echo number_format($addon['price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-right">Grand Total</td>
                        <td class="text-right">ZMW <?php echo number_format($booking['total_price'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business. Payment is due prior to vehicle pickup.</p>
            <p>Car Hire Zambia Ltd.</p>
        </div>
    </div>
    
    <script>
        // Optional: specific print settings or analytics
    </script>
</body>
</html>
