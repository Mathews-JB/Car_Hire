<?php
include_once 'includes/mailer.php';

echo "Generating generic receipt...\n";

// Sample Receipt Data
require_once 'includes/env_loader.php';
$booking_ref = "BK-" . rand(1000, 9999);
$date = date('d M Y');
$customer_email = getenv('SUPPORT_EMAIL'); // Self-test
$customer_name = "Mathews Bwalya";

$items = [
    ['desc' => 'Toyota Hilux 4x4 Rental (3 Days)', 'price' => 4500.00],
    ['desc' => 'Insurance Premium Details', 'price' => 300.00],
    ['desc' => 'GPS Navigation System', 'price' => 150.00]
];

$total = 0;
$rows_html = "";
foreach($items as $item) {
    $total += $item['price'];
    $rows_html .= "
    <tr>
        <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['desc']}</td>
        <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>ZMW " . number_format($item['price'], 2) . "</td>
    </tr>";
}

$vat = $total * 0.16;
$grand_total = $total + $vat;

$receipt_html = "
    <h2>Payment Receipt</h2>
    <p>Dear {$customer_name},</p>
    <p>Thank you for your payment. Below is the receipt for your recent transaction.</p>
    
    <div style='background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;'>
        <strong>Receipt #:</strong> RCPT-" . rand(10000,99999) . "<br>
        <strong>Date:</strong> {$date}<br>
        <strong>Reference:</strong> {$booking_ref}
    </div>

    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
        <thead>
            <tr style='background: #f1f5f9; text-align: left;'>
                <th style='padding: 10px;'>Description</th>
                <th style='padding: 10px; text-align: right;'>Amount</th>
            </tr>
        </thead>
        <tbody>
            {$rows_html}
        </tbody>
        <tfoot>
            <tr>
                <td style='padding: 10px; text-align: right; font-weight: bold;'>Subtotal</td>
                <td style='padding: 10px; text-align: right;'>ZMW " . number_format($total, 2) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; text-align: right; color: #64748b;'>VAT (16%)</td>
                <td style='padding: 10px; text-align: right; color: #64748b;'>ZMW " . number_format($vat, 2) . "</td>
            </tr>
            <tr style='background: #e0f2fe;'>
                <td style='padding: 10px; text-align: right; font-weight: 800; color: #0284c7;'>Total Paid</td>
                <td style='padding: 10px; text-align: right; font-weight: 800; color: #0284c7;'>ZMW " . number_format($grand_total, 2) . "</td>
            </tr>
        </tfoot>
    </table>

    <div style='background: #e0f2fe; padding: 20px; border-radius: 8px; text-align: center; border: 1px dashed #0284c7; margin-top: 30px;'>
        <h3 style='margin-top: 0; color: #0284c7; font-size: 16px;'>📄 Official Document Generated</h3>
        <p style='margin-bottom: 20px; font-size: 14px; color: #334155;'>A downloadable PDF version of this receipt has been automatically created by our system.</p>
        <a href='http://localhost/Car_Higher/portal-customer/view_invoice.php?demo=true' style='display: inline-block; padding: 12px 24px; background: #0f172a; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;'>Download PDF Receipt</a>
        <p style='margin-top: 15px; font-size: 12px; color: #64748b;'>For security, invoices are only accessible via your dashboard.</p>
    </div>

    <p style='font-size: 0.9em; color: #94a3b8; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px;'>This is an automated receipt. Refer to our Terms of Service for refund policies.</p>
";

$mailer = new CarHireMailer();
$result = $mailer->send($customer_email, "Payment Receipt: " . $booking_ref, $receipt_html, null, 'Car Hire Accounts', true);

if ($result) {
    echo "Email sent successfully to {$customer_email}!\n";
} else {
    echo "Failed to send email.\n";
}
?>
