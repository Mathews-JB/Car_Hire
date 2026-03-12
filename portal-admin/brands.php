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

// Handle brand addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name']);
    if (empty($name)) {
        $error = "Brand name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
        if ($stmt->execute([$name])) {
            $success = "Brand added successfully!";
        } else {
            $error = "Failed to add brand.";
        }
    }
}

// Handle brand deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
    try {
        if ($stmt->execute([$id])) {
            $success = "Brand deleted successfully!";
        } else {
            $error = "Failed to delete brand.";
        }
    } catch (PDOException $e) {
        $error = "Cannot delete: This brand is currently associated with vehicles in your fleet.";
    }
}

$brands = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM vehicles WHERE brand_id = b.id) as vehicle_count FROM brands b ORDER BY b.name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand Management | Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Brand Management</h1>
                    <p class="text-secondary">Curate manufacturers for your worldwide fleet inventory.</p>
                </div>
                <div class="header-actions">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <button class="btn btn-outline" onclick="window.location.reload()"><i class="fas fa-sync"></i> Refresh</button>
                </div>
            </div>

            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if ($success): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($success); ?>', 'success');
                    });
                </script>
            <?php endif; ?>

            <div class="data-card" style="margin-bottom: 30px;">
                <h3 style="color: white; font-weight: 800; margin-bottom: 20px; font-size: 1.1rem;"><i class="fas fa-plus-circle" style="color: #3b82f6; margin-right: 10px;"></i> Register Manufacturer</h3>
                <form action="brands.php" method="POST" class="header-actions" style="margin-top: 0;">
                    <input type="text" name="name" placeholder="Brand Name (e.g. Lamborghini)" required class="form-control" style="flex: 2; margin-bottom: 0;">
                    <button type="submit" name="add_brand" class="btn btn-primary" style="flex: 1;"><i class="fas fa-plus"></i> Create Brand</button>
                </form>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding: 0 5px;">
                <h3 style="color: white; font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7;">Collection Catalogue</h3>
                <span style="color: rgba(255,255,255,0.4); font-size: 0.75rem; font-weight: 600;"><?php echo count($brands); ?> Manufacturers</span>
            </div>

            <div class="grid-2">
                <?php foreach ($brands as $brand): ?>
                    <div class="data-card" style="display: flex; justify-content: space-between; align-items: center; padding: 20px;">
                        <div>
                            <h4 style="color: white; font-size: 1.1rem; font-weight: 700; margin-bottom: 5px;"><?php echo htmlspecialchars($brand['name']); ?></h4>
                            <div style="display: flex; align-items: center; gap: 8px; color: rgba(255, 255, 255, 0.4); font-size: 0.75rem;">
                                <i class="fas fa-car-side" style="color: #3b82f6;"></i>
                                <span><?php echo $brand['vehicle_count']; ?> Vehicles</span>
                            </div>
                        </div>
                        <a href="brands.php?delete=<?php echo $brand['id']; ?>" 
                           class="btn btn-outline btn-sm text-danger"
                           style="border-color: rgba(239, 68, 68, 0.2); width: 40px; height: 40px; justify-content: center; padding: 0;"
                           onclick="return confirm('Delete this brand?')"
                           title="Delete Brand">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

