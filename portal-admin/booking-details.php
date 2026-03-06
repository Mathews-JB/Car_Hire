<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($id)) {
    header("Location: bookings.php");
    exit;
}

// Fetch booking details with vehicle metadata
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, 
                              v.make, v.model, v.status as vehicle_status, v.image_url, v.plate_number, v.vin, v.year as vehicle_year
                       FROM bookings b 
                       JOIN users u ON b.user_id = u.id 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       WHERE b.id = ?");
$stmt->execute([$id]);
$booking = $stmt->fetch();

// Fetch Contract Info (graceful fallback if table doesn't exist yet)
$contract = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE booking_id = ?");
    $stmt->execute([$id]);
    $contract = $stmt->fetch();
} catch (PDOException $e) {
    // Table may not exist yet — ignore
}

// Fetch Payment Info (graceful fallback if table doesn't exist yet)
$payment = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();
} catch (PDOException $e) {
    // Table may not exist yet — ignore
}

if (!$booking) {
    header("Location: bookings.php");
    exit;
}

// Handle Contract Upload
if (isset($_POST['upload_contract']) && isset($_FILES['signed_pdf'])) {
    $target_dir = "../uploads/contracts/";
    $file_ext = pathinfo($_FILES["signed_pdf"]["name"], PATHINFO_EXTENSION);
    $file_name = "Contract_" . $id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["signed_pdf"]["tmp_name"], $target_file)) {
        $stmt = $pdo->prepare("INSERT INTO contracts (booking_id, contract_pdf_path, is_signed, signed_at) 
                               VALUES (?, ?, TRUE, NOW()) 
                               ON DUPLICATE KEY UPDATE contract_pdf_path = ?, is_signed = TRUE, signed_at = NOW()");
        $stmt->execute([$id, 'uploads/contracts/' . $file_name, 'uploads/contracts/' . $file_name]);
        header("Location: booking-details.php?id=$id&msg=uploaded");
        exit;
    } else {
        $error = "Failed to upload file.";
    }
}

$success = '';
$error = '';

if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);

        $v_status = 'available';
        if ($new_status === 'confirmed') $v_status = 'hired';
        elseif ($new_status === 'completed') $v_status = 'available';
        elseif ($new_status === 'cancelled') $v_status = 'available';

        $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
        $stmt->execute([$v_status, $booking['vehicle_id']]);

        // Trigger Loyalty Update if completed
        if ($new_status === 'completed') {
            updateUserLoyalty($pdo, $booking['user_id']);
        }

        $pdo->commit();

        // Send WhatsApp notification on completion
        if ($new_status === 'completed') {
            try {
                include_once '../includes/whatsapp.php';
                $wa = new WhatsAppService();
                $wa->send($booking['customer_phone'], 
                    "✅ *Rental Complete – Car Hire*\n\nHi {$booking['customer_name']}, your rental of the {$booking['make']} {$booking['model']} has been marked as completed. Thank you for choosing Car Hire! We hope to see you again soon. 🚗"
                );
            } catch (Exception $wa_err) {
                error_log("WhatsApp completion notification failed: " . $wa_err->getMessage());
            }
        }

        header("Location: booking-details.php?id=$id&msg=updated");
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
    <title>Booking #<?php echo $id; ?> | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .details-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 30px; }
        .info-item { margin-bottom: 25px; }
        .info-label { color: var(--accent-color); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; font-weight: 700; opacity: 0.8; }
        .info-value { font-size: 1.1rem; font-weight: 600; color: white; }
        
        @media (max-width: 1100px) {
            .details-grid { grid-template-columns: 1fr; }
            .rental-summary-header { flex-direction: column; align-items: flex-start !important; }
            .rental-summary-header img { width: 100% !important; height: 200px !important; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <a href="bookings.php" class="btn btn-outline btn-sm" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fas fa-arrow-left"></i></a>
                        <h1 style="margin: 0; font-size: 1.5rem;">Booking #<?php echo $id; ?></h1>
                    </div>
                    <span class="status-pill status-<?php echo $booking['status']; ?>" style="text-transform: uppercase; font-weight: 800; font-size: 0.75rem; padding: 6px 15px;">
                        <?php echo $booking['status']; ?>
                    </span>
                </div>
                <div class="header-actions">
                    <a href="contract-viewer.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary"><i class="fas fa-file-contract"></i> View Legal Agreement</a>
                </div>
            </div>

            <?php include_once '../includes/toast_notifications.php'; ?>
            
            <?php if(isset($_GET['msg'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const msg = <?php echo json_encode($_GET['msg']); ?>;
                        if (msg === 'updated') showToast('Status updated successfully.', 'success');
                        if (msg === 'uploaded') showToast('Signed contract uploaded successfully.', 'success');
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

            <div class="details-grid">
                <div class="main-info">
                    <div class="data-card">
                        <h3 style="margin-bottom: 25px; color: white; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-user-circle" style="color: var(--accent-color);"></i> Customer Information
                        </h3>
                        <div class="grid-2">
                            <div class="info-item">
                                <span class="info-label">Full Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email Address</span>
                                <span class="info-value" style="font-size: 1rem;"><?php echo htmlspecialchars($booking['customer_email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone Number</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['customer_phone'] ?: 'No Phone Provided'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="data-card">
                        <h3 style="margin-bottom: 25px; color: white; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-car" style="color: var(--accent-color);"></i> Rental Details
                        </h3>
                        <div class="rental-summary-header" style="display: flex; gap: 25px; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.05); align-items: center;">
                            <img src="<?php echo !empty($booking['image_url']) ? '../' . $booking['image_url'] : 'https://via.placeholder.com/200x120?text=Car'; ?>" style="width: 180px; height: 110px; object-fit: cover; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                            <div>
                                <h2 style="margin: 0 0 8px; font-size: 1.4rem; color: white;"><?php echo $booking['make'] . ' ' . $booking['model']; ?></h2>
                                <span style="background: rgba(37, 99, 235, 0.1); color: var(--accent-color); padding: 5px 12px; border-radius: 6px; font-weight: 800; font-size: 1.1rem;">ZMW <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="info-item">
                                <span class="info-label">Pickup</span>
                                <span class="info-value" style="font-size: 1rem;"><?php echo $booking['pickup_location']; ?></span>
                                <small style="display:block; opacity: 0.5; margin-top: 5px;"><?php echo date('d M Y, h:i A', strtotime($booking['pickup_date'])); ?></small>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Drop-off</span>
                                <span class="info-value" style="font-size: 1rem;"><?php echo $booking['dropoff_location']; ?></span>
                                <small style="display:block; opacity: 0.5; margin-top: 5px;"><?php echo date('d M Y, h:i A', strtotime($booking['dropoff_date'])); ?></small>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Plate Number</span>
                                <span class="info-value"><?php echo $booking['plate_number'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">VIN / Chassis</span>
                                <span class="info-value" style="font-size: 0.9rem; font-family: monospace;"><?php echo $booking['vin'] ?: 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sidebar-info">
                    <div class="data-card" style="border-top: 4px solid var(--accent-color);">
                        <h3 style="margin-bottom: 20px; color: white;">Actions</h3>
                        <form action="" method="POST">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="info-label">Set Booking Status</label>
                                <select name="status" class="form-control" style="width: 100%;">
                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirm (Check-out)</option>
                                    <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Complete (Returned)</option>
                                    <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancel Booking</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%; height: 50px;">Update Booking Status</button>
                        </form>
                    </div>

                    <div class="data-card">
                        <h4 style="color: white; margin-bottom: 20px;"><i class="fas fa-file-contract" style="color: var(--accent-color);"></i> Legal Documents</h4>
                        <div style="margin-top: 15px;">
                            <a href="contract-viewer.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-outline" style="width: 100%; margin-bottom: 15px;">
                                <i class="fas fa-print"></i> Generate Agreement
                            </a>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                                <a href="inspection.php?id=<?php echo $id; ?>&type=pickup" class="btn btn-outline btn-sm" style="text-align:center;">
                                    <?php 
                                        $stmt = $pdo->prepare("SELECT id FROM vehicle_inspections WHERE booking_id = ? AND inspection_type = 'pickup'");
                                        $stmt->execute([$id]);
                                        echo $stmt->fetch() ? 'View Handover' : 'Start Handover';
                                    ?>
                                </a>
                                <a href="inspection.php?id=<?php echo $id; ?>&type=return" class="btn btn-outline btn-sm" style="text-align:center; border-color: #10b981; color: #10b981;">
                                    <?php 
                                        $stmt = $pdo->prepare("SELECT id FROM vehicle_inspections WHERE booking_id = ? AND inspection_type = 'return'");
                                        $stmt->execute([$id]);
                                        echo $stmt->fetch() ? 'View Return' : 'Start Return';
                                    ?>
                                </a>
                            </div>
                            
                            <?php if ($contract && $contract['is_signed']): ?>
                                <div style="padding: 15px; background: rgba(16, 185, 129, 0.05); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.1);">
                                    <span style="color: #10b981; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; display: block; margin-bottom: 5px;">
                                        <i class="fas fa-check-double"></i> Signed Contract Uploaded
                                    </span>
                                    <a href="../<?php echo $contract['contract_pdf_path']; ?>" target="_blank" style="font-size: 0.85rem; color: #60a5fa; text-decoration: underline;">View Signed PDF</a>
                                </div>
                            <?php else: ?>
                                <form action="" method="POST" enctype="multipart/form-data" style="margin-top: 20px;" onsubmit="return validateFileSize()">
                                    <label class="info-label" style="font-size: 0.7rem;">Upload Signed Copy (PDF)</label>
                                    <input type="file" name="signed_pdf" id="signed_pdf" accept=".pdf" required style="width: 100%; margin-bottom: 10px; font-size: 0.8rem;">
                                    <p id="size_warning" style="color: #ef4444; font-size: 0.75rem; display: none; margin-bottom: 10px;"></p>
                                    <button type="submit" name="upload_contract" class="btn btn-outline btn-sm" style="width: 100%;">Upload Document</button>
                                </form>
                                <script>
                                    function validateFileSize() {
                                        const fileInput = document.getElementById('signed_pdf');
                                        const warning = document.getElementById('size_warning');
                                        if (fileInput.files.length > 0) {
                                            const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                                            if (fileSize > 5) {
                                                warning.textContent = "Error: File size (" + fileSize.toFixed(2) + "MB) exceeds 5MB limit.";
                                                warning.style.display = 'block';
                                                return false;
                                            }
                                        }
                                        return true;
                                    }
                                </script>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="data-card" style="border-top: 4px solid <?php echo $payment ? '#10b981' : '#f59e0b'; ?>;">
                        <h4 style="color: white; margin-bottom: 20px;"><i class="fas fa-credit-card" style="color: <?php echo $payment ? '#10b981' : '#f59e0b'; ?>;"></i> Payment Record</h4>
                        <?php if ($payment): ?>
                            <div style="display: grid; gap: 15px;">
                                <div>
                                    <span class="info-label">Status</span>
                                    <span class="status-pill status-confirmed" style="font-size: 0.75rem; padding: 4px 12px;"><?php echo htmlspecialchars($payment['status']); ?></span>
                                </div>
                                <div>
                                    <span class="info-label">Amount Paid</span>
                                    <span class="info-value" style="color: #10b981; font-size: 1.2rem;">ZMW <?php echo number_format($payment['amount'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="info-label">Transaction ID</span>
                                    <span style="font-family: monospace; font-size: 0.8rem; color: rgba(255,255,255,0.4); word-break: break-all;"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                                </div>
                                <div>
                                    <span class="info-label">Payment Date</span>
                                    <span class="info-value" style="font-size: 0.9rem; opacity: 0.8;"><?php echo date('d M Y, H:i', strtotime($payment['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; background: rgba(245,158,11,0.05); border-radius: 12px; border: 1px dashed rgba(245,158,11,0.2);">
                                <i class="fas fa-clock" style="font-size: 1.5rem; color: #f59e0b; margin-bottom: 10px; display: block;"></i>
                                <p style="font-size: 0.85rem; color: rgba(255,255,255,0.5); margin: 0;">No payment received yet.</p>
                                <?php if ($booking['status'] === 'pending'): ?>
                                    <a href="../portal-customer/payment.php?booking_id=<?php echo $id; ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top: 15px; border-color: #f59e0b; color: #f59e0b; width: 100%;">
                                        <i class="fas fa-external-link-alt"></i> View Payment Page
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="data-card">
                        <h4 style="color: white; margin-bottom: 15px;"><i class="fas fa-info-circle" style="color: var(--accent-color);"></i> Admin Note</h4>
                        <p style="font-size: 0.85rem; opacity: 0.6; line-height: 1.6; margin: 0;">
                            Updating to <strong style="color:white;">'Confirmed'</strong> marks the vehicle as 'Hired'. 
                            Marking <strong style="color:white;">'Completed'</strong> sends an automated WhatsApp notification to the customer.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
