<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions | Car Hire Zambia</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .legal-content {
            background: rgba(30,30,35,0.75);
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
        .legal-content p { margin-bottom: 20px; }
        .legal-content ul { padding-left: 20px; margin-bottom: 30px; }
        .legal-content li { margin-bottom: 10px; }
        
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
            <h1>Terms & Conditions</h1>
            <p>Last Updated: March 7, 2026</p>

            <h2>1. Agreement to Terms</h2>
            <p>By using the Car Hire Zambia website and services, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use our services.</p>

            <h2>2. Eligibility</h2>
            <p>To rent a vehicle, you must be at least 21 years old (25 for premium fleet) and hold a valid, clean driver's license for at least 2 years. International visitors must provide a valid passport and international driving permit if applicable.</p>

            <h2>3. Rental Period and Return</h2>
            <p>The rental period begins at the time of pickup and ends at the time of return as specified in the booking. Late returns without prior authorization will incur penalties of ZMW 500 per hour.</p>

            <h2>4. Payment and Deposit</h2>
            <p>Full payment is required at the time of booking confirmation. A security deposit (excess) may be held on your card or mobile money account depending on the vehicle class.</p>

            <h2>5. Restricted Use</h2>
            <p>Vehicles must not be used for illegal activities, off-road driving (unless specified), or commercial transportation of passengers. Smoking inside the vehicle is strictly prohibited and will result in a cleaning fee.</p>

            <h2>6. Liability</h2>
            <p>The Renter is liable for any damage, theft, or loss of the vehicle not covered by insurance. Basic insurance is included, but the renter is responsible for the excess fee in case of an accident.</p>

            <h2>7. Contact Us</h2>
            <p>For any questions regarding these terms, please contact us at support@carhire.com</p>
        </div>
    </div>

    <footer style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.4); font-size: 0.8rem;">
        &copy; 2026 Car Hire Zambia Ltd. All rights reserved.
    </footer>
</body>
</html>
