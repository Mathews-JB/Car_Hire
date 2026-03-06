<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// Fetch current user security status
$stmt = $pdo->prepare("SELECT recovery_key, account_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$me = $stmt->fetch();

// Handle Recovery Key Generation
if (isset($_POST['generate_key'])) {
    $raw_key = bin2hex(random_bytes(8)); // 16 character hex key
    $hashed_key = password_hash($raw_key, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET recovery_key = ? WHERE id = ?");
    if ($stmt->execute([$hashed_key, $user_id])) {
        $msg = "Your new Master Recovery Key is: <strong style='font-size: 1.5rem; color: #3b82f6; display:block; margin: 10px 0;'>$raw_key</strong><br>SAVE THIS NOW! It will not be shown again. You can use this instead of your password if you forget it.";
        
        // Send Notification
        createNotification($pdo, $user_id, "Security: Master Key Generated", "A new Master Recovery Key was generated for your admin account. If this wasn't you, freeze your account immediately.", "security", ['db', 'email']);
    }
}

// Handle Account Freeze
if (isset($_POST['freeze_account'])) {
    $stmt = $pdo->prepare("UPDATE users SET account_status = 'frozen' WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        session_destroy();
        header("Location: ../login.php?msg=account_frozen");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Security Center | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .security-card {
            background: rgba(30, 30, 35, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .emergency-btn {
            background: linear-gradient(135deg, #ef4444, #991b1b);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            border: 2px solid #ef4444;
            transition: 0.3s;
            display: block;
            width: 100%;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .emergency-btn:hover {
            transform: scale(0.98);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.4);
        }
        .key-display {
            background: rgba(0,0,0,0.3);
            padding: 20px;
            border-radius: 12px;
            border: 1px dashed #3b82f6;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Security Control Center</h1>
                    <p class="text-secondary">Manage master access and emergency lockdowns.</p>
                </div>
                <div class="header-actions">
                    <a href="notifications.php" class="btn btn-outline" style="position: relative; width: 45px; height: 45px; padding: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bell"></i>
                    </a>
                </div>
            </div>

            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if($msg): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($msg); ?>', 'success', 15000);
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

            <div style="max-width: 1000px; margin: 0 auto;">
                <div class="grid-2">
                    <!-- Master Recovery Section -->
                    <div class="security-card">
                        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <div style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 1.2rem;">
                                <i class="fas fa-key"></i>
                            </div>
                            <div>
                                <h2>Master Recovery Key</h2>
                                <p style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">God-mode code for account recovery.</p>
                            </div>
                        </div>
                        
                        <p style="font-size: 0.9rem; line-height: 1.6; color: rgba(255,255,255,0.7);">
                            Generate a unique recovery key that allows you to bypass your regular password. Keep this key offline or in a secure password manager.
                        </p>

                        <form method="POST" onsubmit="return confirm('Generating a new key will invalidate your old one. Continue?')">
                            <button type="submit" name="generate_key" class="btn btn-outline" style="width:100%; margin-top: 15px; border-color: #3b82f6; color: #3b82f6;">
                                Generate New Recovery Key
                            </button>
                        </form>
                    </div>

                    <!-- Emergency Lockdown -->
                    <div class="security-card" style="border-color: rgba(239, 68, 68, 0.3);">
                        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <div style="width: 40px; height: 40px; background: rgba(239, 68, 68, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.2rem;">
                                <i class="fas fa-shield-virus"></i>
                            </div>
                            <div>
                                <h2>Emergency Freeze</h2>
                                <p style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">Instantly disable compromised account.</p>
                            </div>
                        </div>

                        <p style="font-size: 0.9rem; line-height: 1.6; color: rgba(255,255,255,0.7);">
                            If you notice suspicious activity on your account, click below to **instantly freeze** it. You will be logged out and login will be blocked until manual verification.
                        </p>

                        <form method="POST" onsubmit="return confirm('DANGER: This will instantly log you out and lock your account. You will need a database intervention or a Master Key to recover. Continue?')">
                            <button type="submit" name="freeze_account" class="emergency-btn">
                                <i class="fas fa-lock"></i> Freeze My Account Now
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Audit Log Placeholder -->
                <div class="security-card">
                    <h3><i class="fas fa-history"></i> Recent Security Events</h3>
                    <div style="color: rgba(255,255,255,0.4); text-align: center; padding: 40px;">
                        <i class="fas fa-mask" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.2;"></i>
                        <p>No phishy things detected lately. Your account is secure.</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
