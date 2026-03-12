<?php
require_once '../includes/db.php';

// Fetch a confirmed booking to simulate
$stmt = $pdo->query("
    SELECT b.id as booking_id, v.make, v.model, v.license_plate
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.status = 'confirmed'
    LIMIT 1
");
$booking = $stmt->fetch();

$vehicle_id = $booking ? $booking['booking_id'] : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Driver ðŸš—</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            max-width: 400px;
        }
        h1 { margin-top: 0; color: #3b82f6; }
        .car-anim {
            font-size: 4rem;
            display: inline-block;
            margin: 20px 0;
            animation: drive 1s infinite alternate ease-in-out;
        }
        @keyframes drive {
            0% { transform: translateY(0px) rotate(-5deg); }
            100% { transform: translateY(-10px) rotate(5deg); }
        }
        .log {
            font-family: monospace;
            font-size: 0.9rem;
            color: #10b981;
            background: #000;
            padding: 10px;
            border-radius: 8px;
            margin-top: 20px;
            height: 60px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>

    <div class="card">
        <h1>Demo Tracker</h1>
        <p>I am pretending to be the car driving around Lusaka.</p>
        
        <div class="car-anim">ðŸš—</div>
        
        <?php if ($booking): ?>
            <h3>Driving: <?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></h3>
            <p style="color: #64748b;">(<?= htmlspecialchars($booking['license_plate']) ?>)</p>
            
            <p><strong>Instructions:</strong></p>
            <p style="font-size: 0.9rem;">Keep this tab open. Go to the <b>Admin Fleet Radar</b> or <b>Track My Ride</b> in another tab to watch me move live!</p>

            <div class="log" id="log">Engine started...</div>
        <?php else: ?>
            <p style="color: #ef4444;">No active confirmed bookings found to simulate.</p>
        <?php endif; ?>
    </div>

    <script>
    const vehicleId = <?= (int)$vehicle_id ?>;
    const log = document.getElementById('log');

    // Automatically call the simulation API every 2 seconds
    setInterval(async () => {
        try {
            const res = await fetch(`../api/simulate-gps.php?id=${vehicleId}`);
            const data = await res.json();
            
            if(data.success) {
                log.innerHTML = `GPS Ping Sent!<br>Lat: ${data.new_pos.lat.toFixed(4)}, Lng: ${data.new_pos.lng.toFixed(4)}`;
            } else {
                log.innerHTML = `Error: ${data.error}`;
            }
        } catch(e) {
            log.innerHTML = `Network error communicating with satellite.`;
        }
    }, 2000); // 2000ms = 2 seconds
    </script>

</body>
</html>

