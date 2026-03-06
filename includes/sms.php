<?php

/**
 * Simple SMS Service Wrapper
 * Supports Africa's Talking and a generic debug mode
 */
/**
 * Twilio SMS Integration
 * Uses the same credentials as WhatsApp for standard text messages.
 */
function send_sms(string $to, string $message): bool {
    // 1. Format Number (Twilio needs +260XXXXXXXXX)
    $digits = preg_replace('/[^0-9]/', '', $to);
    if (strlen($digits) === 10 && substr($digits, 0, 1) === '0') {
        $digits = '260' . substr($digits, 1);
    }
    $to_formatted = '+' . $digits;

    // 2. Check settings
    $sid      = getenv('TWILIO_ACCOUNT_SID') ?: '';
    $token    = getenv('TWILIO_AUTH_TOKEN')  ?: '';
    $from     = getenv('TWILIO_SMS_FROM')    ?: '';
    $simulate = getenv('SMS_SIMULATE')       !== 'false';

    // 3. Simulation Mode
    if ($simulate || empty($sid) || empty($from)) {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] [SIMULATE] SMS to {$to_formatted}: {$message}\n";
        @file_put_contents(__DIR__ . '/../logs/sms.log', $log_entry, FILE_APPEND);
        return true;
    }

    // 4. Live Send via Twilio
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
    $data = http_build_query([
        'From' => $from,
        'To'   => $to_formatted,
        'Body' => $message,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => "{$sid}:{$token}",
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code >= 200 && $http_code < 300);
}
