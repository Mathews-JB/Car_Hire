<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation & Refund Policy | Car Hire Zambia</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="public/css/theme.css?v=4.0">
    <script src="public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .legal-content {
            background: rgba(30, 30, 35, 0.75);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            padding: 60px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            max-width: 900px;
            margin: 60px auto;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.8;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
        }
        .legal-content h1 { color: white; margin-bottom: 30px; font-weight: 800; border-bottom: 3px solid var(--accent-vibrant); display: inline-block; padding-bottom: 10px; letter-spacing: -1px; }
        .legal-content h2 { color: white; margin-top: 40px; font-size: 1.3rem; font-weight: 700; }
        .navbar { background: rgba(30, 30, 35, 0.85); backdrop-filter: blur(15px); padding: 15px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.08); position: sticky; top: 0; z-index: 1000; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; }
        
        @media (max-width: 768px) {
            .legal-content {
                padding: 30px 20px;
                margin: 20px 10px;
                border-radius: 20px;
            }
            .legal-content h1 { font-size: 1.8rem; }
            .navbar { padding: 10px 0; }
        }
    </style>
</head>
<body class="stabilized-car-bg">

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">Car Hire</a>
            <div style="display: flex; gap: 20px;">
                <a href="index.php" style="color: white; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Home</a>
                <a href="login.php" style="color: white; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Login</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="legal-content">
            <h1>Cancellation & Refund Policy</h1>
            <p>Last Updated: March 7, 2026</p>

            <h2>1. Free Cancellation</h2>
            <p>You can cancel your booking for a full refund up to 48 hours before the scheduled pickup time.</p>

            <h2>2. Late Cancellation</h2>
            <p>Cancellations made within 24 to 48 hours of pickup will incur a 25% cancellation fee. Cancellations made less than 24 hours before pickup are non-refundable.</p>

            <h2>3. No-Show Policy</h2>
            <p>If you fail to pick up the vehicle at the scheduled time without prior notification, the booking will be treated as a "No-Show" and no refund will be issued.</p>

            <h2>4. Early Returns</h2>
            <p>There are no refunds for early returns of rental vehicles.</p>

            <h2>5. Processing Refunds</h2>
            <p>Approved refunds will be processed back to the original payment method (Mobile Money or Card) within 5-10 business days depending on the provider's processing time.</p>

            <h2>6. Technical Failures</h2>
            <p>In the event of a double charge or a system error, please contact support immediately with your transaction reference. We will rectify the issue and issue a corrective refund if necessary.</p>
        </div>
    </div>

    <footer style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.4); font-size: 0.8rem;">
        &copy; 2026 Car Hire Zambia Ltd. All rights reserved.
    </footer>
</body>
</html>
