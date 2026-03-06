<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 1. Capture Callback Params
$reference = $_GET['reference'] ?? '';
$booking_id = $_GET['booking_id'] ?? '';

// 2. Validate Basic Response
if (empty($reference)) {
    // Transaction failed or cancelled
    header("Location: payment.php?booking_id=" . $booking_id . "&error=Payment+reference+missing");
    exit;
}

// 3. Fetch Transaction Status from Lenco
$url = LENCO_API_BASE . "collections/status/" . $reference;
$secret_key = LENCO_SECRET_KEY;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$secret_key}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Verification Error: " . $error);
}

$result = json_decode($response, true);

// 4. Check Verification Result
if (
    isset($result['status']) && $result['status'] === true &&
    isset($result['data']['status']) && $result['data']['status'] === 'successful'
) {
    // Payment is valid at Lenco. Now check logic.
    $lenco_amount = (float)$result['data']['amount'];
    $lenco_currency = $result['data']['currency'];
    $lenco_ref = $result['data']['reference']; // Should match BOOK-{id}-{timestamp}
    
    // Extract Booking ID if needed, but we already have it from GET
    if (empty($booking_id)) {
        // Fallback: extract from reference BOOK-123-timestamp
        $parts = explode('-', $lenco_ref);
        $booking_id = $parts[1] ?? '';
    }

    // Fetch Booking from DB to validate amount
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Booking not found.");
    }

    // Amount Check (Allowing small differences if any)
    if ($lenco_amount >= $booking['total_price'] && $lenco_currency === 'ZMW') {
        
        // 5. Update Database
        try {
            $pdo->beginTransaction();

            // A. Insert Payment Record
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, provider, transaction_id, amount, status, phone_number, created_at) VALUES (?, 'Lenco', ?, ?, 'successful', ?, NOW())");
            
            $transaction_id = $result['data']['id'] ?? $lenco_ref;
            $phone = $result['data']['mobileMoneyDetails']['phone'] ?? $_SESSION['user_phone'] ?? 'N/A';
            
            $stmt->execute([$booking_id, $transaction_id, $lenco_amount, $phone]);

            // B. Update Booking Status
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$booking_id]);

            $pdo->commit();

            // 6. Send WhatsApp payment success notification
            try {
                require_once __DIR__ . '/../includes/whatsapp.php';
                $u_stmt = $pdo->prepare("SELECT u.name, u.phone, v.make, v.model FROM bookings b JOIN users u ON b.user_id = u.id JOIN vehicles v ON b.vehicle_id = v.id WHERE b.id = ?");
                $u_stmt->execute([$booking_id]);
                $wa_data = $u_stmt->fetch(PDO::FETCH_ASSOC);

                if ($wa_data && !empty($wa_data['phone'])) {
                    $wa = new WhatsAppService();
                    $wa->sendPaymentSuccess($wa_data['phone'], [
                        'booking_id'      => $booking_id,
                        'customer_name'   => $wa_data['name'],
                        'vehicle'         => $wa_data['make'] . ' ' . $wa_data['model'],
                        'pickup_date'     => date('d M Y, H:i', strtotime($booking['pickup_date'])),
                        'pickup_location' => $booking['pickup_location'] ?? 'Car Hire Branch',
                        'total_price'     => $lenco_amount,
                    ]);
                }
            } catch (Exception $wa_err) {
                error_log("WhatsApp payment notification failed: " . $wa_err->getMessage());
            }

            // 7. Send Secure Email Notifications to Admin and Customer
            try {
                require_once __DIR__ . '/../includes/mailer.php';
                $admin_email = getenv('SUPPORT_EMAIL');
                if ($wa_data) {
                    $c_email_stmt = $pdo->prepare("SELECT email FROM users WHERE id = (SELECT user_id FROM bookings WHERE id = ?)");
                    $c_email_stmt->execute([$booking_id]);
                    $c_email = $c_email_stmt->fetchColumn();

                    $subject = "Payment Successful: Booking #{$booking_id}";
                    $email_body = "A payment of ZMW " . number_format($lenco_amount, 2) . " has been successfully completed.<br><br>";
                    $email_body .= "<strong>Booking Reference:</strong> #{$booking_id}<br>";
                    $email_body .= "<strong>Vehicle:</strong> {$wa_data['make']} {$wa_data['model']}<br>";
                    $email_body .= "<strong>Customer Name:</strong> {$wa_data['name']}<br>";
                    $email_body .= "<strong>Mobile Used:</strong> {$phone}<br>";
                    $email_body .= "<strong>Transaction ID:</strong> {$transaction_id}<br><br>";
                    $email_body .= "The booking status is now officially CONFIRMED.";

                    // Admin Notification
                    if ($admin_email) {
                        sendSupportEmail('System', $admin_email, "[Admin Alert] " . $subject, $email_body, 'to_customer'); // Workaround to send TO the support email
                    }

                    // Customer Notification
                    if ($c_email) {
                        $customer_mailer = new CarHireMailer();
                        $customer_mailer->send($c_email, $subject, $email_body);
                    }
                }
            } catch (Exception $mail_err) {
                error_log("Email payment notification failed: " . $mail_err->getMessage());
            }

            // 8. Success Redirect
            header("Location: reservation-confirmation.php?booking_id=" . $booking_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Database Error: " . $e->getMessage());
        }

    } else {
        die("Verification Failed: Amount or Currency mismatch. Expected {$booking['total_price']} ZMW, got {$lenco_amount} {$lenco_currency}");
    }

} else {
    // Verification API failed or payment not successful
    $error_msg = $result['message'] ?? 'Transaction verification failed';
    header("Location: payment.php?booking_id=" . $booking_id . "&error=" . urlencode($error_msg));
    exit;
}
?>
