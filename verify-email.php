<?php
include_once 'includes/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($token) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, account_status = 'active' WHERE id = ?");
        if ($update->execute([$user['id']])) {
            $success = "Email verified successfully! You can now <a href='login.php' style='color: #6ee7b7; font-weight: bold;'>login</a>.";
        } else {
            $error = "Failed to verify email. Please contact support.";
        }
    } else {
        $error = "Invalid or expired verification token.";
    }
} else {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body { 
            background: url('public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="auth-card" style="max-width: 500px; padding: 40px; text-align: center;">
        <div style="margin-bottom: 25px;">
            <a href="index.php" class="logo" style="font-size: 2.22rem;">Car Hire</a>
        </div>

        <?php if($success): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: #6ee7b7; padding: 20px; border-radius: 12px;">
                <h2 style="margin-bottom: 10px;">Verification Successful!</h2>
                <p><?php echo $success; ?></p>
            </div>
        <?php else: ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: #fda4af; padding: 20px; border-radius: 12px;">
                <h2 style="margin-bottom: 10px;">Verification Failed</h2>
                <p><?php echo $error; ?></p>
                <a href="login.php" class="btn btn-outline" style="margin-top: 20px; display: inline-block;">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
