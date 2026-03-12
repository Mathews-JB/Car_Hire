<?php
require_once '../includes/env_loader.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch most recent confirmed booking for this customer
$stmt = $pdo->prepare("
    SELECT b.id as booking_id, v.make, v.model, v.license_plate, v.image_url,
           v.last_lat, v.last_lng, v.bearing, v.current_speed,
           b.pickup_date, b.dropoff_date, b.pickup_location, b.dropoff_location
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.user_id = ? AND b.status IN ('confirmed', 'active')
    ORDER BY b.created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php?msg=no_active");
    exit;
}

$car_image = !empty($booking['image_url']) ? '../' . $booking['image_url'] : 'https://via.placeholder.com/80x60?text=Car';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track My Ride | Car Higher</title>

    <!-- Leaflet â€” NO API KEY -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }

        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --dark: #0f172a;
            --card: rgba(15,23,42,0.92);
            --border: rgba(255,255,255,0.10);
        }

        body, html {
            height: 100vh; overflow: hidden;
            font-family: 'Outfit', sans-serif;
            background: var(--dark); color: #fff;
        }

        #map {
            position: absolute; inset: 0;
        }

        /* Map tiles â€” readable streets and area labels */
        .leaflet-tile-pane { filter: none; }

        /* ===== TOP BAR ===== */
        .top-bar {
            position: absolute; top: 0; left: 0; right: 0;
            padding: 16px 20px;
            background: linear-gradient(to bottom, rgba(10,15,30,0.92), transparent);
            display: flex; align-items: center; gap: 14px;
            z-index: 400;
        }

        .back-btn {
            display: flex; align-items: center; gap: 8px;
            background: var(--card); backdrop-filter: blur(12px);
            border: 1px solid var(--border); color: #fff;
            padding: 9px 16px; border-radius: 12px;
            text-decoration: none; font-size: 0.82rem; font-weight: 600;
            transition: background 0.2s;
        }
        .back-btn:hover { background: rgba(59,130,246,0.2); }

        .top-title {
            font-size: 1rem; font-weight: 700;
        }
        .top-sub {
            font-size: 0.72rem; color: rgba(255,255,255,0.5);
        }

        /* Speed bubble */
        .speed-bubble {
            margin-left: auto;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            border-radius: 16px;
            padding: 8px 16px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(59,130,246,0.4);
            min-width: 70px;
        }
        .speed-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.4rem; font-weight: 700; line-height: 1;
        }
        .speed-unit { font-size: 0.6rem; opacity: 0.75; text-transform: uppercase; }

        /* ===== BOTTOM CARD ===== */
        .bottom-card-wrap {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 16px 16px 24px;
            background: linear-gradient(to top, rgba(5,10,25,0.97) 60%, transparent);
            z-index: 400;
        }

        .vehicle-card {
            background: var(--card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 18px;
            display: flex; gap: 16px; align-items: center;
            box-shadow: 0 -4px 30px rgba(0,0,0,0.5);
        }

        .vehicle-img {
            width: 80px; height: 60px;
            border-radius: 14px; object-fit: cover;
            border: 2px solid var(--primary);
            flex-shrink: 0;
        }

        .vehicle-info { flex: 1; min-width: 0; }
        .vehicle-name { font-size: 1.1rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vehicle-plate { font-size: 0.75rem; color: rgba(255,255,255,0.55); margin-bottom: 8px; }

        .stat-chips { display: flex; gap: 8px; flex-wrap: wrap; }
        .chip {
            display: flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px; padding: 4px 10px;
            font-size: 0.72rem; font-weight: 600;
        }
        .chip .dot { width: 7px; height: 7px; border-radius: 50%; }
        .chip .dot.green { background: var(--success); animation: blink 1.5s infinite; }
        .chip .addr-chip { font-size:0.68rem; color:#f59e0b; }

        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

        .heading-chip { color: var(--primary); }

        .route-info {
            display: flex; gap: 12px; margin-top: 14px;
        }
        .route-point {
            display: flex; align-items: flex-start; gap: 8px;
            flex: 1; font-size: 0.75rem;
        }
        .route-icon { font-size: 0.9rem; margin-top: 2px; }
        .route-label { font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.06em; color: rgba(255,255,255,0.4); }
        .route-val { font-weight: 600; }

        @keyframes ringPulse { 0%{transform:scale(0.5);opacity:1} 100%{transform:scale(2.5);opacity:0} }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>

    <div id="map"></div>

    <!-- Top Bar -->
    <div class="top-bar">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <div>
            <div class="top-title">Track My Ride</div>
            <div class="top-sub" id="top-status">Locating vehicle...</div>
        </div>
        <div class="speed-bubble">
            <div class="speed-num" id="spd">0</div>
            <div class="speed-unit">km/h</div>
        </div>
    </div>

    <!-- Bottom Card -->
    <div class="bottom-card-wrap">
        <div class="vehicle-card">
            <img src="<?= $car_image ?>" class="vehicle-img" alt="Vehicle">
            <div class="vehicle-info">
                <div class="vehicle-name"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></div>
                <div class="vehicle-plate"><?= htmlspecialchars($booking['license_plate']) ?> â€¢ Booking #<?= $booking['booking_id'] ?></div>
                <div class="stat-chips">
                    <div class="chip"><div class="dot green"></div> Live Signal</div>
                    <div class="chip heading-chip"><i class="fas fa-compass"></i> <span id="hdg">â€”</span>Â°</div>
                    <div class="chip" style="color:#f59e0b;"><i class="fas fa-map-marker-alt"></i> <span id="area-name">Locatingâ€¦</span></div>
                </div>
            </div>
        </div>

        <div class="route-info" style="padding: 0 4px;">
            <div class="route-point">
                <div class="route-icon" style="color: var(--success);"><i class="fas fa-circle"></i></div>
                <div>
                    <div class="route-label">Pickup</div>
                    <div class="route-val"><?= htmlspecialchars($booking['pickup_location'] ?? 'N/A') ?></div>
                </div>
            </div>
            <div style="color:rgba(255,255,255,0.2); font-size:1.2rem; margin-top:4px;">â†’</div>
            <div class="route-point">
                <div class="route-icon" style="color: var(--primary);"><i class="fas fa-map-pin"></i></div>
                <div>
                    <div class="route-label">Dropoff</div>
                    <div class="route-val"><?= htmlspecialchars($booking['dropoff_location'] ?? 'N/A') ?></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    const BOOKING_ID = <?= (int)$booking['booking_id'] ?>;

    // Map setup
    const map = L.map('map', {
        center: [<?= $booking['last_lat'] ?: -15.3901 ?>, <?= $booking['last_lng'] ?: 28.3235 ?>],
        zoom: 16,
        zoomControl: false,
        attributionControl: false
    });

    // Standard OpenStreetMap tiles â€” maximum detail
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: 'Â© OpenStreetMap'
    }).addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    function carIcon() {
        return L.divIcon({
            className: '',
            html: `
            <div style="position:relative; width:80px; height:80px; display:flex; align-items:center; justify-content:center;">
              <div style="position:absolute; width:65px; height:65px; border-radius:50%; border:2px solid rgba(59,130,246,0.6); animation:ringPulse 2s ease-out infinite;"></div>
              <div class="car-rotator" style="transform: rotate(0deg); display:flex; align-items:center; justify-content:center; width:100%; height:100%; will-change: transform;">
                  <svg width="40" height="70" viewBox="0 0 100 180" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                      <linearGradient id="bodyBaseGrad" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#0f172a"/>
                        <stop offset="25%" stop-color="#cbd5e1"/>
                        <stop offset="50%" stop-color="#ffffff"/>
                        <stop offset="75%" stop-color="#cbd5e1"/>
                        <stop offset="100%" stop-color="#0f172a"/>
                      </linearGradient>
                      <linearGradient id="glass" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#020617"/>
                        <stop offset="50%" stop-color="#3b82f6"/>
                        <stop offset="100%" stop-color="#020617"/>
                      </linearGradient>
                    </defs>
                    
                    <!-- Soft Drop Shadow -->
                    <rect x="15" y="10" width="70" height="165" rx="20" fill="rgba(0,0,0,0.5)" filter="blur(4px)"/>
                    
                    <!-- Main Body Shape (Metallic Gradient) -->
                    <rect x="20" y="10" width="60" height="160" rx="20" fill="url(#bodyBaseGrad)" stroke="#1e293b" stroke-width="2"/>
                    
                    <!-- Hood Curves -->
                    <path d="M 28 35 Q 50 20 72 35 L 75 60 L 25 60 Z" fill="rgba(255,255,255,0.15)"/>
                    
                    <!-- Windshield Front -->
                    <path d="M 25 65 Q 50 50 75 65 L 68 85 L 32 85 Z" fill="url(#glass)"/>
                    <path d="M 30 65 Q 50 55 70 65 L 67 72 Q 50 63 33 72 Z" fill="rgba(255,255,255,0.4)"/>
                    
                    <!-- Windshield Rear -->
                    <path d="M 32 115 L 68 115 L 72 135 Q 50 148 28 135 Z" fill="url(#glass)"/>
                    
                    <!-- Roof -->
                    <rect x="28" y="85" width="44" height="30" rx="8" fill="#f8fafc"/>
                    <rect x="35" y="90" width="30" height="15" rx="4" fill="#0f172a"/>
                  
                    <!-- Headlights (Glowing) -->
                    <ellipse cx="28" cy="14" rx="6" ry="4" fill="#ffffff" filter="drop-shadow(0 -4px 8px rgba(255,255,255,1))"/>
                    <ellipse cx="72" cy="14" rx="6" ry="4" fill="#ffffff" filter="drop-shadow(0 -4px 8px rgba(255,255,255,1))"/>
                    
                    <!-- Taillights (Glowing Red) -->
                    <ellipse cx="28" cy="166" rx="8" ry="4" fill="#ef4444" filter="drop-shadow(0 4px 8px rgba(239,68,68,1))"/>
                    <ellipse cx="72" cy="166" rx="8" ry="4" fill="#ef4444" filter="drop-shadow(0 4px 8px rgba(239,68,68,1))"/>
                  
                    <!-- Side Mirrors -->
                    <path d="M 22 65 L 14 62 L 14 68 Z" fill="#1e293b"/>
                    <path d="M 78 65 L 86 62 L 86 68 Z" fill="#1e293b"/>
                  </svg>
              </div>
            </div>`,
            iconSize: [80, 80], iconAnchor: [40, 40]
        });
    }

    let carMarker = null, routeLineBg = null, routeLine = null, trailPts = [];
    let curLat = <?= $booking['last_lat'] ?: -15.3901 ?>, curLng = <?= $booking['last_lng'] ?: 28.3235 ?>, curBrg = <?= $booking['bearing'] ?: 0 ?>;
    let tgtLat = curLat, tgtLng = curLng, tgtBrg = curBrg;

    // Trail line background
    routeLineBg = L.polyline([], { color:'#1e3a8a', weight:10, opacity:0.5, lineJoin:'round', lineCap:'round' }).addTo(map);
    // Main trail line
    routeLine = L.polyline([], { color:'#3b82f6', weight:6, opacity:1, dashArray:'10, 8', lineJoin:'round', lineCap:'round', className:'route-glow' }).addTo(map);

    // Initial marker
    carMarker = L.marker([curLat, curLng], { icon: carIcon(), zIndexOffset: 1000 }).addTo(map);

    // Smooth animation
    function lerp(a, b, t) { return a + (b - a) * t; }
    (function animate() {
        curLat = lerp(curLat, tgtLat, 0.06);
        curLng = lerp(curLng, tgtLng, 0.06);
        let d = tgtBrg - curBrg;
        if (d > 180) d -= 360; if (d < -180) d += 360;
        curBrg += d * 0.08;
        if (carMarker) {
            carMarker.setLatLng([curLat, curLng]);
            const el = carMarker.getElement();
            if (el) {
                const rot = el.querySelector('.car-rotator');
                if (rot) rot.style.transform = `rotate(${curBrg}deg)`;
            }
            map.setView([curLat, curLng], map.getZoom(), { animate: false });
        }
        requestAnimationFrame(animate);
    })();

    // Nearby POIs via Overpass (free, no key)
    const poiIcon = (color, emoji) => L.divIcon({
        className: '',
        html: `<div style="background:${color};width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 2px 8px rgba(0,0,0,.35);font-size:.75rem;">${emoji}</div>`,
        iconSize:[26,26], iconAnchor:[13,13]
    });
    const POI_TYPES = { fuel:['#f59e0b','â›½'], hospital:['#ef4444','ðŸ¥'], police:['#3b82f6','ðŸš“'], restaurant:['#10b981','ðŸ½ï¸'], school:['#8b5cf6','ðŸ«'], parking:['#6366f1','ðŸ…¿ï¸'] };
    let poiLayer = L.layerGroup().addTo(map), lastPOI = {};

    async function fetchPOIs(lat, lng) {
        if (lastPOI.lat && Math.abs(lat-lastPOI.lat)<0.005 && Math.abs(lng-lastPOI.lng)<0.005) return;
        lastPOI = {lat, lng};
        const q = `[out:json][timeout:10];(node["amenity"~"fuel|hospital|police|restaurant|school|parking"](around:1000,${lat},${lng}););out body 25;`;
        try {
            const r = await fetch('https://overpass-api.de/api/interpreter', {method:'POST', body:q});
            const d = await r.json();
            poiLayer.clearLayers();
            d.elements.forEach(el => {
                const cfg = POI_TYPES[el.tags.amenity]; if (!cfg) return;
                const name = el.tags.name || el.tags.amenity;
                L.marker([el.lat, el.lon], {icon: poiIcon(cfg[0], cfg[1]), zIndexOffset:500})
                    .bindPopup(`<b>${name}</b><br><small style="color:#666">${el.tags.amenity}</small>`)
                    .addTo(poiLayer);
            });
        } catch(e) {}
    }

    // Reverse geocoding (Nominatim â€” free, no key)
    let lastGeo = {};
    async function reverseGeocode(lat, lng) {
        if (lastGeo.lat && Math.abs(lat-lastGeo.lat)<0.002 && Math.abs(lng-lastGeo.lng)<0.002) return;
        lastGeo = {lat, lng};
        try {
            const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, {headers:{'Accept-Language':'en'}});
            const d = await r.json();
            if (d && d.address) {
                const a = d.address;
                const txt = [a.road, a.suburb, a.city||a.town||a.village].filter(Boolean).join(', ');
                document.getElementById('area-name').textContent = txt || 'Unknown area';
                document.getElementById('top-status').textContent = txt || 'Tracking active';
            }
        } catch(e) {}
    }
    // â”€â”€ Poll GPS every 2s â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async function poll() {
        try {
            const r = await fetch(`../api/vehicle-gps.php?booking_id=${BOOKING_ID}`);
            const d = await r.json();
            if (d.success) {
                const v = d.data.vehicle;
                tgtLat = parseFloat(v.lat);
                tgtLng = parseFloat(v.lng);
                
                // Track physical bearing
                if (trailPts.length >= 1) {
                    const lastPt = trailPts[trailPts.length - 1];
                    const dLat = tgtLat - lastPt[0];
                    const dLng = tgtLng - lastPt[1];
                    if (Math.abs(dLat) > 0.00001 || Math.abs(dLng) > 0.00001) {
                        tgtBrg = (Math.atan2(dLng, dLat) * (180 / Math.PI) + 360) % 360;
                    } else {
                        tgtBrg = parseFloat(v.bearing);
                    }
                } else {
                    tgtBrg = parseFloat(v.bearing);
                }

                trailPts.push([tgtLat, tgtLng]);
                if (trailPts.length > 300) trailPts.shift();
                routeLineBg.setLatLngs(trailPts);
                routeLine.setLatLngs(trailPts);

                document.getElementById('spd').textContent = Math.round(v.speed);
                document.getElementById('hdg').textContent = Math.round(v.bearing);

                // Reverse geocode for area name (throttled)
                reverseGeocode(tgtLat, tgtLng);
                // Load nearby POIs (throttled â€” only refreshes every ~500m)
                fetchPOIs(tgtLat, tgtLng);
            }
        } catch(e) { console.warn(e); }
    }

    poll();
    setInterval(poll, 2000);

    // Inject CSS keyframe
    const s = document.createElement('style');
    s.textContent = `
        @keyframes ringPulse{0%{transform:scale(.5);opacity:1}100%{transform:scale(2.4);opacity:0}}
        .route-glow { filter: drop-shadow(0 0 6px rgba(59,130,246,0.8)); }
    `;
    document.head.appendChild(s);
    </script>
</body>
</html>

