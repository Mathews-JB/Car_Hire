<?php
include 'includes/db.php';
$stmt = $pdo->prepare('UPDATE users SET profile_image_path = ?, nrc_image_path = ?, license_image_path = ? WHERE id = 7');
$stmt->execute([
    'uploads/verification/profile_7_1770904304.jpeg',
    'uploads/verification/nrc_front_7_1770904304.png', 
    'uploads/verification/license_front_7_1770904304.jpeg'
]);
echo "Updated user 7 successfully.\n";
?>
