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
    <link rel="stylesheet" href="../public/css/style.css?v=<?php echo time(); ?>">
    <style>
        body { background: #0f172a; margin: 0; padding: 0; overflow: hidden; color: #ffffff; font-family: 'Inter', sans-serif; }
        
        #map { position: absolute; top: 120px; left: 0; width: 100%; height: calc(100% - 120px); z-index: 1; background: #1e293b; }

        /* Top Bar Header */
        .tracking-header {
            height: 120px;
            background: rgba(15,23,42,0.95);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            position: relative;
            z-index: 1001;
        }

        .header-title { font-size: 1.8rem; font-weight: 700; color: #ffffff; margin: 0 0 10px 0; }
        .header-meta { display: flex; justify-content: space-between; align-items: center; color: #94a3b8; font-size: 0.95rem; }
        .meta-left { display: flex; gap: 20px; font-weight: 600; }
        .meta-right { font-family: monospace; color: #94a3b8; }

        /* Vehicle Info Card (Bottom Left) */
        .vehicle-card {
            position: absolute;
            bottom: 40px;
            left: 40px;
            z-index: 1000;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 280px;
            border: 1px solid #e2e8f0;
        }

        .card-header { font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 15px; color: #1e293b; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
        .info-label { color: #64748b; }
        .info-val { font-weight: 600; color: #1e293b; }

        /* Tracking Active Badge (Bottom Right) */
        .status-badge-live {
            position: absolute;
            bottom: 40px;
            right: 40px;
            z-index: 1000;
            background: #f8fafc;
            padding: 10px 20px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
        }

        .status-dot { width: 12px; height: 12px; background: #10b981; border-radius: 50%; }

        /* Custom Marker Styles */
        .car-marker-svg { transition: transform 0.5s linear; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); }
        
        @media (max-width: 768px) {
            .tracking-header { padding: 15px 20px; height: auto; }
            .header-title { font-size: 1.4rem; }
            .meta-left { flex-direction: column; gap: 5px; }
            #map { top: 140px; height: calc(100% - 140px); }
            .vehicle-card { left: 15px; right: 15px; bottom: 100px; width: auto; }
            .status-badge-live { bottom: 20px; left: 15px; right: 15px; justify-content: center; }
        }
    </style>
</head>
<body>

<header class="tracking-header">
    <div style="display:flex; align-items:center; gap:20px; margin-bottom:5px;">
        <a href="my-bookings.php" style="color: #64748b; font-size: 1.2rem; text-decoration: none;"><i class="fas fa-chevron-left"></i></a>
        <h1 class="header-title">Live Vehicle Tracking</h1>
    </div>
    <div class="header-meta">
        <div class="meta-left">
            <span>Booking ID: #<?php echo $booking['booking_id']; ?></span>
            <span style="color: #94a3b8;">|</span>
            <span style="color: #10b981; font-weight: 700;"><i class="fas fa-check-circle"></i> Approved & Active (V2)</span>
            <button onclick="toggleDemo()" id="demoBtn" style="margin-left: 20px; background: #3b82f6; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 700;">
                <i class="fas fa-play"></i> START DEMO
            </button>
        </div>
        <div class="meta-right">
            Lat: <span id="header-lat">0.0000</span>, Lng: <span id="header-lng">0.0000</span>
        </div>
    </div>
</header>

<div id="map"></div>

<!-- Vehicle Info Card -->
<div class="vehicle-card" style="width: 260px; padding: 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">
    <div class="card-header" style="font-size: 1rem; border-bottom: 2px solid #f1f5f9; margin-bottom: 12px; padding-bottom: 8px;">Vehicle Info</div>
    
    <!-- Number Plate -->
    <div style="background: #f8fafc; border: 2px solid #334155; color: #1e293b; padding: 5px 10px; border-radius: 4px; font-family: monospace; font-weight: 700; text-align: center; margin-bottom: 15px; letter-spacing: 2px;">
        <?php echo $booking['plate_number'] ?? 'ABC 1234'; ?>
    </div>

    <div class="info-row" style="margin-bottom: 8px;">
        <span class="info-label" style="font-weight: 400;">Driver:</span>
        <span class="info-val">Brian M.</span>
    </div>
    <div class="info-row" style="margin-bottom: 8px;">
        <span class="info-label" style="font-weight: 400;">Status:</span>
        <span class="info-val" style="color: #1e293b;" id="card-status">Moving</span>
    </div>
    <div class="info-row" style="margin-bottom: 0;">
        <span class="info-label" style="font-weight: 400;">Speed:</span>
        <span class="info-val" style="color: #1e293b;"><span id="card-speed">0</span> km/h</span>
    </div>
</div>

<!-- Tracking Active Indicator -->
<div class="status-badge-live" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 15px; color: #334155; font-size: 0.85rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <div class="status-dot" style="width: 10px; height: 10px; background: #10b981;"></div>
    Tracking Active
</div>
<div id="debug-overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(15,23,42,0.95); color: white; padding: 20px; z-index: 9999; pointer-events: none; text-align: center; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); backdrop-filter: blur(10px);">
    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6; margin-bottom: 10px;"></i><br>
    Loading Map...
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
    
    console.log("✓ Leaflet loaded successfully");
    document.getElementById('debug-overlay').innerHTML = "Step 1: Leaflet OK<br>";
    
    // --- 2. Map Configuration (OpenStreetMap) ---
    const initialLat = <?php echo $booking['last_lat'] ?: -15.4167; ?>;
    const initialLng = <?php echo $booking['last_lng'] ?: 28.2833; ?>;
    
    console.log("Coordinates:", initialLat, initialLng);
    document.getElementById('debug-overlay').innerHTML += "Step 2: Coords = " + initialLat + ", " + initialLng + "<br>";
    
    console.log("Creating map...");
    document.getElementById('debug-overlay').innerHTML += "Step 3: Creating map...<br>";
    
    const map = L.map('map', { 
        zoomControl: false, 
        attributionControl: false 
    }).setView([initialLat, initialLng], 14);
    
    console.log("✓ Map created");
    document.getElementById('debug-overlay').innerHTML += "Step 4: Map created<br>";

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);
    
    console.log("✓ Tiles added");
    document.getElementById('debug-overlay').innerHTML += "Step 5: Tiles added<br>";

    // --- 2. Live State ---
    const bookingId = <?php echo $booking['booking_id']; ?>;
    let vehicleMarker = null;
    let routeLine = L.polyline([], {
        color: '#3b82f6',
        weight: 8,
        opacity: 0.9,
        lineCap: 'round'
    }).addTo(map);

    // Markers for Pickup/Destination
    const pickupMarker = L.marker([-15.4120, 28.2800], {
        icon: L.divIcon({
            className: 'pickup-marker',
            html: `
                <div style="background:#1e40af; color:white; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:3px solid white; box-shadow:0 4px 10px rgba(0,0,0,0.3);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2V15l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7z"/></svg>
                </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        })
    }).addTo(map);

    const destMarker = L.marker([-15.4250, 28.3100], {
        icon: L.divIcon({
            className: 'dest-marker',
            html: `
                <div style="background:#047857; color:white; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:3px solid white; box-shadow:0 4px 10px rgba(0,0,0,0.3);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        })
    }).addTo(map);

    // Uber-like movement interpolators
    let currentPos = { lat: <?php echo $booking['last_lat'] ?: -15.4167; ?>, lng: <?php echo $booking['last_lng'] ?: 28.2833; ?> };
    let targetPos = { lat: currentPos.lat, lng: currentPos.lng };
    let currentAngle = <?php echo $booking['bearing'] ?: 0; ?>;
    let targetAngle = currentAngle;

    // --- 3. Marker & Trail Generation (3D Car Icon) ---
    // --- 3. Marker & Trail Generation (3D BLUE Car Image) ---
    function createCarIcon(angle) {
        return L.divIcon({
            className: 'car-marker-container',
            html: `
                <div style="transform: rotate(${angle}deg); transition: transform 0.1s linear;">
                    <img src="https://img.icons8.com/color/96/sedan-top-view.png" 
                         style="width: 60px; height: 60px; transform: rotate(90deg); filter: hue-rotate(220deg) drop-shadow(0 10px 10px rgba(0,0,0,0.3)); display: block;"
                         alt="Car">
                </div>
            `,
            iconSize: [60, 60],
            iconAnchor: [30, 30]
        });
    }

    vehicleMarker = L.marker([currentPos.lat, currentPos.lng], {
        icon: createCarIcon(currentAngle),
        zIndexOffset: 1000 // Force on top
    }).addTo(map);

    // --- 4. Synchronization Engine ---
    async function syncGPS() {
        try {
            const response = await fetch(`../api/vehicle-gps.php?booking_id=${bookingId}`);
            const result = await response.json();

            if (result.success) {
                const v = result.data.vehicle;
                const hist = result.data.history;

                // Update targets for interpolation
                targetPos = { lat: parseFloat(v.lat), lng: parseFloat(v.lng) };
                targetAngle = parseInt(v.bearing);
                
                // Update HUD
                document.getElementById('card-speed').innerText = Math.round(v.speed);
                document.getElementById('card-status').innerText = v.status.charAt(0).toUpperCase() + v.status.slice(1);
                document.getElementById('header-lat').innerText = parseFloat(v.lat).toFixed(4);
                document.getElementById('header-lng').innerText = parseFloat(v.lng).toFixed(4);

                // Update Polyline
                if (hist && hist.length > 0) {
                    const path = hist.map(p => [p.lat, p.lng]);
                    routeLine.setLatLngs(path);
                }
            }
        } catch (e) {
            console.error("GPS Sync Error:", e);
        }
    }

    // --- Demo Control ---
    let demoInterval = null;
    function toggleDemo() {
        const btn = document.getElementById('demoBtn');
        if (demoInterval) {
            clearInterval(demoInterval);
            demoInterval = null;
            btn.innerHTML = '<i class="fas fa-play"></i> START DEMO';
            btn.style.background = '#3b82f6';
        } else {
            // Trigger immediately
            advanceDemo();
            demoInterval = setInterval(advanceDemo, 3000);
            btn.innerHTML = '<i class="fas fa-stop"></i> STOP DEMO';
            btn.style.background = '#ef4444';
        }
    }

    async function advanceDemo() {
        try {
            await fetch(`../api/simulate-step.php?booking_id=${bookingId}`);
            syncGPS(); // Force UI update immediately
        } catch(e) { console.error('Demo error', e); }
    }

    // --- 5. Logic Car Animation Engine (Smooth INTERP) ---
    function animate() {
        // Linear Interpolation for buttery movement (Uber-style)
        const lerpFactor = 0.05;
        currentPos.lat += (targetPos.lat - currentPos.lat) * lerpFactor;
        currentPos.lng += (targetPos.lng - currentPos.lng) * lerpFactor;
        
        // Simple angle interp
        let diff = targetAngle - currentAngle;
        if (diff > 180) diff -= 360;
        if (diff < -180) diff += 360;
        currentAngle += diff * 0.1;

        vehicleMarker.setLatLng([currentPos.lat, currentPos.lng]);
        vehicleMarker.setIcon(createCarIcon(currentAngle));
        
        // Keep car centered if moving
        if (Math.abs(targetPos.lat - currentPos.lat) > 0.00001) {
            map.panTo([currentPos.lat, currentPos.lng], { animate: true, duration: 0.1 });
        }

        requestAnimationFrame(animate);
    }

    // Start Engines
    animate();
    setInterval(syncGPS, 2000); // Poll every 2 seconds
    syncGPS(); // Initial sync
    
    // Hide debug if we got this far
    setTimeout(() => {
        document.getElementById('debug-overlay').style.display = 'none';
    }, 1000);

</script>

</body>
</html>
