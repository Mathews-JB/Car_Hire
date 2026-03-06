<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    
    $name = $_POST['name'];
    $phone = trim($_POST['phone']);
    
    // Handle Profile Image Upload
    if (!empty($_FILES['profile_image']['name'])) {
        $profile_path = uploadImage($_FILES['profile_image'], 'profiles');
        if ($profile_path) {
            $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?")->execute([$profile_path, $user_id]);
        }
    }
    
    // Handle Cover Image Upload
    if (!empty($_FILES['cover_image']['name'])) {
        $cover_path = uploadImage($_FILES['cover_image'], 'covers');
        if ($cover_path) {
            $pdo->prepare("UPDATE users SET cover_photo = ? WHERE id = ?")->execute([$cover_path, $user_id]);
        }
    }

    // Handle Password Update (if new_password and confirm_password are provided)
    if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    session_regenerate_id(true); // Regenerate session ID on password change
                    $success = "Password updated successfully!";
                } else {
                    $error = "Failed to update password.";
                }
            } else {
                $error = "New password must be at least 6 characters long.";
            }
        } else {
            $error = "New password and confirm password do not match.";
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    if ($stmt->execute([$name, $phone, $user_id])) {
        session_regenerate_id(true);
        $_SESSION['user_name'] = $name;
        $success = "Profile updated successfully!";
    } else {
        $error = "Failed to update profile.";
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Placeholder images
$profile_img = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4b5563&color=fff&size=200';
$cover_img = !empty($user['cover_photo']) ? '../' . $user['cover_photo'] : '../public/images/hero-bg.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="profile-container">
                
                <?php if($success): ?>
                    <div class="form-feedback success" style="margin-bottom: 25px; padding: 15px; background: rgba(16,185,129,0.1); border: 1px solid var(--success); border-radius: 12px; color: var(--success); text-align:center;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-header">
                    <div class="cover-photo-wrapper">
                        <img src="<?php echo $cover_img; ?>" class="cover-photo" id="coverPreview">
                        <label class="cover-upload-btn">
                            <i class="fas fa-camera"></i> Change Cover
                            <input type="file" name="cover_image" form="profileForm" style="display:none;" onchange="previewImage(this, 'coverPreview')">
                        </label>
                    </div>
                    
                    <div class="profile-info-bar">
                        <div class="profile-avatar-wrapper">
                            <img src="<?php echo $profile_img; ?>" class="profile-avatar-img" id="avatarPreview">
                            <label class="avatar-upload-overlay">
                                <i class="fas fa-camera fa-2x" style="color:white;"></i>
                                <input type="file" name="profile_image" form="profileForm" style="display:none;" onchange="previewImage(this, 'avatarPreview')">
                            </label>
                        </div>
                        
                        <div class="profile-text-info">
                            <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p><i class="fas fa-user-shield"></i> System Administrator</p>
                        </div>
                    </div>

                    <div class="profile-nav">
                        <a href="#" class="profile-nav-item active">Admin Settings</a>
                        <a href="dashboard.php" class="profile-nav-item">System Overview</a>
                        <a href="users.php" class="profile-nav-item">Manage Users</a>
                    </div>
                </div>

                <div class="profile-content-grid">
                    <div class="data-card">
                        <h3 style="margin-bottom: 25px; font-size: 1.2rem;">Account Details</h3>
                        <form action="" method="POST" id="profileForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity: 0.5; cursor: not-allowed;">
                            </div>

                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary" style="padding: 15px; width: 100%; border-radius: 12px; font-weight: 700;">Update Admin Profile</button>
                        </form>
                    </div>

                    <div class="profile-sidebar">
                        <div class="profile-stats-grid">
                            <div class="profile-stat-box">
                                <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
                                <div class="stat-details">
                                    <h4>Total Users</h4>
                                    <?php
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                                        $userCount = $stmt->fetchColumn();
                                    ?>
                                    <p class="summary-value"><?php echo $userCount; ?></p>
                                </div>
                            </div>

                            <div class="profile-stat-box">
                                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="stat-details">
                                    <h4>Active Trips</h4>
                                    <?php
                                        $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'hired'");
                                        $tripCount = $stmt->fetchColumn();
                                    ?>
                                    <p class="summary-value"><?php echo $tripCount; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="data-card" style="padding: 20px;">
                            <h4 style="margin-bottom: 12px; font-size: 0.75rem; text-transform: uppercase; color: rgba(255,255,255,0.5);">System Privileges</h4>
                            <div style="display: flex; align-items: center; gap: 12px; background: rgba(59,130,246,0.1); padding: 12px; border-radius: 12px; border: 1px solid rgba(59,130,246,0.2);">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; box-shadow: 0 0 10px #3b82f6;"></div>
                                <span style="font-size: 0.85rem; color: #3b82f6; font-weight: 700;">Full Access</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
