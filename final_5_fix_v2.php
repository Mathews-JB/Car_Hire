<?php
include 'includes/db.php';

$vehicles = [
    8 => ['name' => 'corolla.jpg', 'url' => 'https://images.unsplash.com/photo-1494905998402-395d579af36f?q=80&w=1000'],
    12 => ['name' => 'audi_q7.jpg', 'url' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=1000'],
    13 => ['name' => 'jeep.jpg', 'url' => 'https://images.unsplash.com/photo-1563720223185-11003d516935?q=80&w=1000'],
    15 => ['name' => 'santa_fe.jpg', 'url' => 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?q=80&w=1000'],
    17 => ['name' => 'alphard.jpg', 'url' => 'https://images.unsplash.com/photo-1632733711679-5292d6e3846d?q=80&w=1000']
];

$save_dir = 'public/images/cars/';

foreach ($vehicles as $id => $info) {
    echo "Processing " . $info['name'] . "... ";
    $local_path = $save_dir . $info['name'];
    
    if (file_exists($local_path)) {
        unlink($local_path);
    }

    $ch = curl_init($info['url']);
    $fp = fopen($local_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if (file_exists($local_path) && filesize($local_path) > 100) {
        $stmt = $pdo->prepare('UPDATE vehicles SET image_url = ? WHERE id = ?');
        $stmt->execute([$local_path, $id]);
        echo "Success.\n";
    } else {
        echo "Failed (" . (file_exists($local_path) ? filesize($local_path) : 'missing') . ").\n";
    }
}
?>
