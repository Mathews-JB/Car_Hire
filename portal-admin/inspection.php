<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'agent'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'pickup'; // pickup or return

if (!$id) {
    die("Booking ID required.");
}

// Fetch booking & vehicle data
$stmt = $pdo->prepare("
    SELECT b.*, u.name as customer_name, v.make, v.model, v.plate_number, v.current_mileage, v.vin
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$b = $stmt->fetch();

if (!$b) {
    die("Booking not found.");
}

// Check if inspection already exists for this type
$stmt = $pdo->prepare("SELECT * FROM vehicle_inspections WHERE booking_id = ? AND inspection_type = ?");
$stmt->execute([$id, $type]);
$existing = $stmt->fetch();

if ($existing) {
    $mode = 'view';
} else {
    $mode = 'edit';
}

if (isset($_POST['save_inspection'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $mileage = $_POST['mileage'];
    $fuel = $_POST['fuel_level'];
    $body = $_POST['body_condition'];
    $interior = $_POST['interior_condition'];
    $tire = $_POST['tire_condition'];
    $spare = isset($_POST['spare_tire']) ? 1 : 0;
    $jack = isset($_POST['jack_tool']) ? 1 : 0;

    $pdo->beginTransaction();
    try {
        // Save inspection
        $stmt = $pdo->prepare("INSERT INTO vehicle_inspections 
            (booking_id, inspection_type, inspector_id, mileage, fuel_level, body_condition, interior_condition, tire_condition, spare_tire_exists, jack_tool_exists)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $type, $_SESSION['user_id'], $mileage, $fuel, $body, $interior, $tire, $spare, $jack]);

        // Update vehicle state
        $stmt = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ?");
        $stmt->execute([$mileage, $b['vehicle_id']]);

        // If it's a return, update booking status to completed
        if ($type === 'return') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
            $stmt->execute([$b['vehicle_id']]);
        }

        $pdo->commit();
        
        // Log the action
        log_action($pdo, "Completed " . $type . " inspection", "Booking #" . $id . " for vehicle " . $b['make'] . " " . $b['model'] . " (" . $b['plate_number'] . ")", "Fleet");

        $redirect = ($_SESSION['user_role'] === 'admin') ? "booking-details.php?id=$id&msg=inspected" : "reservation-details.php?id=$id&msg=inspected";
        header("Location: $redirect");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($type); ?> Inspection - #<?php echo $id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
</head>
<body style="min-height: 100vh; background: #0f172a;">

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <?php 
                            $back_url = ($_SESSION['user_role'] === 'admin') ? "booking-details.php?id=$id" : "reservation-details.php?id=$id";
                        ?>
                        <a href="<?php echo $back_url; ?>" class="btn btn-outline btn-sm" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fas fa-arrow-left"></i></a>
                        <h1 style="margin: 0; font-size: 1.5rem;"><?php echo ucfirst($type); ?> Checklist</h1>
                    </div>
                    <p class="text-secondary"><?php echo $b['make'] . ' ' . $b['model']; ?> — Inspection for Booking #<?php echo $id; ?></p>
                </div>
            </div>

            <div class="data-card">
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 25px;">
                    <div>
                        <span class="info-label">Vehicle Identity</span>
                        <h2 style="margin: 5px 0 0; color: white;"><?php echo $b['plate_number']; ?></h2>
                        <small style="opacity: 0.5; font-family: monospace;">VIN: <?php echo $b['vin']; ?></small>
                    </div>
                    <div style="text-align: right;">
                        <span class="info-label">Customer Name</span>
                        <div style="font-weight: 700; color: white; font-size: 1.1rem;"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                    </div>
                </div>

                <?php if ($mode === 'view'): ?>
                    <div class="inspection-results">
                        <div class="grid-2">
                            <div class="info-item">
                                <label class="info-label">Mileage Recorded</label>
                                <div class="info-value" style="font-size: 1.5rem; font-weight: 800;"><?php echo number_format($existing['mileage']); ?> KM</div>
                            </div>
                            <div class="info-item">
                                <label class="info-label">Fuel Level</label>
                                <div class="info-value" style="font-size: 1.5rem; font-weight: 800;"><?php echo $existing['fuel_level']; ?></div>
                            </div>
                        </div>

                        <div class="grid-2 mt-4" style="margin-top: 30px;">
                            <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                                <h4 style="margin: 0 0 10px; font-size: 0.8rem; text-transform: uppercase; color: var(--accent-color);">Body Condition</h4>
                                <p style="margin: 0; opacity: 0.8;"><?php echo nl2br(htmlspecialchars($existing['body_condition'])); ?></p>
                            </div>
                            <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                                <h4 style="margin: 0 0 10px; font-size: 0.8rem; text-transform: uppercase; color: var(--accent-color);">Interior & Interior</h4>
                                <p style="margin: 0; opacity: 0.8;"><?php echo nl2br(htmlspecialchars($existing['interior_condition'])); ?></p>
                            </div>
                        </div>

                        <div style="margin-top: 30px; display: flex; gap: 30px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
                                <i class="fas <?php echo $existing['spare_tire_exists'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                Spare Tire <?php echo $existing['spare_tire_exists'] ? 'Present' : 'Missing'; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
                                <i class="fas <?php echo $existing['jack_tool_exists'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                Jack / Tools <?php echo $existing['jack_tool_exists'] ? 'Present' : 'Missing'; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="grid-2" style="margin-bottom: 30px;">
                            <div class="form-group">
                                <label class="info-label">Odometer Reading (KM)</label>
                                <input type="number" name="mileage" value="<?php echo $b['current_mileage']; ?>" required class="form-control" style="font-size: 1.5rem; font-weight: 800; padding: 20px;">
                            </div>
                            <div class="form-group">
                                <label class="info-label">Estimated Fuel Level</label>
                                <select name="fuel_level" required class="form-control" style="height: 65px;">
                                    <option value="Full">Full</option>
                                    <option value="3/4">3/4</option>
                                    <option value="1/2">1/2</option>
                                    <option value="1/4">1/4</option>
                                    <option value="Empty">Empty (Reserve)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label class="info-label">Exeterior Body Condition (Dents, Scratches, Glass)</label>
                            <textarea name="body_condition" rows="3" class="form-control" placeholder="Describe any exterior physical damage..."></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label class="info-label">Interior & Component Condition</label>
                            <textarea name="interior_condition" rows="3" class="form-control" placeholder="Describe state of seats, AC, gadgets..."></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 30px;">
                            <label class="info-label">Tire Condition</label>
                            <input type="text" name="tire_condition" class="form-control" placeholder="e.g. Good tread depth on all 4 tires...">
                        </div>

                        <div style="display: flex; gap: 30px; margin-bottom: 40px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; font-weight: 600;">
                                <input type="checkbox" name="spare_tire" checked style="width: 20px; height: 20px;"> Spare Tire Available
                            </label>
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; font-weight: 600;">
                                <input type="checkbox" name="jack_tool" checked style="width: 20px; height: 20px;"> Jack & Tool-kit Available
                            </label>
                        </div>

                        <button type="submit" name="save_inspection" class="btn btn-primary" style="width: 100%; height: 60px; font-weight: 800; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-save" style="margin-right: 10px;"></i> Complete <?php echo $type; ?> Inspection
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
