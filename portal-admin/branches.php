<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
if (isset($_POST['add_branch'])) {
    verify_csrf_token($_POST['csrf_token']);
    $name = $_POST['name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $phone = $_POST['phone'];
    
    $stmt = $pdo->prepare("INSERT INTO branches (name, address, city, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $address, $city, $phone]);
    $success = "Branch added successfully!";
}

$stmt = $pdo->query("SELECT * FROM branches ORDER BY name ASC");
$branches = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Management | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Branch Management</h1>
                    <p class="text-secondary">Manage physical locations and contact information.</p>
                </div>
                <div class="header-actions">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <button class="btn btn-primary" onclick="openBranchModal()"><i class="fas fa-plus"></i> Add New Branch</button>
                </div>
            </div>

            <?php if($success): ?>
                <div class="form-feedback success"><?php echo $success; ?></div>
            <?php endif; ?>

    <style>
        .branch-grid {
            margin-top: 30px;
        }
        .branch-icon {
            width: 44px;
            height: 44px;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .branch-card {
            padding: 25px;
            display: flex;
            flex-direction: column;
        }
        .branch-details p {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        .branch-details i {
            width: 18px;
            color: var(--primary-color);
            opacity: 0.6;
        }
    </style>

            <div class="grid-2 branch-grid">
                <?php foreach($branches as $b): ?>
                    <div class="data-card branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="branch-header">
                            <h3 style="color: white; font-size: 1.15rem; margin-bottom: 15px;"><?php echo htmlspecialchars($b['name']); ?></h3>
                        </div>
                        <div class="branch-details">
                            <p title="Address"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($b['address']); ?></p>
                            <p title="City"><i class="fas fa-city"></i> <?php echo htmlspecialchars($b['city']); ?></p>
                            <p title="Phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($b['phone']); ?></p>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <button class="btn btn-outline btn-sm" style="flex: 1;" onclick="editBranch(<?php echo htmlspecialchars(json_encode($b)); ?>)"><i class="fas fa-edit mr-1"></i> Edit</button>
                            <a href="branches.php?delete=<?php echo $b['id']; ?>" class="btn btn-outline btn-sm text-danger" style="flex: 1; border-color: rgba(220, 38, 38, 0.2);" onclick="return confirm('Delete this branch?')"><i class="fas fa-trash mr-1"></i> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Refined Branch Modal -->
    <div id="branchModal" class="modal-overlay">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 25px;">
                <div>
                    <h2 id="modalTitle" style="margin: 0; font-size: 1.4rem;">Add New Branch</h2>
                    <p class="text-secondary" style="font-size: 0.8rem; margin-top: 4px;">Define a physical service location</p>
                </div>
                <button onclick="document.getElementById('branchModal').style.display='none'" style="background: rgba(255,255,255,0.05); border: none; color: white; cursor: pointer; font-size: 1.2rem; width: 32px; height: 32px; border-radius: 50%;">&times;</button>
            </div>
            
            <form action="" method="POST" id="branchForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="branch_id" id="branch_id">
                <input type="hidden" name="add_branch" id="form_action" value="1">
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Branch Name</label>
                        <input type="text" name="name" placeholder="Lusaka Main" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" placeholder="Lusaka" required class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Physical Address</label>
                    <textarea name="address" placeholder="Full address details..." required class="form-control" style="height:80px;"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="phone" placeholder="+260..." required class="form-control">
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <button type="submit" id="submitBtn" class="btn btn-primary" style="flex: 2; height: 50px;">Create Branch</button>
                    <button type="button" class="btn btn-outline" style="flex: 1; height: 50px;" onclick="document.getElementById('branchModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function editBranch(branch) {
            document.getElementById('modalTitle').innerText = 'Edit Branch';
            document.getElementById('submitBtn').innerText = 'Save Changes';
            document.getElementById('form_action').name = 'edit_branch';
            document.getElementById('branch_id').value = branch.id;
            
            // Populate fields
            const form = document.getElementById('branchForm');
            form.elements['name'].value = branch.name;
            form.elements['city'].value = branch.city;
            form.elements['address'].value = branch.address;
            form.elements['phone'].value = branch.phone;
            
            document.getElementById('branchModal').style.display = 'flex';
        }

        // Add specific listener for openModal to reset if it's "Add New"
        function openBranchModal() {
            document.getElementById('modalTitle').innerText = 'Add New Branch';
            document.getElementById('submitBtn').innerText = 'Create Branch';
            document.getElementById('form_action').name = 'add_branch';
            document.getElementById('branch_id').value = '';
            document.getElementById('branchForm').reset();
            document.getElementById('branchModal').style.display = 'flex';
        }
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

