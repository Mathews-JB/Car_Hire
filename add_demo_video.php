<?php
include_once 'includes/db.php';

// Using a sample sample car video from a CDN or similar
$demo_video = "https://test-videos.co.uk/vids/bigbuckbunny/mp4/h264/360/Big_Buck_Bunny_360_10s_1MB.mp4";

try {
    $stmt = $pdo->prepare("UPDATE vehicles SET video_url = ? WHERE id = 1");
    $stmt->execute([$demo_video]);
    echo "Demo video added to Toyota Land Cruiser (ID: 1) successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
