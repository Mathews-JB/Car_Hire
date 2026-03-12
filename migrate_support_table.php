<?php
require_once 'includes/db.php';

try {
    // Check if customer_proposed_price exists
    $stmt = $pdo->query("SHOW COLUMNS FROM support_messages LIKE 'customer_proposed_price'");
    if (!$stmt->fetch()) {
        echo "Adding customer_proposed_price...\n";
        $pdo->exec("ALTER TABLE support_messages ADD COLUMN customer_proposed_price DECIMAL(15,2) DEFAULT 0.00 AFTER fleet_items");
    } else {
        echo "customer_proposed_price already exists.\n";
    }

    // Check if customer_proposed_deposit exists
    $stmt = $pdo->query("SHOW COLUMNS FROM support_messages LIKE 'customer_proposed_deposit'");
    if (!$stmt->fetch()) {
        echo "Adding customer_proposed_deposit...\n";
        $pdo->exec("ALTER TABLE support_messages ADD COLUMN customer_proposed_deposit INT DEFAULT 25 AFTER customer_proposed_price");
    } else {
        echo "customer_proposed_deposit already exists.\n";
    }

    echo "Migration complete.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
