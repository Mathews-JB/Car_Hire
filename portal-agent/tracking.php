<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in as agent
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: ../login.php");
    exit;
}

// Fetch all active/hired vehicles with locations
$stmt = $pdo->query("SELECT id, make, model, plate_number, latitude, longitude, status FROM vehicles WHERE status IN ('hired', 'available')");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count hired for the overlay
$hired_count = 0;
foreach($vehicles as $v) if($v['status'] == 'hired') $hired_count++;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking | Agent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body { background: #0f172a; overflow: hidden; }
        .agent-layout { height: 100vh; display: grid; grid-template-columns: 260px 1fr; }
        .main-content { padding: 0; position: relative; height: 100vh; overflow: hidden; }
        #map { height: 100%; width: 100%; z-index: 1; background: #0f172a; }
        
        .tracking-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            width: 320px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            color: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #10b981;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .pulse {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .vehicle-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.2s;
            background: rgba(255,255,255,0.02);
            margin-bottom: 5px;
        }

        .vehicle-item:hover { background: rgba(255,255,255,0.08); }

        .car-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .car-info h4 { font-size: 0.85rem; margin: 0; }
        .car-info p { font-size: 0.7rem; color: rgba(255,255,255,0.5); margin: 0; }

        .leaflet-container { background: #0f172a !important; }
    </style>
</head>
<body>

    <div class="agent-layout">
        <?php include_once '../includes/agent_sidebar.php'; ?>

        <main class="main-content">
            <div class="tracking-overlay">
                <div class="live-indicator">
                    <div class="pulse"></div>
                    Active Fleet
                </div>
                
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach($vehicles as $v): ?>
                    <div class="vehicle-item" onclick="focusVehicle(<?php echo $v['id']; ?>)">
                        <div class="car-dot" style="background: <?php echo $v['status'] == 'hired' ? '#2563eb' : '#10b981'; ?>"></div>
                        <div class="car-info">
                            <h4><?php echo $v['make'] . ' ' . $v['model']; ?></h4>
                            <p style="margin-bottom: 2px;"><?php echo $v['plate_number']; ?></p>
                            <span style="font-size:0.75rem; color:#3b82f6; font-family: monospace; font-weight: 600;">
                                <?php echo number_format($v['latitude'], 4); ?>, <?php echo number_format($v['longitude'], 4); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="map"></div>
        </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map', {
            zoomControl: true,
            attributionControl: false
        }).setView([-15.3875, 28.3228], 13); 

        // Antigravity Clean Light
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);

        const vehicles = <?php echo json_encode($vehicles); ?>;
        const markers = {};

        function createCarIcon(color, rotation = 0) {
            return L.divIcon({
                className: 'yango-marker',
                html: `<div style="transform: rotate(${rotation}deg);"><img src="https://emojipedia-us.s3.dualstack.us-west-1.amazonaws.com/thumbs/120/apple/285/oncoming-automobile_1f698.png" style="width: 32px; height: 32px; transform: rotate(-90deg); filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
        }

        vehicles.forEach(v => {
            const color = v.status === 'hired' ? '#2563eb' : '#10b981';
            const marker = L.marker([parseFloat(v.latitude), parseFloat(v.longitude)], {
                icon: createCarIcon(color, Math.random() * 360)
            }).addTo(map);
            
            markers[v.id] = { 
                marker: marker, 
                color: color, 
                lat: parseFloat(v.latitude), 
                lng: parseFloat(v.longitude),
                targetLat: parseFloat(v.latitude),
                targetLng: parseFloat(v.longitude),
                angle: 0,
                targetAngle: 0,
                moving: (v.status === 'hired'),
                speed: 0
            };
        });

        // --- Real-Time Sync Engine ---
        async function syncFleet() {
            try {
                const response = await fetch('../api/fleet-gps.php');
                const result = await response.json();
                if (result.success) {
                    result.data.forEach(v => {
                        const car = markers[v.id];
                        if (car) {
                            car.targetLat = parseFloat(v.lat);
                            car.targetLng = parseFloat(v.lng);
                            car.targetAngle = parseInt(v.bearing);
                            car.moving = (v.status === 'online' || v.status === 'hired');
                        }
                    });
                }
            } catch (e) {
                console.error("Agent Sync Error:", e);
            }
        }

        function animate() {
            Object.keys(markers).forEach(id => {
                const car = markers[id];
                const lerpFactor = 0.05;
                
                car.lat += (car.targetLat - car.lat) * lerpFactor;
                car.lng += (car.targetLng - car.lng) * lerpFactor;
                
                let diff = car.targetAngle - car.angle;
                if (diff > 180) diff -= 360;
                if (diff < -180) diff += 360;
                car.angle += diff * 0.1;

                car.marker.setLatLng([car.lat, car.lng]);
                car.marker.setIcon(createCarIcon(car.color, car.angle));
            });
            requestAnimationFrame(animate);
        }

        function focusVehicle(id) {
            const data = markers[id];
            if (data) map.flyTo([data.lat, data.lng], 16, { duration: 1 });
        }

        // Start Engines
        animate();
        setInterval(syncFleet, 2000);
        syncFleet();
        // Search Logic
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search fleet...';
        searchInput.className = 'form-control';
        searchInput.style.marginBottom = '15px';
        searchInput.style.background = 'rgba(255,255,255,0.05)';
        searchInput.style.border = '1px solid rgba(255,255,255,0.1)';
        searchInput.style.color = 'white';
        searchInput.style.padding = '10px';
        searchInput.style.borderRadius = '10px';
        
        const overlay = document.querySelector('.tracking-overlay');
        const list = overlay.querySelector('div[style*="overflow-y: auto"]');
        overlay.insertBefore(searchInput, list);

        const vehicleItems = document.querySelectorAll('.vehicle-item');
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            vehicleItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(term) ? 'flex' : 'none';
            });
            
            // Map markers for agent
            Object.keys(markers).forEach(id => {
                const data = markers[id];
                const vehicle = vehicles.find(v => v.id == id);
                const match = vehicle.make.toLowerCase().includes(term) || 
                              vehicle.model.toLowerCase().includes(term) || 
                              vehicle.plate_number.toLowerCase().includes(term);
                if (match) {
                    if (!map.hasLayer(data.marker)) data.marker.addTo(map);
                } else {
                    if (map.hasLayer(data.marker)) map.removeLayer(data.marker);
                }
            });
        });
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
