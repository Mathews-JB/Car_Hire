<?php
include_once 'includes/db.php';

try {
    // Create settings table
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT
    )";
    $pdo->exec($sql);
    echo "Settings table created/verified.<br>";

    // Default settings
    $defaults = [
        'company_name' => 'Car Hire Zambia',
        'company_email' => 'admin@CarHire.zm',
        'company_phone' => '+260 970 000 000',
        'tax_rate' => '16',
        'currency' => 'ZMW',
        'mtn_api_key' => '',
        'airtel_api_key' => '',
        'maintenance_threshold_km' => '5000'
    ];

    foreach ($defaults as $key => $value) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    echo "Default settings inserted/verified.<br>";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
