<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : (isset($_POST['booking_id']) ? $_POST['booking_id'] : '');

if (empty($booking_id)) {
    header("Location: dashboard.php");
    exit;
}

// Fetch booking details
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';
$init_payment = false;
$validated_phone = '';
$validated_provider = '';

// Check if returning from verification with error
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        :root {
            --payment-bg: rgba(45, 45, 45, 0.65);
            --lenco-blue: #3b82f6;
            --mtn-yellow: #ffcc00;
            --airtel-red: #ed1c24;
            --zamtel-green: #009933;
        }

        html, body { 
            background: #080c17 !important; 
            color: #f8fafc !important; 
            overflow: hidden !important;
            height: 100% !important;
            margin: 0;
            padding: 0;
        }

        .portal-content {
            position: fixed;
            inset: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding-top: 80px;
            padding-bottom: 110px;
            overscroll-behavior: none;
        }

        .payment-card {
            max-width: 500px;
            margin: 40px auto;
            background: var(--payment-bg);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            padding: 40px;
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 40px 100px rgba(0,0,0,0.6);
            position: relative;
            overflow: hidden;
        }

        .payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--mtn-yellow), var(--airtel-red), var(--lenco-blue));
        }

        .payment-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .payment-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
            color: white;
        }

        .amount-display {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .amount-display span {
            display: block;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .amount-display strong {
            font-size: 2.2rem;
            color: #ff9b00;
            font-weight: 900;
        }

        .provider-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .provider-option {
            position: relative;
        }

        .provider-option input {
            display: none;
        }

        .provider-button {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: center;
        }

        .provider-button:hover {
            background: rgba(255, 255, 255, 0.06);
            transform: translateY(-2px);
        }

        .provider-option input:checked + .provider-button {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .provider-option input:checked + .provider-button::after {
            content: '\f058';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: 10px;
            right: 10px;
            color: #10b981;
            font-size: 1rem;
        }

        .provider-button img {
            height: 32px;
            margin-bottom: 8px;
            filter: grayscale(0.2);
            transition: all 0.3s ease;
        }

        .provider-option input:checked + .provider-button img {
            filter: grayscale(0);
            transform: scale(1.1);
        }

        .provider-button span {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .phone-input-group {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .phone-input-group label {
            display: block;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
        }

        .input-with-icon input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 15px 15px 45px;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-with-icon input:focus {
            border-color: #ff9b00;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 15px rgba(255, 155, 0, 0.1);
        }

        .btn-pay {
            width: 100%;
            background: linear-gradient(135deg, #ff9b00, #f59e0b);
            color: #000;
            border: none;
            padding: 20px;
            border-radius: 16px;
            font-weight: 800;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(245, 158, 11, 0.4);
            filter: brightness(1.1);
        }

        .secure-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .secure-footer p {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        body { 
            background: transparent !important;
        }

        @media (max-width: 500px) {
            .payment-card {
                margin: 15px auto 20px; /* Reduced from 20px/30px */
                padding: 15px 15px;
                border-radius: 16px; /* Slightly softer corners */
                max-width: 95%;
            }
            .payment-header {
                margin-bottom: 12px;
            }
            .payment-header h2 {
                font-size: 1.35rem;
                margin-bottom: 4px;
            }
            .payment-header p {
                font-size: 0.75rem !important;
            }
            .amount-display {
                padding: 10px 15px;
                margin-bottom: 12px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: left;
            }
            .amount-display span {
                font-size: 0.75rem;
                margin-bottom: 0;
            }
            .amount-display strong {
                font-size: 1.6rem;
                color: white;
            }
            .provider-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px; /* Tighter gap */
                margin-bottom: 12px;
            }
            .provider-button {
                padding: 8px; /* Slightly less padding */
            }
            .provider-button img {
                height: 22px !important;
                margin-bottom: 4px !important;
            }
            .provider-button span {
                display: block;
                font-size: 0.65rem; /* Marginally smaller text */
            }
            .phone-input-group {
                padding: 12px; /* Tighter padding */
                margin-bottom: 12px; /* Tighter margin */
                border-radius: 14px;
            }
            .phone-input-group label {
                margin-bottom: 6px;
                font-size: 0.75rem;
            }
            .input-with-icon input {
                padding: 12px 12px 12px 40px;
                font-size: 0.95rem;
                border-radius: 8px;
            }
            .input-with-icon i {
                left: 12px;
            }
            .btn-pay {
                padding: 14px; /* Tighter button */
                font-size: 1rem;
                border-radius: 12px;
            }
            .provider-button:hover, .btn-pay:hover {
                transform: none !important;
            }
            .provider-option input:checked + .provider-button {
                transform: none !important;
            }
            .provider-option input:checked + .provider-button img {
                transform: none !important;
            }
            .secure-footer {
                margin-top: 10px;
                padding-top: 10px;
            }
            .secure-footer p {
                font-size: 0.65rem; /* Marginally smaller footer */
            }
        }
    </style>
</head>
<body class="stabilized-car-bg">

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="my-bookings.php" class="active">My Bookings</a>
            <a href="profile.php">Profile</a>
            <a href="support.php">Support</a>
        </div>
        <div class="hub-user">
            <span class="hub-user-name"><?php echo explode(' ', $_SESSION['user_name'])[0]; ?></span>
            <div class="hub-avatar"><?php echo strtoupper($_SESSION['user_name'][0]); ?></div>
        </div>
    </nav>

    <div class="portal-content">
        <div class="container">
            <div class="payment-card">
                <div class="payment-header" style="position: relative;">
                    <a href="my-bookings.php" style="position: absolute; left: 0; top: 5px; color: rgba(255,255,255,0.6); font-size: 1.2rem; text-decoration: none; padding: 5px;"><i class="fas fa-chevron-left"></i></a>
                    <h2>Secure Payment</h2>
                    <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem;">Booking Reference: #<?php echo $booking_id; ?></p>
                </div>

                <div class="amount-display">
                    <span>Total To Pay</span>
                    <strong>ZMW <?php echo number_format($booking['total_price'], 2); ?></strong>
                </div>

                <?php if($error): ?>
                    <div class="form-feedback error" style="margin-bottom: 25px; border-radius: 12px;"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="form-feedback success" style="margin-bottom: 30px; text-align: center; padding: 30px; border-radius: 20px;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                        <p style="font-size: 1.1rem; font-weight: 700;"><?php echo $success; ?></p>
                    </div>
                    <div style="text-align: center;">
                        <a href="my-bookings.php" class="btn btn-primary" style="padding: 15px 40px; border-radius: 12px;">Go to My Bookings</a>
                    </div>
                <?php else: ?>
                    
                    <form id="paymentForm">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        
                        <div style="margin-bottom: 8px; color: rgba(255,255,255,0.5); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Select Payment Method</div>
                        <div class="provider-grid">
                            <!-- MTN -->
                            <label class="provider-option">
                                <input type="radio" name="provider" value="MTN" <?php echo ($validated_provider == 'MTN' || !$validated_provider) ? 'checked' : ''; ?> onchange="updateUI('MTN')">
                                <div class="provider-button">
                                    <img src="../public/images/logos/mtn.svg" alt="MTN">
                                    <span>MTN MoMo</span>
                                </div>
                            </label>

                            <!-- Airtel -->
                            <label class="provider-option">
                                <input type="radio" name="provider" value="AIRTEL" <?php echo ($validated_provider == 'AIRTEL') ? 'checked' : ''; ?> onchange="updateUI('AIRTEL')">
                                <div class="provider-button">
                                    <img src="../public/images/logos/airtel1.png" alt="Airtel" style="height: 28px;">
                                    <span>Airtel Money</span>
                                </div>
                            </label>

                            <!-- Zamtel -->
                            <label class="provider-option">
                                <input type="radio" name="provider" value="ZAMTEL" <?php echo ($validated_provider == 'ZAMTEL') ? 'checked' : ''; ?> onchange="updateUI('ZAMTEL')">
                                <div class="provider-button">
                                    <img src="../public/images/logos/zamtel.svg" alt="Zamtel">
                                    <span>Zamtel Kwacha</span>
                                </div>
                            </label>

                            <!-- Card -->
                            <label class="provider-option">
                                <input type="radio" name="provider" value="CARD" <?php echo ($validated_provider == 'CARD') ? 'checked' : ''; ?> onchange="updateUI('CARD')">
                                <div class="provider-button">
                                    <i class="fas fa-credit-card" style="font-size: 1.5rem; color: white; margin-bottom: 8px;"></i>
                                    <span>Debit / Credit Card</span>
                                </div>
                            </label>
                        </div>

                        <div id="phone-input-wrapper" class="phone-input-group">
                            <label id="phone-label">Enter Mobile Number</label>
                            <div class="input-with-icon">
                                <i class="fas fa-mobile-screen"></i>
                                <input type="text" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($validated_phone); ?>" placeholder="e.g. 0961234567">
                            </div>
                        </div>

                        <button type="submit" class="btn-pay" id="payBtn">
                            <i class="fas fa-shield-halved"></i> Confirm & Pay Now
                        </button>
                    </form>

                    <div class="secure-footer">
                        <p><i class="fas fa-lock"></i> Secured by Lenco | PCI-DSS Compliant</p>
                        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 10px; opacity: 0.3;">
                            <i class="fab fa-cc-visa fa-lg"></i>
                            <i class="fab fa-cc-mastercard fa-lg"></i>
                            <i class="fas fa-shield-alt fa-lg"></i>
                        </div>
                    </div>

                    <script src="<?php echo LENCO_JS_URL; ?>"></script>
                    <script>
                        function updateUI(provider) {
                            const wrapper = document.getElementById('phone-input-wrapper');
                            const label = document.getElementById('phone-label');
                            
                            if (provider === 'CARD') {
                                wrapper.style.display = 'none';
                            } else {
                                wrapper.style.display = 'block';
                                label.innerText = provider + ' Money Number';
                            }
                        }

                        document.addEventListener('DOMContentLoaded', () => {
                            const selected = document.querySelector('input[name="provider"]:checked');
                            if(selected) updateUI(selected.value);
                            
                            document.getElementById('paymentForm').onsubmit = function(e) {
                                e.preventDefault(); // Prevent page reload!
                                
                                const providerInput = document.querySelector('input[name="provider"]:checked');
                                if (!providerInput) {
                                    alert('Please select a payment provider.');
                                    return;
                                }
                                const provider = providerInput.value;
                                
                                let phoneEl = document.getElementById('phone_number');
                                let phone = phoneEl.value.replace(/[^0-9]/g, '');
                                
                                // Client Side Validation
                                if (provider !== 'CARD') {
                                    if (!phone) {
                                        alert('Please enter your mobile number.');
                                        return;
                                    }
                                    if (phone.length !== 10) {
                                        alert('Invalid phone number length. Must be 10 digits (e.g. 096xxxxxxx).');
                                        return;
                                    }
                                    const prefix = phone.substring(0, 3);
                                    let valid = false;
                                    if (provider === 'MTN' && ['096', '076'].includes(prefix)) valid = true;
                                    if (provider === 'AIRTEL' && ['097', '077', '057'].includes(prefix)) valid = true;
                                    if (provider === 'ZAMTEL' && ['095', '075'].includes(prefix)) valid = true;
                                    
                                    if (!valid) {
                                        alert('Invalid prefix for ' + provider + ' Money.');
                                        return;
                                    }
                                }

                                const payBtn = document.getElementById('payBtn');
                                if (payBtn.disabled) return; // Prevent double clicks

                                payBtn.disabled = true;
                                payBtn.style.pointerEvents = 'none';
                                payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                                payBtn.style.opacity = '0.7';

                                startLencoPayment(provider, phone);
                            };
                        });

                        function startLencoPayment(provider, phone) {
                            let channels = (provider === 'CARD') ? ["card"] : ["mobile-money"];
                            let lencoPhone = (provider === 'CARD') ? '' : phone;
                            
                            LencoPay.getPaid({
                                key: '<?php echo LENCO_PUBLIC_KEY; ?>', 
                                reference: 'BOOK-<?php echo $booking_id; ?>-' + Date.now(),
                                email: '<?php echo $_SESSION['user_email'] ?? "customer@example.com"; ?>',
                                amount: <?php echo $booking['total_price']; ?>,
                                currency: "ZMW",
                                companyName: "Car Hiring Company",
                                channels: channels,
                                operator: provider.toLowerCase(),
                                metadata: {
                                    payment_provider: provider.toLowerCase(),
                                    source: 'CarHire_Web'
                                },
                                mobileMoney: {
                                    operator: provider.toLowerCase(),
                                    phone: lencoPhone
                                },
                                customer: {
                                    firstName: "<?php echo explode(' ', $_SESSION['user_name'])[0]; ?>",
                                    lastName: "<?php echo explode(' ', $_SESSION['user_name'])[1] ?? ''; ?>",
                                    phone: lencoPhone || '<?php echo $_SESSION['user_phone'] ?? ""; ?>',
                                },
                                onSuccess: function (response) {
                                    // Change UI to success state immediately while redirecting
                                    const payBtn = document.getElementById('payBtn');
                                    payBtn.innerHTML = '<i class="fas fa-check-circle"></i> Payment Successful! Redirecting...';
                                    payBtn.style.background = '#10b981';
                                    payBtn.style.opacity = '1';
                                    window.location.href = 'verify-payment.php?reference=' + response.reference + '&booking_id=<?php echo $booking_id; ?>';
                                },
                                onClose: function () {
                                    const payBtn = document.getElementById('payBtn');
                                    payBtn.disabled = false;
                                    payBtn.style.pointerEvents = 'auto';
                                    payBtn.innerHTML = '<i class="fas fa-shield-halved"></i> Confirm & Pay Now';
                                    payBtn.style.opacity = '1';
                                }
                            });
                        }
                    </script>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
