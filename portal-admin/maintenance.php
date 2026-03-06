<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle New Maintenance Log Submission
if (isset($_POST['add_log'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $service_type = $_POST['service_type'];
    $service_date = $_POST['service_date'];
    $mileage = $_POST['mileage_at_service'];
    $cost = $_POST['cost'];
    $description = $_POST['description'];
    $next_service_km = $_POST['next_service_km'];

    $pdo->beginTransaction();
    try {
        // 1. Insert into maintenance_logs
        $stmt = $pdo->prepare("INSERT INTO maintenance_logs (vehicle_id, service_type, service_date, mileage_at_service, cost, description, next_service_km) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vehicle_id, $service_type, $service_date, $mileage, $cost, $description, $next_service_km]);

        // 2. Update Vehicle's current mileage and next service KM
        $stmt = $pdo->prepare("UPDATE vehicles SET current_mileage = ?, service_due_km = ? WHERE id = ?");
        $stmt->execute([$mileage, $next_service_km, $vehicle_id]);

        $pdo->commit();
        $success = "Maintenance log added and vehicle records updated.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error adding log: " . $e->getMessage();
    }
}

// Fetch Maintenance Logs
$stmt = $pdo->query("
    SELECT m.*, v.make, v.model, v.plate_number 
    FROM maintenance_logs m 
    JOIN vehicles v ON m.vehicle_id = v.id 
    ORDER BY m.service_date DESC
");
$logs = $stmt->fetchAll();

// Fetch All Vehicles for the dropdown
$stmt = $pdo->query("SELECT id, make, model, plate_number, current_mileage FROM vehicles ORDER BY make ASC");
$vehicles = $stmt->fetchAll();

// Summary Stats
$stmt = $pdo->query("SELECT SUM(cost) FROM maintenance_logs");
$total_maintenance_cost = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE current_mileage >= (service_due_km - 500)");
$pending_services_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Hub | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .service-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-routine { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-repair { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-emergency { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .summary-card { padding: 25px; display: flex; flex-direction: column; justify-content: center; }
        .summary-card h4 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin: 0 0 10px; font-weight: 800; }
        .summary-value { font-size: 1.6rem; font-weight: 800; color: white; line-height: 1; margin-bottom: 8px; }
        .summary-card small { font-size: 0.75rem; opacity: 0.5; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Maintenance Hub</h1>
                    <p class="text-secondary">Track fleet health, service history, and operational costs.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="toggleModal('logModal')"><i class="fas fa-plus"></i> <span class="hide-mobile">Log Service</span><span class="show-mobile">New Log</span></button>
                    <button class="btn btn-outline"><i class="fas fa-file-export"></i> CSV Report</button>
                </div>
            </div>

            <?php if($success || $error): ?>
            <div class="status-pill <?php echo $success ? 'status-confirmed' : 'status-cancelled'; ?>" style="margin-bottom: 25px; width: 100%; text-transform: none; justify-content: flex-start; padding: 15px 20px;">
                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>" style="margin-right: 12px;"></i>
                <span><?php echo $success ?: $error; ?></span>
            </div>
            <?php endif; ?>

            <div class="grid-3" style="margin-bottom: 30px;">
                <div class="data-card summary-card" style="border-top: 3px solid #10b981;">
                    <h4>Fleet Spend</h4>
                    <p class="summary-value">ZMW <?php echo number_format($total_maintenance_cost, 0); ?></p>
                    <small>Total Service Investment</small>
                </div>
                <div class="data-card summary-card" style="border-top: 3px solid <?php echo $pending_services_count > 0 ? '#f59e0b' : '#10b981'; ?>;">
                    <h4>Services Due</h4>
                    <p class="summary-value" style="color:<?php echo $pending_services_count > 0 ? '#f59e0b' : '#10b981'; ?>;">
                        <?php echo $pending_services_count; ?> <span style="font-size: 1rem; opacity: 0.7;">Vehicles</span>
                    </p>
                    <small>Interval Threshold < 500KM</small>
                </div>
                <div class="data-card summary-card" style="border-top: 3px solid #60a5fa;">
                    <h4>Health Index</h4>
                    <p class="summary-value" style="color:#60a5fa;">94%</p>
                    <small>Operational Status</small>
                </div>
            </div>

            <div class="data-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <h3 style="margin: 0; color: white;">Service History</h3>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-outline btn-sm"><i class="fas fa-filter"></i></button>
                        <button class="btn btn-outline btn-sm"><i class="fas fa-file-export"></i></button>
                    </div>
                </div>
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Service Type</th>
                            <th>Date</th>
                            <th>Odometer</th>
                            <th>Cost</th>
                            <th>Next Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td data-label="Vehicle">
                                <div style="font-weight: 700; color: white;"><?php echo $log['make'] . ' ' . $log['model']; ?></div>
                                <small style="opacity:0.5; font-family: monospace;"><?php echo $log['plate_number']; ?></small>
                            </td>
                            <td data-label="Type">
                                <span class="service-badge <?php echo strpos(strtolower($log['service_type']), 'oil') !== false ? 'badge-routine' : 'badge-repair'; ?>">
                                    <?php echo htmlspecialchars($log['service_type']); ?>
                                </span>
                            </td>
                            <td data-label="Date" style="white-space: nowrap;"><?php echo date('d M, Y', strtotime($log['service_date'])); ?></td>
                            <td data-label="Odometer"><?php echo number_format($log['mileage_at_service']); ?> KM</td>
                            <td data-label="Cost" class="font-bold" style="color: #10b981;">ZMW <?php echo number_format($log['cost'], 2); ?></td>
                            <td data-label="Next Service">
                                <div style="font-size: 0.85rem; color: white; font-weight: 700;"><?php echo number_format($log['next_service_km']); ?> KM</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:60px; opacity:0.3;">
                                <i class="fas fa-tools" style="font-size:2.5rem; display:block; margin-bottom:15px;"></i>
                                No maintenance logs found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Log Maintenance Modal -->
    <div id="logModal" class="modal-overlay">
        <div class="modal-content data-card" style="max-width: 600px; width: 95%;">
            <span class="close-modal" onclick="toggleModal('logModal')">&times;</span>
            <div style="margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h2 style="margin: 0; color: white; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-wrench" style="color: var(--accent-color);"></i> Log New Service
                </h2>
            </div>
            <form action="" method="POST">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); text-transform: uppercase;">Select Vehicle</label>
                    <select name="vehicle_id" class="form-control" required onchange="updateOdometer(this)">
                        <option value="">Choose a vehicle...</option>
                        <?php foreach($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" data-odo="<?php echo $v['current_mileage']; ?>">
                                <?php echo $v['make'] . ' ' . $v['model'] . ' (' . $v['plate_number'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); text-transform: uppercase;">Service Type</label>
                        <select name="service_type" class="form-control" required>
                            <option value="Routine Service">Routine Service</option>
                            <option value="Oil & Filter Change">Oil & Filter Change</option>
                            <option value="Tire Replacement">Tire Replacement</option>
                            <option value="Brake Pad Service">Brake Pad Service</option>
                            <option value="Engine Repair">Engine Repair</option>
                            <option value="Suspension Work">Suspension Work</option>
                            <option value="Body Work / Painting">Body Work / Painting</option>
                            <option value="Electrical Repair">Electrical Repair</option>
                            <option value="Wheel Alignment">Wheel Alignment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); text-transform: uppercase;">Service Date</label>
                        <input type="date" name="service_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); text-transform: uppercase;">Odometer (KM)</label>
                        <input type="number" id="odo_input" name="mileage_at_service" class="form-control" placeholder="Current KM" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); text-transform: uppercase;">Cost (ZMW)</label>
                        <input type="number" step="0.01" name="cost" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); text-transform: uppercase;">Next Service at (KM)</label>
                    <input type="number" id="next_odo" name="next_service_km" class="form-control" placeholder="Planned KM for next service" required>
                    <small style="opacity:0.5; display: block; margin-top: 5px;">Usually current KM + 5,000 for routine service.</small>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); text-transform: uppercase;">Work Description / Notes</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Describe the work done..."></textarea>
                </div>

                <button type="submit" name="add_log" class="btn btn-primary" style="width: 100%; height: 50px;">Save Log & Update Vehicle</button>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
        }

        function updateOdometer(select) {
            const odoInput = document.getElementById('odo_input');
            const nextOdo = document.getElementById('next_odo');
            const selectedOption = select.options[select.selectedIndex];
            const currentOdo = selectedOption.getAttribute('data-odo');
            
            if (currentOdo) {
                odoInput.value = currentOdo;
                nextOdo.value = parseInt(currentOdo) + 5000;
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }

        // Auto-trigger if action=log is in URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const vehicleId = urlParams.get('vehicle_id');
            const action = urlParams.get('action');

            if (vehicleId && action === 'log') {
                const select = document.querySelector('select[name="vehicle_id"]');
                if (select) {
                    select.value = vehicleId;
                    updateOdometer(select);
                    toggleModal('logModal');
                }
            }
        });
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
