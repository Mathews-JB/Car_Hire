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

// Fetch Messages with User Details
$stmt = $pdo->query("SELECT m.*, u.name as customer_name, u.email as customer_email 
                     FROM support_messages m 
                     JOIN users u ON m.user_id = u.id 
                     ORDER BY m.created_at DESC");
$messages = $stmt->fetchAll();

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
    <?php include_once '../includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Support Inbox</h1>
                <p class="text-secondary">Manage customer inquiries and platform feedback.</p>
            </div>
            <div class="header-actions">
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
                                    <?php if(strpos($m['subject'], 'Multi-Car/Event') !== false): ?>
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
                                <?php if(strpos($active_msg['subject'], 'Multi-Car/Event') !== false): ?>
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

                        <!-- Reply Form -->
                        <?php if (isset($_GET['replied'])): ?>
                            <!-- Skip reply form if just replied, or keep it. Let's keep it. -->
                        <?php endif; ?>
                        
                        <div style="margin-top: 40px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 25px;">
                            <h4 style="margin-bottom: 20px; color: white;"><i class="fas fa-reply" style="color: var(--accent-color);"></i> Direct Response</h4>
                            <form action="support-inbox.php" method="POST">
                                <input type="hidden" name="msg_id" value="<?php echo $active_msg['id']; ?>">
                                <input type="hidden" name="customer_email" value="<?php echo $active_msg['customer_email']; ?>">
                                <input type="hidden" name="customer_name" value="<?php echo $active_msg['customer_name']; ?>">
                                <input type="hidden" name="subject" value="<?php echo $active_msg['subject']; ?>">
                                
                                <?php if(strpos($active_msg['subject'], 'Multi-Car/Event') !== false): ?>
                                    <textarea name="reply_message" required class="form-control"
                                              style="height: 140px; margin-bottom: 20px;" 
                                              placeholder="Type your response here...&#10;&#10;E.g., Hi <?php echo htmlspecialchars(explode(' ', trim($active_msg['customer_name'] ?? ''))[0] ?? 'Customer'); ?>, thank you for your event request. For this specific fleet, our customized quote is ZMW _____. Please let me know how you'd like to proceed..."></textarea>
                                <?php else: ?>
                                    <textarea name="reply_message" required class="form-control"
                                              style="height: 120px; margin-bottom: 20px;" 
                                              placeholder="Type your response here..."></textarea>
                                <?php endif; ?>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                    <p style="font-size: 0.75rem; opacity: 0.5; margin: 0; flex: 1; min-width: 200px;">This will send a branded HTML email to the customer's registered inbox.</p>
                                    <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 30px;">
                                        <?php echo (strpos($active_msg['subject'], 'Multi-Car/Event') !== false) ? 'Send Quote / Response' : 'Send Response'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; opacity: 0.2; text-align: center; padding: 40px;">
                            <i class="fas fa-envelope-open-text" style="font-size: 4rem; margin-bottom: 20px;"></i>
                            <h3>Select a message to read</h3>
                            <p>Customer inquiries from the contact form appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
    </main>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
