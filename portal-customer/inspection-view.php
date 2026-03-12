<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die("Booking ID required.");

$user_id = $_SESSION['user_id'];

// Verify booking belongs to user
$stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
if (!$stmt->fetch()) die("Access denied.");

// Fetch Inspections (Pickup first)
$stmt = $pdo->prepare("SELECT * FROM vehicle_inspections WHERE booking_id = ? ORDER BY inspection_type DESC");
$stmt->execute([$id]);
$inspections = $stmt->fetchAll();

// Fetch Vehicle Info
$stmt = $pdo->prepare("SELECT v.* FROM vehicles v JOIN bookings b ON v.id = b.vehicle_id WHERE b.id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Report | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .insp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 30px; }
        .insp-card { background: rgba(30,30,35,0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 30px; }
        .damage-canvas { width: 100%; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); margin-top: 15px; }
        body { 
            background: transparent !important;
        }
    </style>
</head>
<body class="stabilized-car-bg">
    <div class="portal-content">
        <div class="container">
            <div class="dashboard-header">
                <a href="my-bookings.php" class="btn btn-outline" style="border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-arrow-left"></i></a>
                <h1>Vehicle Condition Report</h1>
                <p style="color:var(--accent-color); font-weight:700;">Booking #<?php echo $id; ?> - <?php echo $vehicle['make'].' '.$vehicle['model']; ?></p>
            </div>

            <?php if (empty($inspections)): ?>
                <div class="data-card" style="text-align:center; padding:50px !important;">
                    <i class="fas fa-clipboard-list" style="font-size:3rem; opacity:0.2; margin-bottom:15px;"></i>
                    <p>Handover inspection hasn't been conducted yet.</p>
                </div>
            <?php else: ?>
                <div class="insp-grid">
                    <?php foreach($inspections as $insp): ?>
                        <div class="insp-card">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                <h3 style="margin:0; text-transform:uppercase; letter-spacing:1px;"><?php echo $insp['inspection_type']; ?> Report</h3>
                                <span class="status-pill status-confirmed"><?php echo date('d M, Y', strtotime($insp['inspection_date'])); ?></span>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                                <div>
                                    <small style="opacity:0.5; text-transform:uppercase; font-size:0.65rem;">Odometer</small>
                                    <div style="font-weight:700; font-size:1.1rem;"><?php echo number_format($insp['mileage']); ?> KM</div>
                                </div>
                                <div>
                                    <small style="opacity:0.5; text-transform:uppercase; font-size:0.65rem;">Fuel Level</small>
                                    <div style="font-weight:700; font-size:1.1rem;"><?php echo $insp['fuel_level']; ?> / 8</div>
                                </div>
                            </div>

                            <div style="margin-bottom:20px;">
                                <small style="opacity:0.5; text-transform:uppercase; font-size:0.65rem;">Internal Checklist</small>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:8px;">
                                    <?php 
                                        $checklist = json_decode($insp['checklist'], true) ?: [];
                                        foreach($checklist as $item => $status):
                                    ?>
                                        <div style="font-size:0.85rem; display:flex; align-items:center; gap:8px;">
                                            <i class="fas fa-<?php echo $status ? 'check-circle' : 'times-circle'; ?>" style="color:<?php echo $status ? '#10b981' : '#ef4444'; ?>;"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $item)); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if(!empty($insp['notes'])): ?>
                            <div style="padding:15px; background:rgba(255,255,255,0.05); border-radius:12px; margin-bottom:20px;">
                                <small style="display:block; opacity:0.5; margin-bottom:5px;">Staff Notes:</small>
                                <p style="margin:0; font-size:0.9rem; font-style:italic;"><?php echo htmlspecialchars($insp['notes']); ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if(!empty($insp['damage_image_path'])): ?>
                                <label style="opacity:0.5; text-transform:uppercase; font-size:0.65rem;">Marked Damage Chart</label>
                                <img src="../<?php echo $insp['damage_image_path']; ?>" class="damage-canvas">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
