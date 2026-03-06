<?php
ob_start();
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $current_url = $_SERVER['REQUEST_URI'];
    header("Location: ../login.php?msg=not_logged_in&return_url=" . urlencode($current_url));
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT verification_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_status = $stmt->fetchColumn();

if ($user_status !== 'approved') {
    header("Location: verify-profile.php?msg=verification_required");
    exit;
}

$vehicle_id = isset($_GET['vehicle_id']) ? $_GET['vehicle_id'] : '';
$pickup_date = isset($_GET['pickup_date']) ? $_GET['pickup_date'] : '';
$pickup_time = isset($_GET['pickup_time']) ? $_GET['pickup_time'] : '';
$dropoff_date = isset($_GET['dropoff_date']) ? $_GET['dropoff_date'] : '';
$dropoff_time = isset($_GET['dropoff_time']) ? $_GET['dropoff_time'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';

// Combine date and time if they are separate
if ($pickup_time && strpos($pickup_date, ':') === false) {
    $pickup_date .= " " . $pickup_time;
}
if ($dropoff_time && strpos($dropoff_date, ':') === false) {
    $dropoff_date .= " " . $dropoff_time;
}

if (empty($vehicle_id) || empty($pickup_date) || empty($dropoff_date)) {
    header("Location: dashboard.php");
    exit;
}

// Fetch vehicle details
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicle_id]);
$car = $stmt->fetch();

if (!$car) {
    header("Location: dashboard.php");
    exit;
}

// Calculate base total price
$d1 = new DateTime($pickup_date);
$d2 = new DateTime($dropoff_date);
$diff = $d1->diff($d2);
$days = $diff->days > 0 ? $diff->days : 1;
$base_price = $days * $car['price_per_day'];

// Fetch available add-ons
$stmt = $pdo->query("SELECT * FROM add_ons");
$add_ons = $stmt->fetchAll();

$total_price = $base_price;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process Booking
    $user_id = $_SESSION['user_id'];
    $selected_addons = isset($_POST['addons']) ? $_POST['addons'] : [];
    $promo_code = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : '';
    
    // Recalculate total with add-ons
    $total_price = $base_price;
    foreach ($add_ons as $addon) {
        if (in_array($addon['id'], $selected_addons)) {
            $total_price += ($addon['price_per_day'] * $days);
        }
    }

    // Apply Voucher
    $voucher_id = null;
    $discount_amount = 0;
    if ($promo_code) {
        $u_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $u_stmt->execute([$user_id]);
        $u_email = $u_stmt->fetchColumn(); 

        $res = validateVoucher($pdo, $promo_code, $total_price, $u_email);
        if ($res['valid']) {
            $v = $res['data'];
            $voucher_id = $v['id'];
            if ($v['discount_type'] === 'percentage') {
                $discount_amount = $total_price * ($v['discount_value'] / 100);
            } else {
                $discount_amount = $v['discount_value'];
            }
            $total_price -= $discount_amount;
        } else {
            $error = $res['msg'];
        }
    }

    if (empty($error)) {
        $is_available = checkAvailability($pdo, $vehicle_id, $pickup_date, $dropoff_date);
        if (!$is_available) {
            $error = 'Sorry, this vehicle is no longer available for the selected dates.';
            error_log("Booking failed: Vehicle ID $vehicle_id is not available for $pickup_date to $dropoff_date");
        }
    }

    if (empty($error)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, vehicle_id, pickup_location, dropoff_location, pickup_date, dropoff_date, total_price, discount_amount, voucher_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $vehicle_id, $location, $location, $pickup_date, $dropoff_date, $total_price, $discount_amount, $voucher_id]);
            $booking_id = $pdo->lastInsertId();

            if ($voucher_id) {
                $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?")->execute([$voucher_id]);
            }

            // Insert selected add-ons
            foreach ($selected_addons as $aid) {
                $stmt = $pdo->prepare("INSERT INTO booking_add_ons (booking_id, add_on_id) VALUES (?, ?)");
                $stmt->execute([$booking_id, $aid]);
            }

            $pdo->commit();

            // --- Send Confirmation Email ---
            include_once '../includes/mailer.php';
            try {
                // Fetch user email
                $u_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                $u_stmt->execute([$user_id]);
                $user_info = $u_stmt->fetch();

                if ($user_info && !empty($user_info['email'])) {
                    $mailer = new CarHireMailer();
                    $subject = "Booking Confirmation - Ref: #" . $booking_id;
                    $message = "
                    <h2>Booking Confirmed!</h2>
                    <p>Dear " . htmlspecialchars($user_info['name']) . ",</p>
                    <p>Thank you for choosing Car Hire. Your reservation has been successfully placed.</p>";

                    if ($discount_amount > 0) {
                        $message .= "
                        <div style='background: #ecfdf5; border: 2px dashed #10b981; padding: 20px; border-radius: 12px; margin: 25px 0; text-align: center;'>
                            <h3 style='margin: 0; color: #047857; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px;'>🎉 You Scored a Deal! 🎉</h3>
                            <p style='margin: 8px 0 0; color: #064e3b; font-size: 15px;'>Your exclusive promo code was successfully applied.</p>
                            <div style='margin-top: 15px; font-size: 24px; font-weight: 800; color: #059669;'>
                                SAVED ZMW " . number_format($discount_amount, 2) . "
                            </div>
                        </div>
                        ";
                    }

                    $message .= "
                    <h3>Booking Details:</h3>
                    <ul>
                        <li><strong>Reference:</strong> #{$booking_id}</li>
                        <li><strong>Vehicle:</strong> {$car['make']} {$car['model']}</li>
                        <li><strong>Pickup:</strong> " . date('d M Y, H:i', strtotime($pickup_date)) . "</li>
                        <li><strong>Dropoff:</strong> " . date('d M Y, H:i', strtotime($dropoff_date)) . "</li>
                        <li><strong>Total Price:</strong> <span style='font-size: 1.2em; font-weight: bold; color: #2563eb;'>ZMW " . number_format($total_price, 2) . "</span></li>
                    </ul>

                    <div style='background: #e0f2fe; padding: 20px; border-radius: 8px; text-align: center; border: 1px dashed #0284c7; margin: 30px 0;'>
                        <h3 style='margin-top: 0; color: #0284c7; font-size: 16px;'>📄 Invoice</h3>
                        <p style='margin-bottom: 20px; font-size: 14px; color: #334155;'>A downloadable Invoice (PDF) has been generated by the system for your records.</p>
                        <a href='" . APP_URL . "portal-customer/view_invoice.php?booking_id={$booking_id}' style='display: inline-block; padding: 12px 24px; background: #0284c7; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Download Invoice</a>
                    </div>

                    <div style='background: #dcfce7; padding: 20px; border-radius: 8px; text-align: center; border: 1px dashed #10b981; margin: 30px 0;'>
                        <h3 style='margin-top: 0; color: #10b981; font-size: 16px;'>📋 Rental Agreement</h3>
                        <p style='margin-bottom: 20px; font-size: 14px; color: #334155;'>Your official Rental Agreement is ready for review and download.</p>
                        <a href='" . APP_URL . "portal-customer/view_agreement.php?booking_id={$booking_id}' style='display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Download Agreement</a>
                    </div>

                    <p>Please proceed to payment via the portal or at the branch.</p>
                    <p>Safe Travels,<br>Car Hire Team</p>
                    ";

                    // Use default template = true for nice styling wrappers
                    $mailer->send($user_info['email'], $subject, $message, null, 'Car Hire Reservations', true);
                }
                // Send SMS + WhatsApp
                $u_phone_stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
                $u_phone_stmt->execute([$user_id]);
                $user_phone = $u_phone_stmt->fetchColumn();

                if ($user_phone) {
                    // SMS
                    include_once '../includes/sms.php';
                    $sms_msg = "Confirmed! Booking #{$booking_id} for {$car['make']} {$car['model']}. Total: ZMW {$total_price}. View in portal.";
                    send_sms($user_phone, $sms_msg);

                    // WhatsApp
                    include_once '../includes/whatsapp.php';
                    $wa = new WhatsAppService();
                    $wa->sendBookingConfirmation($user_phone, [
                        'booking_id'      => $booking_id,
                        'customer_name'   => $user_info['name'],
                        'vehicle'         => $car['make'] . ' ' . $car['model'],
                        'pickup_location' => $location,
                        'pickup_date'     => date('d M Y, H:i', strtotime($pickup_date)),
                        'dropoff_date'    => date('d M Y, H:i', strtotime($dropoff_date)),
                        'total_price'     => $total_price,
                    ]);
                }

            } catch (Exception $em) {
                // Silent fail for notification to not disrupt booking flow
                error_log("Notification failed: " . $em->getMessage());
            }
            // -------------------------------

            header("Location: reservation-confirmation.php?booking_id=" . $booking_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to process booking: ' . $e->getMessage();
            error_log("CRITICAL BOOKING ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Booking | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body { 
            background: url('../public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
        }
        @media (max-width: 768px) {
            .dashboard-grid {
                display: flex !important;
                flex-direction: column;
                gap: 20px;
            }
            .summary-card {
                order: 1;
                margin-bottom: 10px;
            }
            .booking-details {
                order: 2;
            }
            .interior-exp-grid {
                grid-template-columns: 1fr !important;
            }
            .nav-links {
                display: none !important;
            }
            .container {
                padding-left: 20px;
                padding-right: 20px;
            }
            .booking-details h2 {
                font-size: 1.6rem !important;
            }
            .booking-summary {
                margin-top: 30px;
            }
        }
        
        /* Loading Spinner */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: calc(50% - 10px);
            left: calc(50% - 10px);
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <header class="header-solid">
        <div class="container nav">
            <a href="dashboard.php" class="logo">Car Hire</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Portal Home</a></li>
                <li><a href="my-bookings.php">My Bookings</a></li>
            </ul>
        </div>
    </header>

    <div class="portal-content">
        <div class="container">
            <div class="dashboard-grid" style="padding: 40px 0;">
                <!-- Left Side: Form -->
                <div class="booking-details">
                    <h2 style="font-size: 2rem; margin-bottom: 5px;">Review & Confirm Booking</h2>
                    <p style="margin-bottom: 30px; color: var(--secondary-color);">Please check the details below and proceed to confirm your reservation.</p>

                    <?php if($error): ?>
                        <div class="form-feedback error"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="feature-card" style="text-align: left; margin-bottom: 30px;">
                        <h3 style="margin-bottom: 15px; font-size: 1.2rem;"><i class="fas fa-info-circle"></i> Important Information</h3>
                        <ul style="list-style: disc; padding-left: 20px; color: rgba(255,255,255,0.7);">
                            <li style="margin-bottom: 8px;">Please bring your valid driver's license at checkout.</li>
                            <li style="margin-bottom: 8px;">Payments can be made via Mobile Money (MTN, Airtel, Zamtel).</li>
                            <li>A deposit may be required upon vehicle pickup.</li>
                        </ul>
                    </div>

                    <?php if (!empty($car['interior_image_url']) || !empty($car['features'])): ?>
                    <div class="feature-card" style="text-align: left; margin-bottom: 30px; background: rgba(var(--accent-vibrant-rgb), 0.05);">
                        <h3 style="margin-bottom: 20px; font-size: 1.3rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">
                            <i class="fas fa-couch" style="color: var(--accent-vibrant);"></i> Interior Experience
                        </h3>
                        
                        <div class="interior-exp-grid" style="display: grid; grid-template-columns: <?php echo !empty($car['interior_image_url']) ? '1.5fr 1fr' : '1fr'; ?>; gap: 20px; align-items: start;">
                            <?php if (!empty($car['interior_image_url'])): ?>
                                <div style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
                                    <img src="../<?php echo $car['interior_image_url']; ?>" style="width: 100%; display: block; filter: brightness(1.1);" alt="Interior View">
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5) !important; margin-bottom: 12px;">Premium Specs</h4>
                                <div class="feature-badges">
                                    <?php 
                                    $features = explode(',', $car['features']);
                                    foreach ($features as $feature): 
                                    ?>
                                        <span class="feature-badge" style="background: rgba(30, 41, 59, 0.8); border-color: rgba(255,255,255,0.1);"><?php echo trim($feature); ?></span>
                                    <?php endforeach; ?>
                                    <span class="feature-badge" style="background: rgba(30, 41, 59, 0.8); border-color: rgba(255,255,255,0.1);"><?php echo $car['transmission']; ?></span>
                                    <span class="feature-badge" style="background: rgba(30, 41, 59, 0.8); border-color: rgba(255,255,255,0.1);"><?php echo $car['fuel_type']; ?></span>
                                </div>
                                <p style="font-size: 0.85rem; color: rgba(255,255,255,0.6); margin-top: 15px;">Experience the pinnacle of comfort and sophistication in every journey.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="bookingForm">
                        <h3 style="margin-bottom: 20px;">Protect Your Trip (Add-ons)</h3>
                        <div style="display: grid; gap: 10px; margin-bottom: 30px;">
                            <?php foreach($add_ons as $addon): ?>
                                <label class="feature-card" style="display: flex; align-items: center; text-align: left; padding: 15px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1);">
                                    <input type="checkbox" name="addons[]" value="<?php echo $addon['id']; ?>" class="addon-checkbox" data-price="<?php echo $addon['price_per_day']; ?>" style="margin-right: 15px; width: 20px; height: 20px;">
                                    <div style="flex-grow: 1;">
                                        <strong><?php echo $addon['name']; ?></strong><br>
                                        <small><?php echo $addon['description']; ?></small>
                                    </div>
                                    <span style="font-weight: 700; color: var(--primary-color);">+ ZMW <?php echo $addon['price_per_day']; ?>/day</span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <h3 style="margin-bottom: 20px;">Promo Voucher</h3>
                        <div class="form-group" style="margin-bottom: 30px; display: flex; gap: 10px;">
                            <input type="text" name="promo_code" id="promo_code" class="form-control" placeholder="Enter code (e.g. SAVE20)" style="max-width: 250px; text-transform: uppercase;">
                            <button type="button" class="btn btn-outline" onclick="checkCode()" style="padding: 10px 20px;">Apply</button>
                        </div>
                        <p id="promo_msg" style="margin-top: -25px; margin-bottom: 25px; font-size: 0.85rem;"></p>

                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <button type="submit" id="confirmBtn" class="btn btn-primary" style="flex: 2; padding: 15px; font-size: 1.1rem; min-width: 200px;">Confirm Reservation</button>
                            <a href="search-results.php?pickup_location=<?php echo urlencode($location); ?>&pickup_date=<?php echo $pickup_date; ?>&dropoff_date=<?php echo $dropoff_date; ?>" class="btn btn-outline" style="flex: 1; display:flex; align-items:center; justify-content:center; min-width: 120px; padding: 15px;">Cancel</a>
                        </div>
                    </form>

                    <script>
                        // Form submission handling
                        document.getElementById('bookingForm').addEventListener('submit', function(e) {
                            const btn = document.getElementById('confirmBtn');
                            btn.classList.add('btn-loading');
                            btn.innerHTML = 'Processing...';
                            // No e.preventDefault() here, we want standard submission
                        });

                        const basePrice = <?php echo $base_price; ?>;
                        const days = <?php echo $days; ?>;
                        const totalDisplay = document.querySelector('.summary-total');
                        const checkboxes = document.querySelectorAll('.addon-checkbox');

                        checkboxes.forEach(cb => cb.addEventListener('change', updateTotal));

                        async function checkCode() {
                            const code = document.getElementById('promo_code').value.trim();
                            const msg = document.getElementById('promo_msg');
                            if (!code) return;

                            msg.style.color = 'white';
                            msg.textContent = 'Validating...';

                            try {
                                const response = await fetch(`../api/validate-voucher.php?code=${code}&amount=${basePrice}`);
                                const data = await response.json();

                                if (data.valid) {
                                    msg.style.color = '#10b981';
                                    msg.textContent = `Success! ${data.msg}`;
                                    updateTotal(data.discount);
                                } else {
                                    msg.style.color = '#ef4444';
                                    msg.textContent = data.msg;
                                    updateTotal(0);
                                }
                            } catch (e) {
                                msg.textContent = 'Error check code.';
                            }
                        }

                        function updateTotal(discount = 0) {
                            let total = basePrice;
                            checkboxes.forEach(cb => {
                                if (cb.checked) {
                                    total += (parseFloat(cb.dataset.price) * days);
                                }
                            });
                            
                            if (discount > 0) {
                                total -= discount;
                            }

                            totalDisplay.textContent = 'ZMW ' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    </script>
                </div>

                <!-- Right Side: Summary Card -->
                <div class="summary-card">
                    <img src="<?php echo !empty($car['image_url']) ? '../' . $car['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image'; ?>" alt="Car" style="width: 100%; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 20px;"><?php echo $car['make'] . ' ' . $car['model']; ?></h3>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span>Location</span>
                        <strong><?php echo htmlspecialchars($location); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span>Pickup Date</span>
                        <strong><?php echo date('d M Y, h:i A', strtotime($pickup_date)); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span>Drop-off Date</span>
                        <strong><?php echo date('d M Y, h:i A', strtotime($dropoff_date)); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span>Duration</span>
                        <strong><?php echo $days; ?> Days</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <span>Price per day</span>
                        <strong>ZMW <?php echo number_format($car['price_per_day'], 2); ?></strong>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 20px; font-size: 1.2rem;">
                        <span>Total Cost</span>
                        <span class="summary-total" style="color: var(--primary-color); font-weight: 700;">ZMW <?php echo number_format($total_price, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
