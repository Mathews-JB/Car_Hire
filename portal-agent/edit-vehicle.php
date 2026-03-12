<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : '';
if (!$id) {
    header("Location: fleet.php");
    exit;
}

// Fetch vehicle
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die("Vehicle not found.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $plate_number = $_POST['plate_number'];
    $capacity = $_POST['capacity'];
    $price_per_day = $_POST['price_per_day'];
    $status = $_POST['status'];

    $sql = "UPDATE vehicles SET make = ?, model = ?, year = ?, plate_number = ?, capacity = ?, price_per_day = ?, status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$make, $model, $year, $plate_number, $capacity, $price_per_day, $status, $id]);

    header("Location: fleet.php?success=Vehicle updated");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vehicle | Agent Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="agent-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>
        
        <main class="main-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                <h1>Edit Vehicle</h1>
                <a href="fleet.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
            </div>

            <div class="data-card" style="max-width: 600px;">
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Make</label>
                        <input type="text" name="make" class="form-control" value="<?php echo htmlspecialchars($vehicle['make']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Model</label>
                        <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Year</label>
                        <input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($vehicle['year']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Plate Number</label>
                        <input type="text" name="plate_number" class="form-control" value="<?php echo htmlspecialchars($vehicle['plate_number']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="<?php echo htmlspecialchars($vehicle['capacity']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Price Per Day (ZMW)</label>
                        <input type="number" step="0.01" name="price_per_day" class="form-control" value="<?php echo htmlspecialchars($vehicle['price_per_day']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Status</label>
                        <select name="status" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                            <option value="available" <?php echo $vehicle['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="hired" <?php echo $vehicle['status'] == 'hired' ? 'selected' : ''; ?>>Hired</option>
                            <option value="maintenance" <?php echo $vehicle['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Vehicle</button>
                </form>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

