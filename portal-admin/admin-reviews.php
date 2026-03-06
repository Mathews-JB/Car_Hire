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

// Handle Approval/Decline
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = $_POST['user_id'];
    $action = $_POST['action']; // 'approved' or 'declined'
    $admin_note = trim($_POST['admin_note'] ?? '');

    $stmt = $pdo->prepare("UPDATE users SET verification_status = ?, verification_note = ? WHERE id = ?");
    if ($stmt->execute([$action, $admin_note, $target_user_id])) {
        // Create a notification for the user (Internal system)
        $msg = ($action === 'approved') 
            ? "Congratulations! Your profile has been verified. You can now book vehicles." 
            : "Your verification request was declined. Reason: " . $admin_note;
        
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
            ->execute([$target_user_id, "Verification " . ucfirst($action), $msg, 'system']);
        
        $success = "User status updated to " . $action;

        // --- Send Verification Email + WhatsApp ---
        include_once '../includes/mailer.php';
        include_once '../includes/whatsapp.php';
        try {
            // Fetch user details
            $u_stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
            $u_stmt->execute([$target_user_id]);
            $user_info = $u_stmt->fetch();

            if ($user_info && !empty($user_info['email'])) {
                $mailer = new CarHireMailer();
                $subject = "Identity Verification Update";
                
                if ($action === 'approved') {
                    $email_body = "Dear <strong>" . htmlspecialchars($user_info['name']) . "</strong>,<br><br>Great news! We've successfully verified your identity documents. Your account is now fully activated and ready for use.<br><br>You can now browse our elite fleet and book your high-performance vehicle immediately.<br><br><a href='http://localhost/Car_Higher/portal-customer/browse-vehicles.php' style='display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: 700; margin-top: 10px;'>Browse Available Fleet</a><br><br>Happy Driving,<br><strong>Team Car Hire</strong>";
                } else {
                    $email_body = "Dear <strong>" . htmlspecialchars($user_info['name']) . "</strong>,<br><br>Thank you for submitting your identity documents. Our compliance team has reviewed your application and, unfortunately, we are unable to approve your verification at this time.<br><br><div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 15px 0;'><strong style='color: #991b1b; display: block; margin-bottom: 5px;'>Reason for Decision:</strong><p style='color: #b91c1c; margin: 0;'>" . htmlspecialchars($admin_note) . "</p></div><br>To continue with your registration and gain access to vehicle bookings, please log in to your dashboard and re-upload the necessary documents as indicated above.<br><br><div style='background: #f1f5f9; border-radius: 8px; padding: 20px; margin-top: 15px;'><h4 style='margin-top: 0; color: #1e293b; margin-bottom: 10px;'>Common Fixes:</h4><ul style='color: #475569; padding-left: 20px; margin: 0;'><li>Ensure images are clear and text is legible.</li><li>All edges of the document must be visible.</li><li>Check that your documents have not expired.</li><li>Avoid using flash to prevent glare on plastic cards.</li></ul></div><br><a href='http://localhost/Car_Higher/portal-customer/verify-profile.php' style='display: inline-block; padding: 12px 24px; background: #1e293b; color: white; text-decoration: none; border-radius: 6px; font-weight: 700; margin-top: 10px;'>Re-upload Documents</a><br><br>Regards,<br><strong>Compliance Hub</strong><br>Car Hire Zambia";
                }

                $mailer->send($user_info['email'], $subject, $email_body, null, 'Car Hire Compliance', true);
            }

            // WhatsApp notification
            if ($user_info && !empty($user_info['phone'])) {
                $wa = new WhatsAppService();
                $wa->sendVerificationUpdate(
                    $user_info['phone'],
                    $user_info['name'],
                    $action,
                    $admin_note
                );
            }

        } catch (Exception $em) {
            error_log("Verification notification failed: " . $em->getMessage());
        }
        // -------------------------------
    } else {
        $error = "Failed to update user status.";
    }
}

// Fetch Pending Users Only (Admins want declined/approved to disappear from this queue)
$stmt = $pdo->query("SELECT * FROM users WHERE verification_status = 'pending' ORDER BY id DESC");
$pending_users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Reviews | Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .review-card { 
            background: rgba(30, 41, 59, 0.4) !important; 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px); 
            border: 1px solid rgba(255, 255, 255, 0.05) !important; 
            border-radius: 20px; 
            margin-bottom: 25px; 
            overflow: hidden; 
        }
        .review-header { 
            padding: 20px; 
            background: rgba(0,0,0,0.2); 
            border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 15px;
        }
        .review-body { padding: 25px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .doc-preview { border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 15px; cursor: pointer; position: relative; background: rgba(0,0,0,0.2); transition: all 0.3s; }
        .doc-preview img { width: 100%; display: block; filter: brightness(0.8); }
        .doc-preview:hover { transform: translateY(-5px); border-color: var(--accent-color); }
        .doc-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255, 255, 255, 0.4); margin-bottom: 8px; font-weight: 700; }
        .info-value { color: white; font-weight: 600; margin-bottom: 5px; font-size: 0.95rem; }
        .info-label { color: rgba(255, 255, 255, 0.5); font-size: 0.8rem; }
        
        textarea { 
            background: rgba(15, 23, 42, 0.4) !important; 
            border: 1px solid rgba(255, 255, 255, 0.1) !important; 
            color: white !important; 
            border-radius: 12px; 
            padding: 12px; 
            outline: none; 
            transition: 0.3s;
        }

        @media (max-width: 768px) {
            .review-body { grid-template-columns: 1fr; gap: 25px; padding: 20px; }
            .review-header { flex-direction: column; align-items: flex-start; }
            .doc-grid { grid-template-columns: 1fr 1fr !important; }
        }

        /* Modal for Full Image View */
        #imgModal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        #imgModal img { max-width: 100%; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Verification Queue</h1>
                    <p class="text-secondary">Review and approve customer identity documents for compliance.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" onclick="window.location.reload()"><i class="fas fa-sync"></i> Refresh Queue</button>
                    <a href="users.php" class="btn btn-primary"><i class="fas fa-users"></i> User Directory</a>
                </div>
            </div>

            <?php if($success): ?>
                <div class="status-pill status-confirmed" style="width: 100%; margin-bottom: 20px; text-transform: none; justify-content: flex-start;">
                    <i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if(empty($pending_users)): ?>
                <div class="data-card" style="text-align: center; padding: 60px; background: rgba(30, 41, 59, 0.4); border: 1px dashed rgba(255,255,255,0.1); border-radius: 20px;">
                    <i class="fas fa-tasks" style="font-size: 3rem; color: rgba(255,255,255,0.1); margin-bottom: 20px;"></i>
                    <h3 style="color: white; opacity: 0.8;">Queue is Empty</h3>
                    <p style="color: rgba(255,255,255,0.5);">All verification requests have been processed.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_users as $u): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <strong style="font-size: 1rem; color: white;"><?php echo htmlspecialchars($u['name']); ?></strong> 
                                <span style="color: rgba(255,255,255,0.4); margin-left: 10px; font-size: 0.8rem;">ID: #<?php echo $u['id']; ?></span>
                                <span class="status-pill <?php echo ($u['verification_status'] == 'pending') ? 'status-pending' : 'status-cancelled'; ?>" style="margin-left: 10px;">
                                    <?php echo strtoupper($u['verification_status']); ?>
                                </span>
                            </div>
                            <div style="color: rgba(255,255,255,0.4); font-size: 0.85rem;">
                                Registered: <?php echo date('d M, Y', strtotime($u['created_at'])); ?>
                            </div>
                        </div>
                        <div class="review-body">
                            <!-- User Info -->
                            <div>
                                <div style="margin-bottom: 20px;">
                                    <div class="doc-label">Primary Contact</div>
                                    <div class="info-value"><?php echo htmlspecialchars($u['email']); ?></div>
                                    <div class="info-value"><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></div>
                                </div>
                                <div style="margin-bottom: 20px;">
                                    <div class="doc-label">NRC / License</div>
                                    <div class="info-label">NRC: <strong style="color: white;"><?php echo htmlspecialchars($u['id_number'] ?? 'N/A'); ?></strong></div>
                                    <div class="info-label">License: <strong style="color: white;"><?php echo htmlspecialchars($u['license_number'] ?? 'N/A'); ?></strong></div>
                                </div>
                                <div style="margin-bottom: 20px;">
                                    <div class="doc-label">Physical Address</div>
                                    <div style="font-size: 0.9rem; color: rgba(255,255,255,0.7); line-height: 1.5;"><?php echo htmlspecialchars($u['address'] ?? 'N/A'); ?></div>
                                </div>

                                <!-- OCR Insights -->
                                <?php if (!empty($u['ocr_detected_nrc']) || !empty($u['ocr_detected_license'])): ?>
                                <div style="margin-bottom: 20px; background: rgba(59, 130, 246, 0.1); border: 1px dashed rgba(59, 130, 246, 0.3); border-radius: 12px; padding: 15px;">
                                    <div class="doc-label" style="color: #60a5fa;"><i class="fas fa-robot"></i> OCR Intelligence</div>
                                    <?php if ($u['ocr_detected_nrc']): ?>
                                        <div style="font-size: 0.8rem; margin-bottom: 5px;">
                                            <span style="opacity: 0.6;">Detected NRC:</span> 
                                            <strong style="color: <?php echo ($u['ocr_detected_nrc'] === $u['id_number']) ? '#10b981' : '#f87171'; ?>;">
                                                <?php echo htmlspecialchars($u['ocr_detected_nrc']); ?>
                                            </strong>
                                            <?php if ($u['ocr_detected_nrc'] === $u['id_number']): ?>
                                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 0.7rem;"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($u['ocr_detected_license']): ?>
                                        <div style="font-size: 0.8rem;">
                                            <span style="opacity: 0.6;">Detected License:</span> 
                                            <strong style="color: <?php echo ($u['ocr_detected_license'] === $u['license_number']) ? '#10b981' : '#f87171'; ?>;">
                                                <?php echo htmlspecialchars($u['ocr_detected_license']); ?>
                                            </strong>
                                            <?php if ($u['ocr_detected_license'] === $u['license_number']): ?>
                                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 0.7rem;"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <form action="admin-reviews.php" method="POST" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-bottom: 8px; display: block;">Admin Note (Visible to user if declined)</label>
                                        <textarea name="admin_note" placeholder="Reason for decline or feedback..." style="width: 100%; height: 80px; font-size: 0.85rem;"></textarea>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" name="action" value="approved" class="btn btn-primary" style="flex: 1; background: #10b981; border: none; font-weight: 700;">Approve</button>
                                        <button type="submit" name="action" value="declined" class="btn btn-outline" style="flex: 1; border-color: rgba(239, 68, 68, 0.4); color: #f87171; font-weight: 700;">Decline</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Documents -->
                            <div class="doc-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px;">
                                <!-- Row 1 -->
                                <div>
                                    <div class="doc-label">Profile Selfie</div>
                                    <div class="doc-preview" onclick="showImg('../<?php echo $u['profile_image_path']; ?>')">
                                        <img src="../<?php echo $u['profile_image_path'] ?: 'public/images/cars/default.jpg'; ?>" onerror="this.src='../public/images/cars/default.jpg'">
                                    </div>
                                </div>
                                <div>
                                    <div class="doc-label">NRC (Front)</div>
                                    <div class="doc-preview" onclick="showImg('../<?php echo $u['nrc_image_path']; ?>')">
                                        <img src="../<?php echo $u['nrc_image_path'] ?: 'public/images/cars/default.jpg'; ?>" onerror="this.src='../public/images/cars/default.jpg'">
                                    </div>
                                </div>
                                <div>
                                    <div class="doc-label">NRC (Back)</div>
                                    <div class="doc-preview" onclick="showImg('../<?php echo $u['nrc_back_image_path']; ?>')">
                                        <img src="../<?php echo $u['nrc_back_image_path'] ?: 'public/images/cars/default.jpg'; ?>" onerror="this.src='../public/images/cars/default.jpg'">
                                    </div>
                                </div>
                                <div>
                                    <div class="doc-label">License (Front)</div>
                                    <div class="doc-preview" onclick="showImg('../<?php echo $u['license_image_path']; ?>')">
                                        <img src="../<?php echo $u['license_image_path'] ?: 'public/images/cars/default.jpg'; ?>" onerror="this.src='../public/images/cars/default.jpg'">
                                    </div>
                                </div>
                                <div>
                                    <div class="doc-label">License (Back)</div>
                                    <div class="doc-preview" onclick="showImg('../<?php echo $u['license_back_image_path']; ?>')">
                                        <img src="../<?php echo $u['license_back_image_path'] ?: 'public/images/cars/default.jpg'; ?>" onerror="this.src='../public/images/cars/default.jpg'">
                                    </div>
                                </div>
                                <div>
                                    <div class="doc-label">Utility Bill</div>
                                    <div class="doc-preview" onclick="showImg('../<?php echo $u['utility_bill_image_path']; ?>')">
                                        <img src="../<?php echo $u['utility_bill_image_path'] ?: 'public/images/cars/default.jpg'; ?>" onerror="this.src='../public/images/cars/default.jpg'">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal -->
    <div id="imgModal" onclick="this.style.display='none'">
        <img id="fullImg" src="">
    </div>

    <script>
        function showImg(src) {
            document.getElementById('fullImg').src = src;
            document.getElementById('imgModal').style.display = 'flex';
        }
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
