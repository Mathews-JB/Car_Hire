<?php
/**
 * WhatsApp Webhook Handler
 * ─────────────────────────────────────────────────────────────────────────────
 * This endpoint receives incoming WhatsApp messages from Twilio and
 * responds with the chatbot reply.
 *
 * SETUP:
 * 1. Make this URL publicly accessible (e.g. via ngrok or your live server)
 * 2. In Twilio Console → Messaging → WhatsApp Sandbox (or your number):
 *    Set "When a message comes in" to: https://yourdomain.com/Car_Higher/api/whatsapp-webhook.php
 * 3. Method: HTTP POST
 *
 * SECURITY:
 * - Twilio signs each request. Validate the X-Twilio-Signature header in production.
 */

header('Content-Type: text/xml');

// Load dependencies
require_once __DIR__ . '/../includes/env_loader.php';
require_once __DIR__ . '/../includes/whatsapp.php';

// ── Validate it's a POST from Twilio ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<?xml version="1.0" encoding="UTF-8"?><Response></Response>';
    exit;
}

// ── Extract incoming message data ─────────────────────────────────────────────
$from_number    = $_POST['From']    ?? '';   // e.g. whatsapp:+260961234567
$incoming_body  = $_POST['Body']    ?? '';   // message text
$num_media      = (int)($_POST['NumMedia'] ?? 0);

// Log incoming message
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log_entry = "[" . date('Y-m-d H:i:s') . "] INCOMING from {$from_number}: " . substr($incoming_body, 0, 200) . "\n";
@file_put_contents($log_dir . '/whatsapp_incoming.log', $log_entry, FILE_APPEND | LOCK_EX);

// ── Generate chatbot response ─────────────────────────────────────────────────
$wa = new WhatsAppService();

// Strip the "whatsapp:" prefix for the phone number
$clean_phone = str_replace('whatsapp:', '', $from_number);

$reply = $wa->handleChatbotMessage($incoming_body, $clean_phone);

// ── Respond with TwiML ───────────────────────────────────────────────────────
// Twilio expects TwiML XML response
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';
echo '<Message>' . htmlspecialchars($reply, ENT_XML1, 'UTF-8') . '</Message>';
echo '</Response>';
