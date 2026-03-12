<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch Hired Vehicles
$stmt = $pdo->query("
    SELECT v.*, b.id as booking_id, u.name as customer_name, b.pickup_date, b.dropoff_date
    FROM vehicles v
    JOIN bookings b ON v.id = b.vehicle_id
    JOIN users u ON b.user_id = u.id
    WHERE v.status = 'hired' AND b.status = 'confirmed'
    ORDER BY b.dropoff_date ASC
");
$hired_vehicles = $stmt->fetchAll();

// Count for overview
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM vehicles GROUP BY status");
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hired Vehicles Monitoring | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .plate-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            letter-spacing: 1px;
            font-size: 0.8rem;
        }

        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .trip-progress {
            position: relative;
            height: 6px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            margin: 15px 0 8px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
            position: relative;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .summary-card { padding: 25px; display: flex; flex-direction: column; justify-content: center; }
        .summary-card h4 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin: 0 0 10px; font-weight: 800; }
        .summary-value { font-size: 1.6rem; font-weight: 800; color: white; line-height: 1; margin: 0; }
        .summary-card small { font-size: 0.75rem; opacity: 0.5; font-weight: 600; }

        /* Monitoring-specific layout */
        .monitoring-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
            margin-top: 10px;
        }

        .monitor-card {
            background: rgba(30, 30, 35, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .monitor-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .monitor-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: linear-gradient(to bottom, rgba(255,255,255,0.03), transparent);
        }

        .monitor-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            margin-top: -10px;
            mask-image: linear-gradient(to bottom, black 80%, transparent 100%);
        }

        .monitor-body { padding: 20px; }

        .customer-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .status-badge-live {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            border: 1px solid rgba(16, 185, 129, 0.2);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge-overdue {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .action-footer {
            padding: 15px 20px;
            background: rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .time-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
            margin-top: 8px;
        }
        
        @media (max-width: 768px) {
            .monitoring-grid { grid-template-columns: 1fr; }
            .monitor-image { height: 140px !important; }
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
                    <h1>Hired Vehicles</h1>
                    <p class="text-secondary">Live monitoring of vehicles currently on active trips.</p>
                </div>
            </div>

            <!-- Enhanced Summary Cards -->
            <div class="grid-3" style="margin-bottom: 30px;">
                <div class="data-card summary-card" style="border-top: 3px solid #60a5fa;">
                    <h4>On Trip</h4>
                    <p class="summary-value"><?php echo $stats['hired'] ?? 0; ?></p>
                    <small>Active Rental Contracts</small>
                </div>
                <div class="data-card summary-card" style="border-top: 3px solid #fbbf24;">
                    <h4>Reserved</h4>
                    <p class="summary-value"><?php echo $stats['booked'] ?? 0; ?></p>
                    <small>Upcoming Pickups</small>
                </div>
                <div class="data-card summary-card" style="border-top: 3px solid #10b981;">
                    <h4>Available</h4>
                    <p class="summary-value"><?php echo $stats['available'] ?? 0; ?></p>
                    <small>Ready for Rental</small>
                </div>
            </div>

            <?php if(count($hired_vehicles) > 0): ?>
            <div class="monitoring-grid">
                <?php foreach($hired_vehicles as $v): 
                    $start = strtotime($v['pickup_date']);
                    $end = strtotime($v['dropoff_date']);
                    $now = time();
                    $total_duration = $end - $start;
                    $elapsed = $now - $start;
                    $percent = min(100, max(0, ($elapsed / $total_duration) * 100));
                    $is_overdue = $now > $end;
                ?>
                <div class="monitor-card">
                    <div class="monitor-header">
                        <div>
                            <h3 style="margin:0; font-size:1.1rem;"><?php echo $v['make'] . ' ' . $v['model']; ?></h3>
                            <span class="plate-badge" style="display:inline-block; margin-top:5px;"><?php echo $v['plate_number']; ?></span>
                        </div>
                        <?php if($is_overdue): ?>
                            <div class="status-badge-overdue"><i class="fas fa-exclamation-circle"></i> OVERDUE</div>
                        <?php else: ?>
                            <div class="status-badge-live">
                                <span class="pulse" style="width:6px; height:6px; background:#10b981; border-radius:50%; display:inline-block; animation:pulse 2s infinite;"></span>
                                ON TRIP
                            </div>
                        <?php endif; ?>
                    </div>

                    <img src="../<?php echo !empty($v['image_url']) ? $v['image_url'] : 'public/images/cars/prado.jpg'; ?>" class="monitor-image" alt="Vehicle">

                    <div class="monitor-body">
                        <div class="customer-row">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($v['customer_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size:0.95rem;"><?php echo htmlspecialchars($v['customer_name']); ?></h4>
                                <small style="color:rgba(255,255,255,0.5);">Booking #<?php echo $v['booking_id']; ?></small>
                            </div>
                        </div>
                        <div style="margin: 15px 0; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 5px;">
                                <span style="font-size:0.75rem; color:rgba(255,255,255,0.5); font-weight:700; text-transform:uppercase; letter-spacing:1px;">Live Location</span>
                                <span style="font-size:0.75rem; color:#60a5fa; font-weight:700;">GPS Active</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <i class="fas fa-satellite" style="color:#60a5fa; font-size:0.8rem;"></i>
                                <span style="font-size:0.85rem; font-family: monospace; color:#f8fafc; font-weight:600;">
                                    <?php echo number_format($v['latitude'], 6); ?>, <?php echo number_format($v['longitude'], 6); ?>
                                </span>
                            </div>
                        </div>

                        <div style="margin-top: 10px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:600;">
                                <span>Trip Progress</span>
                                <span><?php echo $is_overdue ? '100% (Late)' : round($percent).'%'; ?></span>
                            </div>
                            <div class="trip-progress">
                                <div class="progress-bar" style="width: <?php echo $percent; ?>%; background: <?php echo $is_overdue ? '#ef4444' : '#10b981'; ?>"></div>
                            </div>
                            <div class="time-labels">
                                <span><i class="fas fa-map-marker-alt"></i> Out: <?php echo date('M d, H:i', $start); ?></span>
                                <span><i class="fas fa-flag-checkered"></i> In: <?php echo date('M d, H:i', $end); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="action-footer">
                        <div style="display: flex; gap: 10px; width: 100%;">
                            <a href="inspection.php?id=<?php echo $v['booking_id']; ?>&type=return" class="btn btn-outline" style="font-size: 0.75rem; padding: 6px 12px; border-color: #10b981; color: #10b981; border-radius: 8px;">
                                Return Inspection
                            </a>
                            <a href="booking-details.php?id=<?php echo $v['booking_id']; ?>" style="color:white; font-size:0.85rem; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:5px; transition:opacity 0.2s; margin-left: auto;">
                                Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state" style="text-align:center; padding: 100px 0; opacity:0.6;">
                <div style="background:rgba(255,255,255,0.05); width:100px; height:100px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 30px;">
                    <i class="fas fa-tasks" style="font-size: 3rem; color:rgba(255,255,255,0.3);"></i>
                </div>
                <h2 style="font-size:1.5rem; margin-bottom:10px;">No Active Trips</h2>
                <p>All vehicles are currently parked at the yard.</p>
            </div>
            <?php endif; ?>

        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

