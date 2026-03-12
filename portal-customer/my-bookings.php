<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Cancellation
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    // Verify booking belongs to user and is pending
    $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$cancel_id, $user_id]);
    $booking = $stmt->fetch();

    if ($booking && $booking['status'] === 'pending') {
        $update = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $update->execute([$cancel_id]);
        header("Location: my-bookings.php?msg=cancelled&id=$cancel_id");
        exit;
    } else {
        $error = "Cannot cancel this booking. It may not exist or is already processed.";
    }
}

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'cancelled') {
        $success = "Booking #" . ($_GET['id'] ?? '') . " has been cancelled successfully.";
    } elseif ($_GET['msg'] === 'cleared') {
        $success = "Cancelled bookings history cleared.";
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'db_error') {
    $error = "A database error occurred while trying to clear the history. Please try again.";
}



// Fetch user bookings with add-ons
$stmt = $pdo->prepare("SELECT b.*, v.make, v.model, v.image_url, v.year, v.capacity, v.price_per_day,
                       GROUP_CONCAT(ao.name SEPARATOR ', ') as addons_list
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       LEFT JOIN booking_add_ons bao ON b.id = bao.booking_id
                       LEFT JOIN add_ons ao ON bao.add_on_id = ao.id
                       WHERE b.user_id = ? 
                       GROUP BY b.id
                       ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// Calculate Stats for "My Activity" section
$active_rentals = 0;
$total_spent = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'confirmed' || $b['status'] === 'active') $active_rentals++;
    if ($b['status'] !== 'cancelled') $total_spent += $b['total_price'];
}
$total_bookings = count($bookings);

// Membership Tier Logic
$membership_tier = 'Member';
if ($total_spent > 50000) $membership_tier = 'Black Diamond';
elseif ($total_spent > 20000) $membership_tier = 'Platinum';
elseif ($total_spent > 5000) $membership_tier = 'Gold';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css?v=2.1">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        body { 
            background: transparent !important;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column !important;
                text-align: center !important;
                gap: 15px !important;
                margin-bottom: 20px !important;
            }
            .dashboard-header h1 {
                font-size: 1.5rem !important;
                margin-bottom: 5px !important;
            }
            .dashboard-header p {
                font-size: 0.8rem !important;
                margin-bottom: 0 !important;
            }
            .dashboard-header div[style*="display: flex"] {
                flex-direction: column !important;
                width: 100% !important;
                gap: 10px !important;
            }
            .dashboard-header .btn {
                width: 100% !important;
                padding: 10px !important;
                font-size: 0.85rem !important;
            }
            .table-container {
                padding: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
            }
            .data-table, .data-table tbody {
                display: block !important;
            }
            .data-table tbody {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 12px !important;
            }
            .data-table thead {
                display: none !important;
            }
            .data-table tbody tr {
                display: flex !important;
                flex-direction: column !important;
                background: rgba(30, 30, 35, 0.6) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 16px !important;
                margin-bottom: 0 !important;
                padding: 10px !important;
                overflow: hidden !important;
            }
            .data-table td {
                display: block !important;
                padding: 2px 0 !important;
                border: none !important;
                text-align: left !important;
            }
            .data-table td::before {
                display: none !important;
            }
            .data-table td[data-label="Dates"] div {
                font-size: 0.75rem !important;
                display: inline-block;
            }
            .data-table td[data-label="Amount"] div {
                font-size: 0.85rem !important;
            }
            .vehicle-mini {
                width: 100% !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 5px !important;
                margin-bottom: 0 !important;
                background: transparent !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
                padding-bottom: 4px !important;
            }
            .vehicle-mini img {
                width: 100% !important;
                height: 85px !important;
                border-radius: 8px !important;
                object-fit: cover !important;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
            }
            .vehicle-mini div {
                text-align: left !important;
                flex: 1;
                width: 100%;
            }
            .vehicle-mini strong {
                font-size: 0.85rem !important;
                display: block !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .vehicle-mini small {
                font-size: 0.6rem !important;
            }
            .status-badge {
                padding: 3px 8px !important;
                font-size: 0.55rem !important;
                border-radius: 4px !important;
            }
            .booking-row td[data-label="Action"] {
                padding: 6px 0 0 !important;
                margin-top: auto; /* Push buttons to the bottom! */
                border-top: 1px solid rgba(255,255,255,0.05);
                width: 100%;
            }
            .booking-row td[data-label="Action"] div {
                flex-direction: column !important;
                justify-content: center !important;
                width: 100% !important;
                gap: 5px !important;
            }
            .booking-row td[data-label="Action"] .btn {
                flex: 1 !important;
                padding: 6px !important;
                width: 100% !important;
                font-size: 0.65rem !important;
                border-radius: 8px !important;
            }
            .modal-content {
                width: 95% !important;
            }
            #modal-vehicle-name {
                font-size: 1.3rem !important;
            }
        }
    </style>
</head>
<body class="stabilized-car-bg">

    <?php include_once '../includes/mobile_header.php'; ?>

    <nav class="hub-bar">
        <a href="../index.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <a href="browse-vehicles.php"><?php echo __('browse_fleet'); ?></a>
            <a href="my-bookings.php" class="active"><?php echo __('my_bookings'); ?></a>
            <a href="support.php"><?php echo __('support'); ?></a>
            <a href="profile.php"><?php echo __('profile'); ?></a>
        </div>
        <div class="hub-user">
            <!-- Theme Switcher -->
            <?php include_once '../includes/theme_switcher.php'; ?>
            
            <?php 
                $display_name = $_SESSION['user_name'] ?? 'User';
                $first_name = explode(' ', $display_name)[0];
                $initial = !empty($display_name) ? strtoupper($display_name[0]) : 'U';
            ?>
            <span class="hub-user-name"><?php echo htmlspecialchars($first_name); ?></span>
            <div class="hub-avatar"><?php echo htmlspecialchars($initial); ?></div>
            <a href="../logout.php" style="color: var(--danger); margin-left: 10px; font-size: 0.85rem;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="portal-content">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1><?php echo __('my_bookings'); ?></h1>
                    <p>Your rental history and upcoming journeys.</p>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <?php 
                        $hasCancelled = false;
                        foreach($bookings as $b) { if($b['status'] === 'cancelled') { $hasCancelled = true; break; } }
                        if($hasCancelled):
                    ?>
                        <a href="clear-cancelled.php" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger);" onclick="return confirm('Remove all cancelled bookings from your history?');">
                            <i class="fas fa-trash-alt"></i> Clear Cancelled
                        </a>
                    <?php endif; ?>
                    <a href="browse-vehicles.php" class="btn btn-primary"><i class="fas fa-plus"></i> <?php echo __('new_booking'); ?></a>
                </div>
            </div> <!-- Close dashboard-header -->

            <div class="how-it-works-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-top: 30px; margin-bottom: 40px;">
                <div class="data-card how-it-works-step" style="border-left: 4px solid var(--success) !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon">
                            <i class="fas fa-car-side" style="font-size: 1.5rem; color: var(--success);"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo $active_rentals; ?></h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;"><?php echo __('active_rentals'); ?></p>
                    </div>
                </div>

                <div class="data-card how-it-works-step" style="border-left: 4px solid var(--accent-color) !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon">
                            <i class="fas fa-calendar-check" style="font-size: 1.5rem; color: var(--accent-color);"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo $total_bookings; ?></h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;"><?php echo __('total_bookings'); ?></p>
                    </div>
                </div>

                <div class="data-card how-it-works-step" style="border-left: 4px solid #3b82f6 !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon">
                            <i class="fas fa-receipt" style="font-size: 1.5rem; color: #3b82f6;"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;">ZMW <?php echo number_format($total_spent, 0); ?></h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;"><?php echo __('total_spent'); ?></p>
                    </div>
                </div>

                <div class="data-card how-it-works-step" style="border-left: 4px solid #ffd700 !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon">
                            <i class="fas fa-award" style="font-size: 1.5rem; color: #ffd700;"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo $membership_tier; ?></h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;"><?php echo __('member_status'); ?></p>
                    </div>
                </div>
            </div>

            <?php if($success): ?>
                <div class="form-feedback success" style="margin-bottom: 25px; padding: 15px; background: rgba(16,185,129,0.1); border: 1px solid var(--success); border-radius: 12px; color: var(--success);">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="form-feedback error" style="margin-bottom: 25px; padding: 15px; background: rgba(239,68,68,0.1); border: 1px solid var(--danger); border-radius: 12px; color: var(--danger);">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (count($bookings) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Dates</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr class="booking-row">
                                    <td data-label="Vehicle">
                                        <div class="vehicle-mini">
                                            <img src="<?php echo !empty($b['image_url']) ? '../' . $b['image_url'] : 'https://via.placeholder.com/100x60'; ?>">
                                            <div>
                                                <strong><?php echo htmlspecialchars($b['make'] . ' ' . $b['model']); ?></strong>
                                                <small>ID: #<?php echo str_pad($b['id'], 5, '0', STR_PAD_LEFT); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Dates">
                                        <div style="font-size: 0.9rem;">
                                            <div style="font-weight: 600;"><?php echo date('d M Y', strtotime($b['pickup_date'])); ?></div>
                                            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6);">to <?php echo date('d M Y', strtotime($b['dropoff_date'])); ?></div>
                                        </div>
                                    </td>
                                    <td data-label="Amount">
                                        <div style="font-weight: 700;">ZMW <?php echo number_format($b['total_price'], 2); ?></div>
                                    </td>
                                    <td data-label="Status">
                                        <?php if($b['status'] === 'confirmed'): ?>
                                            <span class="status-badge" style="background: #10b981; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-check-circle"></i> APP
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo $b['status']; ?>">
                                                <?php echo ucfirst($b['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Action">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                                <a href="payment.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.75rem;">Pay Now</a>
                                                <a href="my-bookings.php?cancel_id=<?php echo $b['id']; ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.75rem; color: var(--danger); border-color: var(--danger);" onclick="return confirm('Are you sure?');">Cancel</a>
                                            </div>
                                        <?php elseif ($b['status'] === 'confirmed' || $b['status'] === 'completed'): ?>
                                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.75rem;" 
                                                        onclick='viewBookingDetails(<?php echo json_encode($b); ?>)'>
                                                    <i class="fas fa-file-invoice"></i> Details
                                                </button>
                                                <?php if($b['status'] === 'confirmed'): ?>
                                                    <a href="track-vehicle.php" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.75rem; background: #3b82f6;">
                                                        <i class="fas fa-map-marker-alt"></i> Track Live
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="opacity: 0.3;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="data-card" style="text-align: center; padding: 60px !important;">
                    <i class="fas fa-calendar-times" style="font-size: 4rem; color: rgba(255,255,255,0.1); margin-bottom: 20px;"></i>
                    <h2>No Bookings Yet</h2>
                    <p style="opacity: 0.6; margin-bottom: 30px;">Your car hire history will appear here once you make a booking.</p>
                    <a href="browse-vehicles.php" class="btn btn-primary">Browse Fleet</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>

    <!-- Booking Details Modal -->
    <div id="bookingDetailsModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(8,12,23,0.85); backdrop-filter:blur(10px); z-index:2000; align-items:center; justify-content:center; padding: 20px;">
        <div class="modal-content" style="background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:24px; width:90%; max-width:550px; padding:0; overflow:hidden; max-height: 90vh; overflow-y: auto;">
            <div id="modal-header-img" style="height:110px; background-size:cover; background-position:center; position:relative;">
                <div style="position:absolute; inset:0; background:linear-gradient(to bottom, transparent, #0f172a);"></div>
                <button onclick="closeModal()" style="position:absolute; top:15px; right:15px; background:rgba(0,0,0,0.5); border:none; color:white; width:32px; height:32px; border-radius:50%; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            
            <div style="padding:20px 25px; margin-top:-30px; position:relative;">
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:15px;">
                    <div>
                        <h2 id="modal-vehicle-name" style="font-size:1.5rem; margin:0;">Vehicle Name</h2>
                        <p id="modal-booking-id" style="color:var(--accent-color); font-weight:700; margin-top:2px; font-size:0.8rem;">BOOKING #000</p>
                    </div>
                    <span id="modal-status" class="status-badge" style="font-size:0.7rem; padding:4px 8px;">Status</span>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                    <div class="info-group">
                        <label style="display:block; font-size:0.65rem; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:1px; margin-bottom:3px;">Pickup Details</label>
                        <div id="modal-pickup" style="font-size:0.85rem;">Location & Date</div>
                    </div>
                    <div class="info-group">
                        <label style="display:block; font-size:0.65rem; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:1px; margin-bottom:3px;">Dropoff Details</label>
                        <div id="modal-dropoff" style="font-size:0.85rem;">Location & Date</div>
                    </div>
                </div>

                <div style="background:rgba(255,255,255,0.03); border-radius:12px; padding:15px; margin-bottom:20px;">
                    <label style="display:block; font-size:0.65rem; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">Cost Breakdown</label>
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.85rem;">
                        <span>Rental Rate</span>
                        <span id="modal-rate">ZMW 0/day</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.85rem;">
                        <span>Add-ons</span>
                        <span id="modal-addons-text" style="font-size:0.8rem; opacity:0.7; max-width:60%;">None</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:10px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.1); font-weight:700; font-size:1.1rem;">
                        <span>Total Paid</span>
                        <span id="modal-total" style="color:var(--accent-color);">ZMW 0</span>
                    </div>
                </div>

                <!-- Live Track Preview -->
                <div id="modal-track-preview" style="display:none; margin-bottom:20px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 5px;">
                        <label style="font-size:0.7rem; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:1px;"><i class="fas fa-satellite"></i> Live Tracking</label>
                        <span style="font-size:0.65rem; color:#10b981; font-weight:700;"><i class="fas fa-circle" style="font-size:6px; animation: pulse 2s infinite;"></i> ONLINE</span>
                    </div>
                    <div id="miniMap" style="height:120px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;"></div>
                    <div style="margin-top: 8px; display:flex; justify-content: space-between; font-size: 0.75rem; color: #64748b;">
                        <span>Ref: #<span id="modal-plate-val">—</span></span>
                        <span style="font-weight: 700; color: #1e293b;"><span id="modal-speed-val">0</span> km/h</span>
                    </div>
                </div>

                <div style="display:flex; gap:12px;">
                    <a id="modal-download-link" href="#" target="_blank" class="btn btn-primary" style="flex:1; padding:10px; text-decoration:none; text-align:center; font-size:0.85rem;">
                        <i class="fas fa-file-pdf"></i> Receipt
                    </a>
                    <a id="modal-track-link" href="#" class="btn btn-primary" style="flex:1; padding:10px; text-decoration:none; text-align:center; background:#3b82f6; display:none; font-size:0.85rem;">
                        <i class="fas fa-expand"></i> Full Map
                    </a>
                    <button class="btn btn-outline" style="flex:1; padding:10px; font-size:0.85rem;" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    let miniMap = null;
    let miniMarker = null;
    let trackInterval = null;

    function viewBookingDetails(data) {
        document.getElementById('modal-header-img').style.backgroundImage = `url(../${data.image_url})`;
        document.getElementById('modal-vehicle-name').innerText = `${data.make} ${data.model}`;
        document.getElementById('modal-booking-id').innerText = `BOOKING #${data.id}`;
        
        const statusEl = document.getElementById('modal-status');
        statusEl.innerText = data.status;
        statusEl.className = `status-badge status-${data.status}`;
        
        document.getElementById('modal-pickup').innerHTML = `<strong>${data.pickup_location}</strong><br>${new Date(data.pickup_date).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' })}`;
        document.getElementById('modal-dropoff').innerHTML = `<strong>${data.dropoff_location}</strong><br>${new Date(data.dropoff_date).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' })}`;
        
        document.getElementById('modal-rate').innerText = `ZMW ${parseFloat(data.price_per_day).toLocaleString()}/day`;
        document.getElementById('modal-addons-text').innerText = data.addons_list || 'No extra add-ons';
        document.getElementById('modal-total').innerText = `ZMW ${parseFloat(data.total_price).toLocaleString()}`;
        
        // Update download link
        document.getElementById('modal-download-link').href = `receipt.php?booking_id=${data.id}&print=true`;
        
        // Track & Inspection Links
        const trackLink = document.getElementById('modal-track-link');
        const inspLink = document.getElementById('modal-inspection-link'); // Note: This might be redundant now but keeping for safety if it existed before. Actually I replaced it in the HTML but let's check.
        
        if (data.status === 'confirmed') {
            trackLink.href = 'track-vehicle.php';
            trackLink.style.display = 'block';
            document.getElementById('modal-track-preview').style.display = 'block';
            initMiniMap(data);
        } else {
            trackLink.style.display = 'none';
            document.getElementById('modal-track-preview').style.display = 'none';
        }
        
        document.getElementById('bookingDetailsModal').style.display = 'flex';
    }

    function initMiniMap(data) {
        if (!miniMap) {
            miniMap = L.map('miniMap', { zoomControl:false, attributionControl:false }).setView([data.last_lat || -15.4167, data.last_lng || 28.2833], 15);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(miniMap);
        } else {
            miniMap.setView([data.last_lat || -15.4167, data.last_lng || 28.2833], 15);
        }

        if (miniMarker) miniMap.removeLayer(miniMarker);
        miniMarker = L.marker([data.last_lat || -15.4167, data.last_lng || 28.2833], {
            icon: L.divIcon({
                className: 'mini-car',
                html: `<div style="transform: rotate(${data.bearing || 0}deg); filter: drop-shadow(0 3px 6px rgba(0,0,0,0.3));">
                    <img src="https://img.icons8.com/3d-fluency/94/car-top-view.png" style="width: 35px; height: 35px; transform: rotate(90deg);">
                </div>`,
                iconSize: [35, 35],
                iconAnchor: [17.5, 17.5]
            })
        }).addTo(miniMap);

        // Start polling
        if (trackInterval) clearInterval(trackInterval);
        trackInterval = setInterval(async () => {
            try {
                const res = await fetch(`../api/vehicle-gps.php?booking_id=${data.id}`);
                const json = await res.json();
                if (json.success) {
                    const v = json.data.vehicle;
                    miniMarker.setLatLng([v.lat, v.lng]);
                    miniMarker.setOpacity(1); 
                    miniMap.panTo([v.lat, v.lng]);
                    document.getElementById('modal-speed-val').innerText = Math.round(v.speed);
                }
            } catch(e) {}
        }, 3000);
        
        setTimeout(() => miniMap.invalidateSize(), 200);
    }

    function closeModal() {
        document.getElementById('bookingDetailsModal').style.display = 'none';
        if (trackInterval) clearInterval(trackInterval);
    }

    // Close on outside click
    window.onclick = function(event) {
        let modal = document.getElementById('bookingDetailsModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
