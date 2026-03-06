<?php
/**
 * Multi-Channel Notification Helper
 * Handles Email, SMS, and WhatsApp (via Twilio)
 */

class NotificationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Send notification through preferred channels
     * Channels: 'db', 'email', 'sms', 'whatsapp'
     */
    public function send($user_id, $title, $message, $channels = ['db', 'email'], $type = 'info') {
        $results = [];

        // Ensure $channels is an array to prevent TypeError in in_array()
        if (!is_array($channels)) {
            $channels = $channels === true ? ['db', 'email', 'sms', 'whatsapp'] : ['db', 'email'];
        }

        // 1. In-App Notification (Database)
        if (in_array('db', $channels)) {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $results['db'] = $stmt->execute([$user_id, $title, $message, $type]);
        }

        // Fetch User Details for External Channels
        $stmt = $this->pdo->prepare("SELECT email, phone, name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) return $results;

        // 2. Email Notification
        if (in_array('email', $channels) && !empty($user['email'])) {
            include_once 'mailer.php';
            $mailer = new CarHireMailer();
            $results['email'] = $mailer->send($user['email'], $title, $message);
        }

        // 3. SMS Notification
        if (in_array('sms', $channels) && !empty($user['phone'])) {
            include_once 'sms.php';
            $results['sms'] = send_sms($user['phone'], strip_tags($message));
        }

        // 4. WhatsApp Notification (Twilio)
        if (in_array('whatsapp', $channels) && !empty($user['phone'])) {
            include_once 'whatsapp.php';
            $wa = new WhatsAppService();
            $wa_result = $wa->send($user['phone'], "📢 *{$title}*\n\n" . strip_tags($message));
            $results['whatsapp'] = $wa_result['success'];
        }

        return $results;
    }

    /**
     * Send a booking confirmation via all channels
     */
    public function sendBookingConfirmation($user_id, array $booking_data) {
        // Fetch user
        $stmt = $this->pdo->prepare("SELECT email, phone, name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) return false;

        $booking_data['customer_name'] = $user['name'];

        // WhatsApp
        if (!empty($user['phone'])) {
            include_once 'whatsapp.php';
            $wa = new WhatsAppService();
            $wa->sendBookingConfirmation($user['phone'], $booking_data);
        }

        // In-app notification
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')");
        $stmt->execute([
            $user_id,
            "Booking Confirmed – #{$booking_data['booking_id']}",
            "Your booking for {$booking_data['vehicle']} has been confirmed. Pickup: {$booking_data['pickup_date']}."
        ]);

        return true;
    }

    /**
     * Send a payment reminder via WhatsApp + DB
     */
    public function sendPaymentReminder($user_id, array $booking_data) {
        $stmt = $this->pdo->prepare("SELECT email, phone, name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) return false;

        $booking_data['customer_name'] = $user['name'];

        if (!empty($user['phone'])) {
            include_once 'whatsapp.php';
            $wa = new WhatsAppService();
            $wa->sendPaymentReminder($user['phone'], $booking_data);
        }

        // In-app
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'warning')");
        $stmt->execute([
            $user_id,
            "Payment Reminder – Booking #{$booking_data['booking_id']}",
            "Your booking for {$booking_data['vehicle']} is awaiting payment of ZMW " . number_format($booking_data['total_price'], 2) . "."
        ]);

        return true;
    }
}
