<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch ROI Data per vehicle
// Formula: Total Revenue (Completed Bookings) - Total Maintenance Cost
$stmt = $pdo->query("
    SELECT 
        v.id, 
        v.make, 
        v.model, 
        v.plate_number, 
        v.image_url,
        COALESCE((SELECT SUM(total_price) FROM bookings WHERE vehicle_id = v.id AND status = 'completed'), 0) as total_revenue,
        COALESCE((SELECT SUM(cost) FROM maintenance_logs WHERE vehicle_id = v.id), 0) as total_maintenance
    FROM vehicles v
    ORDER BY total_revenue DESC
");
$roi_data = $stmt->fetchAll();

// Fetch System-wide expenses (Salaries, Rent, etc.)
$total_system_expenses = $pdo->query("SELECT SUM(amount) FROM system_expenses")->fetchColumn() ?: 0;

// Global Stats
$total_fleet_revenue = array_sum(array_column($roi_data, 'total_revenue'));
$total_fleet_maintenance = array_sum(array_column($roi_data, 'total_maintenance'));
$net_profit = $total_fleet_revenue - $total_fleet_maintenance - $total_system_expenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial ROI Report | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .summary-card { padding: 25px; display: flex; flex-direction: column; justify-content: center; }
        .summary-card h4 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin: 0 0 10px; font-weight: 800; }
        .summary-value { font-size: 1.4rem; font-weight: 800; color: white; line-height: 1; margin-bottom: 8px; }
        .summary-card small { font-size: 0.75rem; opacity: 0.5; font-weight: 600; }
        

        
        .vehicle-flex { display: flex; align-items: center; gap: 15px; }
        .v-thumb { 
            width: 50px; height: 50px; 
            border-radius: 10px; 
            object-fit: cover; 
            background: #222; 
            border: 1px solid rgba(255,255,255,0.1);
        }
        .v-meta h5 { margin: 0; font-size: 0.95rem; font-weight: 700; color: white; }
        .v-meta span { font-size: 0.8rem; color: rgba(255,255,255,0.5); font-family: monospace; letter-spacing: 0.5px; }
        
        .amount { font-family: 'Inter', sans-serif; font-weight: 600; letter-spacing: -0.5px; }
        .text-green { color: #10b981; }
        .text-red { color: #f87171; }
        .text-white { color: white; }
        
        .profit-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .bg-profit-pos { background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.2); }
        .bg-profit-neg { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); }

        /* Print Optimization */
        @media print {
            .sidebar, 
            .admin-sidebar, 
            .mobile-nav, 
            .btn, 
            .dashboard-header button,
            header,
            .hub-bar { 
                display: none !important; 
            }
            
            .admin-layout {
                display: block !important;
                background: white !important;
            }
            
            .main-content { 
                margin-left: 0 !important; 
                width: 100% !important; 
                padding: 0 !important;
                max-width: 100% !important;
            }

            body { 
                background: white !important; 
                color: black !important; 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .table-wrapper { 
                border: 1px solid #000; 
                box-shadow: none; 
                background: white; 
            }
            
            th { 
                color: black; 
                background: #eee !important; 
                border-color: #000; 
            }
            
            td { 
                color: black; 
                border-color: #ddd; 
            }
            
            .profit-badge { 
                border: 1px solid #000; 
                color: #000; 
                background: none; 
            }
            
            .text-white { color: black !important; }
            .v-thumb { display: none; }
        }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Financial Intel</h1>
                    <p class="text-secondary">Track revenue, maintenance costs, and vehicle ROI.</p>
                </div>
                <div class="header-actions">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <button class="btn btn-outline" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-file-export"></i> Download Report</button>
                    <a href="analytics.php" class="btn btn-primary"><i class="fas fa-chart-pie"></i> Visual Analytics</a>
                </div>
            </div>

            <!-- Top Level Stats -->
            <div class="grid-4" style="margin-bottom: 30px;">
                <div class="data-card summary-card" style="border-top: 3px solid #60a5fa;">
                    <h4>Fleet Revenue</h4>
                    <p class="summary-value">ZMW <?php echo number_format($total_fleet_revenue, 0); ?></p>
                    <small>Completed Bookings</small>
                </div>
                <div class="data-card summary-card" style="border-top: 3px solid #f59e0b;">
                    <h4>Maintenance</h4>
                    <p class="summary-value">ZMW <?php echo number_format($total_fleet_maintenance, 0); ?></p>
                    <small>Vehicle Repairs & Logs</small>
                </div>
                <div class="data-card summary-card" style="border-top: 3px solid #ec4899;">
                    <h4>Fixed Expenses</h4>
                    <p class="summary-value">ZMW <?php echo number_format($total_system_expenses, 0); ?></p>
                    <small>Rent, Salaries, Taxes</small>
                </div>
                <div class="data-card summary-card" style="border-top: 3px solid <?php echo $net_profit >= 0 ? '#10b981' : '#ef4444'; ?>;">
                    <h4>Net Profit</h4>
                    <p class="summary-value" style="color:<?php echo $net_profit >= 0 ? '#10b981' : '#ef4444'; ?>;">
                        ZMW <?php echo number_format($net_profit, 0); ?>
                    </p>
                    <small>Real Return on Equity</small>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-weight: 800; font-size: 1.25rem; color: white;" class="text-white">Unit Profitability Breakdown</h3>
                <span class="text-secondary" style="font-size: 0.9rem;"><?php echo count($roi_data); ?> Vehicles Analyzed</span>
            </div>
            
            <!-- Data Table View -->
            <div class="data-card">
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Revenue</th>
                            <th>Expenses</th>
                            <th>Profit / Loss</th>
                            <th>Expense Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($roi_data as $v): 
                            $revenue = (float)$v['total_revenue'];
                            $maintenance = (float)$v['total_maintenance'];
                            $profit = $revenue - $maintenance;
                            
                            $ratio = $revenue > 0 ? ($maintenance / $revenue) * 100 : ($maintenance > 0 ? 100 : 0);
                            $ratio_color = $ratio > 40 ? '#f87171' : '#10b981';
                        ?>
                        <tr>
                            <td data-label="Vehicle">
                                <div class="vehicle-flex">
                                    <img src="../<?php echo !empty($v['image_url']) ? $v['image_url'] : 'public/images/cars/prado.jpg'; ?>" class="v-thumb">
                                    <div class="v-meta">
                                        <h5><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?></h5>
                                        <span><?php echo htmlspecialchars($v['plate_number']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Revenue">
                                <span class="amount" style="color: white;">ZMW <?php echo number_format($revenue, 0); ?></span>
                            </td>
                            <td data-label="Expenses">
                                <span class="amount text-red">ZMW <?php echo number_format($maintenance, 0); ?></span>
                            </td>
                            <td data-label="Profit">
                                <span class="profit-badge <?php echo $profit >= 0 ? 'bg-profit-pos' : 'bg-profit-neg'; ?>">
                                    <?php echo ($profit >= 0 ? '+' : '') . 'ZMW ' . number_format($profit, 0); ?>
                                </span>
                            </td>
                            <td data-label="Ratio">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight: 700; font-size: 0.9rem; min-width: 40px; color: <?php echo $ratio_color; ?>;"><?php echo round($ratio, 1); ?>%</span>
                                    <div style="flex: 1; max-width: 100px; height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                                        <div style="width: <?php echo min(100, $ratio); ?>%; height: 100%; background: <?php echo $ratio_color; ?>; border-radius: 10px;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; text-align: center; color: rgba(255,255,255,0.3); font-size: 0.85rem;">
                <i class="fas fa-info-circle"></i> Revenue figures reflect completed bookings only. Maintenance costs include all logged services.
            </div>

        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

