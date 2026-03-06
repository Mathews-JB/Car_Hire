<?php
/**
 * Payment Reminder Cron Script
 * ─────────────────────────────────────────────────────────────────────────────
 * Finds bookings that are still 'pending' (unpaid) and sends WhatsApp +
 * in-app reminders to the customer.
 *
 * SCHEDULE (Windows Task Scheduler or Linux cron):
 *   Linux:   0 * * * * php /var/www/html/Car_Higher/scripts/send_payment_reminders.php
 *   Windows: Run via Task Scheduler every hour
 *
 * RULES:
 *   - Only remind bookings that are 2–23 hours old (avoid spamming)
 *   - Only send one reminder per booking (tracked via `reminder_sent_at` column)
 *   - Skip bookings whose pickup date has already passed
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
define('CRON_MODE', true);
$root = dirname(__DIR__);

require_once $root . '/includes/env_loader.php';
require_once $root . '/includes/db.php';
require_once $root . '/includes/whatsapp.php';

// ── Logging ───────────────────────────────────────────────────────────────────
$log_dir  = $root . '/logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log_file = $log_dir . '/payment_reminders.log';

function cron_log(string $msg): void {
    global $log_file;
    $line = "[" . date('Y-m-d H:i:s') . "] {$msg}\n";
    echo $line;
    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

cron_log("=== Payment Reminder Cron Started ===");

// ── Find eligible bookings ────────────────────────────────────────────────────
// Bookings that are:
//   - status = 'pending'
//   - created between 2 and 23 hours ago
//   - pickup_date hasn't passed yet
//   - reminder not already sent (reminder_sent_at IS NULL)
$sql = "
    SELECT 
        b.id          AS booking_id,
        b.total_price,
        b.pickup_date,
        b.dropoff_date,
        b.pickup_location,
        u.id          AS user_id,
        u.name        AS customer_name,
        u.phone,
        u.email,
        v.make,
        v.model
    FROM bookings b
    JOIN users    u ON b.user_id    = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.status = 'pending'
      AND b.created_at BETWEEN (NOW() - INTERVAL 23 HOUR) AND (NOW() - INTERVAL 2 HOUR)
      AND b.pickup_date > NOW()
      AND (b.reminder_sent_at IS NULL OR b.reminder_sent_at = '')
";

try {
    $stmt = $pdo->query($sql);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Column might not exist yet — run migration
    cron_log("ERROR fetching bookings: " . $e->getMessage());
    cron_log("Attempting to add reminder_sent_at column...");
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN reminder_sent_at DATETIME NULL DEFAULT NULL");
        cron_log("Column added. Re-running query...");
        $stmt    = $pdo->query($sql);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        cron_log("FATAL: " . $e2->getMessage());
        exit(1);
    }
}

cron_log("Found " . count($bookings) . " booking(s) needing reminders.");

if (empty($bookings)) {
    cron_log("Nothing to do. Exiting.");
    exit(0);
}

// ── Send reminders ────────────────────────────────────────────────────────────
$wa      = new WhatsAppService();
$sent    = 0;
$failed  = 0;

foreach ($bookings as $booking) {
    $booking_id    = $booking['booking_id'];
    $customer_name = $booking['customer_name'];
    $phone         = $booking['phone'];

    cron_log("Processing booking #{$booking_id} for {$customer_name} ({$phone})");

    $booking_data = [
        'booking_id'      => $booking_id,
        'customer_name'   => $customer_name,
        'vehicle'         => $booking['make'] . ' ' . $booking['model'],
        'pickup_location' => $booking['pickup_location'],
        'pickup_date'     => date('d M Y, H:i', strtotime($booking['pickup_date'])),
        'dropoff_date'    => date('d M Y, H:i', strtotime($booking['dropoff_date'])),
        'total_price'     => $booking['total_price'],
    ];

    $wa_sent = false;

    // 1. WhatsApp reminder
    if (!empty($phone)) {
        $result = $wa->sendPaymentReminder($phone, $booking_data);
        if ($result['success']) {
            cron_log("  ✓ WhatsApp sent to {$phone} (SID: {$result['sid']})");
            $wa_sent = true;
        } else {
            cron_log("  ✗ WhatsApp failed: " . ($result['error'] ?? 'unknown'));
        }
    } else {
        cron_log("  ⚠ No phone number for user #{$booking['user_id']}");
    }

    // 2. In-app notification
    try {
        $notif_stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (?, ?, ?, 'warning', NOW())
        ");
        $notif_stmt->execute([
            $booking['user_id'],
            "⏰ Payment Reminder – Booking #{$booking_id}",
            "Your booking for {$booking['make']} {$booking['model']} is awaiting payment of ZMW " . number_format($booking['total_price'], 2) . ". Please pay to secure your vehicle."
        ]);
        cron_log("  ✓ In-app notification created");
    } catch (PDOException $e) {
        cron_log("  ✗ In-app notification failed: " . $e->getMessage());
    }

    // 3. Mark reminder as sent
    try {
        $update = $pdo->prepare("UPDATE bookings SET reminder_sent_at = NOW() WHERE id = ?");
        $update->execute([$booking_id]);
        $sent++;
    } catch (PDOException $e) {
        cron_log("  ✗ Could not update reminder_sent_at: " . $e->getMessage());
        $failed++;
    }
}

cron_log("=== Done. Sent: {$sent} | Failed: {$failed} ===");
exit(0);
