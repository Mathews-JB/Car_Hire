<?php
include 'includes/db.php';

$vehicles = [
    4 => ['name' => 'golf.jpg', 'url' => 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&q=80&w=1000'],
    5 => ['name' => 'defender.jpg', 'url' => 'https://images.unsplash.com/photo-1629897048514-3dd7414fe72a?auto=format&fit=crop&q=80&w=1000'],
    6 => ['name' => 'g63.jpg', 'url' => 'https://images.unsplash.com/photo-1520031441872-265e4ff70366?auto=format&fit=crop&q=80&w=1000'],
    7 => ['name' => 'bmw_x5.jpg', 'url' => 'https://images.unsplash.com/photo-1523983388277-336a66bf9bcc?auto=format&fit=crop&q=80&w=1000'],
    8 => ['name' => 'corolla.jpg', 'url' => 'https://images.unsplash.com/photo-1583121274602-3e2820bc6988?auto=format&fit=crop&q=80&w=1000'],
    9 => ['name' => 'navara.jpg', 'url' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=1000'],
    10 => ['name' => 'pajero.jpg', 'url' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=1000'],
    11 => ['name' => 'camry.jpg', 'url' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&q=80&w=1000'],
    12 => ['name' => 'audi_q7.jpg', 'url' => 'https://images.unsplash.com/photo-1541348263662-e0c8de4221fe?auto=format&fit=crop&q=80&w=1000'],
    13 => ['name' => 'jeep.jpg', 'url' => 'https://images.unsplash.com/photo-1539414417088-348620803cbe?auto=format&fit=crop&q=80&w=1000'],
    14 => ['name' => 'rav4.jpg', 'url' => 'https://images.unsplash.com/photo-1616422329260-23facca31293?auto=format&fit=crop&q=80&w=1000'],
    15 => ['name' => 'santa_fe.jpg', 'url' => 'https://images.unsplash.com/photo-1616422285623-13ff0167c958?auto=format&fit=crop&q=80&w=1000'],
    16 => ['name' => 'jimny.jpg', 'url' => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?auto=format&fit=crop&q=80&w=1000'],
    17 => ['name' => 'alphard.jpg', 'url' => 'https://images.unsplash.com/photo-1621935579201-987820fac849?auto=format&fit=crop&q=80&w=1000'],
    18 => ['name' => 'everest.jpg', 'url' => 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&fit=crop&q=80&w=1000']
];

$save_dir = 'public/images/cars/';

foreach ($vehicles as $id => $info) {
    if (file_exists($save_dir . $info['name']) && filesize($save_dir . $info['name']) > 0) {
        $local_path = $save_dir . $info['name'];
        $stmt = $pdo->prepare('UPDATE vehicles SET image_url = ? WHERE id = ?');
        $stmt->execute([$local_path, $id]);
        echo "Updating ID $id to existing " . $info['name'] . "\n";
        continue;
    }

    echo "Downloading " . $info['name'] . "... ";
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'Mozilla/5.0']]);
    $content = @file_get_contents($info['url'], false, $ctx);
    if ($content) {
        $local_path = $save_dir . $info['name'];
        file_put_contents($local_path, $content);
        
        $stmt = $pdo->prepare('UPDATE vehicles SET image_url = ? WHERE id = ?');
        $stmt->execute([$local_path, $id]);
        echo "Done.\n";
    } else {
        echo "Failed.\n";
    }
}
?>
