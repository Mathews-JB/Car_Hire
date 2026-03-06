<?php
require_once 'env_loader.php';

// Manually include PHPMailer classes since composer is not available in all environments
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class CarHireMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;

    public function __construct() {
        $this->host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->port = (int)(getenv('SMTP_PORT') ?: 587);
        $this->username = getenv('SMTP_USER');
        $this->password = getenv('SMTP_PASS');
        $this->encryption = getenv('SMTP_ENCRYPTION') ?: 'tls'; // 'tls' or 'ssl'
        
        // Handle common Gmail ssl:// prefix if present
        if (strpos($this->host, 'ssl://') === 0) {
            $this->host = substr($this->host, 6);
            $this->encryption = 'ssl';
            $this->port = 465;
        }
    }

    public function send($to, $subject, $message, $fromEmail = null, $fromName = 'Car Hire Support', $useDefaultTemplate = true) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = ($this->encryption === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom($this->username, $fromName);
            $mail->addAddress($to);
            if ($fromEmail) {
                $mail->addReplyTo($fromEmail, $fromName);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            
            if ($useDefaultTemplate) {
                $bodyContent = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: 'Inter', Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #f1f5f9; margin: 0; padding: 20px; }
                        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
                        .header { background: #1e293b; padding: 30px; text-align: left; border-bottom: 4px solid #2563eb; }
                        .logo { color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; text-decoration: none; display: block; }
                        .body { padding: 40px 30px; text-align: left; }
                        .title { font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 20px; letter-spacing: -0.5px; }
                        .message-box { background: #ffffff; color: #334155; font-size: 15px; margin-top: 10px; line-height: 1.7; }
                        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
                        .btn { background: #2563eb; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 700; display: inline-block; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <div class='logo'>Car Hire</div>
                        </div>
                        <div class='body'>
                            <h1 class='title'>{$subject}</h1>
                            <div class='message-box'>
                                {$message}
                            </div>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " Car Hire Zambia. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>";
                $mail->Body = $bodyContent;
            } else {
                $mail->Body = $message;
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}

function sendSupportEmail($name, $email, $subject, $message, $direction = 'to_admin') {
    $mailer = new CarHireMailer();
    
    if ($direction === 'to_customer') {
        $to = $email;
        $fromName = "Car Hire Support";
        $fromEmail = getenv('SUPPORT_EMAIL');
    } else {
        $to = getenv('SUPPORT_EMAIL');
        $fromName = $name;
        $fromEmail = $email;
    }

    return $mailer->send($to, $subject, $message, $fromEmail, $fromName, true);
}
?>
