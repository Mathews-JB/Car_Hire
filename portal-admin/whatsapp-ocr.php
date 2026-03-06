<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include_once '../includes/whatsapp.php';
include_once '../includes/sms.php';

$success = '';
$error   = '';
$wa_status = [];
// Last Updated: ' . date('Y-m-d H:i:s');

// ── Check WhatsApp configuration status ──────────────────────────────────────
$wa_configured  = !empty(app_config('TWILIO_ACCOUNT_SID')) && !empty(app_config('TWILIO_AUTH_TOKEN'));
$wa_simulate    = app_config('WHATSAPP_SIMULATE', 'true') !== 'false';
$sms_configured = !empty(app_config('TWILIO_SMS_FROM'));
$sms_simulate   = app_config('SMS_SIMULATE', 'true') !== 'false';
$ocr_google     = !empty(app_config('GOOGLE_VISION_API_KEY'));
$ocr_tesseract  = false;
$tesseract_path = app_config('TESSERACT_PATH', 'tesseract');
exec(escapeshellarg($tesseract_path) . " --version 2>&1", $tess_out, $tess_ret);
if ($tess_ret === 0) $ocr_tesseract = true;

// ── Handle manual send ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $wa = new WhatsAppService();

    if ($_POST['action'] === 'send_test') {
        $phone   = trim($_POST['test_phone'] ?? '');
        $message = trim($_POST['test_message'] ?? '');
        if (empty($phone) || empty($message)) {
            $error = 'Phone number and message are required.';
        } else {
            $result = $wa->send($phone, $message);
            if ($result['success']) {
                $success = "✅ WhatsApp message sent! SID: " . ($result['sid'] ?? 'N/A');
            } else {
                $error = "❌ Failed: " . ($result['error'] ?? 'Unknown error');
            }
        }
    }

    if ($_POST['action'] === 'send_test_sms') {
        $phone   = trim($_POST['test_phone_sms'] ?? '');
        $message = trim($_POST['test_message_sms'] ?? '');
        if (empty($phone) || empty($message)) {
            $error = 'Phone number and message are required.';
        } else {
            $result = send_sms($phone, $message);
            if ($result) {
                $success = "✅ SMS sent successfully!";
            } else {
                $error = "❌ Failed to send SMS. Check your Twilio SMS settings.";
            }
        }
    }

    if ($_POST['action'] === 'send_reminder') {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        if ($booking_id > 0) {
            $stmt = $pdo->prepare("
                SELECT b.*, u.name, u.phone, v.make, v.model
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();

            if ($booking && !empty($booking['phone'])) {
                $result = $wa->sendPaymentReminder($booking['phone'], [
                    'booking_id'      => $booking['id'],
                    'customer_name'   => $booking['name'],
                    'vehicle'         => $booking['make'] . ' ' . $booking['model'],
                    'pickup_location' => $booking['pickup_location'],
                    'pickup_date'     => date('d M Y, H:i', strtotime($booking['pickup_date'])),
                    'dropoff_date'    => date('d M Y, H:i', strtotime($booking['dropoff_date'])),
                    'total_price'     => $booking['total_price'],
                ]);
                $success = $result['success']
                    ? "✅ Payment reminder sent to {$booking['name']} ({$booking['phone']})"
                    : "❌ Failed: " . ($result['error'] ?? 'Unknown');
            } else {
                $error = 'Booking not found or customer has no phone number.';
            }
        }
    }
}

// ── Fetch recent pending bookings for reminder panel ─────────────────────────
$pending_bookings = $pdo->query("
    SELECT b.id, b.total_price, b.pickup_date, b.created_at, b.reminder_sent_at,
           u.name AS customer_name, u.phone,
           v.make, v.model
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.status = 'pending' AND b.pickup_date > NOW()
    ORDER BY b.created_at DESC
    LIMIT 20
")->fetchAll();

// ── Fetch WhatsApp log (last 30 lines) ───────────────────────────────────────
$wa_log_file = __DIR__ . '/../logs/whatsapp.log';
$wa_log_lines = [];
if (file_exists($wa_log_file)) {
    $all_lines    = file($wa_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $wa_log_lines = array_slice(array_reverse($all_lines), 0, 30);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp & OCR Management | Car Hire Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        :root {
            --wa-green: #25D366;
            --wa-dark:  #128C7E;
            --ocr-purple: #7c3aed;
        }

        .admin-page-header {
            margin-bottom: 35px;
        }
        .admin-page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #25D366, #128C7E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
        }
        .admin-page-header p {
            color: rgba(255,255,255,0.5);
            font-size: 0.95rem;
        }

        /* Status Cards */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .status-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .status-icon.green  { background: rgba(37,211,102,0.15); color: #25D366; }
        .status-icon.red    { background: rgba(239,68,68,0.15);  color: #f87171; }
        .status-icon.yellow { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .status-icon.purple { background: rgba(124,58,237,0.15); color: #a78bfa; }
        .status-label { font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-bottom: 3px; }
        .status-value { font-size: 1rem; font-weight: 700; color: white; }

        /* Panels */
        .panel {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .panel-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .panel-title i { color: var(--wa-green); }

        /* Form */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; color: rgba(255,255,255,0.6); margin-bottom: 8px; }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: white;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus { border-color: var(--wa-green); }
        .form-group textarea { resize: vertical; min-height: 100px; }

        /* Buttons */
        .btn-wa {
            padding: 12px 24px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-wa:hover { opacity: 0.85; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(37,211,102,0.3); }

        .btn-sm-wa {
            padding: 6px 14px;
            background: rgba(37,211,102,0.15);
            color: #25D366;
            border: 1px solid rgba(37,211,102,0.3);
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-sm-wa:hover { background: rgba(37,211,102,0.25); }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            padding: 10px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .data-table td {
            padding: 12px 14px;
            font-size: 0.88rem;
            color: rgba(255,255,255,0.8);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        /* Log */
        .log-box {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.6);
            max-height: 300px;
            overflow-y: auto;
            line-height: 1.6;
        }
        .log-box .log-sent    { color: #6ee7b7; }
        .log-box .log-sim     { color: #93c5fd; }
        .log-box .log-fail    { color: #fca5a5; }
        .log-box .log-date    { color: rgba(255,255,255,0.3); }

        /* OCR Setup Guide */
        .setup-step {
            display: flex;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .setup-step:last-child { border-bottom: none; }
        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ocr-purple), #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            flex-shrink: 0;
            color: white;
        }
        .step-content h4 { font-size: 0.9rem; font-weight: 600; color: white; margin-bottom: 4px; }
        .step-content p  { font-size: 0.82rem; color: rgba(255,255,255,0.5); margin: 0; line-height: 1.5; }
        .step-content code {
            background: rgba(255,255,255,0.08);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.78rem;
            color: #a78bfa;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-green  { background: rgba(37,211,102,0.15); color: #25D366; border: 1px solid rgba(37,211,102,0.3); }
        .badge-red    { background: rgba(239,68,68,0.15);  color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .badge-yellow { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .badge-purple { background: rgba(124,58,237,0.15); color: #a78bfa; border: 1px solid rgba(124,58,237,0.3); }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }
        .alert-error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #fca5a5; }

        @media (max-width: 768px) {
            .main-content { padding: 12px !important; overflow-x: hidden !important; }
            .dashboard-header { margin-bottom: 20px !important; text-align: left !important; }
            .dashboard-header h1 { font-size: 1.25rem !important; }
            .dashboard-header p { font-size: 0.75rem !important; line-height: 1.4; }
            
            .status-grid { 
                grid-template-columns: 1fr 1fr !important; 
                gap: 8px !important; 
                margin-bottom: 15px !important;
            }
            .status-card {
                padding: 10px !important;
                background: rgba(255, 255, 255, 0.05) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
            }
            .status-icon {
                width: 30px !important;
                height: 30px !important;
                font-size: 0.85rem !important;
            }
            .status-label { font-size: 0.6rem !important; color: rgba(255,255,255,0.4) !important; }
            .status-value { font-size: 0.72rem !important; }
            
            .grid-2 { grid-template-columns: 1fr !important; gap: 15px !important; }
            .panel { 
                padding: 15px !important; 
                margin-bottom: 15px !important;
                background: rgba(15, 15, 20, 0.8) !important; /* Darker, more solid for contrast */
                backdrop-filter: blur(10px);
                border-radius: 12px !important;
            }
            .panel-title { 
                font-size: 0.95rem !important; 
                margin-bottom: 12px !important; 
                padding-bottom: 8px !important;
                flex-wrap: wrap !important;
            }
            .panel-title i { font-size: 1rem !important; }
            .panel-title .badge { margin-left: 0 !important; margin-top: 5px !important; width: 100% !important; justify-content: flex-start !important; }
            
            p, code, span, li { word-break: break-word !important; }

            .form-group label { font-size: 0.72rem !important; }
            .form-group input, .form-group textarea, .form-group select {
                padding: 10px !important;
                font-size: 0.8rem !important;
                border-radius: 8px !important;
            }
            .btn-wa { width: 100% !important; justify-content: center !important; font-size: 0.82rem !important; padding: 10px !important; }

            .setup-step { gap: 10px !important; padding: 10px 0 !important; align-items: flex-start !important; }
            .step-num { width: 22px !important; height: 22px !important; font-size: 0.65rem !important; }
            .step-content h4 { font-size: 0.8rem !important; }
            .step-content p { font-size: 0.72rem !important; color: rgba(255,255,255,0.4) !important; }
            .step-content code { font-size: 0.68rem !important; background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.05) !important; }

            .kw-item { 
                padding: 8px !important; 
                background: rgba(255,255,255,0.02) !important;
                border: 1px solid rgba(255,255,255,0.05) !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 5px !important;
            }
            .kw-item code { font-size: 0.72rem !important; }
            .kw-item span { text-align: left !important; font-size: 0.75rem !important; }

            #ocrResult { padding: 12px !important; margin-top: 15px !important; }
            #ocrDetectedNum { font-size: 0.95rem !important; }
            
            .production-box { padding: 12px !important; }
            .production-box ol { padding-left: 15px !important; gap: 8px !important; }
            .production-box li { font-size: 0.75rem !important; margin-bottom: 5px !important; line-height: 1.4 !important; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
    <?php include_once '../includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="dashboard-header">
            <div>
                <h1 style="background: linear-gradient(135deg, #25D366, #128C7E); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><i class="fab fa-whatsapp" style="font-size:1.4rem; -webkit-text-fill-color: #25D366;"></i> WhatsApp & OCR</h1>
                <p class="text-secondary">Configure WhatsApp Business messaging and OCR document verification.</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle" style="margin-right:8px;"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- ── Status Overview ─────────────────────────────────────────────── -->
        <div class="status-grid">
            <div class="status-card">
                <div class="status-icon <?php echo $wa_configured ? 'green' : 'red'; ?>">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div>
                    <div class="status-label">Twilio WhatsApp</div>
                    <div class="status-value"><?php echo $wa_configured ? 'Configured' : 'Not Set Up'; ?></div>
                </div>
            </div>
            <div class="status-card">
                <div class="status-icon <?php echo $wa_simulate ? 'yellow' : 'green'; ?>">
                    <i class="fas fa-<?php echo $wa_simulate ? 'flask' : 'paper-plane'; ?>"></i>
                </div>
                <div>
                    <div class="status-label">Send Mode</div>
                    <div class="status-value"><?php echo $wa_simulate ? 'Simulation (Log)' : 'Live (Real)'; ?></div>
                </div>
            </div>
            <div class="status-card">
                <div class="status-icon <?php echo $ocr_google ? 'green' : 'yellow'; ?>">
                    <i class="fab fa-google"></i>
                </div>
                <div>
                    <div class="status-label">Google Vision OCR</div>
                    <div class="status-value"><?php echo $ocr_google ? 'Configured' : 'Not Set Up'; ?></div>
                </div>
            </div>
            <div class="status-card">
                <div class="status-icon <?php echo ($sms_configured && !$sms_simulate) ? 'green' : ($sms_simulate ? 'yellow' : 'red'); ?>">
                    <i class="fas fa-sms"></i>
                </div>
                <div>
                    <div class="status-label">Direct SMS</div>
                    <div class="status-value"><?php echo $sms_simulate ? 'Simulation' : ($sms_configured ? 'Live' : 'Not Set Up'); ?></div>
                </div>
            </div>
        </div>

        <div class="grid-2" style="gap: 25px;">

            <!-- ── Test WhatsApp Send ──────────────────────────────────────── -->
            <div class="panel">
                <div class="panel-title">
                    <i class="fab fa-whatsapp"></i> Send Test Message
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="send_test">
                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Phone Number (Zambian format)</label>
                        <input type="text" name="test_phone" placeholder="e.g. 0961234567 or +260961234567" required>
                    </div>
                    <div class="form-group" style="margin-bottom:20px;">
                        <label>Message</label>
                        <textarea name="test_message" placeholder="Type your test message here...">Hello from Car Hire! 🚗 This is a test message from our WhatsApp Business integration.</textarea>
                    </div>
                    <button type="submit" class="btn-wa">
                        <i class="fab fa-whatsapp"></i> Send WhatsApp
                    </button>
                    <?php if ($wa_simulate): ?>
                        <p style="font-size:0.78rem; color:rgba(255,255,255,0.4); margin-top:10px;">
                            <i class="fas fa-flask" style="color:#fbbf24;"></i> Simulation mode — messages are logged.
                        </p>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ── Test Direct SMS Send ─────────────────────────────────────── -->
            <div class="panel">
                <div class="panel-title">
                    <i class="fas fa-sms"></i> Send Direct SMS
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="send_test_sms">
                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Phone Number (Zambian format)</label>
                        <input type="text" name="test_phone_sms" placeholder="e.g. 0961234567 or +260961234567" required>
                    </div>
                    <div class="form-group" style="margin-bottom:20px;">
                        <label>Message</label>
                        <textarea name="test_message_sms" placeholder="Type your SMS here...">Car Hire: Your vehicle is ready for pickup! 🚗</textarea>
                    </div>
                    <button type="submit" class="btn-wa" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="fas fa-paper-plane"></i> Send Direct SMS
                    </button>
                    <?php if ($sms_simulate): ?>
                        <p style="font-size:0.78rem; color:rgba(255,255,255,0.4); margin-top:10px;">
                            <i class="fas fa-flask" style="color:#fbbf24;"></i> Simulation mode — SMS logged in <code>logs/sms.log</code>.
                        </p>
                    <?php endif; ?>
                </form>
            </div>
            <!-- ── WhatsApp Chatbot Info ───────────────────────────────────── -->
            <div class="panel">
                <div class="panel-title">
                    <i class="fas fa-robot"></i> Chatbot Keywords
                </div>
                <p style="font-size:0.85rem; color:rgba(255,255,255,0.5); margin-bottom:18px;">
                    Customers can message your WhatsApp number with these keywords to get instant automated replies:
                </p>
                <div style="display:grid; gap:8px;">
                    <?php
                    $keywords = [
                        ['hi / hello',   'Welcome menu with options'],
                        ['1 / book',     'Vehicle booking link'],
                        ['2 / booking',  'Check booking status'],
                        ['3 / pay',      'Payment help & methods'],
                        ['4 / verify',   'Account verification guide'],
                        ['5 / support',  'Contact support team'],
                        ['price / rate', 'Vehicle pricing info'],
                        ['cancel',       'Cancellation policy'],
                        ['agent',        'Connect to human agent'],
                    ];
                    foreach ($keywords as [$kw, $desc]):
                    ?>
                    <div class="kw-item" style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:rgba(255,255,255,0.03); border-radius:8px; font-size:0.82rem; gap:10px;">
                        <code style="color:#25D366; background:rgba(37,211,102,0.1); padding:2px 8px; border-radius:4px;"><?php echo $kw; ?></code>
                        <span style="color:rgba(255,255,255,0.5); text-align: right;"><?php echo $desc; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:18px; padding:12px; background:rgba(37,211,102,0.05); border:1px dashed rgba(37,211,102,0.2); border-radius:10px; font-size:0.8rem; color:rgba(255,255,255,0.5);">
                    <i class="fas fa-info-circle" style="color:#25D366; margin-right:6px;"></i>
                    Webhook URL: <code style="color:#93c5fd;"><?php echo (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/'); ?>api/whatsapp-webhook.php</code>
                </div>
            </div>

            <!-- ── OCR Test Tool ────────────────────────────────────────────── -->
            <div class="panel">
                <div class="panel-title">
                    <i class="fas fa-search" style="color:var(--ocr-purple);"></i> Live OCR Tester
                </div>
                <p style="font-size:0.85rem; color:rgba(255,255,255,0.5); margin-bottom:18px;">
                    Upload a photo to test your Google Vision integration.
                </p>
                <form id="ocrTestForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="doc_type" value="NRC">
                    <div class="form-group" style="margin-bottom:20px;">
                        <label>Select Document Image (Any)</label>
                        <input type="file" name="image" id="test_ocr_file" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn-wa" id="testOCRBtn" style="background: linear-gradient(135deg, var(--ocr-purple), #4f46e5); width: 100%; justify-content: center;">
                        <i class="fas fa-magic"></i> Run OCR Test
                    </button>
                </form>

                <div id="ocrResult" style="display:none; margin-top:20px; padding:15px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:12px;">
                    <div id="ocrStatusBadge" class="badge" style="margin-bottom:10px;"></div>
                    
                    <div style="font-size:0.8rem; color:rgba(255,255,255,0.4); margin-bottom:4px;">Method Used:</div>
                    <div id="ocrMethod" style="font-size:0.85rem; color:#93c5fd; font-weight:700; margin-bottom:12px;">-</div>

                    <div style="font-size:0.8rem; color:rgba(255,255,255,0.4); margin-bottom:4px;">Detected Number:</div>
                    <div id="ocrDetectedNum" style="font-size:1.1rem; font-weight:700; color:white; margin-bottom:12px;">-</div>

                    <div style="font-size:0.8rem; color:rgba(255,255,255,0.4); margin-bottom:4px;">Full Text Preview:</div>
                    <div id="ocrRawText" style="font-size:0.75rem; color:rgba(255,255,255,0.5); font-family:monospace; white-space:pre-wrap; max-height:100px; overflow-y:auto; padding:8px; background:rgba(255,255,255,0.05); border-radius:6px;"></div>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('ocrTestForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('testOCRBtn');
            const resultBox = document.getElementById('ocrResult');
            const formData = new FormData(this);
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
            resultBox.style.display = 'none';

            try {
                const response = await fetch('../api/ocr-verify.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                resultBox.style.display = 'block';
                const badge = document.getElementById('ocrStatusBadge');
                
                if (data.success) {
                    badge.className = data.is_valid_format ? 'badge badge-green' : 'badge badge-yellow';
                    badge.innerHTML = data.is_valid_format ? '<i class="fas fa-check"></i> Success' : '<i class="fas fa-exclamation-triangle"></i> Text Found';
                    
                    document.getElementById('ocrMethod').innerText = data.method.toUpperCase();
                    document.getElementById('ocrDetectedNum').innerText = data.detected_number || 'None detected';
                    document.getElementById('ocrRawText').innerText = data.extracted_text;
                } else {
                    badge.className = 'badge badge-red';
                    badge.innerHTML = '<i class="fas fa-times"></i> Error: ' + (data.error || 'Failed');
                    document.getElementById('ocrMethod').innerText = 'ERROR';
                    document.getElementById('ocrDetectedNum').innerText = '-';
                    document.getElementById('ocrRawText').innerText = data.error || 'No text extracted';
                }
            } catch (err) {
                alert('Connection error. Check your server.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> Run OCR Test';
            }
        });
        </script>

        <!-- ── Pending Bookings – Send Reminders ─────────────────────────── -->
        <div class="panel">
            <div class="panel-title">
                <i class="fas fa-bell"></i> Pending Bookings – Send Payment Reminders
                <span class="badge badge-yellow" style="margin-left:auto;"><?php echo count($pending_bookings); ?> pending</span>
            </div>
            <?php if (empty($pending_bookings)): ?>
                <p style="color:rgba(255,255,255,0.4); text-align:center; padding:30px 0; font-size:0.9rem;">
                    <i class="fas fa-check-circle" style="color:#25D366; font-size:1.5rem; display:block; margin-bottom:10px;"></i>
                    No pending bookings at the moment.
                </p>
            <?php else: ?>
                <div class="table-container" style="margin-top: 20px;">
                    <table class="data-table admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Vehicle</th>
                                <th>Amount</th>
                                <th>Pickup</th>
                                <th>Reminder</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_bookings as $b): ?>
                            <tr>
                                <td data-label="ID"><strong>#<?php echo $b['id']; ?></strong></td>
                                <td data-label="Guest"><?php echo htmlspecialchars($b['customer_name']); ?></td>
                                <td data-label="Phone">
                                    <?php if ($b['phone']): ?>
                                        <span style="color:#25D366;"><?php echo htmlspecialchars($b['phone']); ?></span>
                                    <?php else: ?>
                                        <span style="color:rgba(255,255,255,0.3);">No phone</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Car"><?php echo htmlspecialchars($b['make'] . ' ' . $b['model']); ?></td>
                                <td data-label="Price"><strong>ZMW <?php echo number_format($b['total_price'], 0); ?></strong></td>
                                <td data-label="Pickup" style="font-size:0.8rem;"><?php echo date('d M, H:i', strtotime($b['pickup_date'])); ?></td>
                                <td data-label="Reminder Status">
                                    <?php if ($b['reminder_sent_at']): ?>
                                        <span class="badge badge-green"><i class="fas fa-check"></i> Sent</span>
                                    <?php else: ?>
                                        <span class="badge badge-yellow">Not sent</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions" style="text-align: right;">
                                    <?php if ($b['phone']): 
                                        $clean_phone = preg_replace('/[^0-9]/', '', $b['phone']);
                                        if (str_starts_with($clean_phone, '0')) $clean_phone = '260' . substr($clean_phone, 1);
                                    ?>
                                    <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="send_reminder">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" class="btn-sm-wa" title="Send Automated Reminder">
                                                <i class="fas fa-robot"></i> Remind
                                            </button>
                                        </form>
                                        <a href="https://wa.me/<?php echo $clean_phone; ?>" target="_blank" class="btn-sm-wa" style="background: rgba(37, 211, 102, 0.25); color: #fff; text-decoration: none; display: flex; align-items: center;" title="Open Real Chat">
                                            <i class="fab fa-whatsapp"></i> Chat
                                        </a>
                                    </div>
                                    <?php else: ?>
                                        <span style="font-size:0.78rem; color:rgba(255,255,255,0.3);">No phone</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p style="font-size:0.8rem; color:rgba(255,255,255,0.3); margin-top:15px;">
                    <i class="fas fa-clock"></i> Automated reminders run hourly via cron: <code>php scripts/send_payment_reminders.php</code>
                </p>
            <?php endif; ?>
        </div>

        <!-- ── OCR Setup Guide ────────────────────────────────────────────── -->
        <div class="panel">
            <div class="panel-title" style="color:white;">
                <i class="fas fa-magic" style="color:#a78bfa;"></i> OCR Document Verification – Setup Guide
            </div>
            <p style="font-size:0.85rem; color:rgba(255,255,255,0.5); margin-bottom:22px;">
                The OCR system automatically extracts NRC and Driver's License numbers from uploaded document photos. Choose one of the two options below:
            </p>

            <div class="grid-2" style="gap:25px;">
                <!-- Option 1: Google Vision -->
                <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:22px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                        <div style="width:36px; height:36px; background:rgba(66,133,244,0.15); border-radius:8px; display:flex; align-items:center; justify-content:center;">
                            <i class="fab fa-google" style="color:#4285f4;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; color:white; font-size:0.95rem;">Google Cloud Vision</div>
                            <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);">Recommended · High accuracy</div>
                        </div>
                        <?php 
                        $key_preview = app_config('GOOGLE_VISION_API_KEY');
                        $is_configured = !empty($key_preview);
                        $masked_key = $is_configured ? substr($key_preview, 0, 8) . '...' . substr($key_preview, -4) : 'Not set';
                        ?>
                        <span class="badge <?php echo $is_configured ? 'badge-green' : 'badge-red'; ?>" style="margin-left:auto;" title="<?php echo $is_configured ? 'Key: ' . $masked_key : 'Key not found in .env'; ?>">
                            <?php echo $is_configured ? '✓ Active (' . $masked_key . ')' : '✗ Not set'; ?>
                        </span>
                        <?php if (!$ocr_google): ?>
                        <!-- DEBUG: 
                             API Key Env: '<?php echo getenv('GOOGLE_VISION_API_KEY'); ?>' 
                             API Key $_ENV: '<?php echo $_ENV['GOOGLE_VISION_API_KEY'] ?? 'N/A'; ?>'
                             app_config: '<?php echo app_config('GOOGLE_VISION_API_KEY', 'default_null'); ?>'
                        -->
                        <?php endif; ?>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">1</div>
                        <div class="step-content">
                            <h4>Create Google Cloud Project</h4>
                            <p>Go to <a href="https://console.cloud.google.com" target="_blank" style="color:#93c5fd;">console.cloud.google.com</a> and create a new project.</p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">2</div>
                        <div class="step-content">
                            <h4>Enable Vision API</h4>
                            <p>Search for "Cloud Vision API" and click Enable. Free tier: 1,000 requests/month.</p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">3</div>
                        <div class="step-content">
                            <h4>Create API Key</h4>
                            <p>Go to APIs &amp; Services → Credentials → Create API Key. Restrict it to Vision API only.</p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">4</div>
                        <div class="step-content">
                            <h4>Add to .env</h4>
                            <p><code>GOOGLE_VISION_API_KEY=your_key_here</code></p>
                        </div>
                    </div>
                </div>

                <!-- Option 2: Tesseract -->
                <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:22px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                        <div style="width:36px; height:36px; background:rgba(124,58,237,0.15); border-radius:8px; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-eye" style="color:#a78bfa;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; color:white; font-size:0.95rem;">Tesseract OCR</div>
                            <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);">Free · Works offline</div>
                        </div>
                        <span class="badge <?php echo $ocr_tesseract ? 'badge-green' : 'badge-yellow'; ?>" style="margin-left:auto;">
                            <?php echo $ocr_tesseract ? '✓ Installed' : '⚠ Not found'; ?>
                        </span>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">1</div>
                        <div class="step-content">
                            <h4>Install Tesseract (Windows)</h4>
                            <p>Download from <a href="https://github.com/UB-Mannheim/tesseract/wiki" target="_blank" style="color:#93c5fd;">UB-Mannheim/tesseract</a>. Install to <code>C:\Program Files\Tesseract-OCR\</code></p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">2</div>
                        <div class="step-content">
                            <h4>Install Tesseract (Linux)</h4>
                            <p><code>sudo apt install tesseract-ocr</code></p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">3</div>
                        <div class="step-content">
                            <h4>Update .env path</h4>
                            <p>Windows: <code>TESSERACT_PATH=C:\Program Files\Tesseract-OCR\tesseract.exe</code><br>Linux: <code>TESSERACT_PATH=tesseract</code></p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num">4</div>
                        <div class="step-content">
                            <h4>Verify Installation</h4>
                            <p>Run: <code>tesseract --version</code> in terminal. Status above shows: <strong style="color:<?php echo $ocr_tesseract ? '#25D366' : '#fbbf24'; ?>"><?php echo $ocr_tesseract ? 'Installed ✓' : 'Not found'; ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── WhatsApp Setup Guide ───────────────────────────────────────── -->
        <div class="panel">
            <div class="panel-title">
                <i class="fab fa-whatsapp"></i> WhatsApp Business Setup Guide
            </div>
            <div class="grid-2" style="gap:25px;">
                <div>
                    <h4 style="color:white; font-size:0.9rem; margin-bottom:14px;">Quick Start (Sandbox – Free)</h4>
                    <div class="setup-step">
                        <div class="step-num" style="background:linear-gradient(135deg,#25D366,#128C7E);">1</div>
                        <div class="step-content">
                            <h4>Create Twilio Account</h4>
                            <p>Sign up free at <a href="https://www.twilio.com" target="_blank" style="color:#93c5fd;">twilio.com</a>. No credit card needed for sandbox.</p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num" style="background:linear-gradient(135deg,#25D366,#128C7E);">2</div>
                        <div class="step-content">
                            <h4>Enable WhatsApp Sandbox</h4>
                            <p>Console → Messaging → Try it out → Send a WhatsApp message. Join sandbox by texting <code>join [word]</code> to <code>+1 415 523 8886</code>.</p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num" style="background:linear-gradient(135deg,#25D366,#128C7E);">3</div>
                        <div class="step-content">
                            <h4>Get Credentials</h4>
                            <p>Console → Account Info → Copy <code>Account SID</code> and <code>Auth Token</code>.</p>
                        </div>
                    </div>
                    <div class="setup-step">
                        <div class="step-num" style="background:linear-gradient(135deg,#25D366,#128C7E);">4</div>
                        <div class="step-content">
                            <h4>Update .env</h4>
                            <p><code>TWILIO_ACCOUNT_SID=ACxxx</code><br><code>TWILIO_AUTH_TOKEN=xxx</code><br><code>WHATSAPP_SIMULATE=false</code></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 style="color:white; font-size:0.9rem; margin-bottom:14px;">Production (Official WhatsApp Business API)</h4>
                    <div class="production-box" style="background:rgba(37,211,102,0.05); border:1px solid rgba(37,211,102,0.15); border-radius:12px; padding:18px; font-size:0.83rem; color:rgba(255,255,255,0.6); line-height:1.7;">
                        <p style="margin:0 0 12px;">For a dedicated Zambian WhatsApp Business number:</p>
                        <ol style="padding-left:18px; margin:0; display:grid; gap:8px;">
                            <li>Apply for WhatsApp Business API at <a href="https://www.twilio.com/whatsapp/request-access" target="_blank" style="color:#25D366;">twilio.com/access</a></li>
                            <li>Provide your business details and Facebook Manager ID</li>
                            <li>Get a Zambian (+260) number approved by Meta</li>
                            <li>Update <code>TWILIO_WHATSAPP_FROM</code></li>
                            <li>Set up webhook URL pointing to your server</li>
                        </ol>
                        <p style="margin:12px 0 0; color:rgba(255,255,255,0.4);">Approval takes 2–5 business days. Sandbox works immediately for testing.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── WhatsApp Log ───────────────────────────────────────────────── -->
        <div class="panel">
            <div class="panel-title">
                <i class="fas fa-terminal"></i> WhatsApp Activity Log
                <span style="font-size:0.75rem; color:rgba(255,255,255,0.3); margin-left:auto; font-weight:400;">Last 30 entries · <?php echo basename($wa_log_file); ?></span>
            </div>
            <div class="log-box">
                <?php if (empty($wa_log_lines)): ?>
                    <span style="color:rgba(255,255,255,0.3);">No log entries yet. Send a test message above to see activity here.</span>
                <?php else: ?>
                    <?php foreach ($wa_log_lines as $line): ?>
                        <?php
                        $class = 'log-default';
                        if (str_contains($line, 'SIMULATE')) $class = 'log-sim';
                        elseif (str_contains($line, 'SENT') || str_contains($line, '✓')) $class = 'log-sent';
                        elseif (str_contains($line, 'FAIL') || str_contains($line, 'ERROR')) $class = 'log-fail';
                        ?>
                        <div class="<?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main><!-- /.main-content -->
    </div><!-- /.admin-layout -->
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
