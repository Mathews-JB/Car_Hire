<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: reservations.php");
    exit;
}

// Fetch booking details
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, v.make, v.model, v.status as vehicle_status 
                       FROM bookings b 
                       JOIN users u ON b.user_id = u.id 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       WHERE b.id = ?");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: reservations.php");
    exit;
}

// Handle Status Updates (Check-in / Check-out)
if (isset($_POST['update_status'])) {
    verify_csrf_token($_POST['csrf_token']);
    $new_status = $_POST['status'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);

        // If checking out (confirmed), mark vehicle as hired
        // If checking in (completed), mark vehicle as available
        $v_status = 'available';
        if ($new_status === 'confirmed') {
            $v_status = 'hired';
        } elseif ($new_status === 'completed') {
            $v_status = 'available';
        }

        $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
        $stmt->execute([$v_status, $booking['vehicle_id']]);

        $pdo->commit();
        header("Location: reservation-details.php?id=" . $id . "&success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating status: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .details-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; }
        .info-card { 
            background: rgba(30, 30, 35, 0.6); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 20px; 
            padding: 25px; 
            margin-bottom: 30px; 
            color: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .detail-item { margin-bottom: 20px; }
        .label { color: var(--accent-color); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; font-weight: 700; }
        .value { font-size: 1.1rem; font-weight: 600; color: #f8fafc; }
        .rental-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>

    <div class="agent-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>

        <main class="main-content">
            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if(isset($_GET['success'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('Reservation updated successfully!', 'success');
                    });
                </script>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showToast('<?php echo addslashes($error); ?>', 'error');
                    });
                </script>
            <?php endif; ?>

            <?php if(isset($_GET['msg'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const msg = <?php echo json_encode($_GET['msg']); ?>;
                        if (msg === 'inspected') showToast('Vehicle inspection records updated.', 'success');
                    });
                </script>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                <h1>Reservation #<?php echo $id; ?></h1>
                <a href="reservations.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>

            <div class="details-grid">
                <div class="details-main">
                    <div class="info-card">
                        <h3 style="margin-bottom: 20px;"><i class="fas fa-user-circle"></i> Customer Information</h3>
                        <div class="rental-grid">
                            <div class="detail-item">
                                <span class="label">Name</span>
                                <span class="value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="label">Email</span>
                                <span class="value"><?php echo htmlspecialchars($booking['customer_email']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="label">Phone</span>
                                <span class="value"><?php echo htmlspecialchars($booking['customer_phone'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3 style="margin-bottom: 20px;"><i class="fas fa-car"></i> Rental Details</h3>
                        <div class="rental-grid">
                            <div class="detail-item">
                                <span class="label">Vehicle</span>
                                <span class="value"><?php echo $booking['make'] . ' ' . $booking['model']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Total Price</span>
                                <span class="value">ZMW <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Pickup Location</span>
                                <span class="value"><?php echo $booking['pickup_location']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Pickup Date</span>
                                <span class="value"><?php echo date('d M Y', strtotime($booking['pickup_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="details-sidebar">
                    <div class="info-card" style="border-top: 4px solid var(--primary-color);">
                        <h3>Workflow Status</h3>
                        
                        <div style="margin-top: 20px; display: grid; gap: 15px;">
                            <a href="../portal-admin/inspection.php?id=<?php echo $id; ?>&type=pickup" class="btn btn-outline" style="width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-clipboard-check"></i> Handover Inspection</span>
                                <?php 
                                    $stmt = $pdo->prepare("SELECT id FROM vehicle_inspections WHERE booking_id = ? AND inspection_type = 'pickup'");
                                    $stmt->execute([$id]);
                                    echo $stmt->fetch() ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-chevron-right opacity-50"></i>';
                                ?>
                            </a>

                            <a href="../portal-admin/inspection.php?id=<?php echo $id; ?>&type=return" class="btn btn-outline" style="width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-undo"></i> Return Inspection</span>
                                <?php 
                                    $stmt = $pdo->prepare("SELECT id FROM vehicle_inspections WHERE booking_id = ? AND inspection_type = 'return'");
                                    $stmt->execute([$id]);
                                    echo $stmt->fetch() ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-chevron-right opacity-50"></i>';
                                ?>
                            </a>
                        </div>

                        <hr style="margin: 25px 0; border: 0; border-top: 1px solid rgba(255,255,255,0.05);">

                        <h3>Update Status</h3>
                        <p class="mb-3 text-secondary" style="font-size: 0.85rem;">Lifecycle: Pending -> Handover (Confirmed) -> Return (Completed)</p>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="label">Current Status</label>
                                <select name="status" class="form-control" style="background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; width: 100%;">
                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Check-out (Confirmed)</option>
                                    <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Check-in (Completed)</option>
                                    <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary btn-block" style="width: 100%; padding: 15px; border-radius: 10px; font-weight: 700;">Save Lifecycle State</button>
                        </form>
                    </div>

                    <?php if ($booking['status'] === 'confirmed'): ?>
                    <div class="info-card" style="border-top: 4px solid var(--danger);">
                        <h3>Vehicle Check-in</h3>
                        <p class="mb-3 text-secondary">Log any mechanical or cosmetic issues found during return.</p>
                        <form action="" method="POST">
                            <textarea name="damage_desc" placeholder="Describe damage..." class="form-control mb-3" rows="3"></textarea>
                            <input type="number" name="damage_cost" placeholder="Estimated repair cost (ZMW)" class="form-control mb-3">
                            <button type="submit" name="log_damage" class="btn btn-outline btn-block text-danger w-100">Log Damage & Billed</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <h3>Extra Charges/Add-ons</h3>
                        <p style="font-size: 0.85rem; margin-bottom: 15px;">Apply manual fees (Late return, fuel, etc.)</p>
                        <form action="" method="POST">
                            <input type="text" name="fee_desc" placeholder="Reason (e.g. Late Return)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 10px;">
                            <input type="number" name="fee_amount" placeholder="Amount (ZMW)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px;">
                            <button type="submit" name="apply_fee" class="btn btn-outline btn-block" style="width: 100%;">Apply Fee</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
