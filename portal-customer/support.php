<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';
include_once '../includes/mailer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? 'No Subject';
    $message = $_POST['message'] ?? '';
    $user_name = $_SESSION['user_name'] ?? 'Customer';
    $user_email = $_SESSION['user_email'] ?? 'unknown@user.com'; 

    if (!empty($message)) {
        try {
            // Save to Database
            $stmt = $pdo->prepare("INSERT INTO support_messages (user_id, subject, message, status) VALUES (?, ?, ?, 'new')");
            $stmt->execute([$_SESSION['user_id'], $subject, $message]);

            // Send Email to Admin
            sendSupportEmail($user_name, $user_email, $subject, $message, 'to_admin');
            
            // Send Confirmation to Customer
            sendSupportEmail($user_name, $user_email, "We've received your request: $subject", "Hi $user_name,\n\nThank you for reaching out. We have received your message regarding '$subject' and our team will get back to you shortly.\n\nYour message:\n$message", 'to_customer');

            $success = "Your message has been sent successfully. Our team will contact you shortly.";
        } catch (Exception $e) {
            // Fallback if DB or Mail fails
            $error = "Delivery failed. Please check your internet connection.";
        }
    } else {
        $error = "Please enter a message.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body { 
            background: transparent !important;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
                align-items: stretch !important;
            }
            .stat-card {
                padding: 10px 5px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                height: 90px !important; /* Fixed uniform height */
                margin: 0 !important;
                text-align: center !important;
            }
            .stat-card i {
                font-size: 1.1rem !important;
                margin-bottom: 4px !important;
            }
            .stat-card h3 {
                font-size: 0.85rem !important;
                margin: 0 !important;
                line-height: 1.1 !important;
            }
            .stat-card p {
                font-size: 0.65rem !important;
                margin: 2px 0 0 !important;
                opacity: 0.7 !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                width: 100%;
            }
        }
    </style>
</head>
<body class="stabilized-car-bg">

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <a href="browse-vehicles.php"><?php echo __('browse_fleet'); ?></a>
            <a href="my-bookings.php"><?php echo __('my_bookings'); ?></a>
            <a href="support.php" class="active"><?php echo __('support'); ?></a>
            <a href="profile.php"><?php echo __('profile'); ?></a>
        </div>
        <div class="hub-user">
            <?php 
                $display_name = $_SESSION['user_name'] ?? 'User';
                $first_name = explode(' ', $display_name)[0];
                $initial = !empty($display_name) ? strtoupper($display_name[0]) : 'U';
            ?>
            <span class="hub-user-name"><?php echo htmlspecialchars($first_name); ?></span>
            <div class="hub-avatar"><?php echo htmlspecialchars($initial); ?></div>
            <a href="../logout.php" style="color: var(--danger); margin-left: 10px; font-size: 0.85rem;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="container">
            <div class="dashboard-header" style="margin-bottom: 40px;">
                <div>
                    <h1>How can we help?</h1>
                    <p style="color: rgba(255,255,255,0.6);">Get in touch with our team or browse support options.</p>
                </div>
            </div>

            <div class="stats-grid" style="margin-bottom: 50px;">
                <div class="stat-card">
                    <i class="fas fa-phone-alt" style="color: var(--primary-color);"></i>
                    <h3>Call Us</h3>
                    <p>+260 211 123 456</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-envelope" style="color: var(--accent-color);"></i>
                    <h3>Email</h3>
                    <p>support@CarHire.zm</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-map-marker-alt" style="color: var(--success);"></i>
                    <h3>Visit</h3>
                    <p>Lusaka, Great East Rd</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock" style="color: var(--secondary-color);"></i>
                    <h3>24/7</h3>
                    <p>Priority Support</p>
                </div>
            </div>

            <?php if($success): ?>
                <div class="form-feedback success" style="margin-bottom: 25px; padding: 15px; background: rgba(16,185,129,0.1); border: 1px solid var(--success); border-radius: 12px; color: var(--success); text-align:center;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="form-feedback error" style="margin-bottom: 25px; padding: 15px; background: rgba(239,68,68,0.1); border: 1px solid var(--danger); border-radius: 12px; color: var(--danger); text-align:center;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="data-card" style="max-width: 800px; margin: 0 auto;">
                <h3 style="margin-bottom: 25px;">Send a Message</h3>
                <form action="" method="POST">
                    <div class="responsive-form-grid" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label><?php echo __('full_name'); ?></label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" readonly class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('subject') ?? 'Subject'; ?></label>
                            <select name="subject" class="form-control premium-select">
                                <option>Booking Inquiry</option>
                                <option>Payment Issue</option>
                                <option>Vehicle Feedback</option>
                                <option>Technical Support</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Message</label>
                        <textarea name="message" required style="width: 100%; height: 150px; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;" placeholder="Tell us how we can assist you..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">Send Message</button>
                </form>
            </div>
            <style>
                .responsive-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                @media (max-width: 600px) {
                    .responsive-form-grid { grid-template-columns: 1fr; }
                    .dashboard-header h1 { font-size: 1.8rem !important; }
                }
            </style>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
