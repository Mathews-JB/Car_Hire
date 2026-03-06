<?php
include_once 'includes/db.php';

// Use a reliable demo video
$demo_video = "https://test-videos.co.uk/vids/bigbuckbunny/mp4/h264/360/Big_Buck_Bunny_360_10s_1MB.mp4";

try {
    // Update ALL vehicles to have this demo video for testing
    $stmt = $pdo->prepare("UPDATE vehicles SET video_url = ?");
    $stmt->execute([$demo_video]);
    echo "SUCCESS: Demo video added to ALL vehicles for testing. Please check any car details page.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
