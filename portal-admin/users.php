<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';
include_once '../includes/mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle User Deletion
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    header("Location: users.php?msg=deleted");
    exit;
}

// Handle Role Update
if (isset($_POST['update_role'])) {
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$_POST['role'], $_POST['user_id']]);
    
    // Security: Regenerate ID if the current user is modifying their own role
    if ($_POST['user_id'] == $_SESSION['user_id']) {
        session_regenerate_id(true);
        $_SESSION['user_role'] = $_POST['role'];
    }
    
    $success = "User role updated successfully.";
}

// Handle Add Staff
if (isset($_POST['add_staff'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);
    header("Location: users.php?msg=staff_added");
    exit;
}

// Handle Manual Password Reset
if (isset($_GET['reset_pwd'])) {
    $u_id = $_GET['reset_pwd'];
    $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
    $stmt->execute([$u_id]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$user['email']]);
        $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)")->execute([$user['email'], $token]);

        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Car_Higher/reset-password.php?token=" . $token;
        
        $mailer = new CarHireMailer();
        $subject = "Admin Password Reset - Car Hire";
        $email_body = "
            Hello " . $user['name'] . ",<br><br>
            An administrator has initiated a password reset for your account. Please click the button below to set a new password:<br><br>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$reset_link}' style='background: #2563eb; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 700;'>Set New Password</a>
            </div>
            If you didn't expect this, please contact support immediately.<br><br>
            Best regards,<br>
            The Car Hire Helpdesk
        ";

        if ($mailer->send($user['email'], $subject, $email_body)) {
            $success = "Password reset email sent to " . $user['email'];
        } else {
            $success = "Reset link generated (Email failed): <a href='$reset_link' target='_blank' style='color:var(--accent-color); font-weight:700;'>Manual Reset Link</a>";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>User Management</h1>
                    <p class="text-secondary">Control staff and customer access levels.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addStaffModal')"><i class="fas fa-user-plus"></i> Add Staff</button>
                </div>
            </div>

            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if(isset($success)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($success); ?>', 'success');
                    });
                </script>
            <?php endif; ?>
            
            <?php if(isset($_GET['msg'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const msg = <?php echo json_encode($_GET['msg']); ?>;
                        if (msg === 'deleted') showToast('User deleted successfully', 'success');
                        if (msg === 'staff_added') showToast('Staff member added successfully', 'success');
                    });
                </script>
            <?php endif; ?>

            <div class="data-card">
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td data-label="Name" class="font-bold"><?php echo htmlspecialchars($u['name']); ?></td>
                            <td data-label="Email"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td data-label="Role">
                                <form action="" method="POST" style="display: flex; gap: 8px; align-items: center; width: 100%; justify-content: flex-end;">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role" class="form-control-sm" style="flex: 1; min-width: 80px;">
                                        <option value="customer" <?php echo $u['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="agent" <?php echo $u['role'] == 'agent' ? 'selected' : ''; ?>>Agent</option>
                                        <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                            <td data-label="Action">
                                <div style="display: flex; gap: 8px; justify-content: flex-end; width: 100%;">
                                    <a href="users.php?reset_pwd=<?php echo $u['id']; ?>" class="btn btn-outline btn-sm" style="color: var(--accent-color); border-color: var(--accent-color); flex: 1; justify-content: center;" title="Send Reset Email">
                                        <i class="fas fa-key"></i> <span class="hide-mobile">Reset</span>
                                    </a>
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('Are you sure?')" class="btn btn-outline btn-sm text-danger" style="flex: 0 0 40px; justify-content: center;">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('addStaffModal')">&times;</span>
            <div class="modal-header">
                <h2>Register New Staff</h2>
            </div>
            <form action="" method="POST">
                <div class="form-group mb-3">
                    <label>Full Name</label>
                    <input type="text" name="name" required class="form-control" placeholder="e.g. John Doe">
                </div>
                <div class="form-group mb-3">
                    <label>Email Address</label>
                    <input type="email" name="email" required class="form-control" placeholder="staff@CarHire.com">
                </div>
                <div class="form-group mb-3">
                    <label>Password</label>
                    <input type="password" name="password" required class="form-control" placeholder="••••••••">
                </div>
                <div class="form-group mb-4">
                    <label>Assign Role</label>
                    <select name="role" required class="form-control">
                        <option value="agent">Agent</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="add_staff" class="btn btn-primary w-100 py-3">Register Staff</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'register') {
                openModal('addStaffModal');
            }
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
