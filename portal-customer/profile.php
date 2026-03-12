<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $phone = trim($_POST['phone']);
        $license = trim($_POST['license_no']);
        
        // Handle Profile Image Upload
        if (!empty($_FILES['profile_image']['name'])) {
            $profile_path = uploadImage($_FILES['profile_image'], 'profiles');
            if ($profile_path) {
                // Remove old image if exists
                if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])) {
                    unlink('../' . $user['profile_picture']);
                }
                $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?")->execute([$profile_path, $user_id]);
            }
        }
        
        // Handle Cover Image Upload
        if (!empty($_FILES['cover_image']['name'])) {
            $cover_path = uploadImage($_FILES['cover_image'], 'covers');
            if ($cover_path) {
                // Remove old image if exists
                if (!empty($user['cover_photo']) && file_exists('../' . $user['cover_photo'])) {
                    unlink('../' . $user['cover_photo']);
                }
                $pdo->prepare("UPDATE users SET cover_photo = ? WHERE id = ?")->execute([$cover_path, $user_id]);
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, license_no = ? WHERE id = ?");
        if ($stmt->execute([$name, $phone, $license, $user_id])) {
            session_regenerate_id(true);
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile.";
        }
    } 
    elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();

        if (password_verify($current, $user_data['password'])) {
            if ($new === $confirm) {
                if (strlen($new) >= 6) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
                    session_regenerate_id(true);
                    $success = "Password changed successfully!";
                } else {
                    $error = "New password must be at least 6 characters.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User session invalid. Please log in again.");
}

// Placeholder images if not set
$profile_img = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=4b5563&color=fff&size=200';
$cover_img = !empty($user['cover_photo']) ? '../' . $user['cover_photo'] : '../public/images/hero-bg.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        body { 
            background: transparent !important;
        }

        .profile-content-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
            align-items: flex-start;
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 0 !important;
                margin-top: 0 !important;
            }
            .profile-header {
                border-radius: 0 !important; /* Edge to edge */
            }
            .cover-photo {
                height: 180px !important; /* More car visibility */
                object-fit: cover !important;
                width: 100% !important;
            }
            .profile-info-bar {
                flex-direction: column !important;
                padding: 0 15px 20px !important;
                margin-top: -65px !important; /* Deeper overlap for larger avatar */
                gap: 8px !important;
                text-align: center !important;
            }
            .profile-avatar-wrapper {
                width: 130px !important; /* Bigger profile pic */
                height: 130px !important;
                margin: 0 auto !important;
                position: relative !important;
                border-radius: 50% !important;
                overflow: hidden !important;
                border: 5px solid #0f172a !important; /* Thick border for premium look */
                background: #1e293b !important;
                box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
            }
            .profile-avatar-img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                display: block !important;
            }
            .profile-text-info h2 {
                font-size: 1.2rem !important;
                margin-bottom: 5px !important;
            }
            .profile-text-info p {
                font-size: 0.8rem !important;
                opacity: 0.7 !important;
            }
            .loyalty-badge {
                font-size: 0.6rem !important;
                padding: 2px 8px !important;
            }
            .profile-nav {
                padding: 8px !important;
                display: flex !important;
                justify-content: center !important;
                gap: 5px !important;
                background: rgba(255,255,255,0.03) !important;
                border-radius: 8px !important;
            }
            .profile-nav-item {
                font-size: 0.7rem !important;
                padding: 10px 14px !important;
                border-radius: 8px !important;
                background: #1e293b !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                color: #ffffff !important;
                cursor: pointer;
                text-decoration: none !important;
                display: inline-block;
            }
            .profile-nav-item.active {
                background: var(--accent-vibrant) !important;
                color: white !important;
                border-color: transparent !important;
            }
            .profile-content-grid {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
            .profile-stat-box {
                padding: 10px !important;
                gap: 10px !important;
            }
            .stat-icon {
                width: 32px !important;
                height: 32px !important;
                font-size: 0.9rem !important;
            }
            .stat-details p {
                font-size: 1.1rem !important;
            }
            .hub-user-name {
                display: none !important;
            }
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
            <a href="profile.php" class="active"><?php echo __('profile'); ?></a>
        </div>
        <div style="margin-left:auto; margin-right:20px; display:flex; align-items:center; gap:10px;">
            <span class="loyalty-badge tier-<?php echo strtolower($user['membership_tier']); ?>">
                <i class="fas fa-crown"></i> <?php echo $user['membership_tier']; ?>
            </span>
        </div>
        <div class="hub-user">
            <?php 
                $display_name = $user['name'] ?? ($_SESSION['user_name'] ?? 'User');
                $first_name = explode(' ', $display_name)[0];
            ?>
            <span class="hub-user-name"><?php echo htmlspecialchars($first_name); ?></span>
            <!-- Theme Switcher -->
            <?php include_once '../includes/theme_switcher.php'; ?>

            <div class="hub-avatar">
                <?php if(!empty($user['profile_picture'])): ?>
                    <img src="../<?php echo $user['profile_picture']; ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                    <?php echo htmlspecialchars(!empty($display_name) ? strtoupper($display_name[0]) : 'U'); ?>
                <?php endif; ?>
            </div>
            <a href="../logout.php" style="color: var(--danger); margin-left: 10px; font-size: 0.85rem;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="profile-container">
            
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
                        <div style="display:flex; align-items:center; gap:15px; flex-wrap: wrap;">
                            <h2 style="margin:0;"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <span class="loyalty-badge tier-<?php echo strtolower($user['membership_tier']); ?>" style="padding: 5px 15px; font-size: 0.75rem;">
                                <?php echo $user['membership_tier']; ?> Member
                            </span>
                            <span class="status-badge status-<?php echo $user['verification_status']; ?>" style="border-radius: 50px; font-size: 0.75rem; padding: 4px 12px; font-weight: 700;">
                                <i class="fas <?php echo $user['verification_status'] === 'approved' ? 'fa-check-circle' : 'fa-user-clock'; ?>"></i>
                                VERIFIED: <?php echo strtoupper($user['verification_status']); ?>
                            </span>
                        </div>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <div class="profile-nav">
                    <button type="button" class="profile-nav-item active" onclick="switchTab('profile')">Account Settings</button>
                    <a href="my-bookings.php" class="profile-nav-item"><?php echo __('my_bookings'); ?></a>
                    <button type="button" class="profile-nav-item" onclick="switchTab('security')">Security</button>
                </div>
            </div>

            <!-- Tab 1: Account Settings -->
            <div class="profile-content-grid" id="profileTab">
                <!-- Left Column: Primary Forms & Actions -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div class="data-card" style="padding: 25px;">
                        <h3 style="margin-bottom: 20px; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-user-edit" style="color: var(--accent-color);"></i>
                            Personal Information
                        </h3>
                        <form action="" method="POST" id="profileForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; display: block;"><?php echo __('full_name'); ?></label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="form-control" style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff; padding: 10px 12px; border-radius: 10px; width: 100%;">
                                </div>
                                <div class="form-group">
                                    <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; display: block;"><?php echo __('email'); ?></label>
                                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled class="form-control" style="background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); color: rgba(255, 255, 255, 0.6); padding: 10px 12px; border-radius: 10px; width: 100%; cursor: not-allowed;">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                                <div class="form-group">
                                    <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; display: block;">Phone Number</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>" placeholder="0970000000" class="form-control" style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff; padding: 10px 12px; border-radius: 10px; width: 100%;">
                                </div>
                                <div class="form-group">
                                    <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 8px; display: block;">Driver's License</label>
                                    <input type="text" name="license_no" value="<?php echo htmlspecialchars($user['license_no'] ?: ''); ?>" placeholder="ZMK12345678" class="form-control" style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff; padding: 10px 12px; border-radius: 10px; width: 100%;">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="padding: 15px; width: 100%; border-radius: 10px; font-weight: 700; background: var(--accent-vibrant); border: none;">
                                <i class="fas fa-save" style="margin-right: 8px;"></i> Save Profile Changes
                            </button>
                        </form>
                    </div>

                    <div class="data-card logout-card" style="padding: 20px; border: 1px solid rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05);">
                        <a href="../logout.php" class="btn btn-outline" style="width: 100%; border-color: var(--danger); color: var(--danger); font-weight: 700; gap: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Sign Out of Account
                        </a>
                    </div>

                    <div class="data-card" style="padding: 25px;">
                        <h4 style="margin-bottom: 15px; font-size: 1rem; color: white;">Account Status</h4>
                        <?php if ($user['verification_status'] === 'approved'): ?>
                            <div style="display: flex; align-items: center; gap: 12px; background: rgba(16,185,129,0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(16,185,129,0.2);">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; box-shadow: 0 0 10px #10b981;"></div>
                                <span style="font-size: 0.9rem; color: #10b981; font-weight: 600;">Full Verified Customer</span>
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(245, 158, 11, 0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.2);">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></div>
                                    <span style="font-size: 0.9rem; color: #f59e0b; font-weight: 600;">Verification Required</span>
                                </div>
                                <a href="verify-profile.php" class="btn btn-outline" style="width: 100%; border-color: #f59e0b; color: #f59e0b; font-size: 0.8rem; text-align: center; text-decoration: none; display: block;">Complete Verification</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Stats & Perks -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <div class="profile-stat-box">
                        <div class="stat-icon"><i class="fas fa-car"></i></div>
                        <div class="stat-details">
                            <h4>Total Rentals</h4>
                            <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
                                $stmt->execute([$user_id]);
                                $count = $stmt->fetchColumn();
                            ?>
                            <p><?php echo $count; ?></p>
                        </div>
                    </div>

                    <div class="profile-stat-box">
                        <div class="stat-icon" style="background: var(--accent-color); color: white;"><i class="fas fa-gem"></i></div>
                        <div class="stat-details">
                            <h4>Loyalty Points</h4>
                            <p><?php echo number_format($user['loyalty_points']); ?></p>
                            <small style="opacity:0.6;">Redeemable soon</small>
                        </div>
                    </div>

                    <div class="profile-stat-box">
                        <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                        <div class="stat-details">
                            <h4>Member Since</h4>
                            <p><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="data-card" style="padding: 25px;">
                        <h4 style="margin-bottom: 15px; font-size: 1rem; color: white;">Membership Perks</h4>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 12px; opacity: <?php echo ($user['membership_tier'] === 'Bronze') ? '1' : '0.4'; ?>;">
                                <i class="fas fa-check-circle" style="color: #cd7f32; font-size: 1.1rem;"></i>
                                <span style="font-size: 0.9rem;">Bronze: Standard Rates</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; opacity: <?php echo ($user['membership_tier'] === 'Silver') ? '1' : '0.4'; ?>;">
                                <i class="fas fa-check-circle" style="color: #c0c0c0; font-size: 1.1rem;"></i>
                                <span style="font-size: 0.9rem;">Silver: 5% Automatic Discount</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; opacity: <?php echo ($user['membership_tier'] === 'Gold') ? '1' : '0.4'; ?>;">
                                <i class="fas fa-check-circle" style="color: #ffd700; font-size: 1.1rem;"></i>
                                <span style="font-size: 0.9rem;">Gold: 10% Discount + Priority Pickup</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Security Settings -->
            <div class="profile-content-grid" id="securityTab" style="display: none;">
                <!-- Left Column: Password Form -->
                <div class="data-card" style="padding: 30px;">
                    <h3 style="margin-bottom: 25px; font-size: 1.25rem; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-lock" style="color: var(--accent-color);"></i>
                        Update Security Credentials
                    </h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 30px;">Ensure your account is using a unique, complex password to stay protected.</p>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 10px; display: block;">Old Password</label>
                            <div class="input-wrapper" style="position: relative;">
                                <input type="password" name="current_password" id="current_password" required class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 12px; width: 100%; padding-right: 45px;">
                                <i class="fas fa-eye toggle-password" onclick="togglePass('current_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: rgba(255,255,255,0.4);"></i>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div class="form-group">
                                <label style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 10px; display: block;">New Password</label>
                                <div class="input-wrapper" style="position: relative;">
                                    <input type="password" name="new_password" id="new_password" required minlength="6" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 12px; width: 100%; padding-right: 45px;">
                                    <i class="fas fa-eye toggle-password" onclick="togglePass('new_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: rgba(255,255,255,0.4);"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 10px; display: block;">Confirm Password</label>
                                <div class="input-wrapper" style="position: relative;">
                                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 12px; width: 100%; padding-right: 45px;">
                                    <i class="fas fa-eye toggle-password" onclick="togglePass('confirm_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: rgba(255,255,255,0.4);"></i>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="padding: 15px; width: 100%; border-radius: 12px; font-weight: 700; background: var(--accent-vibrant); border: none;">
                            <i class="fas fa-shield-alt" style="margin-right: 8px;"></i> Commit Security Update
                        </button>
                    </form>
                </div>

                <!-- Right Column: Security Info -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <div class="data-card" style="padding: 25px; border: 1px solid rgba(59, 130, 246, 0.2);">
                        <h4 style="color: white; margin-bottom: 15px; font-size: 1rem;">Protection Tips</h4>
                        <ul style="color: rgba(255,255,255,0.6); font-size: 0.85rem; list-style: none; padding: 0;">
                            <li style="margin-bottom: 12px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Minimum 8 high-entropy chars</li>
                            <li style="margin-bottom: 12px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Include symbols and numerals</li>
                            <li style="margin-bottom: 12px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Non-reusable across platforms</li>
                        </ul>
                    </div>

                    <div class="data-card" style="padding: 25px; border: 1px solid rgba(245, 158, 11, 0.2);">
                        <h4 style="color: white; margin-bottom: 10px; font-size: 1rem;">Vault Recovery</h4>
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.8rem; line-height: 1.6;">Identity verification for password recovery is sent exclusively to: <br><strong style="color: white;"><?php echo $user['email']; ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
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

        function switchTab(tab) {
            const profileTab = document.getElementById('profileTab');
            const securityTab = document.getElementById('securityTab');
            const navItems = document.querySelectorAll('.profile-nav-item');
            
            navItems.forEach(item => item.classList.remove('active'));
            
            if (tab === 'profile') {
                profileTab.style.display = 'grid';
                securityTab.style.display = 'none';
                event.target.classList.add('active');
            } else if (tab === 'security') {
                profileTab.style.display = 'none';
                securityTab.style.display = 'grid';
                event.target.classList.add('active');
            }
        }
    </script>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
