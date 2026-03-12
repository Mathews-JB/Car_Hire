
<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle Notification Broadcast
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $type = $_POST['type'];

    if (empty($title) || empty($message)) {
        $error = "Title and message are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO notifications (title, message, type) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $message, $type])) {
            $success = "Notification broadcasted successfully!";
        } else {
            $error = "Failed to broadcast notification.";
        }
    }
}

// Handle Notification Deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Notification removed.";
    }
}

$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Center | Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .compose-card {
            padding: 30px;
            margin-bottom: 40px;
        }

        .notify-card { 
            padding: 24px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: start; 
            border-left: 5px solid #cbd5e1; 
            transition: all 0.3s ease;
        }
        .notify-card:hover { transform: translateX(5px); background: rgba(255,255,255,0.02); }

        .type-fleet { border-left-color: #3b82f6; }
        .type-promo { border-left-color: #f59e0b; }
        .type-system { border-left-color: #10b981; }
        
        .notify-type-badge { 
            font-size: 0.65rem; 
            text-transform: uppercase; 
            font-weight: 800; 
            padding: 4px 10px; 
            border-radius: 6px; 
            display: inline-block; 
            margin-bottom: 12px; 
            letter-spacing: 0.5px;
        }
        .type-fleet .notify-type-badge { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.2); }
        .type-promo .notify-type-badge { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
        .type-system .notify-type-badge { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }

        @media (max-width: 768px) {
            .compose-card { padding: 20px; }
            .notify-card { flex-direction: column; gap: 15px; }
            .delete-icon { width: 100% !important; height: 44px !important; }
        }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header" style="margin-bottom: 40px;">
                <div>
                    <h1>Notification Center</h1>
                    <p class="text-secondary">Send platform-wide alerts and updates to users.</p>
                </div>
            </div>

            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if($success): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($success); ?>', 'success');
                    });
                </script>
            <?php endif; ?>
            
            <?php if($error): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($error); ?>', 'error');
                    });
                </script>
            <?php endif; ?>

            <div class="data-card compose-card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <i class="fas fa-bullhorn" style="color: #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 8px;"></i>
                    <h3 style="color: white; font-weight: 700; margin: 0; font-size: 1.2rem;">Compose Broadcast</h3>
                </div>
                
                <form action="broadcast-notifications.php" method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Notification Title</label>
                            <input type="text" name="title" placeholder="e.g. System Maintenance" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="type" required class="form-control">
                                <option value="fleet">Fleet Update</option>
                                <option value="promo">Promotion / Discount</option>
                                <option value="system">System Announcement</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Message Content</label>
                        <textarea name="message" placeholder="Enter your message here..." required class="form-control" style="height: 100px;"></textarea>
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">
                            <i class="fas fa-paper-plane"></i> Send Broadcast
                        </button>
                    </div>
                </form>
            </div>

            <h3 style="margin-bottom: 25px; color: white; border-left: 4px solid #3b82f6; padding-left: 15px; font-weight: 800; font-size: 1rem;">RECENT BROADCASTS</h3>
            
            <div class="notify-list">
                <?php foreach ($notifications as $n): ?>
                    <div class="data-card notify-card type-<?php echo $n['type']; ?>">
                        <div style="flex: 1;">
                            <div class="notify-type-badge"><?php echo $n['type']; ?></div>
                            <h4 style="color: white; margin-bottom: 8px; font-size: 1rem;"><?php echo htmlspecialchars($n['title']); ?></h4>
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; line-height: 1.5;"><?php echo htmlspecialchars($n['message']); ?></p>
                            
                            <div style="margin-top: 15px; display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: rgba(255,255,255,0.3);">
                                <i class="far fa-clock"></i> Sent <?php echo date('d M, Y', strtotime($n['created_at'])); ?>
                            </div>
                        </div>
                        <a href="broadcast-notifications.php?delete=<?php echo $n['id']; ?>" 
                           onclick="return confirm('Delete this notification?')"
                           class="btn btn-outline btn-sm text-danger"
                           style="border-color: rgba(239, 68, 68, 0.2); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; padding: 0;"
                           title="Remove Notification">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

