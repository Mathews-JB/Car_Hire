<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle New Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $category = $_POST['category'];
    $amount = (float)$_POST['amount'];
    $date = $_POST['expense_date'];
    $desc = $_POST['description'];

    try {
        $stmt = $pdo->prepare("INSERT INTO system_expenses (category, amount, expense_date, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$category, $amount, $date, $desc]);
        $success = "Expense recorded successfully.";
        log_action($pdo, "Recorded expense", "Category: $category, Amount: ZMW $amount", "Finance");
    } catch (PDOException $e) {
        $error = "Error adding expense: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM system_expenses WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Expense deleted.";
    log_action($pdo, "Deleted expense", "Expense ID: $id", "Finance");
}

// Fetch Expenses
$stmt = $pdo->query("SELECT * FROM system_expenses ORDER BY expense_date DESC");
$expenses = $stmt->fetchAll();

// Categories
$categories = ['Insurance', 'Road Tax', 'Fitness', 'Staff Salaries', 'Office Rent', 'Utilities', 'Marketing', 'Other'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management | Admin Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .expense-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
        @media (max-width: 992px) { .expense-grid { grid-template-columns: 1fr; } }
        
        .form-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px;
            height: fit-content;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Operating Expenses</h1>
                    <p class="text-secondary">Track non-maintenance costs for accurate ROI reporting.</p>
                </div>
            </div>

            <?php if($success): ?>
                <div class="status-pill status-confirmed" style="margin-bottom: 20px; width: 100%; text-transform:none;">
                    <i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo h($success); ?>
                </div>
            <?php endif; ?>

            <div class="expense-grid">
                <!-- Add Form -->
                <div class="form-card">
                    <h3 style="color: white; margin-bottom: 20px;">Record New Expense</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Category</label>
                            <select name="category" class="form-control" required>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Amount (ZMW)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Date</label>
                            <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">Description</label>
                            <textarea name="description" class="form-control" style="height: 80px;" placeholder="Optional details..."></textarea>
                        </div>

                        <button type="submit" name="add_expense" class="btn btn-primary" style="width: 100%;">Save Expense Entry</button>
                    </form>
                </div>

                <!-- List -->
                <div class="data-card" style="background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 25px;">
                    <h3 style="color: white; margin-bottom: 20px;">Expense Log</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($expenses as $e): ?>
                            <tr>
                                <td style="font-size: 0.85rem; opacity: 0.7;"><?php echo date('d M, Y', strtotime($e['expense_date'])); ?></td>
                                <td style="font-weight: 700; color: white;"><?php echo h($e['category']); ?></td>
                                <td style="font-size: 0.85rem; opacity: 0.6;"><?php echo h($e['description']); ?></td>
                                <td style="font-weight: 700; color: #ef4444;">ZMW <?php echo number_format($e['amount'], 2); ?></td>
                                <td>
                                    <a href="?delete_id=<?php echo $e['id']; ?>" class="btn btn-outline btn-sm text-danger" onclick="return confirm('Delete this entry?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($expenses)): ?>
                                <tr><td colspan="5" style="text-align:center; padding: 40px; opacity:0.3;">No expenses recorded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

