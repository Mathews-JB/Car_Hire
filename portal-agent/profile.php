<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
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
    <title>Agent Profile | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

    <div class="admin-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>

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
                            <p><i class="fas fa-id-badge"></i> Certified Fleet Agent</p>
                        </div>
                    </div>

                    <div class="profile-nav">
                        <a href="#" class="profile-nav-item active">Agent Profile</a>
                        <a href="dashboard.php" class="profile-nav-item">My Tasks</a>
                    </div>
                </div>

                <div class="profile-content-grid">
                    <div class="data-card">
                        <h3 style="margin-bottom: 25px; font-size: 1.2rem;">Personal Details</h3>
                        <form action="" method="POST" id="profileForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 10px; display: block;">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 12px; width: 100%;">
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 10px; display: block;">Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); color: rgba(255,255,255,0.4); padding: 12px; border-radius: 12px; width: 100%; cursor: not-allowed;">
                            </div>

                            <div class="form-group" style="margin-bottom: 30px;">
                                <label style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 10px; display: block;">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 12px; width: 100%;">
                            </div>

                            <button type="submit" class="btn btn-primary" style="padding: 15px; width: 100%; border-radius: 12px; font-weight: 700;">Save Agent Profile</button>
                        </form>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 30px;">
                        <div class="profile-stat-box">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-details">
                                <h4>Inspections Done</h4>
                                <p>24</p>
                            </div>
                        </div>

                        <div class="profile-stat-box">
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                            <div class="stat-details">
                                <h4>Agent Rating</h4>
                                <p>4.8</p>
                            </div>
                        </div>

                        <div class="data-card" style="padding: 25px;">
                            <h4 style="margin-bottom: 15px; font-size: 1rem; color: white;">Assigned Branch</h4>
                            <div style="display: flex; align-items: center; gap: 12px; background: rgba(16,185,129,0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(16,185,129,0.2);">
                                <i class="fas fa-building" style="color: #10b981;"></i>
                                <span style="font-size: 0.9rem; color: #10b981; font-weight: 600;">Lusaka Main Office</span>
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
