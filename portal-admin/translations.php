<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle Sync Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    // Run the sync script and capture output
    $output = [];
    $return_var = 0;
    exec("php " . escapeshellarg(realpath(__DIR__ . '/../sync_translations.php')) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        $success = "Translations synced successfully! " . implode(" ", $output);
    } else {
        $error = "Translation sync failed: " . implode(" ", $output);
    }
}

// Load current strings for display
$base_lang = include '../includes/lang/en.php';
$languages = ['bem' => 'Bemba', 'nya' => 'Nyanja', 'ton' => 'Tonga'];
$lang_data = [];

foreach ($languages as $code => $name) {
    if (file_exists("../includes/lang/{$code}.php")) {
        $lang_data[$code] = include "../includes/lang/{$code}.php";
    } else {
        $lang_data[$code] = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Languages & Translation | Car Hire Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .translation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .lang-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .lang-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 15px;
        }
        .lang-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lang-title i { color: #3b82f6; }
        .sync-badge {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .string-list {
            max-height: 300px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-right: 5px;
        }
        .string-item {
            background: rgba(255,255,255,0.02);
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 0.85rem;
        }
        .string-key {
            color: rgba(255,255,255,0.4);
            font-family: monospace;
            font-size: 0.75rem;
            display: block;
            margin-bottom: 4px;
        }
        .string-val { color: rgba(255,255,255,0.8); }
        .btn-sync {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        .btn-sync:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        @media (max-width: 768px) {
            .main-content { padding: 12px !important; }
            .dashboard-header { margin-bottom: 20px !important; }
            .dashboard-header h1 { font-size: 1.3rem !important; }
            
            .translation-grid { 
                grid-template-columns: 1fr !important; 
                gap: 15px !important; 
                margin-top: 20px !important;
            }
            .lang-card { 
                padding: 15px !important; 
                background: rgba(15, 15, 20, 0.8) !important;
                backdrop-filter: blur(10px);
                border-radius: 12px !important;
            }
            .lang-title { font-size: 1rem !important; }
            .sync-badge { font-size: 0.65rem !important; padding: 2px 8px !important; }
            
            .string-item { padding: 8px 12px !important; }
            .string-key { font-size: 0.68rem !important; margin-bottom: 2px !important; }
            .string-val { font-size: 0.82rem !important; line-height: 1.3 !important; }
            
            .header-actions { width: 100% !important; margin-top: 15px !important; }
            .header-actions .btn { font-size: 0.85rem !important; padding: 10px !important; }
            
            .string-list { max-height: 250px !important; }
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
                    <h1>Languages & Translation</h1>
                    <p class="text-secondary">Manage local Zambian translations using the ZED API.</p>
                </div>
                <div class="header-actions">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <form method="POST" style="width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="sync">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-sync-alt"></i> Run Sync
                        </button>
                    </form>
                </div>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="translation-grid">
                <!-- English (Base) -->
                <div class="lang-card" style="border-color: rgba(59, 130, 246, 0.3);">
                    <div class="lang-header">
                        <div class="lang-title"><i class="fas fa-flag-usa"></i> English (Source)</div>
                        <span class="sync-badge" style="background:rgba(59,130,246,0.1); color:#3b82f6; border-color:rgba(59,130,246,0.2);">Base</span>
                    </div>
                    <div class="string-list">
                        <?php foreach($base_lang as $key => $val): ?>
                            <div class="string-item">
                                <span class="string-key"><?php echo $key; ?></span>
                                <span class="string-val"><?php echo htmlspecialchars($val); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php foreach($languages as $code => $name): ?>
                    <div class="lang-card">
                        <div class="lang-header">
                            <div class="lang-title"><i class="fas fa-language"></i> <?php echo $name; ?></div>
                            <?php 
                            $missing = count(array_diff_key($base_lang, $lang_data[$code]));
                            if ($missing > 0): 
                            ?>
                                <span class="sync-badge" style="background:rgba(245,158,11,0.1); color:#f59e0b; border-color:rgba(245,158,11,0.2);">
                                    <?php echo $missing; ?> missing
                                </span>
                            <?php else: ?>
                                <span class="sync-badge">Up to date</span>
                            <?php endif; ?>
                        </div>
                        <div class="string-list">
                            <?php foreach($base_lang as $key => $val): ?>
                                <div class="string-item">
                                    <span class="string-key"><?php echo $key; ?></span>
                                    <span class="string-val">
                                        <?php echo isset($lang_data[$code][$key]) ? htmlspecialchars($lang_data[$code][$key]) : '<em style="color:#ef4444;">Not translated</em>'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

