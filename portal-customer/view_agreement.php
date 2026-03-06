<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login.php");
    exit;
}

$booking_id = $_GET['booking_id'] ?? null;
if (!$booking_id) {
    die("Booking ID required.");
}

$user_id = $_SESSION['user_id'];

// Fetch Comprehensive Booking Data, ensuring it belongs to the logged-in user
$stmt = $pdo->prepare("
    SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.license_no,
           v.make, v.model, v.year, v.plate_number, v.vin, v.color, v.engine_no
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$b = $stmt->fetch();

if (!$b) {
    die("Booking not found or access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Agreement - #<?php echo $b['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --dark: #0f172a;
            --grey: #64748b;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            line-height: 1.6;
            margin: 0;
            padding: 40px;
            background: #f8fafc;
        }
        .contract-paper {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 60px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--dark);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .contract-title {
            text-align: right;
        }
        .contract-title h1 {
            margin: 0;
            font-size: 20px;
            color: var(--primary);
        }
        h2 {
            font-size: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-top: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-box strong {
            display: block;
            font-size: 11px;
            color: var(--grey);
            text-transform: uppercase;
        }
        .terms {
            font-size: 12px;
            color: #334155;
            text-align: justify;
        }
        .signature-area {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
        }
        .sig-box {
            border-top: 1px solid var(--dark);
            padding-top: 10px;
            font-size: 13px;
            text-align: center;
        }
        @media print {
            body { background: white; padding: 0; }
            .contract-paper { border: none; box-shadow: none; width: 100%; max-width: 100%; padding: 0; }
            .no-print { display: none; }
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            background: var(--primary);
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            cursor: pointer;
            border: none;
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Print Agreement / Save as PDF</button>
        <a href="dashboard.php" style="margin-left: 15px; color: var(--grey);">Back to Dashboard</a>
    </div>

    <div class="contract-paper">
        <div class="header">
            <div class="logo">Car Hire</div>
            <div class="contract-title">
                <h1>Rental Agreement</h1>
                <small>Booking Ref: #<?php echo str_pad($b['id'], 5, '0', STR_PAD_LEFT); ?></small>
            </div>
        </div>

        <div class="grid">
            <div class="info-box">
                <strong>Renter / Customer</strong>
                <span><?php echo htmlspecialchars($b['customer_name']); ?></span>
                <small><?php echo htmlspecialchars($b['customer_email']); ?> | <?php echo htmlspecialchars($b['customer_phone']); ?></small>
                <small>ID/NRC: <?php echo htmlspecialchars($b['license_no'] ?: 'Pending'); ?></small>
            </div>
            <div class="info-box">
                <strong>Rental Company</strong>
                <span>Car Hire LTD</span>
                <small>Lusaka, Zambia</small>
                <small>TIN: 1000234567</small>
            </div>
        </div>

        <h2>Vehicle Details</h2>
        <div class="grid">
            <div class="info-box">
                <strong>Make & Model</strong>
                <span><?php echo $b['make'] . ' ' . $b['model']; ?> (<?php echo $b['year']; ?>)</span>
            </div>
            <div class="info-box">
                <strong>License Plate</strong>
                <span><?php echo $b['plate_number'] ?: 'N/A'; ?></span>
            </div>
        </div>
        <div class="grid">
            <div class="info-box">
                <strong>VIN / Chassis</strong>
                <span><?php echo $b['vin'] ?: 'N/A'; ?></span>
            </div>
            <div class="info-box">
                <strong>Booking Period</strong>
                <span><?php echo date('d M, Y', strtotime($b['pickup_date'])); ?> to <?php echo date('d M, Y', strtotime($b['dropoff_date'])); ?></span>
            </div>
        </div>

        <h2>Financial Summary</h2>
        <div class="grid">
            <div class="info-box">
                <strong>Daily Rate</strong>
                <?php 
                $days = ceil((strtotime($b['dropoff_date']) - strtotime($b['pickup_date'])) / 86400);
                if ($days < 1) $days = 1;
                $daily_rate = $b['total_price'] / $days;
                ?>
                <span>ZMW <?php echo number_format($daily_rate, 2); ?></span>
            </div>
            <div class="info-box">
                <strong>Total Amount</strong>
                <span style="font-weight: 700; color: var(--primary);">ZMW <?php echo number_format($b['total_price'], 2); ?></span>
            </div>
        </div>

        <h2>Standard Terms & Conditions</h2>
        <div class="terms">
            <p>1. <strong>Use of Vehicle:</strong> The Renter agrees to operate the vehicle only within the borders of Zambia unless written permission is granted. The vehicle shall not be used for illegal activities, racing, or off-road use beyond standard road capability.</p>
            <p>2. <strong>Documentation:</strong> The Renter must possess a valid Zambian Driver's License or Internationally recognized equivalent. All identification provided must be authentic.</p>
            <p>3. <strong>Return Policy:</strong> The vehicle must be returned at the agreed time and location. Late returns will be billed at a pro-rated hourly rate of 20% of the daily fee per hour, up to 100% after 5 hours.</p>
            <p>4. <strong>Insurance & Liability:</strong> While the vehicle is insured, the Renter is liable for the first ZMW 5,000 (Insurance Excess) in case of any damage. Negligence, driving under influence, or unauthorized drivers void all insurance coverage.</p>
            <p>5. <strong>Fuel:</strong> The vehicle is provided with a full tank and must be returned full. A refueling surcharge of ZMW 500 plus fuel cost applies otherwise.</p>
        </div>

        <div class="signature-area">
            <div class="sig-box">
                <br><br>
                Customer Signature
            </div>
            <div class="sig-box">
                <br><br>
                Authorized Company Rep
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center; font-size: 10px; color: var(--grey);">
            This document is generated by Car Hire Management System. Date: <?php echo date('d M, Y H:i'); ?>
        </div>
    </div>

</body>
</html>
