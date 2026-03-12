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
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Customer';
    $user_email = $_SESSION['user_email'] ?? 'unknown@user.com';

    $event_type = $_POST['event_type'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $duration_days = (int)($_POST['duration_days'] ?? 1);
    $proposed_price = (float)($_POST['proposed_price'] ?? 0);
    $fleet_items = $_POST['fleet_items'] ?? '[]';
    $message_notes = $_POST['details'] ?? '';

    if (empty($event_date) || empty($fleet_items) || $fleet_items === '[]') {
        $error = "Please select an event date and add at least one vehicle to your fleet.";
    } else {
        $subject = "Event Fleet Plan: $event_type";
        $proposed_price = (float)($_POST['proposed_price'] ?? 0);
    $proposed_deposit = (int)($_POST['proposed_deposit_percent'] ?? 25);
    $details = $_POST['details'];
    $event_date = $_POST['event_date'];
    $user_id = $_SESSION['user_id'];
    $duration = $duration_days; // Use the already defined $duration_days

    // Format special message for admin
    $items_arr = json_decode($fleet_items, true);
    $items_list = "";
    $vehicles_count = 0; // Initialize vehicles_count here
    foreach($items_arr as $item) {
        $items_list .= "- " . $item['name'] . " (Qty: " . $item['qty'] . ")\n";
        $vehicles_count += $item['qty']; // Calculate total vehicles
    }

    $full_message = "EVENT FLEET BOOKING REQUEST\n";
    $full_message .= "---------------------------\n";
    $full_message .= "Event Type: $event_type\n";
    $full_message .= "Event Date: $event_date\n";
    $full_message .= "Duration: $duration Days\n\n";
    $full_message .= "REQUESTED VEHICLES:\n$items_list\n";
    $full_message .= "PROPOSED TERMS:\n";
    $full_message .= "- Budget: ZMW " . number_format($proposed_price, 2) . "\n";
    $full_message .= "- Desired Deposit: " . $proposed_deposit . "%\n\n";
    $full_message .= "NOTES:\n$details";

        try {
            $stmt = $pdo->prepare("INSERT INTO support_messages (user_id, subject, message, event_date, duration_days, fleet_items, customer_proposed_price, customer_proposed_deposit) VALUES (?, 'Event / Multi-Car Fleet Request', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $full_message, $event_date, $duration, $fleet_items, $proposed_price, $proposed_deposit]);

            $notif_msg = "Your request for $vehicles_count vehicles is being reviewed. Expect a custom quote shortly.";
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')")->execute([$user_id, "Fleet Plan Received", $notif_msg]);

            sendSupportEmail($user_name, $user_email, $subject, $full_message, 'to_admin');
            $success = "Your Fleet Plan has been submitted! We are now checking availability and applying bulk discounts.";
        } catch (Exception $e) {
            $error = "Submission failed: " . $e->getMessage();
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
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        body { background: transparent !important; }
        .event-banner { background: linear-gradient(135deg, #111, #000); border-radius: 20px; padding: 40px 20px; text-align: center; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.1); }
        .process-timeline { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .step { text-align: center; flex: 1; opacity: 0.3; }
        .step.active { opacity: 1; }
        .step-circle { width: 30px; height: 30px; background: #222; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: 700; color: white; }
        .step.active .step-circle { background: var(--accent-color); }
        
        .fleet-selection-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 25px; max-height: 400px; overflow-y: auto; padding: 5px; }
        .fleet-item-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 12px; text-align: center; transition: all 0.2s; }
        .fleet-item-card:hover { border-color: var(--accent-color); background: rgba(59, 130, 246, 0.05); }
        .fleet-item-card img { width: 100%; height: 60px; object-fit: contain; margin-bottom: 5px; }
        
        #cust-selection-summary { background: #000; border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 15px; padding: 20px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .fq-deposit-presets { display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; margin-top: 5px; }
        .fq-deposit-presets button { padding: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color:white; border-radius: 8px; font-size: 0.7rem; cursor: pointer; }
        .fq-deposit-presets button.active { border-color: var(--success); background: rgba(16, 185, 129, 0.2); color: var(--success); font-weight: 700; }
        
        .form-control { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px; border-radius: 10px; transition: 0.3s; }
        .form-control:focus { border-color: var(--accent-color); outline: none; }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .event-banner { padding: 30px 15px; }
            .event-banner h1 { font-size: 1.5rem; }
            .fleet-selection-grid { 
                grid-template-columns: repeat(2, 1fr) !important; 
                gap: 8px !important;
                max-height: 500px;
            }
            .fleet-item-card { padding: 8px; }
            .fleet-item-card strong { font-size: 0.7rem; }
            .mobile-stack { grid-template-columns: 1fr !important; gap: 20px !important; }
            .fq-deposit-presets { grid-template-columns: repeat(3, 1fr) !important; gap: 4px; }
            .fq-deposit-presets button { padding: 12px 5px !important; font-size: 0.75rem !important; }
        }

        .fq-deposit-presets {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        .fq-deposit-presets button {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            padding: 8px 4px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .fq-deposit-presets button.active {
            background: var(--success);
            border-color: var(--success);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body class="stabilized-car-bg">
    <?php include_once '../includes/mobile_header.php'; ?>


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
        <div class="container" style="max-width: 600px;">
            
            <div class="event-banner">
                <div style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 12px;">
                    <i class="fas fa-gem"></i>
                </div>
                <h1 style="color: white; font-weight: 800; font-size: 2rem; margin-bottom: 8px;">Event & Fleet Booking</h1>
                <p style="color: rgba(255,255,255,0.8); font-size: 0.95rem; max-width: 450px; margin: 0 auto;">Reserve multiple vehicles for weddings, corporate events, or large group travel.</p>
            </div>

            <div class="process-timeline">
                <div class="step active">
                    <div class="step-circle">1</div>
                    <p>Request</p>
                </div>
                <div class="step">
                    <div class="step-circle">2</div>
                    <p>Quote</p>
                </div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <p>Review</p>
                </div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <p>Confirm</p>
                </div>
            </div>

            <?php if($success): ?>
                <div class="data-card" style="text-align: center; padding: 40px 20px;">
                    <div style="width: 80px; height: 80px; background: rgba(16,185,129,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success);"></i>
                    </div>
                    <h2 style="margin-bottom: 15px;">Request Submitted!</h2>
                    <p style="opacity: 0.7; margin-bottom: 30px; font-size: 0.95rem; line-height: 1.6; max-width: 400px; margin-left: auto; margin-right: auto;"><?php echo $success; ?></p>
                    <a href="dashboard.php" class="btn btn-primary" style="padding: 12px 40px;">Return to Dashboard</a>
                </div>
            <?php else: ?>

                <?php if($error): ?>
                    <div class="form-feedback error" style="margin-bottom: 25px; padding: 15px; background: rgba(239,68,68,0.1); border: 1px solid var(--danger); border-radius: 12px; color: var(--danger); text-align:center;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="data-card" style="padding: 30px;">
                    <form action="event-booking.php" method="POST" id="eventForm" onsubmit="return validateAndSubmit()">
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: block;">Event Type</label>
                            <select name="event_type" required class="form-control">
                                <option value="" disabled selected>Select event...</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Corporate Event">Corporate Event</option>
                                <option value="Funeral Escort">Funeral / Escort</option>
                                <option value="VIP Transport">VIP / Convoy Transport</option>
                                <option value="Other">Other Event</option>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                            <div class="form-group">
                                <label style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: block;">Event Start Date</label>
                                <input type="date" name="event_date" id="event_date" required class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="calculateSystemTotal()">
                            </div>
                            <div class="form-group">
                                <label style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: block;">Duration (Days)</label>
                                <input type="number" name="duration_days" id="duration_days" min="1" value="1" class="form-control" onchange="calculateSystemTotal()">
                            </div>
                        </div>

                        <!-- Vehicle Grid Selector -->
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; display: block;">Choose Vehicles for your Fleet</label>
                            <div class="fleet-selection-grid">
                                <?php
                                $stmt = $pdo->query("SELECT id, make, model, image_url, price_per_day FROM vehicles WHERE status = 'available' GROUP BY model ORDER BY make ASC");
                                while($car = $stmt->fetch()):
                                ?>
                                <div class="fleet-item-card">
                                    <img src="../<?php echo $car['image_url']; ?>" alt="Car">
                                    <strong style="font-size: 0.8rem; display: block; color:white;"><?php echo $car['make'].' '.$car['model']; ?></strong>
                                    <small style="color: var(--accent-color); font-weight: 700;">ZMW <?php echo number_format($car['price_per_day'], 0); ?></small>
                                    
                                    <div style="display: flex; gap: 5px; margin-top: 10px; align-items: center;">
                                        <input type="number" id="qty-<?php echo md5($car['model']); ?>" value="1" min="1" style="width: 40px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; font-size: 0.7rem; padding: 4px; border-radius: 5px; text-align: center;">
                                        <button type="button" 
                                                onclick="addCarToRequest('<?php echo $car['make'].' '.$car['model']; ?>', '<?php echo $car['model']; ?>', <?php echo $car['price_per_day']; ?>)" 
                                                class="btn btn-primary" style="flex: 1; padding: 5px; font-size: 0.65rem;">
                                            ADD
                                        </button>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- Selection Summary -->
                        <div id="cust-selection-summary" style="display: none;">
                            <h5 style="color: var(--accent-color); font-size: 0.8rem; margin-bottom: 15px; display: flex; justify-content: space-between;">
                                <span>SELECTED FLEET</span>
                                <span id="item-count-badge" style="background: var(--accent-color); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.65rem;">0 ITEMS</span>
                            </h5>
                            <table style="width: 100%; font-size: 0.85rem;">
                                <tbody id="cust-table-body"></tbody>
                                <tfoot style="border-top: 1px dashed rgba(255,255,255,0.1);">
                                    <tr>
                                        <td colspan="2" style="padding-top: 15px; opacity: 0.6;">Benchmark Total</td>
                                        <td style="padding-top: 15px; text-align: right; font-weight: 800; color: white;">ZMW <span id="system-calc-total">0.00</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Proposed Terms -->
                        <div style="background: rgba(16, 185, 129, 0.04); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 14px; padding: 20px; margin-bottom: 25px;">
                            <label style="color: var(--success); font-weight: 800; font-size: 0.8rem; display: block; margin-bottom: 8px;">PROPOSED BUDGET & TERMS</label>
                            <p style="font-size: 0.7rem; opacity: 0.6; margin-bottom: 15px;">Negotiate your rate or choose a deposit plan.</p>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;" class="mobile-stack">
                                <div class="form-group">
                                    <label style="font-size: 0.7rem;">PROPOSED BUDGET (ZMW)</label>
                                    <input type="number" step="0.01" name="proposed_price" id="proposed_price" class="form-control" style="background: rgba(0,0,0,0.2) !important;">
                                </div>
                                <div class="form-group">
                                    <label style="font-size: 0.7rem;">UPFRONT DEPOSIT</label>
                                    <div class="fq-deposit-presets">
                                        <button type="button" class="active" onclick="setCustDeposit(25)">25%</button>
                                        <button type="button" onclick="setCustDeposit(35)">35%</button>
                                        <button type="button" onclick="setCustDeposit(50)">50%</button>
                                        <button type="button" onclick="setCustDeposit(75)">75%</button>
                                        <button type="button" onclick="setCustDeposit(100)">Full</button>
                                    </div>
                                    <input type="hidden" name="proposed_deposit_percent" id="proposed_deposit_percent" value="25">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 30px;">
                            <label style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: block;">Additional Notes</label>
                            <textarea name="details" required class="form-control" rows="3" placeholder="Any special requests?"></textarea>
                        </div>
                        
                        <input type="hidden" name="fleet_items" id="fleet_items_input">
                        <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; padding: 16px; font-weight: 800; border-radius: 12px;">
                            SUBMIT FLEET PLAN
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include_once '../includes/mobile_nav.php'; ?>

    <script>
        let selectedItems = [];

        async function addCarToRequest(name, model, price) {
            const date = document.getElementById('event_date').value;
            const days = parseInt(document.getElementById('duration_days').value) || 1;
            
            // Get specific QTY for this click
            const md5Key = md5(model);
            const qtyInput = document.getElementById('qty-' + md5Key);
            const qty = parseInt(qtyInput ? qtyInput.value : 1);

            if(!date) {
                alert("Please select an event date first.");
                document.getElementById('event_date').focus();
                return;
            }

            // Real-time Availability Check
            try {
                const response = await fetch(`../api/check_fleet_availability.php?model=${encodeURIComponent(model)}&date=${date}&days=${days}`);
                const data = await response.json();
                if (data.available < qty) {
                    alert(`NOTICE: We only have ${data.available} ${model}(s) available for these dates.`);
                }
            } catch(e) { console.error("Availability check failed", e); }

            const existing = selectedItems.find(i => i.model === model);
            if(existing) {
                existing.qty = qty;
            } else {
                selectedItems.push({ name, model, qty, price });
            }

            renderCustomerItems();
            
            // Visual feedback
            const summary = document.getElementById('cust-selection-summary');
            summary.style.transform = 'scale(1.02)';
            setTimeout(() => summary.style.transform = 'scale(1)', 150);
            summary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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
                            <td style="padding: 10px 0;">
                                <div style="font-weight: 700; color: white;">${item.name}</div>
                                <div style="font-size: 0.7rem; opacity: 0.5;">ZMW ${item.price.toLocaleString()}/day</div>
                            </td>
                            <td style="padding: 10px; text-align: center; color: var(--accent-color); font-weight: 800;">x${item.qty}</td>
                            <td style="padding: 10px; text-align: right;">
                                <button type="button" onclick="removeCustomerItem(${index})" style="background:none; border:none; color:#ef4444; opacity: 0.7; cursor:pointer;">
                                    <i class="fas fa-times"></i>
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

        function calculateSystemTotal() {
            const days = parseInt(document.getElementById('duration_days').value) || 1;
            let total = 0;
            selectedItems.forEach(item => {
                total += (item.price * item.qty * days);
            });
            document.getElementById('system-calc-total').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
            
            // Suggest this total to the proposed price if empty
            const propInput = document.getElementById('proposed_price');
            if(!propInput.value || propInput.dataset.auto === "true") {
                propInput.value = total.toFixed(2);
                propInput.dataset.auto = "true";
            }
        }

        function setCustDeposit(percent) {
            document.getElementById('proposed_deposit_percent').value = percent;
            const buttons = document.querySelectorAll('.fq-deposit-presets button');
            buttons.forEach(btn => {
                btn.classList.remove('active');
                const btnText = btn.innerText.toLowerCase();
                if(btnText.includes(percent + '%') || (percent == 100 && btnText == 'full')) {
                    btn.classList.add('active');
                }
            });
        }

        function validateAndSubmit() {
            if (selectedItems.length === 0) {
                alert("Please add at least one vehicle to your fleet list before submitting.");
                return false;
            }
            
            // Sync selectedItems to hidden input
            document.getElementById('fleet_items_input').value = JSON.stringify(selectedItems);
            
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing Request...';
            btn.style.pointerEvents = 'none';
            btn.disabled = true;
            
            return true;
        }

        // Simple MD5 helper for card ID matching
        function md5(string) {
            function b64_md5(s) { return btoa(s).substring(0, 8); }
            return b64_md5(string); // Simplified matching for the UI
        }
    </script>
</body>
</html>
