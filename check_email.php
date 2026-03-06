<?php
include_once 'includes/db.php';
// Include functions to ensure any session initialization or common setup is handled
include_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Basic format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'invalid', 'message' => 'Invalid email format']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'taken', 'message' => 'Email is already registered.']);
        } else {
            echo json_encode(['status' => 'available', 'message' => 'Email is available.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
}
?>
