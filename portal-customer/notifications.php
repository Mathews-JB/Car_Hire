<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css?v=2.3">
    <style>
        body { background: url('../public/images/cars/camry.jpg') center/cover no-repeat fixed !important; }
        .notif-item {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            transition: all 0.3s ease;
        }
        .notif-item.unread {
            border-left: 4px solid var(--accent-vibrant);
            background: rgba(245, 158, 11, 0.05);
        }
        .notif-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .notif-info { flex: 1; }
        .notif-title { font-weight: 700; color: white; margin-bottom: 5px; }
        .notif-message { color: rgba(255,255,255,0.7); font-size: 0.9rem; line-height: 1.5; }
        .notif-time { font-size: 0.75rem; color: rgba(255,255,255,0.4); margin-top: 8px; }

        .type-info { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
        .type-success { color: #10b981; background: rgba(16, 185, 129, 0.1); }
        .type-warning { color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
        .type-danger { color: #ef4444; background: rgba(239, 68, 68, 0.1); }
    </style>
</head>
<body>
    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="browse-vehicles.php">Browse Fleet</a>
            <a href="my-bookings.php">My Bookings</a>
            <a href="support.php">Support</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="container" style="max-width: 800px;">
            <div style="margin-bottom: 30px;">
                <h1>Notifications</h1>
                <p style="opacity:0.6;">Stay updated with your account activity.</p>
            </div>

            <?php if (empty($notifications)): ?>
                <div style="text-align: center; padding: 60px; background: rgba(15, 23, 42, 0.4); border-radius: 24px; border: 1px dashed rgba(255,255,255,0.1);">
                    <i class="fas fa-bell-slash" style="font-size: 3rem; opacity: 0.1; margin-bottom: 15px; display: block;"></i>
                    <p style="opacity:0.5;">No notifications yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <div class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                        <div class="notif-icon type-<?php echo $n['type']; ?>">
                            <?php 
                                $icon = 'info-circle';
                                if($n['type'] == 'success') $icon = 'check-circle';
                                if($n['type'] == 'warning') $icon = 'exclamation-triangle';
                                if($n['type'] == 'danger') $icon = 'times-circle';
                                if($n['type'] == 'security') $icon = 'shield-alt';
                            ?>
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <div class="notif-info">
                            <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                            <div class="notif-message"><?php echo htmlspecialchars($n['message']); ?></div>
                            <div class="notif-time">
                                <i class="far fa-clock"></i> <?php echo date('M j, Y - H:i', strtotime($n['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
