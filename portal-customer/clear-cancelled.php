<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 1. Delete associated add-ons for cancelled bookings
    $pdo->prepare("DELETE FROM booking_add_ons WHERE booking_id IN (SELECT id FROM bookings WHERE user_id = ? AND status = 'cancelled')")->execute([$user_id]);

    // 2. Delete associated payments for cancelled bookings
    $pdo->prepare("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE user_id = ? AND status = 'cancelled')")->execute([$user_id]);

    // 3. Hard delete cancelled bookings for this user
    $pdo->prepare("DELETE FROM bookings WHERE user_id = ? AND status = 'cancelled'")->execute([$user_id]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: my-bookings.php?error=db_error");
    exit;
}


header("Location: my-bookings.php?msg=cleared");
exit;
?>
