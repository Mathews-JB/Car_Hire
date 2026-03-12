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

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['verification_status'] === 'approved') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $id_number = trim($_POST['id_number']);
    $license_number = trim($_POST['license_number']);
    $id_type = $_POST['id_type'] ?? 'NRC';
    $dob = $_POST['dob'] ?? null;
    $license_expiry = $_POST['license_expiry'] ?? null;
    $license_class = trim($_POST['license_class'] ?? '');
    $em_name = trim($_POST['emergency_contact_name'] ?? '');
    $em_phone = trim($_POST['emergency_contact_phone'] ?? '');

    // Handle File Uploads
    $upload_dir = '../uploads/verification/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $profile_img = $user['profile_image_path'];
    $nrc_img = $user['nrc_image_path'];
    $nrc_back = $user['nrc_back_image_path'];
    $license_img = $user['license_image_path'];
    $license_back = $user['license_back_image_path'];
    $utility_bill = $user['utility_bill_image_path'];

    try {
        $files_to_process = [
            'profile_img' => 'profile_',
            'nrc_img' => 'nrc_front_',
            'nrc_back_img' => 'nrc_back_',
            'license_img' => 'license_front_',
            'license_back_img' => 'license_back_',
            'utility_bill_img' => 'utility_'
        ];

        foreach ($files_to_process as $key => $prefix) {
            if (!empty($_FILES[$key]['name'])) {
                $filename = $prefix . $user_id . '_' . time() . '.' . pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES[$key]['tmp_name'], $upload_dir . $filename)) {
                    $path = 'uploads/verification/' . $filename;
                    if ($key === 'profile_img') $profile_img = $path;
                    if ($key === 'nrc_img') $nrc_img = $path;
                    if ($key === 'nrc_back_img') $nrc_back = $path;
                    if ($key === 'license_img') $license_img = $path;
                    if ($key === 'license_back_img') $license_back = $path;
                    if ($key === 'utility_bill_img') $utility_bill = $path;
                }
            }
        }

        // Update User
        $stmt = $pdo->prepare("UPDATE users SET 
            phone = ?, 
            address = ?, 
            id_number = ?, 
            license_number = ?, 
            id_type = ?,
            dob = ?,
            license_expiry = ?,
            license_class = ?,
            emergency_contact_name = ?,
            emergency_contact_phone = ?,
            profile_image_path = ?, 
            nrc_image_path = ?, 
            nrc_back_image_path = ?,
            license_image_path = ?,
            license_back_image_path = ?,
            utility_bill_image_path = ?,
            verification_status = 'pending'
            WHERE id = ?");
        
        $stmt->execute([
            $phone, $address, $id_number, $license_number,
            $id_type, $dob, $license_expiry, $license_class,
            $em_name, $em_phone,
            $profile_img, $nrc_img, $nrc_back,
            $license_img, $license_back, $utility_bill,
            $user_id
        ]);

        $success = "Verification request submitted! Your account will be reviewed and verified within 1 to 2 working days.";
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

    } catch (Exception $e) {
        $error = "Failed to upload documents: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Profile | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        html, body { 
            background: #080c17 !important; 
            color: #f8fafc !important; 
            overflow: hidden !important;
            height: 100% !important;
            margin: 0;
            padding: 0;
        }

        .portal-content {
            position: fixed;
            inset: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding-top: 80px;
            padding-bottom: 110px;
            overscroll-behavior: none;
        }

        .verify-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .upload-box { border: 2px dashed rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; text-align: center; background: rgba(255,255,255,0.02); transition: all 0.3s; cursor: pointer; }
        .upload-box:hover { border-color: #ff9d00; background: rgba(255,157,0, 0.05); }
        .upload-icon { font-size: 2rem; color: #ff9d00; margin-bottom: 10px; }
        .file-input { display: none; }
        .status-banner { padding: 20px; border-radius: 16px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; }
        .status-pending-banner { background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; }
        .status-declined-banner { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }


        /* ── OCR Styles ─────────────────────────────────────────────────── */
        .ocr-scan-btn {
            display: none;
            margin-top: 10px;
            width: 100%;
            padding: 8px 12px;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 0.5px;
        }
        .ocr-scan-btn:hover { opacity: 0.85; transform: translateY(-1px); }
        .ocr-scan-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .ocr-result-badge {
            display: none;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: left;
            line-height: 1.4;
        }
        .ocr-result-badge.success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
        }
        .ocr-result-badge.warning {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fcd34d;
        }
        .ocr-result-badge.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }
        .ocr-spinner { display: inline-block; animation: spin 1s linear infinite; margin-right: 5px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .ocr-banner {
            background: linear-gradient(135deg, rgba(124,58,237,0.15), rgba(79,70,229,0.1));
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: rgba(255,255,255,0.85);
            font-size: 0.9rem;
        }
        .ocr-banner i { font-size: 1.5rem; color: #a78bfa; flex-shrink: 0; }

        @media (max-width: 768px) {
            .verify-header h1 {
                font-size: 1.6rem !important;
                line-height: 1.2 !important;
            }
            .verify-header p {
                font-size: 0.9rem !important;
                padding: 0 20px;
            }
            .status-banner {
                padding: 15px !important;
                gap: 15px !important;
                flex-direction: column !important;
                text-align: center !important;
                border-radius: 12px !important;
            }
            .status-banner i {
                font-size: 1.8rem !important;
            }
            .status-banner strong {
                font-size: 1rem !important;
                margin-bottom: 5px;
            }
            .status-banner span {
                font-size: 0.8rem !important;
                line-height: 1.4;
                display: block;
            }
            .verify-container {
                padding: 15px !important;
                margin: 10px auto !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .verify-container h1 {
                font-size: 1.5rem !important;
            }
            .data-card {
                padding: 20px !important;
                margin: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .data-card h3 {
                font-size: 1rem !important;
                margin-top: 25px !important;
            }
            .form-grid-mobile {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
            .upload-grid-mobile {
                grid-template-columns: 1fr 1fr !important;
                gap: 12px !important;
            }
            .upload-box {
                padding: 15px 10px !important;
                min-height: 100px !important;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }
            .upload-icon {
                font-size: 1.5rem !important;
                margin-bottom: 5px !important;
            }
            .upload-box div {
                font-size: 0.6rem !important;
                line-height: 1.2;
            }
            .status-banner {
                padding: 15px !important;
                gap: 12px !important;
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <?php include_once '../includes/mobile_header.php'; ?>
    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="browse-vehicles.php">Browse Fleet</a>
            <a href="my-bookings.php">My Bookings</a>
            <a href="support.php">Support</a>
            <a href="profile.php" class="active">Profile</a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="verify-container">
            <div class="verify-header" style="margin-bottom: 35px; text-align: center;">
                <h1 style="font-weight: 800; color: white; margin-bottom: 10px;">ID & Documents Verification</h1>
                <p style="color: rgba(255,255,255,0.6);">Complete your profile to unlock vehicle bookings.</p>
            </div>

            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if ($success): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast(<?php echo json_encode($success); ?>, 'success', 6000);
                    });
                </script>
            <?php endif; ?>

            <?php if ($error): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast(<?php echo json_encode($error); ?>, 'error');
                    });
                </script>
            <?php endif; ?>
            
            <?php if($user['verification_status'] === 'pending'): ?>
                <div class="status-banner status-pending-banner">
                    <i class="fas fa-hourglass-half" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong style="display: block;">Verification Pending</strong>
                        <span style="font-size: 0.9rem;">Our team is currently reviewing your documents. Your account will be verified within 1 to 2 working days. You'll be notified via the portal once approved.</span>
                    </div>
                </div>
            <?php elseif($user['verification_status'] === 'declined'): ?>
                <div class="status-banner status-declined-banner">
                    <i class="fas fa-times-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong style="display: block;">Verification Declined</strong>
                        <span style="font-size: 0.9rem;">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($user['verification_note'] ?? 'No specific reason provided.'); ?><br>
                            Please review the details below and resubmit clear, valid documents.
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- OCR Feature Banner -->
            <div class="ocr-banner">
                <i class="fas fa-magic"></i>
                <div>
                    <strong>Smart Document Scanning</strong><br>
                    <span style="font-size:0.82rem; opacity:0.8;">Upload your NRC or Driver's License and click <em>"Scan Document"</em> to auto-detect your ID number using OCR technology.</span>
                </div>
            </div>

            <form action="verify-profile.php" method="POST" enctype="multipart/form-data" class="data-card" style="padding: 40px;">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <h3 style="color: white; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; margin-bottom: 25px;"><i class="fas fa-user-shield" style="color: var(--accent-color); margin-right: 10px;"></i> Personal & Contact Info</h3>
                <div class="form-grid-mobile" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" disabled style="opacity: 0.6;">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="+260 ...">
                    </div>
                    <div class="form-group">
                        <label>Physical Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required placeholder="Plot No, Street, City">
                    </div>
                </div>

                <h3 style="color: white; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; margin-bottom: 25px; margin-top: 40px;"><i class="fas fa-id-card-alt" style="color: var(--accent-color); margin-right: 10px;"></i> Identity & Driving Specs</h3>
                <div class="form-grid-mobile" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label>ID Type</label>
                        <select name="id_type" style="width: 100%; padding: 12px; background: rgba(30, 30, 35, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                            <option value="NRC" <?php echo ($user['id_type'] ?? '') === 'NRC' ? 'selected' : ''; ?>>NRC (National Registration Card)</option>
                            <option value="Passport" <?php echo ($user['id_type'] ?? '') === 'Passport' ? 'selected' : ''; ?>>Passport</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Identity Number</label>
                        <input type="text" name="id_number" value="<?php echo htmlspecialchars($user['id_number'] ?? ''); ?>" required placeholder="123456/78/1 or L-...">
                    </div>
                    <div class="form-group">
                        <label>Driving License Number</label>
                        <input type="text" name="license_number" value="<?php echo htmlspecialchars($user['license_number'] ?? ''); ?>" required placeholder="ZL-...">
                    </div>
                    <div class="form-group">
                        <label>License Class / Category</label>
                        <input type="text" name="license_class" value="<?php echo htmlspecialchars($user['license_class'] ?? ''); ?>" required placeholder="e.g. B, C1">
                    </div>
                    <div class="form-group">
                        <label>License Expiry Date</label>
                        <input type="date" name="license_expiry" value="<?php echo htmlspecialchars($user['license_expiry'] ?? ''); ?>" required>
                    </div>
                </div>

                <h3 style="color: white; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; margin-bottom: 25px; margin-top: 40px;"><i class="fas fa-phone-alt" style="color: var(--accent-color); margin-right: 10px;"></i> Emergency Contact</h3>
                <div class="form-grid-mobile" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label>Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>" required placeholder="Next of Kin Name">
                    </div>
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>" required placeholder="+260 ...">
                    </div>
                </div>

                <h3 style="color: white; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; margin-bottom: 25px; margin-top: 40px;"><i class="fas fa-file-upload" style="color: var(--accent-color); margin-right: 10px;"></i> Document Uploads</h3>
                <div class="upload-grid-mobile" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="margin-bottom: 10px; display: block;">Profile Photo</label>
                        <div class="upload-box" onclick="document.getElementById('profile_img').click()">
                            <i class="fas fa-user-circle upload-icon"></i>
                            <div style="font-size: 0.7rem;">Selfie Photo</div>
                            <input type="file" name="profile_img" id="profile_img" class="file-input" accept="image/*" <?php echo empty($user['profile_image_path']) ? 'required' : ''; ?>>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="margin-bottom: 10px; display: block;">ID (Front)</label>
                        <div class="upload-box" onclick="document.getElementById('nrc_img').click()" id="nrc_upload_box">
                            <i class="fas fa-id-card upload-icon"></i>
                            <div style="font-size: 0.7rem;">NRC/Passport Front</div>
                            <input type="file" name="nrc_img" id="nrc_img" class="file-input" accept="image/*" <?php echo empty($user['nrc_image_path']) ? 'required' : ''; ?>>
                        </div>
                        <button type="button" class="ocr-scan-btn" id="nrc_scan_btn" onclick="scanDocument('nrc_img', 'NRC', 'id_number', 'nrc_ocr_result')">
                            <i class="fas fa-magic" style="margin-right:5px;"></i> Scan Document
                        </button>
                        <div class="ocr-result-badge" id="nrc_ocr_result"></div>
                    </div>
                    <div class="form-group">
                        <label style="margin-bottom: 10px; display: block;">ID (Back)</label>
                        <div class="upload-box" onclick="document.getElementById('nrc_back_img').click()">
                            <i class="fas fa-id-card upload-icon"></i>
                            <div style="font-size: 0.7rem;">NRC/Passport Back</div>
                            <input type="file" name="nrc_back_img" id="nrc_back_img" class="file-input" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="upload-grid-mobile" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
                    <div class="form-group">
                        <label style="margin-bottom: 10px; display: block;">License (Front)</label>
                        <div class="upload-box" onclick="document.getElementById('license_img').click()" id="license_upload_box">
                            <i class="fas fa-address-card upload-icon"></i>
                            <div style="font-size: 0.7rem;">License Front</div>
                            <input type="file" name="license_img" id="license_img" class="file-input" accept="image/*" <?php echo empty($user['license_image_path']) ? 'required' : ''; ?>>
                        </div>
                        <button type="button" class="ocr-scan-btn" id="license_scan_btn" onclick="scanDocument('license_img', 'LICENSE', 'license_number', 'license_ocr_result')">
                            <i class="fas fa-magic" style="margin-right:5px;"></i> Scan Document
                        </button>
                        <div class="ocr-result-badge" id="license_ocr_result"></div>
                    </div>
                    <div class="form-group">
                        <label style="margin-bottom: 10px; display: block;">License (Back)</label>
                        <div class="upload-box" onclick="document.getElementById('license_back_img').click()">
                            <i class="fas fa-address-card upload-icon"></i>
                            <div style="font-size: 0.7rem;">License Back</div>
                            <input type="file" name="license_back_img" id="license_back_img" class="file-input" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="margin-bottom: 10px; display: block;">Utility Bill</label>
                        <div class="upload-box" onclick="document.getElementById('utility_bill_img').click()">
                            <i class="fas fa-home upload-icon"></i>
                            <div style="font-size: 0.7rem;">Proof of Residence</div>
                            <input type="file" name="utility_bill_img" id="utility_bill_img" class="file-input" accept="image/*">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 2; padding: 18px; font-weight: 700;">Submit for Review</button>
                    <a href="profile.php" class="btn btn-outline" style="flex: 1; text-align: center; border-color: rgba(255,255,255,0.1); opacity: 0.7;">Cancel</a>
                </div>
            </form>


            <script>
                // ── File preview + OCR button reveal ──────────────────────────────
                document.querySelectorAll('.file-input').forEach(input => {
                    input.addEventListener('change', function() {
                        const box = this.parentElement;
                        if (this.files && this.files[0]) {
                            box.style.borderColor = '#10b981';
                            box.style.background = 'rgba(16, 185, 129, 0.05)';
                            box.querySelector('div').textContent = '✅ ' + this.files[0].name;

                            // Show OCR scan button for NRC and License
                            const scanBtn = document.getElementById(this.id.replace('_img', '_scan_btn').replace('nrc', 'nrc').replace('license', 'license'));
                            if (scanBtn) scanBtn.style.display = 'block';
                        }
                    });
                });

                // ── OCR Scan Function ─────────────────────────────────────────────
                async function scanDocument(inputId, docType, targetFieldName, resultDivId) {
                    const fileInput = document.getElementById(inputId);
                    const resultDiv = document.getElementById(resultDivId);
                    const scanBtn   = document.getElementById(inputId.replace('_img', '_scan_btn').replace('nrc', 'nrc').replace('license', 'license'));

                    if (!fileInput.files || !fileInput.files[0]) {
                        showOCRResult(resultDiv, 'error', '⚠️ Please select a document image first.');
                        return;
                    }

                    // Show loading state
                    scanBtn.disabled = true;
                    scanBtn.innerHTML = '<span class="ocr-spinner">⟳</span> Scanning...';
                    showOCRResult(resultDiv, 'warning', '🔍 Analysing document with OCR...');

                    try {
                        const formData = new FormData();
                        formData.append('image', fileInput.files[0]);
                        formData.append('doc_type', docType);
                        formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');

                        const response = await fetch('../api/ocr-verify.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) throw new Error('Server error: ' + response.status);

                        const data = await response.json();

                        if (!data.success) {
                            showOCRResult(resultDiv, 'error', '❌ ' + (data.error || 'OCR failed. Please enter number manually.'));
                            return;
                        }

                        if (data.detected_number) {
                            // Auto-fill the target field
                            const targetField = document.querySelector(`[name="${targetFieldName}"]`);
                            if (targetField) {
                                targetField.value = data.detected_number;
                                targetField.style.borderColor = data.is_valid_format ? '#10b981' : '#f59e0b';
                                targetField.style.boxShadow = data.is_valid_format
                                    ? '0 0 0 3px rgba(16,185,129,0.2)'
                                    : '0 0 0 3px rgba(245,158,11,0.2)';
                            }

                            const icon    = data.is_valid_format ? '✅' : '⚠️';
                            const type    = data.is_valid_format ? 'success' : 'warning';
                            const method  = data.method === 'google_vision' ? 'Google Vision AI' : data.method === 'tesseract' ? 'Tesseract OCR' : 'Pattern Match';
                            const msg     = `${icon} Detected: <strong>${data.detected_number}</strong><br><small>${data.format_message}</small><br><small style="opacity:0.6;">Method: ${method} | Confidence: ${data.confidence}%</small>`;
                            showOCRResult(resultDiv, type, msg);
                        } else {
                            const msg = data.method === 'none'
                                ? '⚠️ OCR not configured on this server. Please enter the number manually.'
                                : '⚠️ Could not detect a number. Ensure the image is clear and well-lit, then enter manually.';
                            showOCRResult(resultDiv, 'warning', msg);
                        }

                    } catch (err) {
                        showOCRResult(resultDiv, 'error', '❌ Scan failed: ' + err.message + '. Please enter number manually.');
                    } finally {
                        scanBtn.disabled = false;
                        scanBtn.innerHTML = '<i class="fas fa-magic" style="margin-right:5px;"></i> Scan Again';
                    }
                }

                function showOCRResult(div, type, html) {
                    div.className = 'ocr-result-badge ' + type;
                    div.innerHTML = html;
                    div.style.display = 'block';
                }
            </script>
        </div>
    </div>
</body>
</html>
