<?php
$data = [
    'text' => 'Hello',
    'source' => 'en',
    'target' => 'bem',
    'api_key' => 'ZED_385793c04e8d46059580aeceae04db70'
];

$ch = curl_init('https://api.lumoafrica.online/v1/translate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Error: $err\n";
echo "Response: $response\n";
