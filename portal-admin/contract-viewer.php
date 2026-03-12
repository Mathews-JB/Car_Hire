<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? '';
$download = isset($_GET['download']);

if (empty($id)) {
    die("Invalid booking ID.");
}

// Fetch booking, vehicle, and user details
$stmt = $pdo->prepare("
    SELECT b.*, v.make, v.model, v.plate_number, v.year, v.color, v.vin, v.fuel_type, v.transmission,
           u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.license_no
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Contract not found.");
}

$reference = "CAR-" . str_pad($data['id'], 6, '0', STR_PAD_LEFT);

// Calculate duration
$start = new DateTime($data['pickup_date']);
$end = new DateTime($data['dropoff_date']);
$days = $start->diff($end)->days ?: 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Agreement - <?php echo $reference; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        :root {
            --primary: #111827;
            --secondary: #4b5563;
            --border: #e5e7eb;
            --accent: #2563eb;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            padding: 40px;
            color: var(--primary);
            line-height: 1.5;
        }
        .contract-page {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 60px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 8px; /* Clean look */
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 40px;
        }
        .logo-section h1 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }
        .agreement-title {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
            font-weight: 800;
            font-size: 1.2rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }
        .section {
            margin-bottom: 35px;
        }
        .section-title {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.9rem;
            color: var(--primary);
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-size: 0.7rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 2px;
            display: block;
        }
        .info-value {
            font-weight: 600;
            font-size: 0.95rem;
            color: #111827;
        }
        .terms p {
            font-size: 0.85rem;
            line-height: 1.6;
            color: #374151;
            margin-bottom: 8px;
            text-align: justify;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            margin-top: 40px;
        }
        .sig-box {
            margin-top: 20px;
        }
        @media print {
            body { background: white; padding: 0; }
            .contract-page { box-shadow: none; border-radius: 0; padding: 0; margin: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
            .btn { display: none !important; }
        }
        .controls {
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary { background: #111827; color: white; }
        .btn-outline { background: white; border: 1px solid #d1d5db; color: #374151; }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>

    <div class="controls no-print">
        <a href="contracts.php" class="btn btn-outline">â† Back to Hub</a>
        <div>
            <button onclick="window.print()" class="btn btn-outline">Print Agreement</button>
            <button id="downloadBtn" class="btn btn-primary">Download PDF</button>
        </div>
    </div>

    <div class="contract-page" id="contractContent">
        <!-- Header Section -->
        <div class="header">
            <div class="logo-section">
                <h1>Car Hire</h1>
                <small style="display:block; margin-top:5px; color:#666; font-size: 0.85rem;">Vehicle Rental Services Ltd.</small>
                <small style="display:block; color:#999; font-size: 0.8rem;">Lusaka, Zambia | +260 970 000 000</small>
            </div>
            <div style="text-align: right;">
                <div class="info-label">AGREEMENT REF</div>
                <div class="info-value" style="color: #2563eb; font-family: monospace; font-size: 1.1rem;"><?php echo $reference; ?></div>
                
                <div class="info-label" style="margin-top:10px;">GENERATED ON</div>
                <div class="info-value"><?php echo date('d M, Y \a\t H:i'); ?></div>
                
                <div class="info-label" style="margin-top:10px;">STATUS</div>
                <div class="info-value" style="text-transform: uppercase; color: <?php echo ($data['status']=='confirmed') ? '#10b981' : '#f59e0b'; ?>;"><?php echo htmlspecialchars($data['status']); ?></div>
            </div>
        </div>

        <h2 class="agreement-title">OFFICIAL VEHICLE RENTAL AGREEMENT</h2>

        <p style="font-size: 0.9rem; margin-bottom: 30px; text-align: justify; color: #4b5563;">
            This Vehicle Rental Agreement (the "Agreement") is made and entered into as of <strong><?php echo date('F j, Y'); ?></strong>, by and between <strong>Car Hire Zambia Ltd.</strong> ("Owner") and the individual or entity identified below ("Renter").
        </p>

        <!-- 1. The Parties -->
        <div class="section">
            <div class="section-title">1. THE PARTIES</div>
            <div class="grid">
                <div style="background: #f9fafb; padding: 15px; border-radius: 6px; border: 1px solid #eee;">
                    <div class="info-label" style="font-weight: 800; color: #111; margin-bottom: 5px;">OWNER (LESSOR)</div>
                    <div class="info-value">Car Hire Zambia Ltd.</div>
                    <div class="info-value" style="font-weight:400; font-size:0.8rem; margin-top:5px; color: #555;">
                        Lusaka Main Branch<br>
                        Great East Road, Lusaka<br>
                        support@CarHire.com
                    </div>
                </div>
                <div style="background: #f9fafb; padding: 15px; border-radius: 6px; border: 1px solid #eee;">
                    <div class="info-label" style="font-weight: 800; color: #111; margin-bottom: 5px;">RENTER (LESSEE)</div>
                    <div class="info-value" style="font-size: 1.05rem;"><?php echo htmlspecialchars($data['customer_name']); ?></div>
                    <div class="info-value" style="font-weight:400; font-size:0.8rem; margin-top:5px; color: #555;">
                        Email: <?php echo htmlspecialchars($data['customer_email']); ?><br>
                        Phone: <?php echo htmlspecialchars($data['customer_phone'] ?: 'N/A'); ?><br>
                        License No: <?php echo htmlspecialchars($data['license_no'] ?: 'Pending Verification'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. The Vehicle -->
        <div class="section">
            <div class="section-title">2. THE VEHICLE</div>
            <div class="grid" style="align-items: center;">
                <div>
                    <div class="info-item">
                        <div class="info-label">MAKE & MODEL</div>
                        <div class="info-value" style="font-size: 1.1rem;"><?php echo $data['year'] . ' ' . $data['make'] . ' ' . $data['model']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">REGISTRATION PLATE</div>
                        <div class="info-value" style="font-family: monospace; background: #eee; padding: 2px 6px; border-radius: 4px; display: inline-block; border: 1px solid #ddd;"><?php echo $data['plate_number']; ?></div>
                    </div>
                </div>
                <div>
                    <div class="grid" style="gap: 10px;">
                        <div class="info-item">
                            <div class="info-label">COLOR</div>
                            <div class="info-value"><?php echo $data['color'] ?: 'Standard'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">FUEL / TRANS.</div>
                            <div class="info-value"><?php echo ucfirst($data['fuel_type']) . ' / ' . ucfirst($data['transmission']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">VIN / CHASSIS</div>
                            <div class="info-value" style="font-size: 0.8rem; font-family: monospace;"><?php echo $data['vin'] ?: '________________'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Rental Period & Fees -->
        <div class="section">
            <div class="section-title">3. RENTAL PERIOD & FINANCIALS</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px 0; color: #666;">Rental Start (Pickup)</td>
                    <td style="padding: 10px 0; font-weight: 600; text-align: right;"><?php echo date('d M, Y', strtotime($data['pickup_date'])); ?> (10:00 AM)</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px 0; color: #666;">Rental End (Return)</td>
                    <td style="padding: 10px 0; font-weight: 600; text-align: right;"><?php echo date('d M, Y', strtotime($data['dropoff_date'])); ?> (10:00 AM)</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px 0; color: #666;">Total Duration</td>
                    <td style="padding: 10px 0; font-weight: 600; text-align: right;"><?php echo $days; ?> Days</td>
                </tr>
                <tr style="background: #ecfdf5; border-radius: 4px;">
                    <td style="padding: 15px 10px; font-weight: 800; color: #065f46;">TOTAL RENTAL FEE (PAID)</td>
                    <td style="padding: 15px 10px; font-weight: 800; color: #065f46; text-align: right; font-size: 1.1rem;">ZMW <?php echo number_format($data['total_price'], 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- 4. Terms -->
        <div class="section terms">
            <div class="section-title">4. TERMS & ACCEPTANCE</div>
            <p><strong>4.1 Condition of Vehicle:</strong> The Renter acknowledges receiving the Vehicle in good mechanical condition and clean bodywork.</p>
            <p><strong>4.2 Use of Vehicle:</strong> The Vehicle shall NOT be used for: (a) Transportation of passengers for hire; (b) Any race, test, or contest; (c) Any illegal purpose.</p>
            <p><strong>4.3 Insurance & Liability:</strong> Basic insurance is included. The Renter is liable for the first ZMW 5,000 for any damage caused due to negligence (Excess Fee).</p>
            <p><strong>4.4 Fuel Policy:</strong> The Vehicle is provided with a full/partial tank and must be returned with the same fuel level. Missing fuel will be charged at current pump rates + 10% service fee.</p>
            <p><strong>4.5 Return:</strong> Late returns will incur a penalty of ZMW 500 per hour delayed.</p>
        </div>

        <!-- Signatures -->
        <div class="section" style="margin-top: 50px;">
            <p style="font-size: 0.8rem; margin-bottom: 30px;">IN WITNESS WHEREOF, the parties have executed this Agreement as of the date first above written.</p>
            
            <div class="signature-grid">
                <div class="sig-box">
                    <div style="height: 40px; display: flex; align-items: flex-end; margin-bottom: 5px;">
                        <span style="font-family: 'Cursive', serif; font-size: 1.2rem; color: #2563eb;">Authorized Rep.</span>
                    </div>
                    <div style="border-top: 1px solid #000; padding-top: 5px;">
                        <strong>Car Hire Representative</strong><br>
                        <span style="font-size: 0.7rem; color: #666;">Authorized Signature</span>
                    </div>
                </div>
                <div class="sig-box" style="position: relative;">
                    <div style="height: 40px;"></div> <!-- Space for wet signature -->
                    <div style="border-top: 1px solid #000; padding-top: 5px;">
                        <strong><?php echo htmlspecialchars($data['customer_name']); ?></strong><br>
                        <span style="font-size: 0.7rem; color: #666;">Renter Signature</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #999; font-size: 0.7rem;">
            Generated by Car Hire Digital Contract System | ID: <?php echo $reference; ?> | <?php echo date('Y-m-d H:i:s'); ?><br>
            Use of this document implies acceptance of all standard Terms & Conditions available at CarHire.com/terms
        </div>
    </div>

    <!-- PDF Generation Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.getElementById('downloadBtn').onclick = function() {
            var element = document.getElementById('contractContent');
            var opt = {
                margin:       10,
                filename:     'Rental-Agreement-<?php echo $reference; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        };

        <?php if($download): ?>
        window.onload = function() {
            document.getElementById('downloadBtn').click();
        };
        <?php endif; ?>
    </script>
</body>
</html>

