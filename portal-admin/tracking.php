<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch vehicles
$stmt = $pdo->query("SELECT * FROM vehicles WHERE status IN ('hired', 'available')");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hired_count = 0;
foreach($vehicles as $v) if($v['status'] == 'hired') $hired_count++;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Live Fleet Tracking | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        /* --- Dark Mode Pro Theme --- */
        body { 
            background: #111827; 
            overflow: hidden; 
            color: #e2e8f0; 
            margin: 0; 
            padding: 0; 
        }
        
        /* Layout Structure */
        .admin-layout { 
            display: flex; 
            height: 100vh; 
            width: 100vw; 
            overflow: hidden; 
            position: relative; 
        }

        .main-content {
            flex: 1;
            position: relative;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-left: 0; /* Default mobile */
        }

        @media (min-width: 769px) {
            .main-content { margin-left: 280px; } /* Sidebar space */
            .sidebar { display: flex !important; }
        }

        #map {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background: #111827;
        }

        /* --- UI Layers (Dark Glassmorphism) --- */
        .top-ui-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            pointer-events: none;
        }

        .circle-btn {
            width: 45px;
            height: 45px;
            background: rgba(45, 45, 50, 0.85); /* Dark Neutral Glass */
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #f8fafc;
            pointer-events: auto;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }
        .circle-btn:hover { background: rgba(51, 65, 85, 0.9); transform: scale(1.05); }

        .earnings-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 20px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 380px;
            margin: 40px auto 0;
            pointer-events: auto;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .earnings-header {
            font-size: 0.85rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .earnings-body { display: flex; justify-content: space-between; align-items: flex-end; }
        
        .earnings-details { display: flex; flex-direction: column; gap: 4px; font-size: 0.8rem; color: #cbd5e1; }
        
        .earnings-amount {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        
        .earnings-sub { font-size: 0.7rem; color: #64748b; text-align: right; margin-top: 4px; }

        /* --- Floating Actions --- */
        .floating-layer {
            position: absolute;
            bottom: 250px; /* Adjusted for bottom panel */
            width: 100%;
            padding: 0 20px;
            z-index: 900;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            pointer-events: none;
        }

        .fab-col { display: flex; flex-direction: column; gap: 15px; pointer-events: auto; }

        .fab-btn {
            width: 50px;
            height: 50px;
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .fab-btn:hover { transform: scale(1.1); }
        
        .fab-sos { background: #ef4444; color: white; border: none; box-shadow: 0 0 20px rgba(239, 68, 68, 0.4); animation: pulse 2s infinite; }
        .fab-traffic { border-radius: 14px; color: #10b981; }

        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }

        /* --- Bottom Panel --- */
        .bottom-panel {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(30, 30, 35, 0.95); /* Deep matte dark */
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px 24px 0 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transform: translateY(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .panel-drawer {
            width: 40px;
            height: 4px;
            background: #475569;
            border-radius: 2px;
            margin: 12px auto;
            cursor: pointer;
        }

        .panel-content { padding: 0 20px 20px; }
        
        /* Search Box Spacing */
        .search-box { margin-bottom: 5px; }

        .vehicle-list {
            margin-top: 15px;
            max-height: 250px;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        /* Custom Scrollbar */
        .vehicle-list::-webkit-scrollbar { width: 4px; }
        .vehicle-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }

        .vehicle-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .vehicle-item:hover { background: rgba(51, 65, 85, 0.4); }

        .v-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(51, 65, 85, 0.5);
            color: #94a3b8;
        }
        .v-icon.active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        
        /* Footer Blue Bar */
        .app-footer {
            background: #1e3a8a; /* Deep Blue toggle bar */
            padding: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }
        
        .toggle-track {
            width: 56px;
            height: 28px;
            background: rgba(0,0,0,0.3);
            border-radius: 14px;
            position: relative;
            cursor: pointer;
        }
        .toggle-thumb {
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            left: 2px;
            transition: left 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .toggle-track.active .toggle-thumb { left: 30px; }
        .status-txt { font-weight: 700; font-size: 0.8rem; letter-spacing: 1px; color: rgba(255,255,255,0.6); }
        .status-txt.active { color: white; opacity: 1; }

    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>

<div class="admin-layout">
    <!-- Desktop Sidebar -->
    <div class="sidebar d-none d-md-flex">
        <?php include_once '../includes/admin_sidebar.php'; ?>
    </div>

    <!-- Main Map Area -->
    <main class="main-content">
        
        <!-- Top Stats (Floating) -->
        <div class="top-ui-layer">
            <div class="circle-btn d-md-none" onclick="history.back()"><i class="fas fa-arrow-left"></i></div>
            
            <div class="earnings-card">
                <div class="earnings-header">Fleet Status â€¢ Live</div>
                <div class="earnings-body">
                    <div class="earnings-details">
                        <span><i class="fas fa-car-side"></i> <?php echo count($vehicles); ?> Total</span>
                        <span style="color: #10b981"><i class="fas fa-bolt"></i> <?php echo $hired_count; ?> Active</span>
                    </div>
                    <div>
                        <div class="earnings-amount"><?php echo $hired_count; ?></div>
                        <div class="earnings-sub">Trips in Progress</div>
                    </div>
                </div>
            </div>

            <!-- Spacer for layout balance -->
            <div class="circle-btn d-md-none" style="opacity:0; pointer-events:none"></div>
        </div>

        <!-- Floating Actions -->
        <div class="floating-layer">
            <div class="fab-col">
                <div class="fab-btn fab-sos" onclick="alert('SOS Filter Activated')">SOS</div>
            </div>
            <div class="fab-col">
                <div class="fab-btn fab-traffic"><i class="fas fa-traffic-light"></i></div>
                <div class="fab-btn" onclick="recenterMap()"><i class="fas fa-crosshairs"></i></div>
            </div>
        </div>

        <!-- Bottom Sheet -->
        <div class="bottom-panel" id="bottomPanel">
            <div class="panel-drawer" onclick="togglePanel()"></div>
            <div class="panel-content">
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="Search vehicle, plate, or driver..." oninput="filterList(this.value)">
                </div>
                
                <div class="vehicle-list">
                    <?php foreach($vehicles as $v): ?>
                        <div class="vehicle-item" onclick="flyToVehicle(<?php echo $v['id']; ?>)">
                            <div class="v-icon <?php echo $v['status'] == 'hired' ? 'active' : ''; ?>">
                                <i class="fas fa-car"></i>
                            </div>
                            <div style="flex:1">
                                <strong style="display:block; font-size:0.95rem;"><?php echo $v['make'] . ' ' . $v['model']; ?></strong>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <span style="font-size:0.75rem; color:#64748b; font-family: monospace;"><?php echo $v['plate_number']; ?></span>
                                    <span style="font-size:0.75rem; color:#475569;">|</span>
                                    <span style="font-size:0.75rem; color:#3b82f6; font-family: monospace; font-weight: 600;">
                                        <?php echo number_format($v['latitude'], 4); ?>, <?php echo number_format($v['longitude'], 4); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if($v['status'] == 'hired'): ?>
                                <span style="font-size:0.75rem; color:#10b981; font-weight:600;">MOVING</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="app-footer">
                <span class="status-txt">ALL</span>
                <div class="toggle-track active" onclick="toggleFilter()" id="viewToggle">
                    <div class="toggle-thumb"></div>
                </div>
                <span class="status-txt active">ACTIVE</span>
            </div>

            <!-- Uber-Style Floating HUD -->
            <div class="speed-hud" id="adminSpeedometer" style="position: absolute; bottom: 20px; left: 20px; z-index: 1000; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 20px; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; font-weight: 800; color: #1e293b; line-height: 1;" id="liveSpeed">0</div>
                    <div style="font-size: 0.6rem; color: #64748b; text-transform: uppercase; font-weight: 700; margin-top: 4px;">Fleet KM/H</div>
                </div>
            </div>
        </div>

        <div id="map"></div>
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // --- 1. Map Init (Light Mode) ---
    const map = L.map('map', { zoomControl: true, attributionControl: false }).setView([-15.3875, 28.3228], 13);
    
    // Antigravity Clean Light (CartoDB Positron)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 20
    }).addTo(map);

    const vehicles = <?php echo json_encode($vehicles); ?>;
    const markers = {};
    const trails = {};

    // --- 2. Custom Marker (Realistic Car Image) ---
    function createMarkerIcon(moving, angle) {
        return L.divIcon({
            className: 'nav-car',
            html: `
                <div style="transform: rotate(${angle}deg); filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));">
                    <img src="https://emojipedia-us.s3.dualstack.us-west-1.amazonaws.com/thumbs/120/apple/285/oncoming-automobile_1f698.png" style="width: 40px; height: 40px; transform: rotate(-90deg);">
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });
    }

    // --- 3. Marker & Trail Setup ---
    vehicles.forEach(v => {
        const isMoving = v.status === 'hired';
        const angle = Math.random() * 360; // Initial random angle
        
        // Polyline Trail (Route History)
        const trail = L.polyline([], {
            color: '#3b82f6',
            weight: 3,
            opacity: 0.6,
            dashArray: '1, 8'
        }).addTo(map);

        const marker = L.marker([v.latitude, v.longitude], {
            icon: createMarkerIcon(isMoving, angle)
        }).addTo(map);

        markers[v.id] = {
            marker: marker,
            trail: trail,
            lat: parseFloat(v.latitude),
            lng: parseFloat(v.longitude),
            targetLat: parseFloat(v.latitude),
            targetLng: parseFloat(v.longitude),
            angle: angle,
            targetAngle: angle,
            moving: isMoving,
            speed: 0
        };
    });

    let activeFocusId = null;

    // --- 4. Logic Car Physics Animation (The "Pro" Movement) ---
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
                        car.moving = (v.status === 'online');
                        car.speed = parseFloat(v.speed);
                        
                        // Update HUD if focused
                        if (car.moving && car.speed > 0) {
                            document.getElementById('adminSpeedometer').style.display = 'block';
                            document.getElementById('liveSpeed').innerText = Math.round(car.speed);
                        }
                    }
                });
            }
        } catch (e) {
            console.error("Fleet Sync Error:", e);
        }
    }

    function loop() {
        Object.keys(markers).forEach(id => {
            const car = markers[id];
            
            // Linear Interpolation for buttery movement
            const lerpFactor = 0.05;
            car.lat += (car.targetLat - car.lat) * lerpFactor;
            car.lng += (car.targetLng - car.lng) * lerpFactor;
            
            // Angle interpolation
            let diff = (car.targetAngle || 0) - car.angle;
            if (diff > 180) diff -= 360;
            if (diff < -180) diff += 360;
            car.angle += diff * 0.1;

            car.marker.setLatLng([car.lat, car.lng]);
            car.marker.setIcon(createMarkerIcon(car.moving, car.angle));
            
            // Add point to trail if moving
            if (car.moving && Math.random() > 0.95) {
                car.trail.addLatLng([car.lat, car.lng]);
                if (car.trail.getLatLngs().length > 30) car.trail.getLatLngs().shift();
            }
        });
        requestAnimationFrame(loop);
    }

    // Start Real-Time Pipeline
    loop(); 
    setInterval(syncFleet, 2000);
    syncFleet();

    // --- 5. Interactions ---
    function flyToVehicle(id) {
        const car = markers[id];
        if (car) {
            map.flyTo([car.lat, car.lng], 16, { duration: 1.5 });
            togglePanel(true); // Minimize panel
        }
    }

    function togglePanel(forceClose = false) {
        const panel = document.getElementById('bottomPanel');
        if (forceClose || panel.style.transform === 'translateY(0px)') {
            panel.style.transform = 'translateY(60%)'; // Peek mode
        } else {
            panel.style.transform = 'translateY(0px)'; // Full mode
        }
    }
    
    function recenterMap() { map.setView([-15.3875, 28.3228], 13); }

    function filterList(term) {
        term = term.toLowerCase();
        document.querySelectorAll('.vehicle-item').forEach(el => {
            el.style.display = el.innerText.toLowerCase().includes(term) ? 'flex' : 'none';
        });
    }

    function toggleFilter() {
        const track = document.getElementById('viewToggle');
        track.classList.toggle('active');
        const showActive = track.classList.contains('active');
        
        Object.keys(markers).forEach(id => {
            const car = markers[id];
            if (showActive && !car.moving) {
                map.removeLayer(car.marker);
                map.removeLayer(car.trail);
            } else {
                car.marker.addTo(map);
                car.trail.addTo(map);
            }
        });
    }

    // Default View: minimized panel on load for better map visibility
    setTimeout(() => togglePanel(true), 1000);

</script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

