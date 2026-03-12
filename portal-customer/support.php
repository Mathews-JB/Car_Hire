<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';
include_once '../includes/mailer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$error = '';

// Fetch fleet options for the smart selector
$fleet_stmt = $pdo->query("SELECT make, model, price_per_day, COUNT(*) as qty FROM vehicles WHERE status != 'deleted' GROUP BY model ORDER BY make ASC");
$fleet_options = $fleet_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? 'No Subject';
    $message = $_POST['message'] ?? '';
    $user_name = $_SESSION['user_name'] ?? 'Customer';
    $user_email = $_SESSION['user_email'] ?? 'unknown@user.com'; 

    if (!empty($message)) {
        try {
            $fleet_items = isset($_POST['fleet_items']) ? $_POST['fleet_items'] : null;
            $duration_days = isset($_POST['duration_days']) ? (int)$_POST['duration_days'] : null;
            $proposed_price = isset($_POST['proposed_price']) ? (float)$_POST['proposed_price'] : null;
            $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;

            $stmt = $pdo->prepare("INSERT INTO support_messages (user_id, subject, message, status, fleet_items, duration_days, customer_proposed_price, event_date) VALUES (?, ?, ?, 'new', ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $subject, $message, 'new', $fleet_items, $duration_days, $proposed_price, $event_date]);

            if ($fleet_items) {
                $items = json_decode($fleet_items, true);
                $prop_dep = isset($_POST['proposed_deposit_percent']) ? $_POST['proposed_deposit_percent'] . '%' : 'Standard';
                $message .= "\n\n--- FLEET REQUEST DETAILS ---\n";
                $message .= "Event Date: " . ($event_date ? date('d M Y', strtotime($event_date)) : 'TBD') . "\n";
                $message .= "Duration: $duration_days Days\n";
                foreach($items as $item) {
                    $message .= "- " . $item['name'] . " (Qty: " . $item['qty'] . ")\n";
                }
                $message .= "\nPROPOSED TERMS:\n";
                $message .= "Quote Total: ZMW " . number_format($proposed_price, 2) . "\n";
                $message .= "Desired Deposit: " . $prop_dep . "\n";
            }

            sendSupportEmail($user_name, $user_email, $subject, $message, 'to_admin');
            sendSupportEmail($user_name, $user_email, "We've received your request: $subject", "Hi $user_name,\n\nThank you for reaching out. We have received your message regarding '$subject' and our team will get back to you shortly.", 'to_customer');

            $success = "Your message has been sent successfully.";
        } catch (Exception $e) {
            $error = "Delivery failed: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a message.";
    }
}

// Fetch user message history
$stmt_h = $pdo->prepare("SELECT * FROM support_messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt_h->execute([$_SESSION['user_id']]);
$history = $stmt_h->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        body { background: transparent !important; }
        .fleet-item-card:hover { border-color: var(--accent-color) !important; background: rgba(59, 130, 246, 0.08) !important; transform: translateY(-3px); }
        .fq-deposit-presets button { border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color:white; padding: 8px 2px; border-radius: 8px; font-size: 0.65rem; cursor: pointer; transition: all 0.2s; }
        .fq-deposit-presets button.active { border-color: var(--success); background: rgba(16, 185, 129, 0.15); color: var(--success); font-weight: 700; transform: scale(1.05); }
        #cust-selection-summary { position: relative; overflow: hidden; }
        #cust-selection-summary::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--accent-color), transparent); }
    </style>
</head>
<body class="stabilized-car-bg">
    <?php include_once '../includes/mobile_header.php'; ?>


    <nav class="hub-bar">
        <a href="dashboard.php" class="logo">Car Hire</a>
        <div class="hub-nav">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <a href="browse-vehicles.php"><?php echo __('browse_fleet'); ?></a>
            <a href="my-bookings.php"><?php echo __('my_bookings'); ?></a>
            <a href="support.php" class="active"><?php echo __('support'); ?></a>
            <a href="profile.php"><?php echo __('profile'); ?></a>
        </div>
        <div class="hub-user">
            <!-- Theme Switcher -->
            <?php include_once '../includes/theme_switcher.php'; ?>
            
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
        <div class="container">
            <div class="dashboard-header" style="margin-bottom: 40px;">
                <div>
                    <h1>How can we help?</h1>
                    <p style="color: rgba(255,255,255,0.6);">Get in touch with our team or browse support options.</p>
                </div>
            </div>

            <div class="how-it-works-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 50px;">
                <div class="data-card how-it-works-step" style="border-left: 4px solid var(--primary-color) !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon" style="background: rgba(var(--primary-rgb), 0.1); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                            <i class="fas fa-phone-alt" style="font-size: 1.5rem; color: #3b82f6;"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo __('Call Us') ?? 'Call Us'; ?></h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;">+260 211 123 456</p>
                    </div>
                </div>
                
                <div class="data-card how-it-works-step" style="border-left: 4px solid var(--accent-color) !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon">
                            <i class="fas fa-envelope" style="font-size: 1.5rem; color: var(--accent-color);"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo __('Email') ?? 'Email'; ?></h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;">support@CarHire.zm</p>
                    </div>
                </div>

                <div class="data-card how-it-works-step" style="border-left: 4px solid var(--success) !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon">
                            <i class="fas fa-map-marker-alt" style="font-size: 1.5rem; color: var(--success);"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo __('Visit') ?? 'Visit'; ?></h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;">Lusaka, Great East Rd</p>
                    </div>
                </div>

                <div class="data-card how-it-works-step" style="border-left: 4px solid #8b5cf6 !important;">
                    <div class="how-it-works-content">
                        <div class="step-icon">
                            <i class="fas fa-clock" style="font-size: 1.5rem; color: #8b5cf6;"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;">24/7 Support</h3>
                        <p style="font-size: 0.85rem; opacity: 0.8;">Priority Response</p>
                    </div>
                </div>
            </div>

            <?php if($success): ?>
                <div class="form-feedback success" style="margin-bottom: 25px; padding: 15px; background: rgba(16,185,129,0.1); border: 1px solid var(--success); border-radius: 12px; color: var(--success); text-align:center;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="form-feedback error" style="margin-bottom: 25px; padding: 15px; background: rgba(239,68,68,0.1); border: 1px solid var(--danger); border-radius: 12px; color: var(--danger); text-align:center;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="data-card" style="max-width: 800px; margin: 0 auto;">
                <h3 style="margin-bottom: 25px;">Send a Message</h3>
                <form action="" method="POST" id="fleet-support-form" onsubmit="return validateAndSubmit()">
                    <div class="responsive-form-grid" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label><?php echo __('full_name'); ?></label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" readonly class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('subject') ?? 'Subject'; ?></label>
                            <select name="subject" id="subject-selector" class="form-control premium-select" onchange="toggleFleetBuilder()">
                                <option>Booking Inquiry</option>
                                <option>Event / Multi-Car Fleet</option>
                                <option>Payment Issue</option>
                                <option>Vehicle Feedback</option>
                                <option>Technical Support</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Fleet Builder for Customers -->
                    <div id="customer-fleet-builder" style="display: none; background: rgba(59, 130, 246, 0.03); border: 1px solid rgba(59, 130, 246, 0.1); border-radius: 16px; padding: 25px; margin-bottom: 25px;">
                        <h4 style="margin-bottom: 25px; color: var(--accent-color); display: flex; align-items: center; gap: 10px; font-weight: 800;">
                            <i class="fas fa-car-side"></i> BUILD YOUR EVENT FLEET
                        </h4>
                        
                        <div class="responsive-form-grid" style="margin-bottom: 25px;">
                            <div class="form-group">
                                <label style="font-size: 0.75rem; letter-spacing: 0.5px; opacity: 0.7;">EVENT START DATE</label>
                                <input type="date" name="event_date" id="event_date" class="form-control" style="background: rgba(0,0,0,0.2); color:white; height: 50px; border-radius: 12px;" onchange="calculateSystemTotal()">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.75rem; letter-spacing: 0.5px; opacity: 0.7;">DURATION (DAYS)</label>
                                <input type="number" name="duration_days" id="duration_days" min="1" value="1" class="form-control" onchange="calculateSystemTotal()" style="background: rgba(0,0,0,0.2); color:white; height: 50px; border-radius: 12px;">
                            </div>
                        </div>

                        <!-- Visual Fleet Selection Grid -->
                        <label style="font-size: 0.75rem; letter-spacing: 0.5px; opacity: 0.7; margin-bottom: 12px; display: block;">CHOOSE VEHICLES TO ADD</label>
                        <div id="fleet-selection-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; max-height: 400px; overflow-y: auto; padding-right: 5px;">
                            <?php foreach($fleet_options as $option): ?>
                                <div class="fleet-item-card" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 15px; text-align: center; transition: all 0.2s ease;">
                                    <div style="font-weight: 700; color: white; margin-bottom: 5px; font-size: 0.85rem;"><?php echo htmlspecialchars($option['make'] . ' ' . $option['model']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--accent-color); margin-bottom: 12px;">ZMW <?php echo number_format($option['price_per_day'], 0); ?>/day</div>
                                    
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <input type="number" id="qty-<?php echo md5($option['model']); ?>" value="1" min="1" style="width: 45px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; font-size: 0.8rem; padding: 5px; border-radius: 6px; text-align: center;">
                                        <button type="button" 
                                                onclick="addCarToRequest('<?php echo htmlspecialchars($option['make'] . ' ' . $option['model']); ?>', '<?php echo htmlspecialchars($option['model']); ?>', <?php echo $option['price_per_day']; ?>)" 
                                                class="btn btn-primary" 
                                                style="flex: 1; padding: 6px; font-size: 0.75rem; border-radius: 6px;">
                                            <i class="fas fa-plus"></i> ADD
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Receipt Style Selection Summary -->
                        <div id="cust-selection-summary" style="background: #000; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px; padding: 20px; display: none; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
                            <h5 style="color: var(--accent-color); font-size: 0.8rem; letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                                <span>YOUR REQUESTED FLEET</span>
                                <span id="item-count-badge" style="background: var(--accent-color); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.65rem;">0 ITEMS</span>
                            </h5>
                            
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                                <tbody id="cust-table-body"></tbody>
                                <tfoot style="border-top: 1px dashed rgba(255,255,255,0.1);">
                                    <tr>
                                        <td colspan="2" style="padding-top: 15px; color: var(--text-secondary);">Benchmark Total Cost</td>
                                        <td style="padding-top: 15px; text-align: right; font-weight: 800; font-size: 1rem; color: #fff;">ZMW <span id="system-calc-total">0.00</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Proposed Terms -->
                        <div style="background: rgba(16, 185, 129, 0.04); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 14px; padding: 20px;">
                            <label style="color: var(--success); font-weight: 800; font-size: 0.8rem; display: block; margin-bottom: 8px;">
                                <i class="fas fa-hand-holding-usd"></i> YOUR PROPOSED TERMS
                            </label>
                            <p style="font-size: 0.7rem; color: var(--text-secondary); margin-bottom: 15px;">Set your target budget and deposit plan.</p>
                            
                            <div class="responsive-form-grid">
                                <div>
                                    <label style="font-size: 0.7rem; opacity: 0.6;">PROPOSED BUDGET (ZMW)</label>
                                    <input type="number" step="0.01" name="proposed_price" id="proposed_price" class="form-control" style="background: rgba(0,0,0,0.2); color:white; border-color: rgba(16, 185, 129, 0.2); height: 45px; border-radius: 10px;">
                                </div>
                                <div>
                                    <label style="font-size: 0.7rem; opacity: 0.6;">INTENDED DEPOSIT</label>
                                    <div class="fq-deposit-presets" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-top: 5px;">
                                        <button type="button" onclick="setCustDeposit(25)">25%</button>
                                        <button type="button" onclick="setCustDeposit(35)">35%</button>
                                        <button type="button" onclick="setCustDeposit(50)">50%</button>
                                        <button type="button" onclick="setCustDeposit(75)">75%</button>
                                        <button type="button" onclick="setCustDeposit(100)">Full</button>
                                    </div>
                                    <input type="hidden" name="proposed_deposit_percent" id="proposed_deposit_percent">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="fleet_items" id="fleet_items_input">
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Message</label>
                        <textarea name="message" required style="width: 100%; height: 150px; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;" placeholder="Tell us how we can assist you..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">Send Message</button>
                </form>
            </div>

            <!-- History Section -->
            <?php if(!empty($history)): ?>
                <div class="data-card" style="margin-top: 50px; background: rgba(255,255,255,0.02);">
                    <h3 style="margin-bottom: 25px;"><i class="fas fa-history"></i> My Requests & Quotes</h3>
                    <div style="overflow-x: auto;">
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                    <th style="padding: 15px 10px; opacity: 0.6; font-size: 0.8rem;">Date</th>
                                    <th style="padding: 15px 10px; opacity: 0.6; font-size: 0.8rem;">Subject</th>
                                    <th style="padding: 15px 10px; opacity: 0.6; font-size: 0.8rem;">Status</th>
                                    <th style="padding: 15px 10px; opacity: 0.6; font-size: 0.8rem; text-align: right;">Action/Quote</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($history as $h): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 15px 10px; font-size: 0.85rem; opacity: 0.8;"><?php echo date('d M, Y', strtotime($h['created_at'])); ?></td>
                                        <td style="padding: 15px 10px; font-size: 0.9rem;">
                                            <strong><?php echo htmlspecialchars($h['subject']); ?></strong>
                                            <div style="font-size: 0.75rem; opacity: 0.5; margin-top: 4px;"><?php echo htmlspecialchars(substr($h['message'], 0, 60)) . '...'; ?></div>
                                        </td>
                                        <td style="padding: 15px 10px;">
                                            <span class="status-pill status-<?php echo ($h['status'] === 'replied') ? 'confirmed' : 'pending'; ?>" style="font-size: 0.7rem; padding: 4px 10px;">
                                                <?php echo ($h['status'] === 'replied') ? 'Replied' : (($h['status'] === 'read') ? 'Reviewed' : 'Sent'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px 10px; text-align: right;">
                                            <?php if($h['quote_amount'] > 0): ?>
                                                <div style="margin-bottom: 5px; font-weight: 700; color: #60a5fa; font-size: 0.85rem;">ZMW <?php echo number_format($h['quote_amount'], 2); ?></div>
                                                <?php if($h['quote_status'] === 'sent'): ?>
                                                    <a href="payment.php?booking_id=<?php echo $h['booking_id']; ?>" class="btn btn-primary" style="padding: 6px 15px; font-size: 0.75rem; border-radius: 6px;">
                                                        <i class="fas fa-credit-card"></i> PAY NOW
                                                    </a>
                                                <?php elseif($h['quote_status'] === 'paid'): ?>
                                                    <span class="status-pill status-confirmed" style="background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2);"><i class="fas fa-check"></i> PAID</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="opacity: 0.3; font-size: 0.75rem;">Waiting for Response</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <style>
                .responsive-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                @media (max-width: 600px) {
                    .responsive-form-grid { grid-template-columns: 1fr; }
                    .dashboard-header h1 { font-size: 1.8rem !important; }
                    .table th:nth-child(1), .table td:nth-child(1) { display: none; }
                }
            </style>
        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>
    <script>
        let selectedItems = [];

        function toggleFleetBuilder() {
            const subject = document.getElementById('subject-selector').value;
            const builder = document.getElementById('customer-fleet-builder');
            builder.style.display = (subject === 'Event / Multi-Car Fleet') ? 'block' : 'none';
        }

        async function addCarToRequest(name, model, price) {
            const date = document.getElementById('event_date').value;
            const days = parseInt(document.getElementById('duration_days').value) || 1;
            const qtyInput = document.getElementById('qty-' + btoa(model).replace(/=/g, '')); 
            // Corrected selector for dynamic IDs
            const selectorId = 'qty-' + btoa(model).replace(/=/g, '');
            const qty = parseInt(document.querySelector(`[id^="qty-"]`).value); // Fallback for testing
            
            // Getting specific QTY for the clicked car
            let actualQty = 1;
            const allQtys = document.querySelectorAll('[id^="qty-"]');
            allQtys.forEach(input => {
                if(input.id.includes(hexMD5(model))) actualQty = parseInt(input.value);
            });

            if(!date) {
                alert("Please select an event date first.");
                document.getElementById('event_date').focus();
                return;
            }

            // Real-time Availability Check
            try {
                const response = await fetch(`../api/check_fleet_availability.php?model=${encodeURIComponent(model)}&date=${date}&days=${days}`);
                const data = await response.json();
                if (data.available < actualQty) {
                    alert(`Notice: Only ${data.available} ${model}s available for these dates.`);
                }
            } catch(e) {}

            const existing = selectedItems.find(i => i.name === name);
            if(existing) {
                existing.qty = actualQty;
            } else {
                selectedItems.push({ name, model, qty: actualQty, price });
            }

            renderCustomerItems();
            
            // Visual feedback (Bounce)
            const summary = document.getElementById('cust-selection-summary');
            summary.style.transform = 'scale(1.02)';
            setTimeout(() => summary.style.transform = 'scale(1)', 200);
            
            summary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Helper for MD5 matching used in PHP
        function hexMD5(string) {
            return btoa(string).substring(0,8); // Simplified for JS matching
        }

        function removeCustomerItem(index) {
            selectedItems.splice(index, 1);
            renderCustomerItems();
        }

        function renderCustomerItems() {
            const body = document.getElementById('cust-table-body');
            const summary = document.getElementById('cust-selection-summary');
            const badge = document.getElementById('item-count-badge');
            body.innerHTML = '';
            
            if(selectedItems.length > 0) {
                summary.style.display = 'block';
                badge.innerText = selectedItems.length + ' ITEMS';
                
                selectedItems.forEach((item, index) => {
                    body.innerHTML += `
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 12px 0;">
                                <div style="font-weight: 700; color: white;">${item.name}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">ZMW ${item.price.toLocaleString()}/day</div>
                            </td>
                            <td style="padding: 12px; text-align: center; color: var(--accent-color); font-weight: 800;">x${item.qty}</td>
                            <td style="padding: 12px; text-align: right;">
                                <button type="button" onclick="removeCustomerItem(${index})" style="background:none; border:none; color:#ef4444; cursor:pointer; padding: 5px;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                summary.style.display = 'none';
            }

            calculateSystemTotal();
        }

        function setCustDeposit(percent) {
            document.getElementById('proposed_deposit_percent').value = percent;
            const buttons = document.querySelectorAll('.fq-deposit-presets button');
            buttons.forEach(btn => {
                btn.classList.remove('active');
                if(btn.innerText.includes(percent + '%') || (percent == 100 && btn.innerText == 'Full')) {
                    btn.classList.add('active');
                }
            });
        }

        // Ensure data is saved even if user forgets to click '+'
        function validateAndSubmit() {
            const subject = document.getElementById('subject-selector').value;
            if (subject === 'Event / Multi-Car Fleet') {
                const select = document.getElementById('cust-car');
                // If they have a car selected but list is empty, add it automatically
                if (select.value && selectedItems.length === 0) {
                    addCarToRequest();
                }
                
                // Final sync
                document.getElementById('fleet_items_input').value = JSON.stringify(selectedItems);
            }
            return true;
        }

        function calculateSystemTotal() {
            const daysInput = document.getElementById('duration_days');
            const days = parseInt(daysInput.value) || 1;
            let total = 0;
            selectedItems.forEach(item => {
                total += item.price * item.qty * days;
            });

            document.getElementById('system-calc-total').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('fleet_items_input').value = JSON.stringify(selectedItems);
            
            // Auto-suggest proposed price if empty or 0
            const proposed = document.getElementById('proposed_price');
            if(!proposed.value || proposed.value == 0) {
                proposed.value = total > 0 ? total.toFixed(2) : "";
            }
        }
    </script>
</body>
</html>
