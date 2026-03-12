<?php
/**
 * Script to generate ACTUAL functional QR codes for the Car Hire app.
 */

// Define the URLs
$androidUrl = "https://CarHire.zm/CarHire_Professional_v5.apk"; // Direct APK download
$iosUrl = "https://apps.apple.com/app/car-hire-zambia/id123456789"; // Placeholder App Store link

// QR API: api.qrserver.com
$androidQrApi = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($androidUrl);
$iosQrApi = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($iosUrl);

// Save paths
$androidPath = "public/images/android_qr.png";
$iosPath = "public/images/ios_qr.png";

echo "Generating Android QR for: $androidUrl\n";
$androidImg = file_get_contents($androidQrApi);
if ($androidImg) {
    file_put_contents($androidPath, $androidImg);
    echo "Android QR saved to $androidPath\n";
} else {
    echo "Failed to generate Android QR.\n";
}

echo "Generating iOS QR for: $iosUrl\n";
$iosImg = file_get_contents($iosQrApi);
if ($iosImg) {
    file_put_contents($iosPath, $iosImg);
    echo "iOS QR saved to $iosPath\n";
} else {
    echo "Failed to generate iOS QR.\n";
}
?>
