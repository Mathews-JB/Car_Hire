<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle Flash Messages
$success = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Handle Updates
// ── Security Actions (Recovery Key & Account Freeze) ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // 1. Handle Recovery Key Generation
    if (isset($_POST['generate_key'])) {
        verify_csrf_token($_POST['csrf_token'] ?? '');
        $raw_key = bin2hex(random_bytes(8)); // 16 character hex key
        $hashed_key = password_hash($raw_key, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET recovery_key = ? WHERE id = ?");
        if ($stmt->execute([$hashed_key, $user_id])) {
            $key_display = "<div style='margin-top:15px; padding:15px; background:rgba(0,0,0,0.2); border-radius:10px; border:1px solid rgba(255,255,255,0.1); text-align:center;'>
                                <div id='masterKeyVal' style='font-family:monospace; font-size:1.5rem; letter-spacing:2px; font-weight:800; color:#fff; margin-bottom:10px;'>$raw_key</div>
                                <button type='button' onclick='copyMasterKey()' class='btn btn-outline' style='font-size:0.8rem; padding:5px 15px; border-color:#3b82f6; color:#3b82f6;'>
                                    <i class='fas fa-copy'></i> Copy Key
                                </button>
                            </div>
                            <script>
                                function copyMasterKey() {
                                    const text = document.getElementById(\"masterKeyVal\").innerText;
                                    navigator.clipboard.writeText(text).then(() => {
                                        const btn = event.currentTarget;
                                        btn.innerHTML = \"<i class='fas fa-check'></i> Copied!\";
                                        setTimeout(() => { btn.innerHTML = \"<i class='fas fa-copy'></i> Copy Key\"; }, 2000);
                                    });
                                }
                            </script>";
            $_SESSION['flash_success'] = "<strong>Security: Master Recovery Key Generated</strong><br>SAVE THIS NOW! This is your only access if your account is locked.<br>" . $key_display;
            
            $notif_msg = "A new Master Recovery Key has been generated for your admin account. If you did not request this, please freeze your account or change your password immediately.";
            
            // We DO NOT store the raw key in the database or send it via email for security reasons.
            // Notifications should only alert about the event.
            createNotification($pdo, $user_id, "Security: Master Key Generated", $notif_msg, "security", ['db', 'email']);
        }
        header("Location: settings.php");
        exit;
    }

    // 2. Handle Account Freeze
    if (isset($_POST['freeze_account'])) {
        verify_csrf_token($_POST['csrf_token'] ?? '');
        $stmt = $pdo->prepare("UPDATE users SET account_status = 'frozen' WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            session_destroy();
            header("Location: ../login.php?msg=account_frozen");
            exit;
        }
    }
}

// ── General Settings Updates ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tax_rate'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    // Basic Validation
    if (!is_numeric($_POST['tax_rate'])) {
        $_SESSION['flash_error'] = "Tax Rate must be a number.";
        header("Location: settings.php");
        exit;
    }
    
    $updates = [
        'company_name' => $_POST['company_name'] ?? '',
        'company_email' => filter_var($_POST['company_email'], FILTER_SANITIZE_EMAIL),
        'company_phone' => $_POST['company_phone'] ?? '',
        'company_address' => $_POST['company_address'] ?? '',
        'company_tpin' => $_POST['company_tpin'] ?? '',
        'tax_rate' => $_POST['tax_rate'] ?? '0',
        'currency' => $_POST['currency'] ?? 'ZMW',
        'lenco_api_key' => $_POST['lenco_api_key'] ?? '',
        'maintenance_threshold_km' => $_POST['maintenance_threshold_km'] ?? '5000'
    ];

    try {
        $pdo->beginTransaction();
        foreach ($updates as $key => $val) {
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, trim($val)]);
        }
        $pdo->commit();
        $_SESSION['flash_success'] = "System settings updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Error updating settings: " . $e->getMessage();
    }

    header("Location: settings.php");
    exit;
}

// Fetch Current Settings
$settings_raw = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    if (empty($error)) $error = "Database Error: " . $e->getMessage();
}

// Defaults helper
function get_setting($key, $default = '') {
    global $settings_raw;
    return $settings_raw[$key] ?? $default;
}

// Fetch current user security status
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT account_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$me = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .settings-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 20px; 
            margin-top: 30px;
        }
        @media (max-width: 768px) {
            .settings-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>System Configuration</h1>
                    <p class="text-secondary">Manage global parameters, API keys, and operational rules.</p>
                </div>
            </div>

            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if(!empty($success)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($success); ?>', 'success');
                    });
                </script>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($error); ?>', 'error');
                    });
                </script>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="settings-grid">
                    
                    <!-- Company Info -->
                    <div class="config-card">
                        <div class="config-title"><i class="fas fa-building"></i> Organization Profile</div>
                        
                        <div class="form-group">
                            <label>Business Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars(get_setting('company_name')); ?>" required>
                        </div>
                        
                        <div class="info-grid">
                            <div class="form-group">
                                <label>Support Email</label>
                                <input type="email" name="company_email" class="form-control" value="<?php echo htmlspecialchars(get_setting('company_email')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Support Phone</label>
                                <input type="text" name="company_phone" class="form-control" value="<?php echo htmlspecialchars(get_setting('company_phone')); ?>" required>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="form-group">
                                <label>Physical Address</label>
                                <input type="text" name="company_address" class="form-control" value="<?php echo htmlspecialchars(get_setting('company_address')); ?>" placeholder="Plot 101, Great East Road">
                            </div>

                            <div class="form-group">
                                <label>TPIN Number</label>
                                <input type="text" name="company_tpin" class="form-control" value="<?php echo htmlspecialchars(get_setting('company_tpin')); ?>" placeholder="100XXXXXXXX">
                            </div>
                        </div>
                    </div>

                    <!-- Financials -->
                    <div class="config-card">
                        <div class="config-title"><i class="fas fa-wallet"></i> Financial Parameters</div>
                        
                        <div class="info-grid">
                            <div class="form-group">
                                <label>Currency Code</label>
                                <input type="text" name="currency" class="form-control" value="<?php echo htmlspecialchars(get_setting('currency', 'ZMW')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Tax Rate (%)</label>
                                <input type="number" step="0.01" name="tax_rate" class="form-control" value="<?php echo htmlspecialchars(get_setting('tax_rate', '16')); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Lenco API Secret Key</label>
                            <input type="password" name="lenco_api_key" class="form-control" value="<?php echo htmlspecialchars(get_setting('lenco_api_key')); ?>" placeholder="••••••••••••••••">
                            <small style="display: block; margin-top: 8px; color: rgba(255,255,255,0.4); font-size: 0.8rem;">Used for payment verification callbacks.</small>
                        </div>
                    </div>

                    <!-- Security Control -->
                    <div class="config-card">
                        <div class="config-title"><i class="fas fa-shield-alt"></i> Security Center</div>
                        
                        <div class="info-grid">
                            <div class="form-group">
                                <label>Master Recovery Key</label>
                                <p style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-bottom: 12px;">Reset code if locked out.</p>
                                <button type="button" class="btn btn-outline" style="width: 100%; border-color: #3b82f6; color: #3b82f6; font-size: 0.75rem;" onclick="if(confirm('Generate new recovery key? This will invalidate your old one.')) document.getElementById('genKeyForm').submit();">Generate Key</button>
                            </div>

                            <div class="form-group">
                                <label style="color: #ef4444;">Account Freeze</label>
                                <p style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-bottom: 12px;">Instantly disable account.</p>
                                <button type="button" class="btn btn-outline" style="width: 100%; border-color: #ef4444; color: #ef4444; font-size: 0.75rem;" onclick="if(confirm('DANGER: This will instantly lock you out. Continue?')) document.getElementById('freezeForm').submit();">Freeze Account</button>
                            </div>
                        </div>
                    </div>

                    <!-- Operations -->
                    <div class="config-card">
                        <div class="config-title"><i class="fas fa-cogs"></i> Operations Logic</div>
                        
                        <div class="form-group">
                            <label>Maintenance Alert Threshold (KM)</label>
                            <input type="number" name="maintenance_threshold_km" class="form-control" value="<?php echo htmlspecialchars(get_setting('maintenance_threshold_km', '5000')); ?>">
                            <small style="display: block; margin-top: 8px; color: rgba(255,255,255,0.4); font-size: 0.8rem;">Auto-flag vehicles for service after this distance.</small>
                        </div>

                        <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1); margin-top: 30px;">
                            <label style="margin-bottom: 5px; color: rgba(255, 255, 255, 0.6); display: flex; justify-content: space-between; align-items: center;">
                                System Version
                                <span style="font-size: 0.65rem; background: #10b981; color: black; padding: 2px 6px; border-radius: 4px; font-weight: 800;">LATEST</span>
                            </label>
                            <div style="font-family: monospace; font-size: 1.1rem; color: white;">v2.4.0 (Stable)</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="config-card save-card">
                        <div style="text-align: center; color: rgba(255, 255, 255, 0.4); margin-bottom: 25px;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3.5rem; opacity: 0.5; margin-bottom: 15px;"></i>
                            <p style="margin: 0; font-size: 0.9rem;">Review your changes before deploying.</p>
                        </div>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Configuration</button>
                    </div>

                </div>
            </form>

            <form id="genKeyForm" method="POST" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="generate_key" value="1">
            </form>
            <form id="freezeForm" method="POST" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="freeze_account" value="1">
            </form>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
