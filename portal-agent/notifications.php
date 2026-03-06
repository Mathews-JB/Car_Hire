<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['read_all'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: notifications.php");
    exit;
}

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Agent Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .notif-item {
            background: rgba(30, 30, 35, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            transition: 0.3s;
        }
        .notif-item.unread {
            border-left: 4px solid #3b82f6;
            background: rgba(59, 130, 246, 0.05);
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
        }
        .notif-info { flex: 1; }
        .notif-title { color: white; font-weight: 700; margin-bottom: 5px; }
        .notif-time { font-size: 0.75rem; color: rgba(255,255,255,0.4); }
        
        .type-info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .type-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .type-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .type-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .type-security { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
    </style>
</head>
<body>
    <div class="agent-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <div>
                    <h1>Notifications</h1>
                    <p class="text-secondary">Track important alerts and updates.</p>
                </div>
                <div class="header-actions">
                    <a href="notifications.php?read_all=1" class="btn btn-outline" style="font-size: 0.8rem;">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </a>
                </div>
            </div>

            <div style="max-width: 800px; margin: 30px auto;">
                <?php if (empty($notifications)): ?>
                    <div style="text-align: center; padding: 60px; color: rgba(255,255,255,0.3);">
                        <i class="fas fa-bell-slash" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.1;"></i>
                        <p>No notifications yet.</p>
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
                                <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($n['message']); ?>
                                </div>
                                <div class="notif-time">
                                    <i class="far fa-clock"></i> <?php echo date('M j, Y - H:i', strtotime($n['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
