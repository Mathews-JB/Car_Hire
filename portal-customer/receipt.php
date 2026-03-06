<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';
if (empty($booking_id)) {
    die("Invalid Booking ID");
}

$user_id = $_SESSION['user_id'];

// Fetch detailed booking info
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                       v.make, v.model, v.plate_number, v.price_per_day,
                       GROUP_CONCAT(ao.name SEPARATOR ', ') as addons_list,
                       SUM(ao.price_per_day) as addons_total_daily
                       FROM bookings b 
                       JOIN users u ON b.user_id = u.id 
                       JOIN vehicles v ON b.vehicle_id = v.id
                       LEFT JOIN booking_add_ons bao ON b.id = bao.booking_id
                       LEFT JOIN add_ons ao ON bao.add_on_id = ao.id
                       WHERE b.id = ? AND b.user_id = ?
                       GROUP BY b.id");
$stmt->execute([$booking_id, $user_id]);
$b = $stmt->fetch();

if (!$b) {
    die("Booking not found or access denied.");
}

// Calculate days
$start = new DateTime($b['pickup_date']);
$end = new DateTime($b['dropoff_date']);
$days = $start->diff($end)->days ?: 1;

// Fetch settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$company_name = $settings['company_name'] ?? 'Car Hire Rentals Ltd.';
$company_email = $settings['company_email'] ?? 'accounts@CarHire.zm';
$company_address = $settings['company_address'] ?? 'Plot 10101, Great East Road, Lusaka, Zambia';
$company_tpin = $settings['company_tpin'] ?? '1001234567';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo str_pad($b['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e293b;
            --secondary: #64748b;
            --accent: #2563eb;
            --border: #e2e8f0;
            --light: #f8fafc;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: url('../public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
            color: var(--primary);
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            -webkit-print-color-adjust: exact;
        }

        .receipt-paper {
            background: white;
            width: 100%;
            max-width: 800px; /* A4 width approx */
            padding: 60px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        /* Decorative top bar */
        .receipt-paper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--accent), #1d4ed8);
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 50px;
        }

        .company-branding h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin: 0;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .company-branding p {
            margin: 8px 0 0;
            color: var(--secondary);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .receipt-meta {
            text-align: right;
        }

        .receipt-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--secondary);
            font-weight: 700;
        }

        .receipt-number {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 5px 0 5px;
            color: var(--primary);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #bbf7d0;
            margin-top: 5px;
        }

        .billed-to {
            margin-bottom: 50px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
        }

        .section-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--secondary);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .client-info h3 {
            margin: 0 0 5px;
            font-size: 1.1rem;
        }
        .client-info p {
            margin: 0;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .dates-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            text-align: right;
        }

        .date-box strong { display: block; font-size: 0.95rem; }
        .date-box span { font-size: 0.85rem; color: var(--secondary); }

        /* Table */
        .line-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .line-items th {
            text-align: left;
            padding: 15px;
            background: var(--light);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--secondary);
            letter-spacing: 0.5px;
        }

        .line-items td {
            padding: 20px 15px;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
        }

        .item-desc strong { display: block; margin-bottom: 4px; }
        .item-desc small { color: var(--secondary); font-size: 0.85rem; }

        .pricing-column {
            text-align: right;
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }

        /* Summary */
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 60px;
        }

        .summary-table {
            width: 350px;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .summary-table tr:last-child td {
            border-bottom: none;
            padding-top: 20px;
        }

        .total-label { font-weight: 600; color: var(--secondary); }
        .total-value { text-align: right; font-weight: 600; }
        .grand-total { font-size: 1.4rem; color: var(--accent); font-weight: 800; }

        /* Footer */
        .receipt-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px dashed var(--border);
            padding-top: 30px;
        }

        .qr-placeholder {
            width: 80px;
            height: 80px;
            background: white;
            border: 1px solid var(--border);
            padding: 5px;
        }

        .footer-text {
            flex: 1;
            margin-left: 20px;
            font-size: 0.8rem;
            color: var(--secondary);
            line-height: 1.5;
        }

        .print-fab {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: var(--accent);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
            transition: all 0.3s;
            z-index: 100;
        }
        
        .print-fab:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(37, 99, 235, 0.5); }

        @media print {
            body { background: white !important; padding: 0 !important; }
            .receipt-paper { box-shadow: none !important; padding: 40px !important; max-width: 100% !important; }
            .print-fab { display: none !important; }
        }

        /* Mobile Fixes */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .receipt-paper {
                padding: 30px 20px;
                border-radius: 12px;
            }
            .header-row {
                flex-direction: column;
                gap: 30px;
                margin-bottom: 30px;
            }
            .receipt-meta {
                text-align: left;
                width: 100%;
            }
            .billed-to {
                flex-direction: column;
                gap: 25px;
                margin-bottom: 30px;
            }
            .dates-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                text-align: left;
            }
            .line-items-container {
                overflow-x: auto;
                margin-bottom: 25px;
                -webkit-overflow-scrolling: touch;
            }
            .line-items {
                min-width: 600px;
                margin-bottom: 0;
            }
            .summary-section {
                justify-content: center;
                margin-bottom: 40px;
            }
            .summary-table {
                width: 100%;
            }
            .receipt-footer {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            .footer-text {
                margin-left: 0;
            }
            .print-fab {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
            }
            .company-branding h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

    <button class="print-fab" onclick="window.print()" title="Print Receipt">
        <i class="fas fa-print"></i>
    </button>

    <div class="receipt-paper">
        <!-- Header -->
        <div class="header-row">
            <div class="company-branding">
                <h1>
                    <i class="fas fa-layer-group" style="color: var(--accent); font-size: 1.8rem;"></i> 
                    <?php echo htmlspecialchars($company_name); ?>
                </h1>
                <p>
                    <strong><?php echo htmlspecialchars($company_name); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($company_address)); ?><br>
                    TPIN: <?php echo htmlspecialchars($company_tpin); ?> • <?php echo htmlspecialchars($company_email); ?>
                </p>
            </div>
            <div class="receipt-meta">
                <div class="receipt-label">Receipt Number</div>
                <div class="receipt-number">#<?php echo str_pad($b['id'], 6, '0', STR_PAD_LEFT); ?></div>
                <div class="status-badge">PAID IN FULL</div>
                <div style="margin-top: 8px; color: var(--secondary); font-size: 0.85rem;">
                    Date Issued: <?php echo date('M d, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Billed To -->
        <div class="billed-to">
            <div>
                <div class="section-label">Billed To</div>
                <div class="client-info">
                    <h3><?php echo htmlspecialchars($b['customer_name']); ?></h3>
                    <p><?php echo htmlspecialchars($b['customer_email']); ?></p>
                    <p><?php echo htmlspecialchars($b['customer_phone']); ?></p>
                </div>
            </div>
            <div class="dates-grid">
                <div class="date-box">
                    <div class="section-label">Pickup Date</div>
                    <strong><?php echo date('d M Y', strtotime($b['pickup_date'])); ?></strong>
                    <span><?php echo date('H:i', strtotime($b['pickup_date'])); ?> HRS</span>
                </div>
                <div class="date-box">
                    <div class="section-label">Return Date</div>
                    <strong><?php echo date('d M Y', strtotime($b['dropoff_date'])); ?></strong>
                    <span><?php echo date('H:i', strtotime($b['dropoff_date'])); ?> HRS</span>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="line-items-container">
            <table class="line-items">
                <thead>
                    <tr>
                        <th width="50%">Description</th>
                        <th class="pricing-column">Rate</th>
                        <th class="pricing-column" style="text-align: center;">Duration</th>
                        <th class="pricing-column">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="item-desc">
                            <strong>Vehicle Rental: <?php echo $b['make'] . ' ' . $b['model']; ?></strong>
                            <small>Plate No: <?php echo $b['plate_number']; ?></small>
                        </td>
                        <td class="pricing-column">ZMW <?php echo number_format($b['price_per_day'], 2); ?></td>
                        <td class="pricing-column" style="text-align: center; color: var(--secondary);"><?php echo $days; ?> Days</td>
                        <td class="pricing-column" style="font-weight: 600;">ZMW <?php echo number_format($b['price_per_day'] * $days, 2); ?></td>
                    </tr>
                    <?php if ($b['addons_list']): 
                        // Split addons for cleaner display if comma separated
                        $addons = explode(', ', $b['addons_list']);
                    ?>
                        <tr>
                            <td class="item-desc">
                                <strong>Additional Services</strong>
                                <small><?php echo implode(' • ', $addons); ?></small>
                            </td>
                            <td class="pricing-column">ZMW <?php echo number_format($b['addons_total_daily'], 2); ?></td>
                            <td class="pricing-column" style="text-align: center; color: var(--secondary);"><?php echo $days; ?> Days</td>
                            <td class="pricing-column" style="font-weight: 600;">ZMW <?php echo number_format($b['addons_total_daily'] * $days, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="total-label">Subtotal</td>
                    <td class="total-value">ZMW <?php echo number_format($b['total_price'], 2); ?></td>
                </tr>
                <tr>
                    <td class="total-label">VAT (16%)</td>
                    <td class="total-value" style="color: var(--secondary); font-weight: 400;">Inclusive</td>
                </tr>
                <tr>
                    <td class="total-label" style="padding-top: 20px;">Total Paid</td>
                    <td class="total-value grand-total">ZMW <?php echo number_format($b['total_price'], 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="qr-placeholder">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?php echo urlencode('CarHire-REC-' . $b['id'] . '-' . $b['total_price']); ?>" alt="QR">
            </div>
            <div class="footer-text">
                <strong>Thank you for your business.</strong><br>
                For questions regarding this receipt, please contact <strong>accounts@CarHire.zm</strong>.<br>
                <span style="opacity: 0.7;">This receipt was computer generated on <?php echo date('Y-m-d H:i:s'); ?> and is valid without a signature.</span>
            </div>
        </div>
    </div>

    <script>
        // Auto-trigger print on load if requested
        if (window.location.search.includes('print=true')) {
            window.onload = function() { setTimeout(window.print, 500); }
        }
    </script>
</body>
</html>
