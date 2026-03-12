<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    die("No booking ID provided.");
}

$user_id = $_SESSION['user_id'];

// Fetch booking and vehicle details
$stmt = $pdo->prepare("
    SELECT b.id 
    as booking_id, b.status as booking_status, b.pickup_date, b.dropoff_date,
           v.id as vehicle_id, v.make, v.model, CONCAT('ZMA ', v.id + 4520) as plate_number, v.image_url, v.last_lat, v.last_lng, v.current_speed, v.bearing
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found or access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Live Tracking V2 | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <link rel="stylesheet" href="../public/css/style.css?v=<?php echo time(); ?>
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>">
    <style>
        body { background: #0f172a; margin: 0; padding: 0; overflow: hidden; color: #f1f5f9; font-family: 'Inter', sans-serif; height: 100vh; width: 100vw; }
        
        #map { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; background: #1e293b; }

        /* Floating Header Overlay */
        .tracking-header {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            background: rgba(30, 30, 35, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 15px 25px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1001;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .header-title { font-size: 1.2rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.5px; }
        .header-meta { display: flex; align-items: center; gap: 20px; color: rgba(255, 255, 255, 0.6); font-size: 0.85rem; }
        .meta-left { display: flex; align-items: center; gap: 15px; font-weight: 600; }
        .meta-right { font-family: 'JetBrains Mono', monospace; color: var(--accent-color); font-weight: 700; }

        /* Vehicle Info Card (Bottom Left) */
        .vehicle-card {
            position: absolute;
            bottom: 30px;
            left: 20px;
            z-index: 1000;
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(20px);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            width: 240px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-header { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--accent-color); margin-bottom: 12px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; }
        .info-label { color: rgba(255, 255, 255, 0.5); }
        .info-val { font-weight: 700; color: #fff; }

        /* Tracking Active Badge (Bottom Right) */
        .status-badge-live {
            position: absolute;
            bottom: 30px;
            right: 20px;
            z-index: 1000;
            background: rgba(30, 30, 35, 0.8);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        /* Custom Marker Styles */
        .car-marker-svg { transition: transform 0.1s linear; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); }
        
        @media (max-width: 768px) {
            .tracking-header { top: 10px; left: 10px; right: 10px; padding: 12px 15px; flex-direction: column; align-items: flex-start; gap: 10px; }
            .header-meta { width: 100%; justify-content: space-between; }
            .vehicle-card { left: 10px; right: 10px; bottom: 80px; width: auto; padding: 15px; }
            .status-badge-live { bottom: 20px; left: 10px; right: 10px; justify-content: center; }
        }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>

<header class="tracking-header">
    <div style="display:flex; align-items:center; gap:20px;">
        <a href="my-bookings.php" style="color: #fff; font-size: 1rem; text-decoration: none; background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-arrow-left"></i></a>
        <h1 class="header-title">Live Tracking</h1>
    </div>
    <div class="header-meta">
        <div class="meta-left">
            <span>Booking ID: <span style="color: #fff;">#<?php echo $booking['booking_id']; ?></span></span>
            <button onclick="toggleDemo()" id="demoBtn" style="background: var(--primary-color); color: white; border: none; padding: 6px 15px; border-radius: 8px; cursor: pointer; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-play"></i> Simulate Step
            </button>
        </div>
        <div class="meta-right">
            <span id="header-lat">0.0000</span>, <span id="header-lng">0.0000</span>
        </div>
    </div>
</header>

<div id="map"></div>

<!-- Vehicle Info Card -->
<div class="vehicle-card">
    <div class="card-header">Live Telemetry</div>
    
    <div class="info-row">
        <span class="info-label">Vehicle:</span>
        <span class="info-val"><?php echo $booking['make'] . ' ' . $booking['model']; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Status:</span>
        <span class="info-val" id="card-status" style="color: #10b981;">Moving</span>
    </div>
    <div class="info-row">
        <span class="info-label">Speed:</span>
        <span class="info-val"><span id="card-speed">0</span> km/h</span>
    </div>
    <div class="info-row">
        <span class="info-label">Distance:</span>
        <span class="info-val"><span id="card-distance">0.0</span> km</span>
    </div>
    
    <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px; font-family: monospace; font-size: 0.8rem; text-align: center; color: var(--accent-color); font-weight: 800; border: 1px solid rgba(255,255,255,0.05);">
        <?php echo $booking['plate_number']; ?>
    </div>
</div>

<!-- Tracking Active Indicator -->
<div class="status-badge-live">
    <div class="status-dot"></div>
    Live Stream Active
</div>

<div id="debug-overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(30, 30, 35, 0.95); color: white; padding: 30px; z-index: 9999; pointer-events: none; text-align: center; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 40px 100px rgba(0,0,0,0.8);">
    <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 10px;">Establishing Stream...</div>
    <div style="font-size: 0.9rem; opacity: 0.6;">Connecting to vehicle telemetry...</div>
</div>

<script>
    // CRITICAL DEBUGGING
    window.onerror = function(msg, url, line, col, error) {
        alert("JS Error: " + msg + "\nLine: " + line);
        document.getElementById('debug-overlay').innerHTML += "<br>Error: " + msg;
        return false;
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
    if (typeof L === 'undefined') {
        alert("CRITICAL ERROR: Leaflet Map Library did not load. Check your internet connection or firewall.");
        document.getElementById('debug-overlay').innerHTML += "<br>Leaflet failed to load.";
        throw new Error("Leaflet missing");
    }
    // --- 2. Map Configuration (Dark/Standard Mix) ---
    const map = L.map('map', { 
        zoomControl: false, 
        attributionControl: false 
    }).setView([<?php echo $booking['last_lat'] ?: -15.4167; ?>, <?php echo $booking['last_lng'] ?: 28.2833; ?>], 15);

    // Using a cleaner tile set for premium look
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    // --- 3. Telemetry State ---
    const bookingId = <?php echo $booking['booking_id']; ?>;
    let vehicleMarker = null;
    let routeLine = L.polyline([], {
        color: '#3b82f6',
        weight: 6,
        opacity: 0.6,
        lineCap: 'round',
        dashArray: '10, 10'
    }).addTo(map);

    function calculateDistance(points) {
        let total = 0;
        for (let i = 0; i < points.length - 1; i++) {
            total += L.latLng(points[i]).distanceTo(L.latLng(points[i+1]));
        }
        return (total / 1000).toFixed(1);
    }

    // Uber-like movement interpolators
    let currentPos = { lat: <?php echo $booking['last_lat'] ?: -15.4167; ?>, lng: <?php echo $booking['last_lng'] ?: 28.2833; ?> };
    let targetPos = { lat: currentPos.lat, lng: currentPos.lng };
    let currentAngle = <?php echo $booking['bearing'] ?: 0; ?>;
    let targetAngle = currentAngle;

    function createCarIcon(angle) {
        return L.divIcon({
            className: 'car-marker-container',
            html: `
                <div style="transform: rotate(${angle}deg); transition: transform 0.1s linear;">
                    <img src="https://img.icons8.com/color/96/sedan-top-view.png" 
                         style="width: 50px; height: 50px; transform: rotate(90deg); filter: hue-rotate(220deg) drop-shadow(0 10px 15px rgba(0,0,0,0.5)); display: block;"
                         alt="Car">
                </div>
            `,
            iconSize: [50, 50],
            iconAnchor: [25, 25]
        });
    }

    vehicleMarker = L.marker([currentPos.lat, currentPos.lng], {
        icon: createCarIcon(currentAngle),
        zIndexOffset: 1000
    }).addTo(map);

    // --- 4. Stream Synchronization ---
    async function syncGPS() {
        try {
            const response = await fetch(`../api/vehicle-gps.php?booking_id=${bookingId}&v=${Date.now()}`);
            const result = await response.json();

            if (result.success) {
                const v = result.data.vehicle;
                const hist = result.data.history;

                targetPos = { lat: parseFloat(v.lat), lng: parseFloat(v.lng) };
                targetAngle = parseInt(v.bearing);
                
                document.getElementById('card-speed').innerText = Math.round(v.speed);
                document.getElementById('card-status').innerText = v.status.charAt(0).toUpperCase() + v.status.slice(1);
                document.getElementById('header-lat').innerText = parseFloat(v.lat).toFixed(4);
                document.getElementById('header-lng').innerText = parseFloat(v.lng).toFixed(4);

                if (hist && hist.length > 0) {
                    const path = hist.map(p => [p.lat, p.lng]);
                    routeLine.setLatLngs(path);
                    document.getElementById('card-distance').innerText = calculateDistance(path);
                }
                
                // If demo is off, center on update
                if (!demoInterval) {
                   map.setView([targetPos.lat, targetPos.lng], 16, { animate: true });
                }
            }
        } catch (e) {
            console.error("Stream Error:", e);
        }
    }

    // --- 5. Movement Simulation ---
    let demoInterval = null;
    function toggleDemo() {
        const btn = document.getElementById('demoBtn');
        if (demoInterval) {
            clearInterval(demoInterval);
            demoInterval = null;
            btn.innerHTML = '<i class="fas fa-play"></i> Simulate Step';
            btn.style.background = 'var(--primary-color)';
        } else {
            advanceDemo();
            demoInterval = setInterval(advanceDemo, 3000);
            btn.innerHTML = '<i class="fas fa-stop"></i> Active Simulation';
            btn.style.background = '#ef4444';
        }
    }

    async function advanceDemo() {
        try {
            await fetch(`../api/simulate-step.php?booking_id=${bookingId}`);
            syncGPS();
        } catch(e) { console.error('Sim error', e); }
    }

    // --- 6. Fluid Animation Engine ---
    function animate() {
        const lerpFactor = 0.05;
        currentPos.lat += (targetPos.lat - currentPos.lat) * lerpFactor;
        currentPos.lng += (targetPos.lng - currentPos.lng) * lerpFactor;
        
        let diff = targetAngle - currentAngle;
        if (diff > 180) diff -= 360;
        if (diff < -180) diff += 360;
        currentAngle += diff * 0.1;

        vehicleMarker.setLatLng([currentPos.lat, currentPos.lng]);
        vehicleMarker.setIcon(createCarIcon(currentAngle));
        
        if (demoInterval && Math.abs(targetPos.lat - currentPos.lat) > 0.00001) {
            map.panTo([currentPos.lat, currentPos.lng], { animate: true, duration: 0.1 });
        }

        requestAnimationFrame(animate);
    }

    animate();
    setInterval(syncGPS, 2000);
    syncGPS();
    
    setTimeout(() => {
        document.getElementById('debug-overlay').style.opacity = '0';
        setTimeout(() => document.getElementById('debug-overlay').style.display = 'none', 500);
    }, 1500);

</script>

</body>
</html>

