<?php
/**
 * LENCO PAYMENT GATEWAY STANDALONE DEMO
 * Created for: Car Hire Integration
 * Purpose: Easy copy-paste for future projects.
 */

// --- 1. CONFIGURATION ---
include_once 'includes/db.php';

$is_live = LENCO_IS_LIVE;
$publicKey = LENCO_PUBLIC_KEY;
$secretKey = LENCO_SECRET_KEY;
$jsUrl = LENCO_JS_URL;
$apiBase = LENCO_API_BASE;

// --- 2. SERVER-SIDE VERIFICATION HANDLER ---
$payment_status = null;
$error_message = null;
$transaction_data = null;

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    $url = $apiBase . "collections/status/" . $reference;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        $error_message = "CURL Error: " . $error;
    } else {
        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'successful') {
            $payment_status = "success";
            $transaction_data = $result['data'];
        } else {
            $payment_status = "failed";
            $error_message = $result['message'] ?? 'Verification failed matching Lenco record.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lenco Payment Integration Demo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg: #0b0e14;
            --surface: #12161f;
            --primary: #f59e0b;
            --primary-hover: #fbbf24;
            --text-main: #ffffff;
            --text-dim: #94a3b8;
            --border: #1e293b;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(30, 41, 59, 0.2) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .payment-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            padding: 40px 40px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .brand-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bg);
            font-size: 1.2rem;
        }

        .brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
        }

        h2 { 
            font-family: 'Outfit', sans-serif;
            font-size: 1.2rem; 
            font-weight: 700; 
            margin-bottom: 8px; 
            color: var(--text-main);
        }

        p.subtitle { 
            color: var(--text-dim); 
            font-size: 0.9rem; 
            line-height: 1.5;
        }

        .card-body {
            padding: 40px;
        }

        .input-group { 
            margin-bottom: 24px; 
        }

        .input-group label { 
            display: block; 
            font-size: 0.8rem; 
            font-weight: 600; 
            color: var(--text-dim); 
            margin-bottom: 10px; 
            letter-spacing: 0.5px;
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 18px;
            color: var(--text-dim);
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            background: #1a1f29;
            border: 1.5px solid var(--border);
            padding: 16px 20px 16px 48px;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        input:focus { 
            outline: none; 
            border-color: var(--primary); 
            background: #1c222d;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
        }

        .amount-input {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .pay-btn {
            width: 100%;
            background: var(--primary);
            color: var(--bg);
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
        }

        .pay-btn:hover { 
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .pay-btn:active {
            transform: translateY(0);
        }

        .card-footer {
            padding: 24px 40px;
            background: #0d1117;
            border-top: 1px solid var(--border);
            text-align: center;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--text-dim);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-badge {
            position: absolute;
            top: 25px;
            right: 25px;
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .mode-live { background: var(--success); color: #fff; }
        .mode-test { background: #3b82f6; color: #fff; }

        .result-box {
            text-align: center;
            padding: 20px;
        }

        .result-box.success { color: var(--success); }
        .result-box.error { color: var(--danger); }
    </style>
</head>
<body>

    <div class="payment-card">
        <span class="status-badge <?php echo $is_live ? 'mode-live' : 'mode-test'; ?>">
            <i class="fas <?php echo $is_live ? 'fa-check-circle' : 'fa-flask'; ?>"></i> 
            <?php echo $is_live ? 'Live' : 'Test Mode'; ?>
        </span>

        <div class="card-header">
            <div class="brand-pill">
                <div class="brand-icon"><i class="fas fa-shield-check"></i></div>
                <span class="brand-name">Lenco Pay</span>
            </div>
            <h2>Secure Checkout</h2>
            <p class="subtitle">Complete your transaction safely via Mobile Money or Card.</p>
        </div>

        <div class="card-body">
            <?php if ($payment_status === 'success'): ?>
                <div class="result-box success">
                    <i class="fas fa-check-circle fa-3x" style="margin-bottom:20px;"></i>
                    <h3 style="margin-bottom:10px;">Payment Verified</h3>
                    <p style="font-size:0.85rem; opacity:0.7;">Ref: <?php echo htmlspecialchars($reference); ?></p>
                    <div style="margin-top:25px; padding-top:20px; border-top:1px solid var(--border);">
                        <a href="demo.payment.php" style="color:var(--primary); font-weight:700; text-decoration:none;">New Transaction</a>
                    </div>
                </div>
            <?php elseif ($payment_status === 'failed'): ?>
                <div class="result-box error">
                    <i class="fas fa-times-circle fa-3x" style="margin-bottom:20px;"></i>
                    <h3 style="margin-bottom:10px;">Unverified</h3>
                    <p style="font-size:0.85rem;"><?php echo htmlspecialchars($error_message); ?></p>
                    <div style="margin-top:25px; padding-top:20px; border-top:1px solid var(--border);">
                        <a href="demo.payment.php" style="color:var(--primary); font-weight:700; text-decoration:none;">Try Again</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="input-group">
                    <label>Payment Amount</label>
                    <div class="input-wrapper">
                        <i>ZMW</i>
                        <input type="number" id="pay_amount" class="amount-input" value="50.00" step="0.01">
                    </div>
                </div>

                <div class="input-group">
                    <label>Mobile Number / Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-mobile-screen"></i>
                        <input type="text" id="cust_phone" placeholder="Reach you at...">
                    </div>
                </div>

                <button class="pay-btn" onclick="triggerLenco()">
                    Confirm Payment <i class="fas fa-arrow-right"></i>
                </button>
            <?php endif; ?>
        </div>

        <div class="card-footer">
            <div class="secure-badge">
                <i class="fas fa-lock"></i>
                <span>PCI DSS Compliant &middot; Secure 256-bit</span>
            </div>
        </div>
    </div>

    <!-- Lenco SDK -->
    <script src="<?php echo $jsUrl; ?>"></script>
    
    <script>
        function triggerLenco() {
            const amount = document.getElementById('pay_amount').value;
            const phone = document.getElementById('cust_phone').value;
            
            if(!phone) {
                alert("Please enter a phone number or email.");
                return;
            }

            // --- LencoPay JS Initiation ---
            LencoPay.getPaid({
                key: '<?php echo $publicKey; ?>',
                reference: 'DEMO-' + Date.now(),
                email: 'customer@test.com', // Use actual cust email in production
                amount: amount,
                currency: "ZMW",
                channels: ["card", "mobile-money"],
                customer: {
                    firstName: "Demo",
                    lastName: "User",
                    phone: phone,
                },
                onSuccess: function (response) {
                    // Redirect back to this same page with reference for PHP verification
                    window.location.href = 'demo.payment.php?reference=' + response.reference;
                },
                onClose: function () {
                    // Removed alert for premium UX
                    console.log('Checkout window closed.');
                },
                onConfirmationPending: function () {
                    // Removed alert to prevent popup loop
                    console.log('Payment pending confirmation...');
                }
            });
        }
    </script>
</body>
</html>
