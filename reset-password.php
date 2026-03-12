<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';

// Diagnostic: Log POST to hidden comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<!-- POST received: " . implode(', ', array_keys($_POST)) . " -->";
}

$message = '';
$status = '';
$valid_token = false;
$email = '';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($token) {
    // For debugging, get current times
    $db_time = $pdo->query("SELECT NOW()")->fetchColumn();
    $php_time = date('Y-m-d H:i:s');
    echo "<!-- Time Debug: DB=$db_time, PHP=$php_time -->";

    // Check if token exists (Relaxed time check for debugging)
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $valid_token = true;
        $email = $reset['email'];
    } else {
        $message = 'This reset link is invalid or has already been used.';
        $status = 'error';
    }
} else {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Diagnostic: Log all keys for troubleshooting
    $keys = implode(', ', array_keys($_POST));
    echo "<!-- DEBUG: POST keys received: $keys -->";

    if (isset($_POST['password']) || isset($_POST['update_password'])) {
        if (!$valid_token) {
            $message = 'Security session expired or invalid token. If you used an old link, please request a new one.';
            $status = 'error';
        } else {
            verify_csrf_token($_POST['csrf_token'] ?? '');
            
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (empty($password)) {
                $message = 'Please enter a new password.';
                $status = 'error';
            } elseif ($password !== $confirm) {
                $message = 'Passwords do not match.';
                $status = 'error';
            } elseif (($pwd_error = validate_password($password)) !== true) {
                // Pass directly to error message
                $message = $pwd_error;
                $status = 'error';
            } else {
                try {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, is_verified = 1, account_status = 'active' WHERE email = ?");
                    $stmt->execute([$hashed, $email]);

                    // Clean up
                    $stmt_del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt_del->execute([$email]);
                    
                    $message = 'Success! Your password has been updated. You will be redirected to the login page momentarily.';
                    $status = 'success';
                    $valid_token = false; // Prevent form showing again
                    
                    header("Refresh:3; url=login.php");
                } catch (PDOException $e) {
                    $message = 'Database error: ' . $e->getMessage();
                    $status = 'error';
                }
            }
        }
    } else {
        $message = 'Invalid form submission. Please try clicking the button again.';
        $status = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="public/css/theme.css?v=4.0">
    <script src="public/js/theme-switcher.js?v=4.0"></script>
    <style>
        html, body { 
            overflow: hidden !important;
            height: 100% !important;
            margin: 0;
            padding: 0;
        }
        .input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255,255,255,0.4);
            transition: color 0.3s;
            z-index: 10;
        }
        .toggle-password:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="auth-bg">
        <!-- Floating Theme Switcher for Reset -->
        <div style="position: fixed; z-index: 1000; top: 15px; right: 15px;">
            <?php include_once 'includes/theme_switcher.php'; ?>
        </div>
        <div class="auth-card">
            <div style="text-align: center; margin-bottom: 40px;">
                <a href="index.php" class="logo" style="font-size: 2.22rem; display: block; margin-bottom: 5px;">Car Hire</a>
                <span style="color: var(--accent-color); font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px;">Security Update</span>
            </div>

            <h2 style="margin-bottom: 15px; font-weight: 800; font-size: 1.6rem; color: white;">Set New Password</h2>
            
            <?php if($message): ?>
                <div style="background: <?php echo $status === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>; border: 1px solid <?php echo $status === 'success' ? 'var(--success)' : 'var(--danger)'; ?>; color: <?php echo $status === 'success' ? '#6ee7b7' : '#fda4af'; ?>; padding: 12px; border-radius: 12px; margin-bottom: 25px; font-size: 0.8rem;">
                    <i class="fas <?php echo $status === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $message; ?>
                    <?php if($status === 'success'): ?>
                        <br><br>Redirecting to login page...
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($valid_token && $status !== 'success'): ?>
                <!-- Debug: Token Valid for <?php echo htmlspecialchars($email); ?> -->
                <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 25px;">Create a strong password to protect your account for <strong><?php echo htmlspecialchars($email); ?></strong></p>
                <form id="resetForm" action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: block;">New Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" required placeholder="••••••••" style="width: 100%; padding-right: 45px;" autofocus>
                            <i class="fas fa-eye toggle-password" onclick="togglePass('password', this)"></i>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 30px;">
                        <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: block;">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••" style="width: 100%; padding-right: 45px;">
                            <i class="fas fa-eye toggle-password" onclick="togglePass('confirm_password', this)"></i>
                        </div>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem; background: var(--accent-vibrant); border: none; cursor: pointer;">Update Password</button>
                </form>
            <?php endif; ?>
            
            <p style="text-align: center; margin-top: 35px; font-size: 0.9rem; color: rgba(255,255,255,0.5);">
                Remembered? <a href="login.php" style="color: var(--white); font-weight: 700; border-bottom: 1px solid var(--accent-color);">Back to Login</a>
            </p>
        </div>
    </div>
    <script>
        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
