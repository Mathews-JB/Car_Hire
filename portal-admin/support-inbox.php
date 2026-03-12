<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle Status Updates
if (isset($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE support_messages SET status = 'read' WHERE id = ?");
    $stmt->execute([$_GET['mark_read']]);
    header("Location: support-inbox.php?id=" . $_GET['mark_read']);
    exit;
}

// Handle Admin Replies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $msg_id = $_POST['msg_id'];
    $reply_text = $_POST['reply_message'];
    $customer_email = $_POST['customer_email'];
    $customer_name = $_POST['customer_name'];
    $original_subject = $_POST['subject'];

    // Update status in DB
    $stmt = $pdo->prepare("UPDATE support_messages SET status = 'replied' WHERE id = ?");
    $stmt->execute([$msg_id]);

    // Send Branded Email to Customer
    include_once '../includes/mailer.php';
    $email_subject = "Re: " . $original_subject;
    sendSupportEmail($customer_name, $customer_email, $email_subject, $reply_text, 'to_customer');

    header("Location: support-inbox.php?id=$msg_id&replied=1");
    exit;
}

    // Handle Admin Quoting for Events
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_quote'])) {
        $msg_id = $_POST['msg_id'];
        $quote_amount = $_POST['quote_amount'];
        $deposit_amount = isset($_POST['deposit_amount']) ? (float)$_POST['deposit_amount'] : 0;
        $customer_email = $_POST['customer_email'];
        $customer_name = $_POST['customer_name'];
        $original_subject = $_POST['subject'];
    
        // 1. Get User ID and Fleet Vehicle
        $stmt_m = $pdo->prepare("SELECT user_id, booking_id FROM support_messages WHERE id = ?");
        $stmt_m->execute([$msg_id]);
        $msg_data = $stmt_m->fetch();
        $user_id = $msg_data['user_id'];
        $existing_booking_id = $msg_data['booking_id'];
    
        $stmt_v = $pdo->prepare("SELECT id FROM vehicles WHERE model = 'Multi-Car Fleet' LIMIT 1");
        $stmt_v->execute();
        $fleet_vehicle_id = $stmt_v->fetchColumn() ?: 0;
    
        // 2. Create or Update Booking (with deposit tracking)
        if ($existing_booking_id) {
            $stmt_b = $pdo->prepare("UPDATE bookings SET total_price = ?, deposit_amount = ?, payment_status = 'unpaid' WHERE id = ?");
            $stmt_b->execute([$quote_amount, $deposit_amount, $existing_booking_id]);
            $booking_id = $existing_booking_id;
        } else {
            $stmt_b = $pdo->prepare("INSERT INTO bookings (user_id, vehicle_id, pickup_location, dropoff_location, pickup_date, dropoff_date, total_price, deposit_amount, payment_status, status) 
                                     VALUES (?, ?, 'Office/Delivery', 'Office/Delivery', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), ?, ?, 'unpaid', 'pending')");
            $stmt_b->execute([$user_id, $fleet_vehicle_id, $quote_amount, $deposit_amount]);
            $booking_id = $pdo->lastInsertId();
        }
    
        // 3. Update message link, quote status, and deposit
        $stmt_u = $pdo->prepare("UPDATE support_messages SET quote_amount = ?, deposit_quote = ?, quote_status = 'sent', booking_id = ?, status = 'replied' WHERE id = ?");
        $stmt_u->execute([$quote_amount, $deposit_amount, $booking_id, $msg_id]);
    
        // 4. Send Branded Quote Email (with deposit info)
        include_once '../includes/mailer.php';
        $deposit_line = '';
        if ($deposit_amount > 0) {
            $balance = $quote_amount - $deposit_amount;
            $deposit_line = "\n\nPAYMENT PLAN:\n- Deposit to Lock Fleet: ZMW " . number_format($deposit_amount, 2) . "\n- Balance Due Before Event: ZMW " . number_format($balance, 2);
        }
        $quote_msg = "Hi $customer_name,\n\nOur logistics team has reviewed your fleet request regarding '$original_subject'. \n\nWe have generated a custom quote for your event:\nTOTAL AMOUNT: ZMW " . number_format($quote_amount, 2) . $deposit_line . "\n\nTo lock this fleet and confirm your reservation, please log into your portal and complete the payment. \n\nThank you for choosing Car Hire.";
        sendSupportEmail($customer_name, $customer_email, "Custom Fleet Quote: ZMW " . number_format($quote_amount, 2), $quote_msg, 'to_customer');
    
        // 5. Notification to user
        $notif_title = "New Fleet Quote Received";
        $deposit_note = ($deposit_amount > 0) ? " Pay a deposit of ZMW " . number_format($deposit_amount, 2) . " to lock the fleet." : "";
        $notif_msg = "We have sent you an official quote of ZMW " . number_format($quote_amount, 2) . " for your event." . $deposit_note . " Visit 'My Bookings' to pay and confirm.";
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")->execute([$user_id, $notif_title, $notif_msg]);
    
        header("Location: support-inbox.php?id=$msg_id&quoted=1");
        exit;
    }

    // Handle Manual Approval (Offline Payment)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_confirm'])) {
        $msg_id = $_POST['msg_id'];
        $booking_id = $_POST['booking_id'];
        
        $pdo->beginTransaction();
        try {
            // 1. Confirm Booking
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$booking_id]);

            // 2. Mark Quote as Paid
            $stmt = $pdo->prepare("UPDATE support_messages SET quote_status = 'paid' WHERE id = ?");
            $stmt->execute([$msg_id]);

            // 3. Notify user
            $stmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $user_id = $stmt->fetchColumn();

            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Booking Confirmed Manually', 'Your fleet request has been approved and confirmed by the admin.', 'success')")->execute([$user_id]);

            $pdo->commit();
            header("Location: support-inbox.php?id=$msg_id&confirmed=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error confirming: " . $e->getMessage());
        }
    }

// Fetch Messages with User Details
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT m.*, u.name as customer_name, u.email as customer_email 
          FROM support_messages m 
          JOIN users u ON m.user_id = u.id ";

if ($filter === 'events') {
    $query .= " WHERE m.subject LIKE '%Event%' ";
}

$query .= " ORDER BY m.created_at DESC";
$stmt = $pdo->query($query);
$messages = $stmt->fetchAll();

// Fetch Fleet Data for the Smart Calculator
$fleet_stmt = $pdo->query("SELECT make, model, price_per_day, COUNT(*) as qty FROM vehicles WHERE status != 'deleted' GROUP BY model ORDER BY make ASC");
$fleet_options = $fleet_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread
$stmt = $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'new'");
$unread_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Inbox | Staff Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .msg-grid { 
            display: grid; 
            grid-template-columns: 320px 1fr; 
            gap: 0; 
            background: rgba(30, 41, 59, 0.4); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            border-radius: 20px; 
            min-height: 700px; 
            overflow: hidden; 
            backdrop-filter: blur(12px);
        }
        
        @media (max-width: 992px) {
            .msg-grid { grid-template-columns: 1fr; min-height: auto; }
            .msg-list { border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.05); max-height: 350px; }
            .msg-view { min-height: 400px; padding: 25px !important; }
        }

        .msg-list { border-right: 1px solid rgba(255, 255, 255, 0.05); overflow-y: auto; max-height: 800px; background: rgba(0, 0, 0, 0.2); }
        .msg-view { padding: 40px; overflow-y: auto; background: rgba(0, 0, 0, 0.1); }
        
        .msg-item { padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.03); cursor: pointer; transition: 0.2s; position: relative; }
        .msg-item:hover { background: rgba(255, 255, 255, 0.03); }
        .msg-item.active { background: rgba(37, 99, 235, 0.1); border-left: 4px solid var(--accent-color); }
        .msg-item.status-new::after { content: ''; position: absolute; top: 22px; right: 20px; width: 8px; height: 8px; border-radius: 50%; background: var(--accent-color); box-shadow: 0 0 10px var(--accent-color); }
        
        .msg-item h4 { margin: 0 0 5px 0; font-size: 0.95rem; color: white; }
        .msg-item p { margin: 0; font-size: 0.8rem; opacity: 0.6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-item .time { font-size: 0.7rem; opacity: 0.4; margin-top: 8px; display: block; }
        
        .header-strip { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: rgba(0, 0, 0, 0.2); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        
        .message-content { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 25px; line-height: 1.7; margin-top: 25px; white-space: pre-wrap; color: rgba(255, 255, 255, 0.9); }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <?php include_once '../includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Support Inbox</h1>
                <p class="text-secondary">Manage customer inquiries and platform feedback.</p>
            </div>
            <div class="header-actions">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="support-inbox.php?filter=all" class="btn <?php echo ($filter === 'all') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.75rem; padding: 5px 12px; border-radius: 20px;">All Messages</a>
                    <a href="support-inbox.php?filter=events" class="btn <?php echo ($filter === 'events') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.75rem; padding: 5px 12px; border-radius: 20px; <?php echo ($filter === 'events') ? 'background: #2563eb; color: white;' : 'color: #60a5fa; border-color: rgba(59, 130, 246, 0.4);'; ?>">
                        <i class="fas fa-car-side"></i> Event Requests
                    </a>
                </div>
                <?php include_once '../includes/theme_switcher.php'; ?>
                <div class="status-pill status-pending" style="text-transform: none; padding: 10px 20px;">
                    <span style="font-weight: 800; margin-right: 5px;"><?php echo $unread_count; ?> NEW</span> INQUIRIES
                </div>
            </div>
        </div>

        <div class="msg-grid">
                <div class="msg-list">
                    <div class="header-strip">
                        <small style="text-transform: uppercase; letter-spacing: 1px; font-weight: 700; opacity: 0.6;">Conversations</small>
                        <i class="fas fa-filter" style="opacity: 0.4; cursor: pointer;"></i>
                    </div>
                    <?php if (empty($messages)): ?>
                        <div style="padding: 40px; text-align: center; opacity: 0.4;">No messages found.</div>
                    <?php else: ?>
                        <?php foreach($messages as $m): ?>
                            <div class="msg-item <?php echo $m['status'] === 'new' ? 'status-new' : ''; ?> <?php echo (isset($_GET['id']) && $_GET['id'] == $m['id']) ? 'active' : ''; ?>" 
                                 onclick="window.location.href='support-inbox.php?id=<?php echo $m['id']; ?>'">
                                <h4><?php echo htmlspecialchars($m['customer_name'] ?? 'Guest'); ?></h4>
                                <p style="margin-bottom: 2px;">
                                    <?php if(strpos($m['subject'], 'Event') !== false || strpos($m['subject'], 'Multi-Car') !== false): ?>
                                        <i class="fas fa-car-side" style="color: #3b82f6; font-size: 0.8rem;"></i> <strong>Event Fleet Request</strong>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($m['subject']); ?>
                                    <?php endif; ?>
                                </p>
                                <span class="time"><?php echo date('d M, H:i', strtotime($m['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="msg-view">
                    <?php 
                    $active_msg = null;
                    if (isset($_GET['id'])) {
                        foreach($messages as $m) if($m['id'] == $_GET['id']) $active_msg = $m;
                    }
                    ?>

                    <?php if ($active_msg): ?>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                            <div>
                                <?php if(strpos($active_msg['subject'], 'Event') !== false || strpos($active_msg['subject'], 'Multi-Car') !== false): ?>
                                    <span style="display: inline-block; padding: 4px 10px; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; border: 1px solid rgba(59, 130, 246, 0.4);">
                                        <i class="fas fa-car-side"></i> Event & Fleet Quote Request
                                    </span>
                                <?php endif; ?>
                                <h2 style="margin: 0; font-size: 1.4rem; color: white;"><?php echo htmlspecialchars($active_msg['subject']); ?></h2>
                                <p style="margin: 8px 0 0 0; opacity: 0.6; font-size: 0.9rem;">
                                    From: <strong style="color: var(--accent-color);"><?php echo htmlspecialchars($active_msg['customer_name']); ?></strong> 
                                    (<?php echo htmlspecialchars($active_msg['customer_email']); ?>)
                                </p>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <?php if($active_msg['status'] === 'new'): ?>
                                    <a href="support-inbox.php?mark_read=<?php echo $active_msg['id']; ?>" class="btn btn-outline btn-sm" style="border-color: var(--accent-color); color: var(--accent-color); height: 40px;"><i class="fas fa-check"></i> Read</a>
                                <?php endif; ?>
                                <a href="mailto:<?php echo $active_msg['customer_email']; ?>?subject=Re: <?php echo urlencode($active_msg['subject']); ?>" class="btn btn-primary btn-sm" style="height: 40px;"><i class="fas fa-envelope"></i> Reply</a>
                            </div>
                        </div>

                        <div class="message-content">
                            <?php echo htmlspecialchars($active_msg['message']); ?>
                        </div>

                        <?php if (isset($_GET['replied'])): ?>
                            <div class="status-pill status-confirmed" style="margin-top:20px; width: 100%; text-transform: none; justify-content: flex-start;">
                                <i class="fas fa-check-circle" style="margin-right: 10px;"></i> Reply sent successfully to customer.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['quoted'])): ?>
                            <div class="status-pill status-confirmed" style="margin-top:20px; width: 100%; text-transform: none; justify-content: flex-start; background: rgba(37, 99, 235, 0.1); border-color: #2563eb; color: #60a5fa;">
                                <i class="fas fa-file-invoice-dollar" style="margin-right: 10px;"></i> Official Quote sent successfully.
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['confirmed'])): ?>
                            <div class="status-pill status-confirmed" style="margin-top:20px; width: 100%; text-transform: none; justify-content: flex-start; background: rgba(16, 185, 129, 0.1); border-color: #10b981; color: #10b981;">
                                <i class="fas fa-check-double" style="margin-right: 10px;"></i> Fleet request manually confirmed and approved!
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 40px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 25px;">
                            <h4 style="margin-bottom: 20px; color: white;"><i class="fas fa-reply" style="color: var(--accent-color);"></i> Direct Response</h4>
                            <form action="support-inbox.php" method="POST">
                                <input type="hidden" name="msg_id" value="<?php echo $active_msg['id']; ?>">
                                <input type="hidden" name="customer_email" value="<?php echo $active_msg['customer_email']; ?>">
                                <input type="hidden" name="customer_name" value="<?php echo $active_msg['customer_name']; ?>">
                                <input type="hidden" name="subject" value="<?php echo $active_msg['subject']; ?>">
                                
                                <textarea name="reply_message" required class="form-control"
                                          style="height: 120px; margin-bottom: 20px;" 
                                          placeholder="Type your response here..."></textarea>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                    <p style="font-size: 0.75rem; opacity: 0.5; margin: 0; flex: 1; min-width: 200px;">This will send a branded HTML email to the customer's registered inbox.</p>
                                    <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 30px;">
                                         Send Response
                                    </button>
                                </div>
                            </form>
                        </div>

                                <!-- Fleet Event Quick Summary & Link -->
                        <?php if(strpos($active_msg['subject'] ?? '', 'Event') !== false || strpos($active_msg['subject'] ?? '', 'Multi-Car') !== false): ?>
                            <div style="margin-top: 30px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 25px;">
                                <h4 style="color: var(--accent-color); margin-bottom: 15px;"><i class="fas fa-car-side"></i> Fleet Event Request</h4>

                                <?php if(($active_msg['quote_status'] ?? '') === 'paid'): ?>
                                    <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 18px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2); font-weight: 700; text-align: center;">
                                        <i class="fas fa-check-circle"></i> DEAL CLOSED & PAID — ZMW <?php echo number_format($active_msg['quote_amount'] ?? 0, 2); ?>
                                    </div>
                                <?php elseif(($active_msg['quote_status'] ?? '') === 'sent'): ?>
                                    <div style="background: rgba(255,255,255,0.03); color: var(--text-secondary); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); font-size: 0.9rem; margin-bottom: 15px;">
                                        <i class="fas fa-paper-plane" style="color: var(--accent-color);"></i>
                                        Quote for <strong>ZMW <?php echo number_format($active_msg['quote_amount'] ?? 0, 2); ?></strong> sent — awaiting payment.
                                        <?php if(($active_msg['deposit_quote'] ?? 0) > 0): ?>
                                            <br><small style="opacity:0.6;">Deposit: ZMW <?php echo number_format($active_msg['deposit_quote'], 2); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if(($active_msg['customer_proposed_price'] ?? 0) > 0): ?>
                                    <div style="font-size: 0.85rem; margin-bottom: 12px; color: var(--text-secondary);">
                                        <i class="fas fa-hand-holding-usd" style="color: #f59e0b;"></i>
                                        Customer proposed: <strong style="color: #f59e0b;">ZMW <?php echo number_format($active_msg['customer_proposed_price'], 2); ?></strong>
                                    </div>
                                <?php endif; ?>

                                <a href="fleet-quote.php?id=<?php echo $active_msg['id']; ?>" class="btn btn-primary" style="width: 100%; height: 52px; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 700;">
                                    <i class="fas fa-calculator"></i>
                                    <?php echo (($active_msg['quote_status'] ?? '') === 'paid') ? 'View Quote Details' : 'Open Fleet Quote Builder'; ?>
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; opacity: 0.2; text-align: center; padding: 40px;">
                            <?php if ($filter === 'events'): ?>
                                <i class="fas fa-car-side" style="font-size: 4rem; margin-bottom: 20px; color: #3b82f6;"></i>
                                <h3 style="color: #60a5fa;">Select an Event Request to Approve</h3>
                                <p>Choose a fleet inquiry from the list on the left to review and send a quote.</p>
                            <?php else: ?>
                                <i class="fas fa-envelope-open-text" style="font-size: 4rem; margin-bottom: 20px;"></i>
                                <h3>Select a message to read</h3>
                                <p>Customer inquiries from the contact form appear here.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
    </main>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
