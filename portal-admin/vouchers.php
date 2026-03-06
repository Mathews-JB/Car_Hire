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

// Handle Voucher Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_voucher'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $value = (float)$_POST['discount_value'];
    $min_amount = (float)$_POST['min_booking_amount'];
    $limit = (int)$_POST['usage_limit'];
    $assigned_email = !empty($_POST['assigned_user_email']) ? trim($_POST['assigned_user_email']) : null;
    $expiry = $_POST['expiry_date'];

    if (empty($code) || $value <= 0 || $limit <= 0) {
        $_SESSION['flash_error'] = "Invalid input. Please check all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO vouchers (code, discount_type, discount_value, expiry_date, min_booking_amount, usage_limit, assigned_user_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $expiry, $min_amount, $limit, $assigned_email]);
            $_SESSION['flash_success'] = "Voucher code '$code' created successfully.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error: Voucher code likely already exists.";
        }
    }
    header("Location: vouchers.php");
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    $stmt = $pdo->prepare("UPDATE vouchers SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = "Voucher status updated.";
    header("Location: vouchers.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = "Voucher deleted permanently.";
    header("Location: vouchers.php");
    exit;
}


// Fetch Vouchers
$stmt = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC");
$vouchers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Vouchers | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .voucher-code { font-family: monospace; font-weight: 800; letter-spacing: 2px; color: var(--accent-color); }
        .progress-track { height: 4px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; margin-top: 6px; }
        .progress-fill { height: 100%; border-radius: 4px; transition: width 0.5s; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
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
            
            <div class="dashboard-header">
                <div>
                    <h1>Promo Vouchers</h1>
                    <p class="text-secondary">Manage marketing campaigns and discount codes.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="toggleModal('voucherModal')">
                        <i class="fas fa-plus"></i> Create Voucher
                    </button>
                </div>
            </div>

            <div class="data-card">
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>Voucher</th>
                            <th>Discount</th>
                            <th>Limit</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vouchers as $v): 
                            $percent = $v['usage_limit'] > 0 ? ($v['used_count'] / $v['usage_limit']) * 100 : 0;
                            $is_expired = strtotime($v['expiry_date']) < time();
                        ?>
                        <tr>
                            <td data-label="Voucher">
                                <span class="voucher-code"><?php echo htmlspecialchars($v['code']); ?></span>
                                <?php if($v['assigned_user_email']): ?>
                                    <div style="font-size:0.6rem; margin-top:5px; color:#fbbf24;"><i class="fas fa-lock"></i> <?php echo $v['assigned_user_email']; ?></div>
                                <?php else: ?>
                                    <div style="font-size:0.6rem; margin-top:5px; color:rgba(255,255,255,0.4);"><i class="fas fa-globe"></i> Public</div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Discount">
                                <span style="font-weight: 700; color: #10b981;">
                                    <?php echo $v['discount_type'] === 'percentage' ? '-' . $v['discount_value'] . '%' : '- ZMW ' . number_format($v['discount_value'], 2); ?>
                                </span>
                                <div style="font-size: 0.6rem; opacity: 0.5;">Min. ZMW <?php echo number_format($v['min_booking_amount'], 0); ?></div>
                            </td>
                            <td data-label="Limit">
                                <div style="display:flex; align-items:center; justify-content: flex-end; width: 100%;">
                                    <span style="font-size: 0.8rem; color: rgba(255,255,255,0.7);"><?php echo $v['used_count']; ?> / <?php echo $v['usage_limit']; ?></span>
                                </div>
                                <div class="progress-track" style="margin-left: auto;">
                                    <div class="progress-fill" style="width: <?php echo min(100, $percent); ?>%; background: <?php echo $percent >= 100 ? '#ef4444' : 'linear-gradient(90deg, #10b981, #34d399)'; ?>"></div>
                                </div>
                            </td>
                            <td data-label="Expiry">
                                <span style="<?php echo $is_expired ? 'color: #ef4444;' : ''; ?>">
                                    <?php echo date('d M, Y', strtotime($v['expiry_date'])); ?>
                                </span>
                            </td>
                            <td data-label="Status">
                                <?php if (!$v['is_active']): ?>
                                    <span class="status-pill status-cancelled">DISABLED</span>
                                <?php elseif ($is_expired): ?>
                                    <span class="status-pill status-pending text-warning">EXPIRED</span>
                                <?php elseif ($v['used_count'] >= $v['usage_limit']): ?>
                                    <span class="status-pill status-dark">DEPLETED</span>
                                <?php else: ?>
                                    <span class="status-pill status-confirmed">ACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <div style="display:flex; gap:8px; justify-content: flex-end;">
                                    <a href="?toggle_id=<?php echo $v['id']; ?>" class="btn btn-outline btn-sm">
                                        <?php echo $v['is_active'] ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>'; ?>
                                    </a>
                                    <a href="?delete_id=<?php echo $v['id']; ?>" class="btn btn-outline btn-sm text-danger" onclick="return confirm('Permanently delete this voucher?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($vouchers)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 60px;">
                                <i class="fas fa-ticket-alt" style="font-size: 3rem; color: rgba(255,255,255,0.1); margin-bottom: 20px;"></i>
                                <p style="color: rgba(255,255,255,0.4);">No voucher campaigns found.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Voucher Modal -->
    <div id="voucherModal" class="modal-overlay">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0; font-size: 1.3rem;">Create Campaign</h2>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Launch a new discount incentive</p>
                </div>
                <button onclick="toggleModal('voucherModal')" style="background: rgba(255,255,255,0.05); border: none; color: white; cursor: pointer; font-size: 1rem; width: 32px; height: 32px; border-radius: 50%;">&times;</button>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="add_voucher" value="1">
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Voucher Code</label>
                        <input type="text" name="code" class="form-control" placeholder="SUMMER25" required style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Redeem Limit</label>
                        <input type="number" name="usage_limit" class="form-control" value="100" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Discount Type</label>
                        <select name="discount_type" class="form-control" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed (ZMW)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount / Value</label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" placeholder="10.00" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Min. Booking</label>
                        <input type="number" step="0.01" name="min_booking_amount" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Target User (Email - Optional)</label>
                    <input type="email" name="assigned_user_email" class="form-control" placeholder="leave blank for public">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; margin-top: 10px;">
                    <i class="fas fa-rocket"></i> Launch Campaign
                </button>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 for Premium Popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Check for PHP session flash messages and trigger SweetAlert
        <?php if($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo addslashes($success); ?>',
            background: '#1e1e24',
            color: '#fff',
            confirmButtonColor: '#3b82f6',
            timer: 3000,
            timerProgressBar: true
        });
        <?php endif; ?>

        <?php if($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Action Failed',
            text: '<?php echo addslashes($error); ?>',
            background: '#1e1e24',
            color: '#fff',
            confirmButtonColor: '#ef4444'
        });
        <?php endif; ?>

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
