<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';
include_once 'includes/mailer.php';

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = ' Please enter your email address.';
        $status = 'error';
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)")->execute([$email, $token]);

            $reset_link = APP_URL . "reset-password.php?token=" . $token;
            
            $mailer = new CarHireMailer();
            $subject = "Reset Your Password - Car Hire";
            $email_body = "
                Hello " . $user['name'] . ",<br><br>
                We received a request to reset your Car Hire password. Click the button below to secure your account:<br><br>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_link}' style='background: #2563eb; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 700;'>Reset Password</a>
                </div>
                If you didn't request this, you can safely ignore this email. The link will expire in 24 hours.<br><br>
                Best regards,<br>
                The Car Hire Team
            ";

            if ($mailer->send($email, $subject, $email_body)) {
                $message = 'We have sent a password reset link to your email.';
                $status = 'success';
            } else {
                // Fallback for demo if mail server fails
                $message = 'Email could not be sent. <br><small style="color:rgba(255,255,255,0.5);">DEBUG: <a href="'.$reset_link.'" style="color:var(--accent-color);">Click here to reset (Demo Bypass)</a></small>';
                $status = 'success'; 
            }
        } else {
            // Don't reveal if email exists or not for security, but user-friendly:
            $message = 'If an account exists with that email, you will receive a reset link shortly.';
            $status = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Car Hire</title>
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
    </style>
</head>
<body>
    <?php include_once 'includes/mobile_header.php'; ?>

    <div class="auth-bg">
        <!-- Floating Theme Switcher for Recovery -->
        <div style="position: fixed; z-index: 1000; top: 15px; right: 15px;">
            <?php include_once 'includes/theme_switcher.php'; ?>
        </div>
        <div class="auth-card">
            <div style="text-align: center; margin-bottom: 40px;">
                <a href="index.php" class="logo" style="font-size: 2.22rem; display: block; margin-bottom: 5px;">Car Hire</a>
                <span style="color: var(--accent-color); font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px;">Account Recovery</span>
            </div>

            <h2 style="margin-bottom: 15px; font-weight: 800; font-size: 1.6rem; color: white;">Forgot Password?</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 25px;">Enter your email address and we'll send you a link to reset your password.</p>
            
            <?php if($message): ?>
                <div style="background: <?php echo $status === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>; border: 1px solid <?php echo $status === 'success' ? 'var(--success)' : 'var(--danger)'; ?>; color: <?php echo $status === 'success' ? '#6ee7b7' : '#fda4af'; ?>; padding: 12px; border-radius: 12px; margin-bottom: 25px; font-size: 0.8rem;">
                    <i class="fas <?php echo $status === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: block;">Email Address</label>
                    <input type="email" name="email" required placeholder="name@domain.com" style="width: 100%;" autofocus>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem; background: var(--accent-vibrant); border: none;">Send Reset Link</button>
            </form>
            
            <p style="text-align: center; margin-top: 35px; font-size: 0.9rem; color: rgba(255,255,255,0.5);">
                Remembered? <a href="login.php" style="color: var(--white); font-weight: 700; border-bottom: 1px solid var(--accent-color);">Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>
