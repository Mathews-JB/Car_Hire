<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $v_id = $_POST['vehicle_id'];
    $new_status = $_POST['action'] === 'set_maintenance' ? 'maintenance' : 'available';
    $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $v_id]);
    header("Location: fleet.php?updated=1");
    exit;
}

$stmt = $pdo->query("SELECT * FROM vehicles ORDER BY make ASC");
$vehicles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Management | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
</head>
<body>

    <div class="agent-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>

        <main class="main-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                <h1>Fleet Management</h1>
                <a href="add-vehicle.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Vehicle</a>
            </div>

            <?php if(isset($_GET['updated'])): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                Full vehicle status updated successfully.
            </div>
            <?php endif; ?>

            <div class="data-card">
                <table class="data-table admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 15px; text-align: left;">Vehicle</th>
                            <th style="padding: 15px; text-align: left;">Year</th>
                            <th style="padding: 15px; text-align: left;">Capacity</th>
                            <th style="padding: 15px; text-align: left;">Price/Day</th>
                            <th style="padding: 15px; text-align: left;">Status</th>
                            <th style="padding: 15px; text-align: left;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vehicles as $v): ?>
                        <tr>
                            <td data-label="Vehicle"><strong><?php echo $v['make'] . ' ' . $v['model']; ?></strong></td>
                            <td data-label="Year"><?php echo $v['year']; ?></td>
                            <td data-label="Capacity"><?php echo $v['capacity']; ?> Seats</td>
                            <td data-label="Price/Day">ZMW <?php echo number_format($v['price_per_day'], 2); ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo $v['status']; ?>">
                                    <?php echo $v['status']; ?>
                                </span>
                            </td>
                            <td data-label="Action">
                                <div style="display: flex; gap: 8px;">
                                    <a href="edit-vehicle.php?id=<?php echo $v['id']; ?>" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.8rem;">Edit</a>
                                    
                                    <?php if($v['status'] !== 'maintenance'): ?>
                                        <form method="POST" action="fleet.php" style="display:inline;" onsubmit="return confirm('Set vehicle to maintenance mode?');">
                                            <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                                            <input type="hidden" name="action" value="set_maintenance">
                                            <button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.8rem; color: #dc3545; border-color: #dc3545;">Maint.</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="fleet.php" style="display:inline;" onsubmit="return confirm('Set vehicle to available?');">
                                            <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                                            <input type="hidden" name="action" value="set_available">
                                            <button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.8rem; color: #10b981; border-color: #10b981;">Active</button>
                                        </form>
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

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
