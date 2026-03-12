<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Car Hire Zambia</title>
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
            <h1>Privacy Policy</h1>
            <p>Last Updated: March 7, 2026</p>

            <h2>1. Information We Collect</h2>
            <p>We collect personal information that you provide to us, including your name, contact details, driver's license information, and payment details. We also use OCR technology to verify your identity documents.</p>

            <h2>2. How We Use Your Information</h2>
            <p>Your information is used to process bookings, verify your identity for safety reasons, communicate with you regarding your rental, and improve our services. GPS data may be tracked for fleet security purposes.</p>

            <h2>3. Document Security</h2>
            <p>Uploaded documents (NRC/License) are stored securely and used exclusively for verification. We do not sell your personal data to third parties.</p>

            <h2>4. Payment Security</h2>
            <p>All financial transactions are processed through Lenco, a PCI-DSS compliant payment gateway. We do not store your full card details on our servers.</p>

            <h2>5. Cookies and Tracking</h2>
            <p>We use cookies to maintain your session and provide a personalized experience. You can disable cookies in your browser settings, though some features may not function correctly.</p>

            <h2>6. Your Rights</h2>
            <p>You have the right to access, correct, or request the deletion of your personal data. Please contact our data protection officer for assistance.</p>
        </div>
    </div>

    <footer style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.4); font-size: 0.8rem;">
        &copy; 2026 Car Hire Zambia Ltd. All rights reserved.
    </footer>
</body>
</html>
