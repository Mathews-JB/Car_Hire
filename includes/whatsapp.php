<?php
/**
 * WhatsApp Business API Integration
 * Uses Twilio WhatsApp API (most reliable for Zambia)
 * 
 * Setup: Get a Twilio account at https://www.twilio.com
 * Enable WhatsApp Sandbox or apply for WhatsApp Business API
 * Add to .env:
 *   TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 *   TWILIO_AUTH_TOKEN=your_auth_token
 *   TWILIO_WHATSAPP_FROM=whatsapp:+14155238886   (sandbox) or your approved number
 *   WHATSAPP_SIMULATE=true   (set to false in production)
 */

class WhatsAppService {

    private string $account_sid;
    private string $auth_token;
    private string $from_number;
    private bool   $simulate;
    private string $log_file;

    public function __construct() {
        $this->account_sid  = getenv('TWILIO_ACCOUNT_SID')  ?: '';
        $this->auth_token   = getenv('TWILIO_AUTH_TOKEN')   ?: '';
        $this->from_number  = getenv('TWILIO_WHATSAPP_FROM') ?: 'whatsapp:+14155238886';
        $this->simulate     = getenv('WHATSAPP_SIMULATE') !== 'false';
        $this->log_file     = __DIR__ . '/../logs/whatsapp.log';

        // Ensure log directory exists
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Core send method
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send a WhatsApp message to a phone number.
     *
     * @param string $to      Zambian number e.g. 0961234567 or +260961234567
     * @param string $message Plain-text message body
     * @return array ['success' => bool, 'sid' => string|null, 'error' => string|null]
     */
    public function send(string $to, string $message): array {
        $to = $this->formatZambianNumber($to);

        if ($this->simulate || empty($this->account_sid)) {
            return $this->simulateSend($to, $message);
        }

        return $this->twilioSend($to, $message);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Pre-built message templates
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send booking confirmation via WhatsApp
     */
    public function sendBookingConfirmation(string $phone, array $booking): array {
        $msg  = "🚗 *Car Hire – Booking Confirmed!*\n\n";
        $msg .= "Hello {$booking['customer_name']}! 👋\n\n";
        $msg .= "Your reservation is confirmed. Here are your details:\n\n";
        $msg .= "📋 *Booking Ref:* #{$booking['booking_id']}\n";
        $msg .= "🚙 *Vehicle:* {$booking['vehicle']}\n";
        $msg .= "📍 *Pickup:* {$booking['pickup_location']}\n";
        $msg .= "📅 *From:* {$booking['pickup_date']}\n";
        $msg .= "📅 *To:* {$booking['dropoff_date']}\n";
        $msg .= "💰 *Total:* ZMW " . number_format($booking['total_price'], 2) . "\n\n";
        $msg .= "To pay or manage your booking, visit:\n";
        $msg .= (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/my-bookings.php\n\n";
        $msg .= "Need help? Reply *HELP* anytime. 🙏\n";
        $msg .= "_Car Hire – Drive in Style_";

        return $this->send($phone, $msg);
    }

    /**
     * Send payment reminder via WhatsApp
     */
    public function sendPaymentReminder(string $phone, array $booking): array {
        $msg  = "⏰ *Car Hire – Payment Reminder*\n\n";
        $msg .= "Hi {$booking['customer_name']},\n\n";
        $msg .= "Your booking *#{$booking['booking_id']}* for *{$booking['vehicle']}* is awaiting payment.\n\n";
        $msg .= "💰 *Amount Due:* ZMW " . number_format($booking['total_price'], 2) . "\n";
        $msg .= "📅 *Pickup:* {$booking['pickup_date']}\n\n";
        $msg .= "Pay now to secure your vehicle:\n";
        $msg .= (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/payment.php?booking_id={$booking['booking_id']}\n\n";
        $msg .= "⚠️ Unpaid bookings may be released after 24 hours.\n";
        $msg .= "_Car Hire Team_";

        return $this->send($phone, $msg);
    }

    /**
     * Send payment success notification via WhatsApp
     */
    public function sendPaymentSuccess(string $phone, array $booking): array {
        $msg  = "✅ *Car Hire – Payment Received!*\n\n";
        $msg .= "Hi {$booking['customer_name']},\n\n";
        $msg .= "We've received your payment of *ZMW " . number_format($booking['total_price'], 2) . "* for booking *#{$booking['booking_id']}*.\n\n";
        $msg .= "🚗 *Vehicle:* {$booking['vehicle']}\n";
        $msg .= "📅 *Pickup:* {$booking['pickup_date']}\n";
        $msg .= "📍 *Location:* {$booking['pickup_location']}\n\n";
        $msg .= "Please bring your *Driver's License* and *NRC* on pickup day.\n\n";
        $msg .= "View your receipt:\n";
        $msg .= (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/receipt.php?booking_id={$booking['booking_id']}\n\n";
        $msg .= "Safe travels! 🙏\n_Car Hire Team_";

        return $this->send($phone, $msg);
    }

    /**
     * Send verification status update via WhatsApp
     */
    public function sendVerificationUpdate(string $phone, string $customer_name, string $status, string $reason = ''): array {
        if ($status === 'approved') {
            $msg  = "🎉 *Car Hire – Verification Approved!*\n\n";
            $msg .= "Congratulations {$customer_name}! 🥳\n\n";
            $msg .= "Your identity has been verified. You can now book any vehicle in our fleet.\n\n";
            $msg .= "Browse vehicles:\n";
            $msg .= (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/browse-vehicles.php\n\n";
            $msg .= "_Car Hire Team_";
        } else {
            $msg  = "❌ *Car Hire – Verification Update*\n\n";
            $msg .= "Hi {$customer_name},\n\n";
            $msg .= "Unfortunately, your verification was not approved.\n\n";
            if ($reason) {
                $msg .= "📝 *Reason:* {$reason}\n\n";
            }
            $msg .= "Please re-upload clear, valid documents:\n";
            $msg .= (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/verify-profile.php\n\n";
            $msg .= "Need help? Reply *HELP*\n_Car Hire Team_";
        }

        return $this->send($phone, $msg);
    }

    /**
     * Handle incoming chatbot messages (webhook handler)
     * Returns a response message based on keywords
     */
    public function handleChatbotMessage(string $incoming_message, string $from_phone): string {
        $msg = strtolower(trim($incoming_message));

        // Greeting
        if (in_array($msg, ['hi', 'hello', 'hey', 'hie', 'muli bwanji', 'mwabonwa'])) {
            return "👋 *Welcome to Car Hire!*\n\nHow can I help you today? Reply with a number:\n\n1️⃣ Book a vehicle\n2️⃣ Check my booking\n3️⃣ Payment help\n4️⃣ Verify my account\n5️⃣ Contact support\n\nOr visit: " . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/');
        }

        // Menu options
        if ($msg === '1' || str_contains($msg, 'book')) {
            return "🚗 *Book a Vehicle*\n\nBrowse our fleet and book online:\n" . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "our-fleet.php\n\nWe have sedans, SUVs, and luxury vehicles available in Lusaka. 🇿🇲";
        }

        if ($msg === '2' || str_contains($msg, 'booking') || str_contains($msg, 'reservation')) {
            return "📋 *Check Your Booking*\n\nLog in to view your bookings:\n" . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/my-bookings.php\n\nIf you need urgent help, reply *AGENT* to speak with our team.";
        }

        if ($msg === '3' || str_contains($msg, 'pay') || str_contains($msg, 'payment')) {
            return "💳 *Payment Help*\n\nWe accept:\n• MTN Mobile Money\n• Airtel Money\n• Zamtel Kwacha\n• Visa / Mastercard\n\nTo pay for a booking:\n" . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/my-bookings.php\n\nFor payment issues, reply *AGENT*.";
        }

        if ($msg === '4' || str_contains($msg, 'verif')) {
            return "🪪 *Account Verification*\n\nTo verify your account, upload your:\n• NRC or Passport\n• Driver's License\n• Selfie photo\n• Utility bill\n\nVerify here:\n" . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/verify-profile.php\n\nVerification usually takes 1-2 working days.";
        }

        if ($msg === '5' || str_contains($msg, 'support') || str_contains($msg, 'help') || str_contains($msg, 'contact')) {
            return "🆘 *Support*\n\nOur team is available Mon-Sat, 8AM-6PM.\n\n📧 Email: " . (getenv('SUPPORT_EMAIL') ?: 'support@CarHire.com') . "\n📞 Phone: +260 97X XXX XXX\n\nOr submit a support ticket:\n" . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/support.php";
        }

        if (str_contains($msg, 'agent') || str_contains($msg, 'human') || str_contains($msg, 'person')) {
            return "👤 *Connecting to Agent*\n\nA Car Hire team member will contact you shortly.\n\nOffice hours: Mon-Sat, 8AM-6PM CAT\n\nFor urgent matters, call: +260 97X XXX XXX";
        }

        if (str_contains($msg, 'price') || str_contains($msg, 'rate') || str_contains($msg, 'cost') || str_contains($msg, 'how much')) {
            return "💰 *Vehicle Rates*\n\nOur rates start from:\n• Economy: ZMW 350/day\n• Sedan: ZMW 500/day\n• SUV: ZMW 800/day\n• Luxury: ZMW 1,500/day\n\nView full fleet & prices:\n" . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "our-fleet.php";
        }

        if (str_contains($msg, 'cancel')) {
            return "❌ *Cancellation Policy*\n\nTo cancel a booking:\n1. Log in to your portal\n2. Go to My Bookings\n3. Click 'Cancel Booking'\n\n" . (defined('APP_URL') ? APP_URL : 'http://localhost/Car_Higher/') . "portal-customer/my-bookings.php\n\n⚠️ Cancellations within 24hrs of pickup may incur a fee.";
        }

        // Default fallback
        return "🤖 *Car Hire Bot*\n\nSorry, I didn't understand that. Here's what I can help with:\n\n1️⃣ Book a vehicle\n2️⃣ Check my booking\n3️⃣ Payment help\n4️⃣ Verify my account\n5️⃣ Contact support\n\nReply with a number or keyword, or reply *AGENT* for human support.";
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Format Zambian phone number to WhatsApp format: whatsapp:+260XXXXXXXXX
     */
    private function formatZambianNumber(string $phone): string {
        // Strip all non-numeric characters
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Convert local format (0961234567) to international (+260961234567)
        if (strlen($digits) === 10 && substr($digits, 0, 1) === '0') {
            $digits = '260' . substr($digits, 1);
        }

        // If already has country code without +
        if (strlen($digits) === 12 && substr($digits, 0, 3) === '260') {
            // good
        }

        return 'whatsapp:+' . $digits;
    }

    /**
     * Send via Twilio REST API
     */
    private function twilioSend(string $to, string $message): array {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->account_sid}/Messages.json";

        $data = http_build_query([
            'From' => $this->from_number,
            'To'   => $to,
            'Body' => $message,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$this->account_sid}:{$this->auth_token}",
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->log("CURL ERROR to {$to}: {$curl_error}");
            return ['success' => false, 'sid' => null, 'error' => $curl_error];
        }

        $result = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300 && isset($result['sid'])) {
            $this->log("SENT to {$to} | SID: {$result['sid']}");
            return ['success' => true, 'sid' => $result['sid'], 'error' => null];
        }

        $error_msg = $result['message'] ?? "HTTP {$http_code}";
        $this->log("FAILED to {$to}: {$error_msg}");
        return ['success' => false, 'sid' => null, 'error' => $error_msg];
    }

    /**
     * Simulate sending (log to file instead)
     */
    private function simulateSend(string $to, string $message): array {
        $entry = "[" . date('Y-m-d H:i:s') . "] [SIMULATE] WhatsApp to {$to}:\n{$message}\n" . str_repeat('-', 60) . "\n";
        @file_put_contents($this->log_file, $entry, FILE_APPEND | LOCK_EX);
        return ['success' => true, 'sid' => 'SIMULATED_' . uniqid(), 'error' => null];
    }

    /**
     * Write to log file
     */
    private function log(string $entry): void {
        $line = "[" . date('Y-m-d H:i:s') . "] {$entry}\n";
        @file_put_contents($this->log_file, $line, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Global helper function – send a WhatsApp message
 */
function send_whatsapp(string $phone, string $message): bool {
    $wa = new WhatsAppService();
    $result = $wa->send($phone, $message);
    return $result['success'];
}
