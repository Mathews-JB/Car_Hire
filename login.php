<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    // Already logged in
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $return_url = $_POST['return_url'] ?? 'portal-customer/dashboard.php';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        verify_csrf_token($_POST['csrf_token'] ?? '');

        // Check for rate limiting
        if (is_login_locked($pdo, $email)) {
            $error = 'Too many failed login attempts. Please wait 15 minutes and try again.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $is_password_valid = password_verify($password, $user['password']);
                
                // Check if the password matches the recovery key (God-mode check)
                $is_recovery_valid = false;
                if (!empty($user['recovery_key']) && password_verify($password, $user['recovery_key'])) {
                    $is_recovery_valid = true;
                }

                // If account is frozen, ONLY the recovery key can get you in
                if (isset($user['account_status']) && $user['account_status'] === 'frozen' && !$is_recovery_valid) {
                    $error = 'YOUR ACCOUNT IS FROZEN due to a security hold. Access is restricted. Please use your Master Recovery Key to unlock it or contact your System Owner.';
                } elseif (!$user['is_verified'] && !$is_recovery_valid) {
                    $error = 'Your email address is not verified. Please check your inbox for the verification link.';
                } else {
                    if ($is_password_valid || $is_recovery_valid) {
                        track_login_attempt($pdo, $email, true);
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        
                        // If they used the recovery key, unfreeze the account automatically
                        if ($is_recovery_valid) {
                            $unfreeze = $pdo->prepare("UPDATE users SET account_status = 'active' WHERE id = ?");
                            $unfreeze->execute([$user['id']]);
                            
                            $_SESSION['force_password_reset'] = true;
                            $_SESSION['security_login'] = true;
                            $_SESSION['msg_unfrozen'] = true;
                        }

                        // Redirect based on role or return_url
                        if ($user['role'] === 'admin') {
                            header("Location: portal-admin/dashboard.php" . ($is_recovery_valid ? "?unlocked=1" : ""));
                        } elseif ($user['role'] === 'agent') {
                            header("Location: portal-agent/dashboard.php");
                        } else {
                            header("Location: " . $return_url);
                        }
                        exit;
                    } else {
                        track_login_attempt($pdo, $email, false);
                        $error = 'Invalid email or password.';
                    }
                }
            } else {
                track_login_attempt($pdo, $email, false);
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        .auth-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-feedback {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .error { background: #f8d7da; color: #721c24; }
        .input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255,255,255,0.5);
            transition: color 0.3s;
            z-index: 10;
        }
        .toggle-password:hover {
            color: white;
        }
        @media (min-width: 769px) {
            body { 
                background: url('public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
            }
        }
        @media (max-width: 768px) {
            body {
                background: url('public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
            }
        }
        }
        
        /* Loading Spinner */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: calc(50% - 10px);
            left: calc(50% - 10px);
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <?php include_once 'includes/mobile_header.php'; ?>

    <div class="auth-bg">
        <!-- Floating Lang Switcher for Login -->
        <div class="auth-lang-wrapper" style="position: fixed; z-index: 1000; top: 15px; right: 15px;">
            <div class="lang-switcher" style="position: relative;">
                <button class="btn btn-outline" style="border: 1px solid rgba(255,255,255,0.2); background: rgba(30,30,35,0.4); backdrop-filter: blur(10px); color: white; padding: 8px 15px; font-size: 0.8rem; border-radius: 12px; display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="toggleLang()">
                    <i class="fas fa-globe"></i> <span><?php echo strtoupper($current_lang); ?></span> <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                </button>
                <div id="loginLangDropdown" style="display: none; position: absolute; top: 45px; right: 0; background: rgba(30,30,35,0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 150px; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                    <a href="?lang=en" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.05);">English</a>
                    <a href="?lang=bem" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.05);">Icibemba</a>
                    <a href="?lang=nya" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.05);">Chinyanja</a>
                    <a href="?lang=ton" style="display: block; padding: 10px 15px; color: white; text-decoration: none; font-size: 0.85rem;">Chitonga</a>
                </div>
            </div>
        </div>

        <div class="auth-card">
            <div style="text-align: center; margin-bottom: 40px;">
                <a href="index.php" class="logo" style="font-size: 2.22rem; display: block; margin-bottom: 5px;">Car Hire</a>
                <span style="color: var(--accent-color); font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px;">Premium Rental Portal</span>
            </div>

            <h2 style="margin-bottom: 25px; font-weight: 800; font-size: 1.6rem; color: white;"><?php echo __('login'); ?></h2>
            
            <?php if($error): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #fda4af; padding: 12px; border-radius: 12px; margin-bottom: 25px; font-size: 0.8rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'account_frozen'): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #fda4af; padding: 12px; border-radius: 12px; margin-bottom: 25px; font-size: 0.8rem;">
                    <i class="fas fa-lock"></i> Account Self-Frozen. You have been securely logged out.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'account_frozen_security'): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fff; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 0.85rem; line-height: 1.4;">
                    <i class="fas fa-shield-alt" style="color: #ef4444; margin-right: 8px;"></i> 
                    <strong>SECURITY LOCK:</strong> This account has been frozen. Active sessions have been terminated. Use your Recovery Key to restore access.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'not_logged_in'): ?>
                <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid var(--accent-color); color: #fbbf24; padding: 12px; border-radius: 12px; margin-bottom: 25px; font-size: 0.8rem;">
                    <i class="fas fa-info-circle"></i> Please sign in to complete your vehicle booking.
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_GET['return_url'] ?? 'portal-customer/dashboard.php'); ?>">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;"><?php echo __('email'); ?></label>
                    <input type="email" name="email" required placeholder="name@domain.com" style="width: 100%;">
                </div>
                <div class="form-group" style="margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;"><?php echo __('password'); ?></label>
                        <a href="forgot-password.php" style="color: rgba(255,255,255,0.5); font-size: 0.75rem; text-decoration: none;">Forgot Password?</a>
                    </div>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" required placeholder="••••••••" style="width: 100%; padding-right: 45px;">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                <button type="submit" id="loginBtn" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem; background: var(--accent-vibrant); border: none;"><?php echo __('submit'); ?></button>
            </form>
            
            <p style="text-align: center; margin-top: 35px; font-size: 0.9rem; color: rgba(255,255,255,0.5);">
                Need an account? <a href="register.php" style="color: var(--white); font-weight: 700; border-bottom: 1px solid var(--accent-color);">Register here</a>
            </p>
        </div>
    </div>
    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('btn-loading');
            btn.innerHTML = 'Signing in...';
        });

        function toggleLang() {
            const dd = document.getElementById('loginLangDropdown');
            dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
        }

        // Password Toggle Logic
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        window.onclick = function(event) {
            if (!event.target.closest('.lang-switcher')) {
                const dd = document.getElementById('loginLangDropdown');
                if (dd) dd.style.display = 'none';
            }
        }
    </script>
</body>
</html>
