<?php
require_once '../includes/db.php';

// Fetch ALL bookings (not just confirmed) so admin always has something to select
try {
    $stmt = $pdo->query("
        SELECT b.id as booking_id, b.status, v.make, v.model, v.license_plate, v.image_url,
               v.last_lat, v.last_lng, v.bearing, v.current_speed,
               u.name as customer
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        JOIN users u ON b.user_id = u.id
        WHERE b.status IN ('confirmed', 'active', 'ongoing', 'pending')
        ORDER BY b.created_at DESC
        LIMIT 50
    ");
    $active_bookings = $stmt->fetchAll();
} catch(Exception $e) {
    $active_bookings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Fleet Tracker | Car Higher Admin</title>

    <!-- Leaflet CSS — NO API KEY NEEDED -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --darker: #060d1a;
            --card: rgba(15, 23, 42, 0.88);
            --border: rgba(255,255,255,0.10);
            --text-muted: rgba(255,255,255,0.5);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--darker);
            color: #fff;
            height: 100vh;
            overflow: hidden;
        }

        /* ===== DESKTOP: full-screen map with absolute overlays ===== */
        #map {
            position: absolute;
            inset: 0;
            z-index: 0;
        }

        /* Map tiles — no filter so streets/labels are fully readable */
        .leaflet-tile-pane { filter: none; }

        /* ===== LEFT HUD ===== */
        .hud-left {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 300px;
            z-index: 400;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .glass {
            background: var(--card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.55);
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 14px;
        }

        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }

        .brand-title { font-size: 1rem; font-weight: 700; }
        .brand-sub { font-size: 0.7rem; color: var(--text-muted); }

        .live-dot {
            width: 8px; height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: blink 1.5s ease-in-out infinite;
            display: inline-block;
            margin-right: 6px;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(0.7); }
        }

        .badge-live {
            display: inline-flex;
            align-items: center;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--success);
            background: rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.25);
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }

        .vehicle-title { font-size: 1.25rem; font-weight: 700; line-height: 1.2; margin-bottom: 3px; }
        .vehicle-plate { font-size: 0.8rem; color: var(--text-muted); }

        /* Telemetry */
        .tele-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
        .tele-item { display: flex; flex-direction: column; gap: 2px; }
        .tele-label { font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); }
        .tele-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 600;
            color: #fff;
        }
        .tele-val .unit { font-size: 0.65rem; color: var(--text-muted); margin-left: 2px; }

        .tele-full { grid-column: 1 / -1; }

        /* Route progress bar */
        .route-bar-wrap { margin-top: 14px; }
        .route-bar-label { display: flex; justify-content: space-between; font-size: 0.68rem; color: var(--text-muted); margin-bottom: 6px; }
        .route-bar-bg { background: rgba(255,255,255,0.07); border-radius: 6px; height: 5px; overflow: hidden; }
        .route-bar-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, var(--primary), var(--success)); transition: width 0.8s ease; }

        /* Customer card */
        .cust-row { display: flex; align-items: center; gap: 12px; }
        .cust-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1rem; flex-shrink: 0;
        }
        .cust-name { font-size: 0.9rem; font-weight: 600; }
        .cust-sub { font-size: 0.7rem; color: var(--text-muted); }

        /* Fleet mini list */
        .fleet-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.15s;
            border-radius: 8px;
            padding: 9px 8px;
        }
        .fleet-item:last-child { border-bottom: none; }
        .fleet-item:hover { background: rgba(255,255,255,0.04); }
        .fleet-item.active-item { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.25); }
        .fleet-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .fleet-dot.online { background: var(--success); box-shadow: 0 0 6px var(--success); }
        .fleet-dot.offline { background: var(--danger); }
        .fleet-make { font-size: 0.82rem; font-weight: 600; }
        .fleet-plate { font-size: 0.68rem; color: var(--text-muted); }

        /* ===== BOTTOM BAR ===== */
        .bottom-bar {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 400;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .ctrl-btn {
            display: flex; align-items: center; gap: 8px;
            background: var(--card);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            color: #fff;
            padding: 10px 20px;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .ctrl-btn:hover { background: rgba(59,130,246,0.2); border-color: var(--primary); }
        .ctrl-btn.primary-btn { background: var(--primary); border-color: var(--primary); }
        .ctrl-btn.primary-btn:hover { background: #2563eb; }

        select.ctrl-btn { min-width: 220px; }
        select.ctrl-btn option { background: #111827; color: #fff; }

        /* ===== COMPASS ===== */
        .compass {
            position: absolute; top: 20px; right: 20px;
            width: 58px; height: 58px;
            background: var(--card); backdrop-filter: blur(12px);
            border: 1px solid var(--border); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            z-index: 400;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
        }
        .compass svg { transition: transform 0.4s ease; }

        /* ===== LOADER ===== */
        #loader {
            position: fixed; inset: 0;
            background: var(--darker);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            z-index: 9999;
            transition: opacity 0.6s;
        }
        .loader-ring {
            width: 48px; height: 48px;
            border: 3px solid rgba(59,130,246,0.15);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text { margin-top: 16px; font-size: 0.78rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-muted); }

        /* ===== CUSTOM CAR MARKER ===== */
        .car-marker-wrap {
            display: flex; align-items: center; justify-content: center;
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.6));
        }
        .car-pulse-ring {
            position: absolute;
            width: 60px; height: 60px;
            border-radius: 50%;
            border: 2px solid rgba(59,130,246,0.6);
            animation: ringPulse 2s ease-out infinite;
        }
        @keyframes ringPulse {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }

        /* ===== MOBILE: stacked layout — map on top, controls below ===== */
        @media (max-width: 768px) {
            body { overflow: auto; height: auto; }

            /* Map as a fixed-height block at the top */
            #map {
                position: relative !important;
                height: 52vh;
                width: 100%;
                inset: unset;
                z-index: 1;
            }

            /* Compass inside the map */
            .compass {
                top: 10px;
                right: 10px;
                width: 44px;
                height: 44px;
            }

            /* HUD becomes scrollable content below the map */
            .hud-left {
                position: relative !important;
                top: unset; left: unset;
                width: 100%;
                padding: 12px 12px 0;
                z-index: 2;
                gap: 10px;
            }

            /* Bottom bar sits naturally at the bottom of the scroll */
            .bottom-bar {
                position: relative !important;
                bottom: unset; left: unset;
                transform: none;
                flex-direction: column;
                width: 100%;
                padding: 12px;
                z-index: 2;
                gap: 8px;
                margin-bottom: 70px; /* space for mobile nav */
            }

            .ctrl-btn, select.ctrl-btn {
                width: 100%;
                justify-content: center;
            }

            /* Loader fix */
            #loader { position: fixed; }
        }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div id="loader">
        <div class="loader-ring"></div>
        <p class="loader-text">Connecting to fleet satellites...</p>
    </div>

    <!-- MAP -->
    <div id="map"></div>

    <!-- Compass -->
    <div class="compass" id="compass">
        <svg width="32" height="32" viewBox="0 0 32 32" id="compass-svg">
            <polygon points="16,4 19,16 16,14 13,16" fill="#ef4444"/>
            <polygon points="16,28 19,16 16,18 13,16" fill="#e2e8f0"/>
        </svg>
    </div>

    <!-- LEFT HUD -->
    <div class="hud-left">

        <!-- Brand + Signal Card -->
        <div class="glass">
            <div class="brand-header">
                <div class="brand-icon"><i class="fas fa-satellite-dish"></i></div>
                <div>
                    <div class="brand-title">Live Fleet Tracker</div>
                    <div class="brand-sub">Car Higher Mission Control</div>
                </div>
            </div>

            <div class="badge-live"><span class="live-dot"></span> Signal Active</div>
            <div class="vehicle-title" id="hud-car-name">Select a vehicle</div>
            <div class="vehicle-plate" id="hud-car-plate">No active booking selected</div>

            <div class="tele-grid">
                <div class="tele-item">
                    <span class="tele-label">Speed</span>
                    <span class="tele-val" id="hud-speed">0<span class="unit">km/h</span></span>
                </div>
                <div class="tele-item">
                    <span class="tele-label">Heading</span>
                    <span class="tele-val" id="hud-bearing">—<span class="unit">°</span></span>
                </div>
                <div class="tele-item tele-full">
                    <span class="tele-label">GPS Coordinates</span>
                    <span class="tele-val" style="font-size:0.85rem; color: var(--primary);" id="hud-coords">—</span>
                </div>
                <div class="tele-item tele-full">
                    <span class="tele-label">Current Area</span>
                    <span class="tele-val" style="font-size:0.82rem; color:#f59e0b;" id="hud-location">Locating…</span>
                </div>
            </div>

            <div class="route-bar-wrap">
                <div class="route-bar-label"><span>Journey Progress</span><span id="hud-pct">0%</span></div>
                <div class="route-bar-bg"><div class="route-bar-fill" id="route-fill" style="width:0%"></div></div>
            </div>
        </div>

        <!-- Customer Card -->
        <div class="glass" id="cust-card">
            <div class="tele-label" style="margin-bottom:10px;">Customer in Possession</div>
            <div class="cust-row">
                <div class="cust-avatar" id="cust-initial">?</div>
                <div>
                    <div class="cust-name" id="cust-name">None selected</div>
                    <div class="cust-sub">Confirmed Booking</div>
                </div>
            </div>
        </div>

        <!-- Fleet List -->
        <?php if (count($active_bookings) > 0): ?>
        <div class="glass">
            <div class="tele-label" style="margin-bottom:10px;">Active Bookings (<?= count($active_bookings) ?>)</div>
            <?php foreach ($active_bookings as $b): ?>
            <div class="fleet-item" 
                 data-booking-id="<?= $b['booking_id'] ?>"
                 data-name="<?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?>"
                 data-plate="<?= htmlspecialchars($b['license_plate']) ?>"
                 data-customer="<?= htmlspecialchars($b['customer']) ?>"
                 onclick="selectBookingFromList(this)">
                <div class="fleet-dot <?= ($b['last_lat'] ? 'online' : 'offline') ?>"></div>
                <div>
                    <div class="fleet-make"><?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?></div>
                    <div class="fleet-plate"><?= htmlspecialchars($b['license_plate']) ?> • <?= htmlspecialchars($b['customer']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="glass" style="text-align:center; color: var(--text-muted);">
            <i class="fas fa-car" style="font-size:2rem; margin-bottom:10px; opacity:0.3;"></i>
            <p style="font-size:0.85rem;">No confirmed bookings found.</p>
        </div>
        <?php endif; ?>

    </div>

    <!-- Bottom Controls -->
    <div class="bottom-bar">
        <select class="ctrl-btn" id="booking-selector">
            <option value="">— Select Active Booking —</option>
            <?php foreach ($active_bookings as $b): ?>
            <option value="<?= $b['booking_id'] ?>"
                    data-name="<?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?>"
                    data-plate="<?= htmlspecialchars($b['license_plate']) ?>"
                    data-customer="<?= htmlspecialchars($b['customer']) ?>">
                [<?= strtoupper($b['status']) ?>] <?= htmlspecialchars($b['license_plate']) ?> — <?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?> (<?= htmlspecialchars($b['customer']) ?>)
            </option>
            <?php endforeach; ?>
            <?php if (empty($active_bookings)): ?>
            <option disabled>No bookings found in database</option>
            <?php endif; ?>
        </select>
        <button class="ctrl-btn" onclick="toggleFollow()"><i class="fas fa-crosshairs"></i> <span id="follow-btn-text">Following ON</span></button>
        <a href="dashboard.php" class="ctrl-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>

    <!-- Leaflet JS — NO API KEY NEEDED -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // ============================================================
    // LEAFLET MAP SETUP
    // ============================================================
    const map = L.map('map', {
        center: [-15.3901, 28.3235],
        zoom: 14,
        zoomControl: false,
        attributionControl: true
    });

    // Standard OpenStreetMap tiles — shows maximum detail, small businesses, and all street names at high zoom levels.
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // ── Nearby Landmarks (Overpass API — free, no key) ──────────────
    const poiIcon = (color, icon) => L.divIcon({
        className: '',
        html: `<div style="background:${color};width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 2px 8px rgba(0,0,0,.35);font-size:.8rem;">${icon}</div>`,
        iconSize: [28, 28], iconAnchor: [14, 14]
    });

    const poiColors = { fuel: ['#f59e0b','⛽'], hospital: ['#ef4444','🏥'], police: ['#3b82f6','🚓'], restaurant: ['#10b981','🍽️'], school: ['#8b5cf6','🏫'], parking: ['#6366f1','🅿️'] };
    let poiLayerGroup = L.layerGroup().addTo(map);
    let lastPoiFetch = { lat: null, lng: null };

    async function fetchNearbyPOIs(lat, lng) {
        // Only refresh POIs if moved more than ~500m from last fetch
        if (lastPoiFetch.lat && Math.abs(lat - lastPoiFetch.lat) < 0.005 && Math.abs(lng - lastPoiFetch.lng) < 0.005) return;
        lastPoiFetch = { lat, lng };

        const radius = 1000; // metres
        const query = `[out:json][timeout:10];
            (
              node["amenity"~"fuel|hospital|police|restaurant|school|parking"](around:${radius},${lat},${lng});
            );
            out body 30;`;

        try {
            const r = await fetch('https://overpass-api.de/api/interpreter', {
                method: 'POST', body: query
            });
            const data = await r.json();
            poiLayerGroup.clearLayers();

            data.elements.forEach(el => {
                const type = el.tags.amenity;
                const conf = poiColors[type];
                if (!conf) return;
                const [color, emoji] = conf;
                const name = el.tags.name || type.charAt(0).toUpperCase() + type.slice(1);
                L.marker([el.lat, el.lon], { icon: poiIcon(color, emoji), zIndexOffset: 500 })
                    .bindPopup(`<b>${name}</b><br><small style="color:#666">${type}</small>`)
                    .addTo(poiLayerGroup);
            });
        } catch(e) { /* Overpass temporarily unavailable */ }
    }

    // ── Reverse Geocoding (Nominatim — free, no key) ──────────────────
    let lastGeocodeLat = null, lastGeocodeLng = null;
    async function reverseGeocode(lat, lng) {
        if (lastGeocodeLat && Math.abs(lat - lastGeocodeLat) < 0.002 && Math.abs(lng - lastGeocodeLng) < 0.002) return;
        lastGeocodeLat = lat; lastGeocodeLng = lng;
        try {
            const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, {
                headers: { 'Accept-Language': 'en' }
            });
            const d = await r.json();
            if (d && d.address) {
                const area = d.address.road || d.address.suburb || d.address.county || '';
                const city = d.address.city || d.address.town || d.address.village || '';
                document.getElementById('hud-location').textContent = [area, city].filter(Boolean).join(', ') || 'Locating…';
            }
        } catch(e) {}
    }

    // Custom zoom control (bottom right)
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // ============================================================
    // STATE
    // ============================================================
    let activeBookingId = null;
    let carMarker = null;
    let routeLineBg = null;
    let routeLine = null;
    let trailPoints = [];
    let isFollowing = true;
    let pollInterval = null;

    // Interpolation state
    let currentLat = -15.3901, currentLng = 28.3235, currentBearing = 0;
    let targetLat  = -15.3901, targetLng  = 28.3235, targetBearing  = 0;
    let animRunning = false;

    // ============================================================
    // CAR ICON (SVG, rotatable)
    // ============================================================
    function createCarIcon() {
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
            iconSize: [80, 80],
            iconAnchor: [40, 40]
        });
    }

    // ============================================================
    // SMOOTH ANIMATION (lerp)
    // ============================================================
    function lerp(a, b, t) { return a + (b - a) * t; }

    function animateMarker() {
        if (!animRunning) return;

        currentLat = lerp(currentLat, targetLat, 0.06);
        currentLng = lerp(currentLng, targetLng, 0.06);

        let diff = targetBearing - currentBearing;
        if (diff > 180) diff -= 360;
        if (diff < -180) diff += 360;
        currentBearing += diff * 0.08;

        if (carMarker) {
            carMarker.setLatLng([currentLat, currentLng]);
            const el = carMarker.getElement();
            if (el) {
                const rotator = el.querySelector('.car-rotator');
                if (rotator) rotator.style.transform = `rotate(${currentBearing}deg)`;
            }
        }

        if (isFollowing) map.setView([currentLat, currentLng], map.getZoom(), { animate: false });

        // Update compass
        document.querySelector('#compass-svg').style.transform = `rotate(${-currentBearing}deg)`;

        requestAnimationFrame(animateMarker);
    }

    // ============================================================
    // BOOKING SELECTION
    // ============================================================
    function selectBooking(bookingId, name, plate, customer) {
        activeBookingId = bookingId;

        document.getElementById('hud-car-name').textContent = name;
        document.getElementById('hud-car-plate').textContent = plate;
        document.getElementById('cust-name').textContent = customer;
        document.getElementById('cust-initial').textContent = customer.charAt(0).toUpperCase();

        // Highlight list item
        document.querySelectorAll('.fleet-item').forEach(el => el.classList.remove('active-item'));
        const listItem = document.querySelector(`.fleet-item[data-booking-id="${bookingId}"]`);
        if (listItem) listItem.classList.add('active-item');

        // Create trail with a glowing thick background
        if (routeLineBg) map.removeLayer(routeLineBg);
        if (routeLine) map.removeLayer(routeLine);
        trailPoints = [];
        
        routeLineBg = L.polyline([], {
            color: '#1e3a8a',
            weight: 12,
            opacity: 0.5,
            lineJoin: 'round',
            lineCap: 'round'
        }).addTo(map);

        routeLine = L.polyline([], {
            color: '#3b82f6',
            weight: 6,
            opacity: 1,
            dashArray: '10, 8',
            lineJoin: 'round',
            lineCap: 'round',
            className: 'route-glow'
        }).addTo(map);

        // Initial fetch
        fetchTelemetry(true);
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => fetchTelemetry(false), 2000);
    }

    document.getElementById('booking-selector').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!this.value) return;
        selectBooking(this.value, opt.dataset.name, opt.dataset.plate, opt.dataset.customer);
    });

    function selectBookingFromList(el) {
        selectBooking(el.dataset.bookingId, el.dataset.name, el.dataset.plate, el.dataset.customer);
        // Sync dropdown
        document.getElementById('booking-selector').value = el.dataset.bookingId;
    }

    // ============================================================
    // TELEMETRY POLLING
    // ============================================================
    async function fetchTelemetry(isFirst = false) {
        if (!activeBookingId) return;
        try {
            const res  = await fetch(`../api/vehicle-gps.php?booking_id=${activeBookingId}`);
            const data = await res.json();

            if (data.success) {
                const v = data.data.vehicle;
                const hist = data.data.history || [];

                // Update targets
                targetLat     = parseFloat(v.lat);
                targetLng     = parseFloat(v.lng);
                
                // Calculate actual physical heading based on movement vector (makes turns look realistic)
                if (trailPoints.length >= 1) {
                    const lastPt = trailPoints[trailPoints.length - 1];
                    const dLat = targetLat - lastPt[0];
                    const dLng = targetLng - lastPt[1];
                    if (Math.abs(dLat) > 0.00001 || Math.abs(dLng) > 0.00001) {
                        targetBearing = (Math.atan2(dLng, dLat) * (180 / Math.PI) + 360) % 360;
                    } else {
                        targetBearing = parseFloat(v.bearing);
                    }
                } else {
                    targetBearing = parseFloat(v.bearing);
                }

                if (isFirst) {
                    currentLat = targetLat;
                    currentLng = targetLng;
                    currentBearing = targetBearing;

                    // Place marker
                    if (carMarker) map.removeLayer(carMarker);
                    carMarker = L.marker([currentLat, currentLng], {
                        icon: createCarIcon(),
                        zIndexOffset: 1000
                    }).addTo(map).bindPopup(`<b>${document.getElementById('hud-car-name').textContent}</b><br>${document.getElementById('hud-car-plate').textContent}`);

                    map.setView([currentLat, currentLng], 16);
                    if (!animRunning) { animRunning = true; animateMarker(); }

                    // Load history trail
                    if (hist.length > 0) {
                        const pts = hist.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
                        trailPoints = pts;
                        routeLineBg.setLatLngs(pts);
                        routeLine.setLatLngs(pts);
                    }
                } else {
                    // Add new point to trail
                    trailPoints.push([targetLat, targetLng]);
                    if (trailPoints.length > 200) trailPoints.shift();
                    routeLineBg.setLatLngs(trailPoints);
                    routeLine.setLatLngs(trailPoints);
                }

                // Update HUD
                document.getElementById('hud-speed').innerHTML   = `${Math.round(v.speed)}<span class="unit">km/h</span>`;
                document.getElementById('hud-bearing').innerHTML = `${Math.round(v.bearing)}<span class="unit">°</span>`;
                document.getElementById('hud-coords').textContent = `${parseFloat(v.lat).toFixed(5)}, ${parseFloat(v.lng).toFixed(5)}`;

                // Journey progress
                const pct = Math.min(100, Math.round((trailPoints.length / 200) * 100));
                document.getElementById('route-fill').style.width = pct + '%';
                document.getElementById('hud-pct').textContent = pct + '%';

                // Reverse geocode + nearby POIs (throttled)
                reverseGeocode(parseFloat(v.lat), parseFloat(v.lng));
                fetchNearbyPOIs(parseFloat(v.lat), parseFloat(v.lng));
            }
        } catch (e) {
            console.warn('Telemetry fetch error:', e);
        }
    }

    // ============================================================
    // CONTROLS
    // ============================================================
    function toggleFollow() {
        isFollowing = !isFollowing;
        document.getElementById('follow-btn-text').textContent = isFollowing ? 'Following ON' : 'Following OFF';
    }

    // ============================================================
    // DISMISS LOADER
    // ============================================================
    map.whenReady(() => {
        setTimeout(() => {
            const l = document.getElementById('loader');
            l.style.opacity = '0';
            setTimeout(() => l.remove(), 600);
        }, 800);
    });

    // Inject pulse ring and glow keyframe
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ringPulse { 0%{transform:scale(0.5);opacity:1} 100%{transform:scale(2.2);opacity:0} }
        .route-glow { filter: drop-shadow(0 0 6px rgba(59,130,246,0.8)); }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
