<?php
require 'includes/db.php';

$updates = [
    'Land Cruiser Prado' => 'https://images.unsplash.com/photo-1594535182308-8ffefbb661e1?auto=format&fit=crop&q=80&w=1000',
    'Golf 7 TSI' => 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&q=80&w=1000',
    'Defender 110' => 'https://images.unsplash.com/photo-1629897048514-3dd7414fe72a?auto=format&fit=crop&q=80&w=1000',
    'G63 AMG' => 'https://images.unsplash.com/photo-1520031441872-265e4ff70366?auto=format&fit=crop&q=80&w=1000',
    'X5 xDrive40i' => 'https://images.unsplash.com/photo-1523983388277-336a66bf9bcc?auto=format&fit=crop&q=80&w=1000',
    'Corolla Quest' => 'https://images.unsplash.com/photo-1623860841270-1793699b6f84?auto=format&fit=crop&q=80&w=1000',
    'Navara PRO-4X' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=1000',
    'Pajero Sport' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=1000',
    'Camry' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&q=80&w=1000',
    'Q7 Quattro' => 'https://images.unsplash.com/photo-1541348263662-e0c8de4221fe?auto=format&fit=crop&q=80&w=1000',
    'Grand Cherokee' => 'https://images.unsplash.com/photo-1539414417088-348620803cbe?auto=format&fit=crop&q=80&w=1000',
    'Rav4' => 'https://images.unsplash.com/photo-1616422329260-23facca31293?auto=format&fit=crop&q=80&w=1000',
    'Santa Fe' => 'https://images.unsplash.com/photo-1616422285623-13ff0167c958?auto=format&fit=crop&q=80&w=1000',
    'Jimny' => 'https://images.unsplash.com/photo-1614741369527-0cf11c5218d6?auto=format&fit=crop&q=80&w=1000',
    'Alphard' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=1000',
    'Everest' => 'https://images.unsplash.com/photo-1611016186353-9af58c69a533?auto=format&fit=crop&q=80&w=1000'
];

foreach ($updates as $model => $url) {
    try {
        $stmt = $pdo->prepare("UPDATE vehicles SET image_url = ? WHERE model = ?");
        $stmt->execute([$url, $model]);
        echo "Updated $model\n";
    } catch (Exception $e) {
        echo "Error updating $model: " . $e->getMessage() . "\n";
    }
}
echo "Fleet update complete.";
