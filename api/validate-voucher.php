<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

header('Content-Type: application/json');

$code = $_GET['code'] ?? '';
$amount = $_GET['amount'] ?? 0;

if (empty($code)) {
    echo json_encode(['valid' => false, 'msg' => 'Code is required.']);
    exit;
}

// Get user email if logged in to validate targeted vouchers
$user_id = $_SESSION['user_id'] ?? null;
$user_email = null;

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_email = $stmt->fetchColumn();
    } catch (Exception $e) { /* silent fail */ }
}

$res = validateVoucher($pdo, $code, $amount, $user_email);

if ($res['valid']) {
    $v = $res['data'];
    $discount = 0;
    $msg = '';

    if ($v['discount_type'] === 'percentage') {
        $discount = $amount * ($v['discount_value'] / 100);
        $msg = $v['discount_value'] . '% Discount applied.';
    } else {
        $discount = $v['discount_value'];
        $msg = 'ZMW ' . $v['discount_value'] . ' Discount applied.';
    }

    echo json_encode([
        'valid' => true,
        'msg' => $msg,
        'discount' => $discount
    ]);
} else {
    echo json_encode($res);
}
?>
