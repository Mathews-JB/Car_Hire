<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 1. Revenue Analytics (Last 30 Days)
$days_data = [];
$start_date = date('Y-m-d', strtotime("-29 days"));

// Initialize the 30-day window
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days_data[$date] = [
        'lbl' => date('d M', strtotime($date)),
        'real' => 0,
        'proj' => 0
    ];
}

ksort($days_data); // Ensure chronological order by keys

// Fetch Real Cash (Payments)
$stmt = $pdo->prepare("SELECT DATE(created_at) as d, SUM(amount) as s FROM payments WHERE status = 'successful' AND created_at >= ? GROUP BY d");
$stmt->execute([$start_date]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if(isset($days_data[$row['d']])) $days_data[$row['d']]['real'] = max(0, (float)$row['s']);
}

// Fetch Projections (Bookings)
$stmt = $pdo->prepare("SELECT DATE(created_at) as d, SUM(total_price) as s FROM bookings WHERE status IN ('pending', 'confirmed', 'completed') AND created_at >= ? GROUP BY d");
$stmt->execute([$start_date]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if(isset($days_data[$row['d']])) $days_data[$row['d']]['proj'] = max(0, (float)$row['s']);
}

$revenue_labels = [];
$cum_real_arr = [];
$cum_proj_arr = [];
$total_real = 0; $total_proj = 0;

foreach($days_data as $date => $data) {
    $revenue_labels[] = $data['lbl'];
    
    $total_real += $data['real'];
    $total_proj += $data['proj'];
    
    // Strict non-decreasing logic
    $last_real = empty($cum_real_arr) ? 0 : end($cum_real_arr);
    $last_proj = empty($cum_proj_arr) ? 0 : end($cum_proj_arr);
    
    $cum_real_arr[] = max($last_real, $total_real);
    $cum_proj_arr[] = max($last_proj, $total_proj);
}

$cumulative_revenue = $total_real;
$projected_revenue = $total_proj;
$chart_seed = time();

// 2. Booking Distribution ... (keep existing)
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Most Popular Vehicles
$stmt = $pdo->query("SELECT v.make, v.model, COUNT(b.id) as count 
                     FROM bookings b 
                     JOIN vehicles v ON b.vehicle_id = v.id 
                     GROUP BY v.id, v.make, v.model
                     ORDER BY count DESC LIMIT 5");
$popular_vehicles = $stmt->fetchAll();

// 4. Analytics KPIs
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_v, 
    SUM(CASE WHEN status IN ('confirmed', 'completed') THEN 1 ELSE 0 END) as success_v 
    FROM bookings");
$kb = $stmt->fetch();
$conv_rate = $kb['total_v'] > 0 ? round(($kb['success_v'] / $kb['total_v']) * 100) : 0;

$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE status IN ('confirmed', 'completed')");
$unique_customers = $stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Analytics | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px; }
        .chart-card { 
            background: rgba(30, 30, 35, 0.6); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 24px; 
            padding: 30px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            color: white;
        }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .chart-title { font-size: 1.1rem; font-weight: 700; color: var(--accent-color); }

        @media (max-width: 768px) {
            .analytics-grid { grid-template-columns: 1fr !important; }
            .main-content { padding: 15px; }
            .page-title-box { margin-bottom: 25px !important; text-align: center; }
            .page-title-box h1 { font-size: 1.8rem !important; }
            .chart-card { padding: 20px 15px; }
            .chart-header { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Business Analytics</h1>
                    <p class="text-secondary">Visual performance data and growth metrics for the last 30 days.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print PDF</button>
                    <a href="reports-financial.php" class="btn btn-primary"><i class="fas fa-chart-line"></i> Financials</a>
                </div>
            </div>

            <div class="analytics-grid">
                <!-- Revenue Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <span class="chart-title">Revenue Trajectory (ZMW)</span>
                            <small style="opacity: 0.5; display: block; margin-top: 4px;">
                                Total Potential: <strong>ZMW <?php echo number_format($projected_revenue); ?></strong>
                            </small>
                            <span style="font-size: 0.6rem; opacity: 0.3;">Last Sync: <?php echo date('H:i:s'); ?></span>
                        </div>
                        <select id="revenueFilter" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 10px; font-size: 0.8rem;">
                            <option>Last 30 Days</option>
                        </select>
                    </div>
                    <div id="chart-container-<?php echo $chart_seed; ?>" style="height: 350px; position: relative; margin-top: 20px;">
                        <canvas id="revenueChart_<?php echo $chart_seed; ?>"></canvas>
                    </div>
                </div>

                <!-- Booking Status -->
                <div class="chart-card">
                    <div class="chart-header">
                        <span class="chart-title">Booking Distribution</span>
                    </div>
                    <canvas id="statusChart" height="350"></canvas>
                </div>
            </div>

            <div class="analytics-grid" style="grid-template-columns: 1fr 1fr;">
                <!-- Popular Fleet -->
                <div class="chart-card">
                    <div class="chart-header">
                        <span class="chart-title">Most Popular Vehicles</span>
                    </div>
                    <canvas id="fleetChart" height="250"></canvas>
                </div>

                <!-- KPI Quick Stats -->
                <div class="chart-card">
                    <div class="chart-header">
                        <span class="chart-title">Engagement Insights</span>
                    </div>
                    <div class="grid-2">
                        <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px; text-align: center;">
                            <span class="summary-value" style="color: var(--accent-color); font-size: 1.2rem; font-weight: 800; display: block;"><?php echo $conv_rate; ?>%</span>
                            <small style="opacity: 0.5; text-transform: uppercase; font-size: 0.6rem; display:block; margin-top: 5px;">Conversion</small>
                        </div>
                        <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px; text-align: center;">
                            <span class="summary-value" style="color: var(--success); font-size: 1.2rem; font-weight: 800; display: block;">4.8</span>
                            <small style="opacity: 0.5; text-transform: uppercase; font-size: 0.6rem; display:block; margin-top: 5px;">Rating</small>
                        </div>
                        <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px; text-align: center;">
                            <span class="summary-value" style="color: var(--primary-color); font-size: 1.2rem; font-weight: 800; display: block;">12h</span>
                            <small style="opacity: 0.5; text-transform: uppercase; font-size: 0.6rem; display:block; margin-top: 5px;">Pickup</small>
                        </div>
                        <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px; text-align: center;">
                            <span class="summary-value" style="color: var(--accent-vibrant); font-size: 1.2rem; font-weight: 800; display: block;"><?php echo $unique_customers; ?></span>
                            <small style="opacity: 0.5; text-transform: uppercase; font-size: 0.6rem; display:block; margin-top: 5px;">Repeats</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Revenue Analytics - Stepped Cumulative Model (Guaranteed Upward)
        const ctxRev = document.getElementById('revenueChart_<?php echo $chart_seed; ?>').getContext('2d');
        new Chart(ctxRev, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($revenue_labels); ?>,
                datasets: [
                    {
                        label: 'Realized Revenue',
                        data: <?php echo json_encode($cum_real_arr); ?>,
                        borderColor: '#4ade80', // Vibrant Green
                        backgroundColor: 'rgba(74, 222, 128, 0.1)',
                        fill: true,
                        stepped: true, // Flat horizontal lines only
                        tension: 0,
                        borderWidth: 4,
                        pointRadius: 0
                    },
                    {
                        label: 'Potential Revenue',
                        data: <?php echo json_encode($cum_proj_arr); ?>,
                        borderColor: '#60a5fa', // Bright Blue
                        borderWidth: 2,
                        borderDash: [5, 5],
                        stepped: true,
                        tension: 0,
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { position: 'top', labels: { color: 'rgba(255,255,255,0.7)', usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ZMW ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        suggestedMax: <?php echo max($projected_revenue, $cumulative_revenue) * 1.15; ?>,
                        grid: { color: 'rgba(255,255,255,0.05)' }, 
                        ticks: { color: 'rgba(255,255,255,0.5)', callback: v => 'ZMW ' + (v >= 1000 ? (v/1000) + 'k' : v) } 
                    },
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: 'rgba(255,255,255,0.5)', autoSkip: true, maxTicksLimit: 10 } 
                    }
                }
            }
        });

        // Status Chart
        const ctxStat = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStat, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($status_counts)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($status_counts)); ?>,
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: 'white', padding: 20, usePointStyle: true } }
                }
            }
        });

        // Fleet Popularity
        const ctxFleet = document.getElementById('fleetChart').getContext('2d');
        new Chart(ctxFleet, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(fn($v) => $v['make'].' '.$v['model'], $popular_vehicles)); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode(array_column($popular_vehicles, 'count')); ?>,
                    backgroundColor: '#6366f1',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.5)' } },
                    x: { grid: { display: false }, ticks: { color: 'white' } }
                }
            }
        });
    </script>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
