<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Clear All
if (isset($_GET['clear_all'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: notifications.php");
    exit;
}

// Mark all as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Unread count for the bell in hub-bar
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css?v=2.6">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        body { background: transparent !important; }
        .notif-item {
            background: rgba(30,30,35, 0.4);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .notif-item:hover {
            transform: translateY(-4px);
            border-color: rgba(255,255,255,0.15);
            background: rgba(30, 30, 35, 0.6);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
        .notif-item.unread {
            border-left: 6px solid var(--accent-vibrant);
            background: rgba(245, 158, 11, 0.05);
        }
        .notif-icon {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            background: rgba(255,255,255,0.05);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .notif-info { flex: 1; }
        .notif-title { font-weight: 800; color: white; margin-bottom: 6px; font-size: 1.1rem; }
        .notif-message { color: rgba(255,255,255,0.73); font-size: 0.95rem; line-height: 1.6; }
        .notif-time { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 12px; font-weight: 500; display: flex; align-items: center; gap: 6px; }

        .type-info { color: #3b82f6; background: rgba(59, 130, 246, 0.15); }
        .type-success { color: #10b981; background: rgba(16, 185, 129, 0.15); }
        .type-warning { color: #f59e0b; background: rgba(245, 158, 11, 0.15); }
        .type-danger { color: #ef4444; background: rgba(239, 68, 68, 0.15); }
        .type-security { color: #8b5cf6; background: rgba(139, 92, 246, 0.15); }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: rgba(30, 30, 35, 0.4);
            border-radius: 32px;
            border: 1px dashed rgba(255,255,255,0.1);
            margin-top: 50px;
        }
    </style>
</head>
<body class="stabilized-car-bg">
    <?php include_once '../includes/mobile_header.php'; ?>

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <a href="browse-vehicles.php"><?php echo __('browse_fleet'); ?></a>
            <a href="my-bookings.php"><?php echo __('my_bookings'); ?></a>
            <a href="support.php"><?php echo __('support'); ?></a>
            <a href="profile.php"><?php echo __('profile'); ?></a>
        </div>
        <div class="hub-user">
            <!-- Theme Switcher -->
            <?php include_once '../includes/theme_switcher.php'; ?>
            
            <!-- Notifications Bell -->
            <a href="notifications.php" style="position: relative; margin-right: 15px; text-decoration: none; color: white; display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; background: rgba(255,255,255,0.1); border-radius: 50%;">
                <i class="fas fa-bell" style="font-size: 1.1rem; color: var(--accent-color);"></i>
            </a>

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
        <div class="container" style="max-width: 850px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 20px;">
                <div>
                    <h1 style="font-size: 2.2rem; margin-bottom: 5px;">Notifications</h1>
                    <p style="opacity:0.6; font-size: 1rem;">Stay updated with your account activity and platform news.</p>
                </div>
                <?php if (!empty($notifications)): ?>
                    <a href="?clear_all=1" class="btn btn-outline" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3); font-size: 0.8rem; padding: 8px 16px;" onclick="return confirm('Delete all notifications permanently?')">
                        <i class="fas fa-trash-alt"></i> Clear All
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.02); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                        <i class="fas fa-bell-slash" style="font-size: 3rem; opacity: 0.2;"></i>
                    </div>
                    <h2 style="margin-bottom: 10px; opacity: 0.9;">All Clear!</h2>
                    <p style="opacity:0.5; max-width: 300px; margin: 0 auto;">You don't have any notifications at the moment. We'll alert you when something important happens.</p>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 30px;">Back to Dashboard</a>
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
                                if($n['type'] == 'verify') $icon = 'user-check';
                            ?>
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <div class="notif-info">
                            <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                            <div class="notif-message"><?php echo htmlspecialchars($n['message']); ?></div>
                            <div class="notif-time">
                                <i class="far fa-clock"></i> <?php echo date('M j, Y - H:i', strtotime($n['created_at'])); ?> 
                                <?php if($n['is_read']): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; margin-left:10px; opacity:0.5; font-size:0.7rem;"><i class="fas fa-check-double"></i> Read</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <p style="text-align: center; opacity: 0.3; font-size: 0.8rem; margin-top: 40px; font-weight: 600;">Showing internal updates from the last 50 events.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
