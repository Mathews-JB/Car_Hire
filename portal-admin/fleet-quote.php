<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg_id = $_GET['id'] ?? null;
if (!$msg_id) {
    header("Location: support-inbox.php?filter=events");
    exit;
}

// Fetch the support message
$stmt = $pdo->prepare("SELECT m.*, u.name as customer_name, u.email as customer_email
                        FROM support_messages m
                        JOIN users u ON m.user_id = u.id
                        WHERE m.id = ?");
$stmt->execute([$msg_id]);
$msg = $stmt->fetch();

if (!$msg) {
    header("Location: support-inbox.php?filter=events");
    exit;
}

// Fetch fleet options
$fleet_options = $pdo->query("SELECT make, model, price_per_day, COUNT(*) as qty FROM vehicles WHERE status != 'deleted' GROUP BY model ORDER BY make ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Quote Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_quote'])) {
    $quote_amount = $_POST['quote_amount'];
    $deposit_amount = isset($_POST['deposit_amount']) ? (float)$_POST['deposit_amount'] : 0;

    // Get User ID and Fleet Vehicle
    $user_id = $msg['user_id'];
    $existing_booking_id = $msg['booking_id'] ?? null;

    $stmt_v = $pdo->prepare("SELECT id FROM vehicles WHERE model = 'Multi-Car Fleet' LIMIT 1");
    $stmt_v->execute();
    $fleet_vehicle_id = $stmt_v->fetchColumn() ?: 0;

    // Create or Update Booking
    if ($existing_booking_id) {
        $stmt_b = $pdo->prepare("UPDATE bookings SET total_price = ?, deposit_amount = ?, payment_status = 'unpaid' WHERE id = ?");
        $stmt_b->execute([$quote_amount, $deposit_amount, $existing_booking_id]);
        $booking_id = $existing_booking_id;
    } else {
        $event_date = $msg['event_date'] ?? date('Y-m-d');
        $duration = $msg['duration_days'] ?? 1;
        $dropoff = date('Y-m-d', strtotime($event_date . ' + ' . $duration . ' days'));
        $stmt_b = $pdo->prepare("INSERT INTO bookings (user_id, vehicle_id, pickup_location, dropoff_location, pickup_date, dropoff_date, total_price, deposit_amount, payment_status, status)
                                 VALUES (?, ?, 'Office/Delivery', 'Office/Delivery', ?, ?, ?, ?, 'unpaid', 'pending')");
        $stmt_b->execute([$user_id, $fleet_vehicle_id, $event_date, $dropoff, $quote_amount, $deposit_amount]);
        $booking_id = $pdo->lastInsertId();
    }

    // Update message
    $stmt_u = $pdo->prepare("UPDATE support_messages SET quote_amount = ?, deposit_quote = ?, quote_status = 'sent', booking_id = ?, status = 'replied' WHERE id = ?");
    $stmt_u->execute([$quote_amount, $deposit_amount, $booking_id, $msg_id]);

    // Send Email
    include_once '../includes/mailer.php';
    $deposit_line = '';
    if ($deposit_amount > 0) {
        $balance = $quote_amount - $deposit_amount;
        $deposit_line = "\n\nPAYMENT PLAN:\n- Deposit to Lock Fleet: ZMW " . number_format($deposit_amount, 2) . "\n- Balance Due Before Event: ZMW " . number_format($balance, 2);
    }
    $quote_msg = "Hi " . $msg['customer_name'] . ",\n\nOur logistics team has reviewed your fleet request regarding '" . $msg['subject'] . "'.\n\nWe have generated a custom quote for your event:\nTOTAL AMOUNT: ZMW " . number_format($quote_amount, 2) . $deposit_line . "\n\nTo lock this fleet and confirm your reservation, please log into your portal and complete the payment.\n\nThank you for choosing Car Hire.";
    sendSupportEmail($msg['customer_name'], $msg['customer_email'], "Custom Fleet Quote: ZMW " . number_format($quote_amount, 2), $quote_msg, 'to_customer');

    // Notification
    $deposit_note = ($deposit_amount > 0) ? " Pay a deposit of ZMW " . number_format($deposit_amount, 2) . " to lock the fleet." : "";
    $notif_msg = "We have sent you an official quote of ZMW " . number_format($quote_amount, 2) . " for your event." . $deposit_note . " Visit 'My Bookings' to pay and confirm.";
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")->execute([$user_id, "New Fleet Quote Received", $notif_msg]);

    header("Location: fleet-quote.php?id=$msg_id&quoted=1");
    exit;
}

// Handle Manual Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_confirm'])) {
    $booking_id = $_POST['booking_id'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ?")->execute([$booking_id]);
        $pdo->prepare("UPDATE support_messages SET quote_status = 'paid' WHERE id = ?")->execute([$msg_id]);
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Booking Confirmed', 'Your fleet booking has been approved and confirmed by admin.', 'success')")->execute([$msg['user_id']]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    header("Location: fleet-quote.php?id=$msg_id&confirmed=1");
    exit;
}

// Re-fetch after potential update
$stmt->execute([$msg_id]);
$msg = $stmt->fetch();

$event_date = $msg['event_date'] ?? null;
$duration_days = $msg['duration_days'] ?? null;
$proposed_price = $msg['customer_proposed_price'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Quote Builder | Car Hire Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .fq-page { max-width: 900px; margin: 0 auto; }
        .fq-card {
            background: var(--light-bg, rgba(26,26,26,0.95));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            backdrop-filter: blur(20px);
        }
        .fq-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .fq-card-header i {
            font-size: 1.2rem;
            color: var(--accent-color);
        }
        .fq-card-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .fq-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        .fq-info-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 15px;
        }
        .fq-info-item label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 5px;
        }
        .fq-info-item span {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .fq-proposed {
            background: rgba(245, 158, 11, 0.08);
            border-color: rgba(245, 158, 11, 0.2);
        }
        .fq-proposed span { color: #f59e0b !important; }
        .fq-builder-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
        }
        .fq-avail-badge {
            font-size: 0.75rem;
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }
        .fq-avail-ok { background: rgba(16,185,129,0.1); color: var(--success); display: block; }
        .fq-avail-warn { background: rgba(239,68,68,0.1); color: var(--danger); display: block; }
        .fq-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .fq-items-table th {
            text-align: left;
            color: var(--text-secondary);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 8px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .fq-items-table td {
            padding: 12px 8px;
            color: var(--text-primary);
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .fq-items-table tfoot td {
            font-weight: 800;
            font-size: 1rem;
            color: var(--accent-color);
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .fq-deposit-section {
            background: rgba(16, 185, 129, 0.04);
            border: 1px solid rgba(16, 185, 129, 0.12);
            border-radius: 14px;
            padding: 20px;
        }
        .fq-deposit-presets {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .fq-deposit-presets button {
            padding: 5px 14px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }
        .fq-deposit-presets button:hover,
        .fq-deposit-presets button.active {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.3);
            color: var(--success);
        }
        .fq-balance-box {
            padding: 12px 15px;
            background: rgba(0,0,0,0.12);
            border-radius: 10px;
            color: var(--text-secondary);
            font-weight: 700;
            display: flex;
            align-items: center;
            height: 48px;
        }
        .fq-status-paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 20px;
            border-radius: 14px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            font-weight: 700;
            text-align: center;
            font-size: 1.1rem;
        }
        .fq-status-pending {
            background: rgba(37, 99, 235, 0.08);
            color: var(--text-secondary);
            padding: 15px 20px;
            border-radius: 14px;
            border: 1px solid rgba(37,99,235,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .fq-submit-btn {
            width: 100%;
            height: 58px;
            background: linear-gradient(135deg, #4b5563, #374151);
            border: none;
            font-weight: 800;
            font-size: 1.05rem;
            border-radius: 14px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .fq-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .fq-remove-btn {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 0.85rem;
            padding: 4px;
        }
        @media (max-width: 768px) {
            .fq-builder-row { grid-template-columns: 1fr; }
            .fq-info-grid { grid-template-columns: 1fr 1fr; }
            .fq-status-pending { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>
        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-car-side" style="margin-right:10px; color: var(--accent-color);"></i>Fleet Event Quote</h1>
                    <p class="text-secondary">Build and send a custom quote for <?php echo htmlspecialchars($msg['customer_name']); ?></p>
                </div>
                <div class="header-actions" style="display: flex; gap: 10px; align-items: center;">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <a href="support-inbox.php?id=<?php echo $msg_id; ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Inbox</a>
                </div>
            </div>

            <div class="fq-page">

                <!-- Success Banners -->
                <?php if(isset($_GET['quoted'])): ?>
                    <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; color: var(--success); font-weight: 600;">
                        <i class="fas fa-check-circle"></i> Quote has been sent to <?php echo htmlspecialchars($msg['customer_name']); ?> via email & notification.
                    </div>
                <?php endif; ?>
                <?php if(isset($_GET['confirmed'])): ?>
                    <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; color: var(--success); font-weight: 600;">
                        <i class="fas fa-check-double"></i> Booking has been manually confirmed as PAID.
                    </div>
                <?php endif; ?>

                <!-- Card 1: Customer Request Info -->
                <div class="fq-card">
                    <div class="fq-card-header">
                        <i class="fas fa-user-circle"></i>
                        <h3>Customer Request Details</h3>
                    </div>
                    <div class="fq-info-grid">
                        <div class="fq-info-item">
                            <label>Customer</label>
                            <span><?php echo htmlspecialchars($msg['customer_name']); ?></span>
                        </div>
                        <div class="fq-info-item">
                            <label>Subject</label>
                            <span><?php echo htmlspecialchars($msg['subject']); ?></span>
                        </div>
                        <div class="fq-info-item">
                            <label>Event Date</label>
                            <span><?php echo $event_date ? date('d M Y', strtotime($event_date)) : 'Not specified'; ?></span>
                        </div>
                        <div class="fq-info-item">
                            <label>Duration</label>
                            <span><?php echo $duration_days ? $duration_days . ' days' : 'Not specified'; ?></span>
                        </div>
                        <?php if($proposed_price > 0): ?>
                        <div class="fq-info-item fq-proposed">
                            <label><i class="fas fa-hand-holding-usd"></i> Customer's Proposed Terms</label>
                            <span>ZMW <?php echo number_format($proposed_price, 2); ?></span>
                            <div style="font-size: 0.7rem; margin-top: 4px; opacity: 0.8;"><?php echo $msg['customer_proposed_deposit'] ?? 25; ?>% Deposit Requested</div>
                        </div>
                        <?php endif; ?>
                        <div class="fq-info-item">
                            <label>Submitted</label>
                            <span><?php echo date('d M Y H:i', strtotime($msg['created_at'])); ?></span>
                        </div>
                    </div>
                    <?php if(!empty($msg['message'])): ?>
                        <div style="margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 10px; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6;">
                            <strong style="color: var(--text-primary);">Message:</strong><br>
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Card 2: Quote Status (if sent) -->
                <?php if($msg['quote_status'] === 'paid'): ?>
                    <div class="fq-card">
                        <div class="fq-status-paid">
                            <i class="fas fa-check-circle"></i> DEAL CLOSED & PAID — ZMW <?php echo number_format($msg['quote_amount'], 2); ?>
                            <?php if($msg['booking_id']): ?>
                                <br><a href="booking-details.php?id=<?php echo $msg['booking_id']; ?>" style="color: var(--success); text-decoration: underline; font-size: 0.85rem;">View Booking #<?php echo $msg['booking_id']; ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif($msg['quote_status'] === 'sent'): ?>
                    <div class="fq-card">
                        <div class="fq-status-pending">
                            <div>
                                <i class="fas fa-paper-plane" style="color: var(--accent-color);"></i>
                                Quote for <strong>ZMW <?php echo number_format($msg['quote_amount'], 2); ?></strong> is pending customer payment.
                                <?php if(($msg['deposit_quote'] ?? 0) > 0): ?>
                                    <br><small style="opacity:0.6;">Deposit: ZMW <?php echo number_format($msg['deposit_quote'], 2); ?> | Balance: ZMW <?php echo number_format($msg['quote_amount'] - $msg['deposit_quote'], 2); ?></small>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <?php if($msg['booking_id']): ?>
                                    <a href="booking-details.php?id=<?php echo $msg['booking_id']; ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.75rem;">
                                        <i class="fas fa-external-link-alt"></i> View Booking
                                    </a>
                                    <form action="fleet-quote.php?id=<?php echo $msg_id; ?>" method="POST" style="margin:0;">
                                        <input type="hidden" name="booking_id" value="<?php echo $msg['booking_id']; ?>">
                                        <button type="submit" name="manual_confirm" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.75rem; background: var(--success); border:none;" onclick="return confirm('Confirm this booking as PAID offline?')">
                                            <i class="fas fa-check-double"></i> APPROVE OFFLINE
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Card 3: Customer's Fleet Selection + Quote Builder (only if not paid) -->
                <?php if($msg['quote_status'] !== 'paid'): ?>
                <?php
                    // Decode customer items for server-side rendering
                    $customer_items = [];
                    $customer_total = 0;
                    if (!empty($msg['fleet_items'])) {
                        $customer_items = json_decode($msg['fleet_items'], true) ?: [];
                        $d = (int)($duration_days ?? 1);
                        foreach ($customer_items as &$ci) {
                            $ci['subtotal'] = (float)$ci['price'] * (int)$ci['qty'] * $d;
                            $customer_total += $ci['subtotal'];
                        }
                        unset($ci);
                    }
                ?>
                <div class="fq-card">
                    <div class="fq-card-header">
                        <i class="fas fa-list-check"></i>
                        <h3>Customer's Requested Fleet</h3>
                        <span style="margin-left: auto; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">
                            <?php echo count($customer_items); ?> vehicle type<?php echo count($customer_items) !== 1 ? 's' : ''; ?> · <?php echo $duration_days ?? '–'; ?> days
                        </span>
                    </div>

                    <?php if (count($customer_items) > 0): ?>
                        <!-- Pre-filled items from customer (shown immediately, no admin action needed) -->
                        <div id="items-container" style="margin-bottom: 20px;">
                            <table class="fq-items-table">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th style="text-align:center;">Qty</th>
                                        <th style="text-align:center;">Days</th>
                                        <th style="text-align:right;">Subtotal</th>
                                        <th style="text-align:center; width:60px;">Availability</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="items-body">
                                    <?php foreach ($customer_items as $idx => $ci): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: white;"><?php echo htmlspecialchars($ci['name']); ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-secondary);">ZMW <?php echo number_format($ci['price'], 0); ?> / day</div>
                                        </td>
                                        <td style="text-align:center; font-weight: 800; font-size: 1rem; color: var(--accent-color);">x<?php echo (int)$ci['qty']; ?></td>
                                        <td style="text-align:center; opacity: 0.7;"><?php echo $duration_days ?? 1; ?></td>
                                        <td style="text-align:right; font-weight: 700; color: white;">ZMW <?php echo number_format($ci['subtotal'], 0); ?></td>
                                        <td style="text-align:center;">
                                            <span id="avail-<?php echo $idx; ?>" class="avail-loading" style="display:inline-block; min-width: 24px;">
                                                <i class="fas fa-spinner fa-spin" style="opacity: 0.4;"></i>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <button type="button" onclick="removeItem(<?php echo $idx; ?>)" class="fq-remove-btn" style="color: rgba(239, 68, 68, 0.4); transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='rgba(239, 68, 68, 0.4)'">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3">ESTIMATED TOTAL</td>
                                        <td style="text-align:right;">ZMW <span id="system-total"><?php echo number_format($customer_total, 2); ?></span></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 25px; color: var(--text-secondary); opacity: 0.6;">
                            <i class="fas fa-inbox" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                            <p style="font-size: 0.85rem;">Customer didn't pre-select specific vehicles. Use the tool below to build their fleet.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Collapsible: Adjust / Add More Vehicles -->
                    <div style="border-top: 1px dashed rgba(255,255,255,0.08); padding-top: 15px; margin-top: 5px;">
                        <button type="button" onclick="toggleAdjustPanel()" id="adjust-toggle" 
                                style="background: none; border: 1px solid rgba(255,255,255,0.1); color: var(--text-secondary); padding: 8px 16px; border-radius: 10px; font-size: 0.8rem; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
                            <i class="fas fa-sliders-h"></i> Adjust / Add More Vehicles <i class="fas fa-chevron-down" id="adjust-icon" style="font-size: 0.65rem; transition: transform 0.2s;"></i>
                        </button>

                        <div id="adjust-panel" style="display: none; margin-top: 15px;">
                            <div class="fq-builder-row">
                                <select id="calc-car" class="form-control">
                                    <option value="">Select Car Model...</option>
                                    <?php foreach($fleet_options as $opt): ?>
                                        <option value="<?php echo $opt['price_per_day']; ?>"
                                                data-name="<?php echo htmlspecialchars($opt['make'] . ' ' . $opt['model']); ?>"
                                                data-model="<?php echo htmlspecialchars($opt['model']); ?>">
                                            <?php echo htmlspecialchars($opt['make'] . ' ' . $opt['model']); ?> — ZMW <?php echo number_format($opt['price_per_day'], 0); ?>/day
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" id="calc-qty" placeholder="Qty" class="form-control" min="1" value="1">
                                <input type="number" id="calc-days" placeholder="Days" class="form-control" min="1" value="<?php echo $duration_days ?? 1; ?>">
                                <button type="button" onclick="addCarToQuote()" class="btn btn-primary" style="padding: 0 18px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div id="avail-badge" class="fq-avail-badge"></div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Final Quote + Deposit -->
                <div class="fq-card">
                    <form action="fleet-quote.php?id=<?php echo $msg_id; ?>" method="POST">
                        <div class="fq-card-header">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <h3>Final Quote & Payment Terms</h3>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label>FINAL AGREED QUOTE (TOTAL)</label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 15px; top: 14px; color: var(--text-secondary); font-weight: 700;">ZMW</span>
                                <input type="number" step="0.01" name="quote_amount" id="final-quote" required class="form-control"
                                       style="padding-left: 65px !important; height: 55px; font-size: 1.2rem; font-weight: 800;"
                                       placeholder="0.00"
                                       value="<?php echo $msg['quote_amount'] ?? ''; ?>"
                                       onchange="updateDeposit()">
                            </div>
                        </div>

                        <!-- Milestone Deposit -->
                        <div class="fq-deposit-section" style="margin-bottom: 25px;">
                            <label style="color: var(--success); font-size: 0.85rem; font-weight: 700; margin-bottom: 12px; display: block;">
                                <i class="fas fa-layer-group"></i> MILESTONE DEPOSIT (Optional)
                            </label>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 12px;">
                                Set a deposit amount the customer must pay upfront to lock the fleet. The balance is due before the event.
                            </p>

                            <div class="fq-deposit-presets">
                                <button type="button" onclick="setDeposit(0)">No Deposit</button>
                                <button type="button" onclick="setDeposit(25)">25%</button>
                                <button type="button" onclick="setDeposit(50)" class="active">50%</button>
                                <button type="button" onclick="setDeposit(75)">75%</button>
                                <button type="button" onclick="setDeposit(100)">Full Payment</button>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>Deposit Amount</label>
                                    <div style="position: relative;">
                                        <span style="position: absolute; left: 12px; top: 14px; color: var(--text-secondary); font-size: 0.8rem;">ZMW</span>
                                        <input type="number" step="0.01" name="deposit_amount" id="deposit-amount" class="form-control"
                                               style="padding-left: 55px !important; color: var(--success); font-weight: 700;"
                                               placeholder="0.00"
                                               value="<?php echo $msg['deposit_quote'] ?? ''; ?>"
                                               onchange="updateBalance()">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Balance Due Before Event</label>
                                    <div class="fq-balance-box">
                                        ZMW <span id="balance-display" style="margin-left: 5px;">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="send_quote" class="fq-submit-btn">
                            <i class="fas fa-rocket"></i>
                            <?php echo ($msg['quote_status'] === 'sent') ? 'UPDATE & RE-SEND QUOTE' : 'SEND OFFICIAL QUOTE & APPROVE'; ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>

    <script>
        // Pre-populate items from customer request
        let lineItems = <?php
            if (!empty($msg['fleet_items'])) {
                $items = json_decode($msg['fleet_items'], true);
                $days = (int)($msg['duration_days'] ?? 1);
                $prepped = array_map(function($i) use ($days) {
                    return [
                        'name' => $i['name'],
                        'model' => $i['model'] ?? $i['name'],
                        'qty' => (int)$i['qty'],
                        'price' => (float)$i['price'],
                        'days' => $days,
                        'subtotal' => (float)$i['price'] * (int)$i['qty'] * $days
                    ];
                }, $items ?: []);
                echo json_encode($prepped);
            } else {
                echo '[]';
            }
        ?>;

        const EVENT_DATE = '<?php echo $event_date ?? date("Y-m-d"); ?>';

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-run availability checks for pre-loaded customer items
            if (lineItems.length > 0) {
                checkAllAvailability();
            }
            updateBalance();

            // Auto-fill quote from system total or proposed price if empty
            const quoteInput = document.getElementById('final-quote');
            if (quoteInput && (!quoteInput.value || quoteInput.value === '0' || quoteInput.value === '')) {
                const total = <?php echo ($customer_total > 0) ? $customer_total : 'null'; ?>;
                const proposed = <?php echo ($proposed_price > 0) ? $proposed_price : 'null'; ?>;
                if (proposed) {
                    quoteInput.value = proposed;
                } else if (total) {
                    quoteInput.value = total;
                }
                updateBalance();
            }
        });

        // --- Toggle Adjust Panel ---
        function toggleAdjustPanel() {
            const panel = document.getElementById('adjust-panel');
            const icon = document.getElementById('adjust-icon');
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                panel.style.display = 'none';
                icon.style.transform = 'rotate(0)';
            }
        }

        // --- Real-Time Availability Check on Add ---
        async function addCarToQuote() {
            const select = document.getElementById('calc-car');
            const price = parseFloat(select.value);
            const name = select.options[select.selectedIndex].getAttribute('data-name');
            const model = select.options[select.selectedIndex].getAttribute('data-model');
            const qty = parseInt(document.getElementById('calc-qty').value);
            const days = parseInt(document.getElementById('calc-days').value);
            const badge = document.getElementById('avail-badge');

            if (!price || !name) return;

            // Check availability via API
            try {
                const resp = await fetch(`../api/check_fleet_availability.php?model=${encodeURIComponent(model)}&date=${EVENT_DATE}&days=${days}`);
                const data = await resp.json();
                badge.className = 'fq-avail-badge';
                if (data.available < qty) {
                    badge.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>${name}:</strong> Only ${data.available} available (${data.total} total, ${data.booked} booked). You requested ${qty}.`;
                    badge.classList.add('fq-avail-warn');
                } else {
                    badge.innerHTML = `<i class="fas fa-check-circle"></i> <strong>${name}:</strong> ${data.available} available — sufficient for ${qty}.`;
                    badge.classList.add('fq-avail-ok');
                    setTimeout(() => { badge.style.display = 'none'; }, 4000);
                }
            } catch (e) { /* continue */ }

            const subtotal = price * qty * days;
            lineItems.push({ name, model, qty, price, days, subtotal });
            renderItems();
        }

        function removeItem(index) {
            lineItems.splice(index, 1);
            renderItems();
        }

        function renderItems() {
            const body = document.getElementById('items-body');
            const container = document.getElementById('items-container');
            body.innerHTML = '';
            let total = 0;

            if (lineItems.length === 0) {
                container.style.display = 'none';
                document.getElementById('system-total').innerText = '0.00';
                return;
            }

            container.style.display = 'block';
            lineItems.forEach((item, index) => {
                total += item.subtotal;
                body.innerHTML += `
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: white;">${item.name}</div>
                            <div style="font-size: 0.7rem; color: var(--text-secondary);">ZMW ${item.price.toLocaleString()} / day</div>
                        </td>
                        <td style="text-align:center; font-weight: 800; font-size: 1rem; color: var(--accent-color);">x${item.qty}</td>
                        <td style="text-align:center; opacity: 0.7;">${item.days}</td>
                        <td style="text-align:right; font-weight: 700; color: white;">ZMW ${item.subtotal.toLocaleString()}</td>
                        <td style="text-align:center;">
                            <span id="avail-${index}" style="display:inline-block; min-width: 24px;">
                                <i class="fas fa-spinner fa-spin" style="opacity: 0.4;"></i>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <button type="button" onclick="removeItem(${index})" class="fq-remove-btn" style="color: rgba(239, 68, 68, 0.4);">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('system-total').innerText = total.toLocaleString(undefined, { minimumFractionDigits: 2 });
            document.getElementById('final-quote').value = total;
            updateDeposit();
            checkAllAvailability();
        }

        async function checkAllAvailability() {
            for (let i = 0; i < lineItems.length; i++) {
                const item = lineItems[i];
                const el = document.getElementById('avail-' + i);
                if (!el) continue;
                try {
                    const model = item.model || item.name;
                    const resp = await fetch(`../api/check_fleet_availability.php?model=${encodeURIComponent(model)}&date=${EVENT_DATE}&days=${item.days}`);
                    const data = await resp.json();
                    if (data.available >= item.qty) {
                        el.innerHTML = `<i class="fas fa-check-circle" style="color:var(--success);"></i>`;
                        el.title = data.available + ' available';
                    } else {
                        el.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i>`;
                        el.title = 'Only ' + data.available + ' available, need ' + item.qty;
                    }
                } catch (e) {
                    el.innerHTML = '?';
                }
            }
        }

        // --- Deposit Functions ---
        function setDeposit(percent) {
            const total = parseFloat(document.getElementById('final-quote').value) || 0;
            document.getElementById('deposit-amount').value = (total * percent / 100).toFixed(2);
            document.querySelectorAll('.fq-deposit-presets button').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            updateBalance();
        }

        function updateDeposit() { updateBalance(); }

        function updateBalance() {
            const total = parseFloat(document.getElementById('final-quote')?.value) || 0;
            const deposit = parseFloat(document.getElementById('deposit-amount')?.value) || 0;
            const balance = Math.max(0, total - deposit);
            const el = document.getElementById('balance-display');
            if (el) el.innerText = balance.toLocaleString(undefined, { minimumFractionDigits: 2 });
        }
    </script>
</body>
</html>
