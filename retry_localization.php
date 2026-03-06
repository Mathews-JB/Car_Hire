<?php
include 'includes/db.php';

$vehicles = [
    7 => ['name' => 'bmw_x5.jpg', 'url' => 'https://images.unsplash.com/photo-1523983388277-336a66bf9bcc?auto=format&fit=crop&q=80&w=1000'],
    8 => ['name' => 'corolla.jpg', 'url' => 'https://images.unsplash.com/photo-1583121274602-3e2820bc6988?auto=format&fit=crop&q=80&w=1000'],
    12 => ['name' => 'audi_q7.jpg', 'url' => 'https://images.unsplash.com/photo-1541348263662-e0c8de4221fe?auto=format&fit=crop&q=80&w=1000'],
    13 => ['name' => 'jeep.jpg', 'url' => 'https://images.unsplash.com/photo-1539414417088-348620803cbe?auto=format&fit=crop&q=80&w=1000'],
    14 => ['name' => 'rav4.jpg', 'url' => 'https://images.unsplash.com/photo-1616422329260-23facca31293?auto=format&fit=crop&q=80&w=1000'],
    15 => ['name' => 'santa_fe.jpg', 'url' => 'https://images.unsplash.com/photo-1616422285623-13ff0167c958?auto=format&fit=crop&q=80&w=1000'],
    17 => ['name' => 'alphard.jpg', 'url' => 'https://images.unsplash.com/photo-1621935579201-987820fac849?auto=format&fit=crop&q=80&w=1000']
];

$save_dir = 'public/images/cars/';

foreach ($vehicles as $id => $info) {
    echo "Processing " . $info['name'] . "... ";
    $local_path = $save_dir . $info['name'];
    
    // Use curl for better reliability
    $ch = curl_init($info['url']);
    $fp = fopen($local_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if (file_exists($local_path) && filesize($local_path) > 0) {
        $stmt = $pdo->prepare('UPDATE vehicles SET image_url = ? WHERE id = ?');
        $stmt->execute([$local_path, $id]);
        echo "Success.\n";
    } else {
        echo "Failed.\n";
    }
}
?>
