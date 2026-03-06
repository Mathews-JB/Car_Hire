<?php
include 'includes/db.php';
$id = 17;
$name = 'alphard.jpg';
$url = 'https://images.unsplash.com/photo-1595062584113-f272590db915?q=80&w=1000';
$save_dir = 'public/images/cars/';
$local_path = $save_dir . $name;

if (file_exists($local_path)) unlink($local_path);

$ch = curl_init($url);
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
    echo "Failed.\n";
}
?>
