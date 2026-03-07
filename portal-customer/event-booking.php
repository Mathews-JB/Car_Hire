<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';
include_once '../includes/mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicles_needed = $_POST['vehicles_needed'] ?? '';
    $event_type = $_POST['event_type'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $details = $_POST['details'] ?? '';
    
    $user_name = $_SESSION['user_name'] ?? 'Customer';
    $user_email = $_SESSION['user_email'] ?? 'unknown@user.com'; 

    if (empty($vehicles_needed) || empty($event_type) || empty($event_date) || empty($details)) {
        $error = "Please fill in all required fields.";
    } else {
        $subject = "Multi-Car/Event Booking Request: $event_type";
        $full_message = "A multi-car booking request has been submitted.\n\n" .
                        "Event Type: $event_type\n" .
                        "Date of Event: $event_date\n" .
                        "Number of Vehicles Needed: $vehicles_needed\n\n" .
                        "Additional Details/Preferences:\n$details";

        try {
            // Save to Database
            $stmt = $pdo->prepare("INSERT INTO support_messages (user_id, subject, message, status) VALUES (?, ?, ?, 'new')");
            $stmt->execute([$_SESSION['user_id'], $subject, $full_message]);

            // Send Email to Admin
            sendSupportEmail($user_name, $user_email, $subject, $full_message, 'to_admin');
            
            // Send Confirmation to Customer
            sendSupportEmail($user_name, $user_email, "We've received your request: $subject", "Hi $user_name,\n\nThank you for choosing us for your $event_type. We have received your request for $vehicles_needed vehicles. Our specialized corporate and event booking team will get in touch with you shortly to finalize details and provide a custom quote.\n\nYour Request Details:\n$full_message", 'to_customer');

            $success = "Your multi-car event request has been sent! Our booking specialists will contact you shortly with a tailored quote.";
        } catch (Exception $e) {
            $error = "Delivery failed. Please check your internet connection.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event & Multi-Car Booking | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body { 
            background: transparent !important;
        }

        .event-banner {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(30, 30, 35, 0.95));
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .event-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/stardust.png');
            opacity: 0.2;
            pointer-events: none;
        }

        .responsive-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: white !important;
            padding: 15px !important;
            border-radius: 12px !important;
            font-size: 1rem !important;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.2) !important;
            outline: none;
        }

        @media (max-width: 768px) {
            .responsive-form-grid { grid-template-columns: 1fr; }
            .event-banner { padding: 30px 20px; }
            .event-banner h1 { font-size: 1.8rem !important; }
            .form-control { padding: 12px !important; font-size: 0.9rem !important; }
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
<body class="stabilized-car-bg">

    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="browse-vehicles.php">Browse Fleet</a>
            <a href="my-bookings.php">My Bookings</a>
            <a href="support.php">Support</a>
            <a href="profile.php">Profile</a>
        </div>
        <div class="hub-user">
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
        <div class="container" style="max-width: 800px;">
            
            <div class="event-banner">
                <div style="font-size: 3rem; color: #60a5fa; margin-bottom: 15px;">
                    <i class="fas fa-car-side"></i> <i class="fas fa-car-side"></i> <i class="fas fa-car-side"></i>
                </div>
                <h1 style="color: white; font-weight: 800; font-size: 2.5rem; margin-bottom: 10px;">Event & Fleet Booking</h1>
                <p style="color: rgba(255,255,255,0.8); font-size: 1.1rem; max-width: 600px; margin: 0 auto;">Reserve multiple vehicles for weddings, corporate events, or large group travel. Enjoy priority handling and custom pricing formats.</p>
            </div>

            <?php if($success): ?>
                <div class="form-feedback success" style="margin-bottom: 30px; padding: 20px; background: rgba(16,185,129,0.1); border: 1px solid var(--success); border-radius: 12px; color: var(--success); text-align:center;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i> 
                    <strong><?php echo $success; ?></strong>
                </div>
                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="dashboard.php" class="btn btn-primary" style="padding: 12px 30px;">Return to Dashboard</a>
                </div>
            <?php else: ?>

                <?php if($error): ?>
                    <div class="form-feedback error" style="margin-bottom: 25px; padding: 15px; background: rgba(239,68,68,0.1); border: 1px solid var(--danger); border-radius: 12px; color: var(--danger); text-align:center;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="data-card" style="padding: 40px;">
                    <form action="event-booking.php" method="POST" id="eventForm">
                        <div class="responsive-form-grid" style="margin-bottom: 25px;">
                            <div class="form-group">
                                <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: block;">Number of Vehicles</label>
                                <select name="vehicles_needed" required class="form-control premium-select">
                                    <option value="" disabled selected>Select quantity...</option>
                                    <option value="2">2 Vehicles</option>
                                    <option value="3">3 Vehicles</option>
                                    <option value="4-5">4 to 5 Vehicles</option>
                                    <option value="6-10">6 to 10 Vehicles</option>
                                    <option value="10+">More than 10 Vehicles</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: block;">Event Type</label>
                                <select name="event_type" required class="form-control premium-select">
                                    <option value="" disabled selected>Select event...</option>
                                    <option value="Wedding">Wedding</option>
                                    <option value="Corporate Event">Corporate Event</option>
                                    <option value="Funeral Escort">Funeral / Escort</option>
                                    <option value="VIP Transport">VIP / Convoy Transport</option>
                                    <option value="Other">Other Event</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: block;">Date of Event</label>
                            <input type="date" name="event_date" required class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 30px;">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: block;">Vehicle Preferences & Details</label>
                            <textarea name="details" required class="form-control" rows="5" placeholder="Let us know what kind of vehicles you're looking for (e.g., 2 Land Cruisers, 1 Mercedes). Also mention any special arrangements..."></textarea>
                        </div>
                        
                        <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.1rem; font-weight: 800; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Request Quote & Vehicles
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>

    <script>
        document.getElementById('eventForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('btn-loading');
            btn.innerHTML = 'Sending Request...';
        });
    </script>
</body>
</html>
