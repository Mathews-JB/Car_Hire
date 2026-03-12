<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';
$backup_dir = '../backups/';

// Handle Delete
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    if (file_exists($backup_dir . $file)) {
        unlink($backup_dir . $file);
        $success = "Backup '$file' deleted.";
        log_action($pdo, "Deleted backup", "Filename: $file", "System");
    }
}

// Handle Trigger Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Absolute pathing to ensure realpath doesn't return false for non-existent-but-creatable dirs
    $backup_root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups';
    if (!file_exists($backup_root)) {
        mkdir($backup_root, 0777, true);
    }
    
    $filepath = $backup_root . DIRECTORY_SEPARATOR . $filename;
    
    // Get DB config from getenv (already loaded in db.php)
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_name = getenv('DB_NAME') ?: 'car_hire';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    
    // Commands for Windows (XAMPP)
    $mysqldump_path = 'C:\xampp\mysql\bin\mysqldump.exe';
    
    if ($db_pass) {
        $command = "\"$mysqldump_path\" --host=\"$db_host\" --user=\"$db_user\" --password=\"$db_pass\" \"$db_name\" > \"$filepath\" 2>&1";
    } else {
        $command = "\"$mysqldump_path\" --host=\"$db_host\" --user=\"$db_user\" \"$db_name\" > \"$filepath\" 2>&1";
    }

    $output = [];
    $ret_val = 0;
    exec($command, $output, $ret_val);

    if ($ret_val === 0 && file_exists($filepath)) {
        $success = "Backup created successfully: $filename";
        log_action($pdo, "Created manual backup", "Filename: $filename", "System");
    } else {
        $error = "Backup failed (Code $ret_val). " . implode(" ", $output);
        // If file was created but returned error check content
        if(file_exists($filepath) && filesize($filepath) < 500) {
            $error .= " - Log: " . file_get_contents($filepath);
            unlink($filepath); // Clean up failed file
        }
    }
}

// Fetch existing backups
$backups = [];
if (is_dir($backup_dir)) {
    foreach (glob($backup_dir . "*.sql") as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'time' => filemtime($file)
        ];
    }
}
usort($backups, function($a, $b) { return $b['time'] - $a['time']; });

// DB Stats
$tables = $pdo->query("SHOW TABLE STATUS")->fetchAll();
$total_size = 0;
foreach($tables as $t) {
    $total_size += $t['Data_length'] + $t['Index_length'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backups | Admin Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- Theme System -->
    <link rel="stylesheet" href="../public/css/theme.css?v=4.0">
    <script src="../public/js/theme-switcher.js?v=4.0"></script>
    <style>
        .backup-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(12px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .stat-val { font-size: 1.5rem; font-weight: 800; color: white; display: block; margin-bottom: 5px; }
        .stat-label { font-size: 0.75rem; color: rgba(255, 255, 255, 0.5); text-transform: uppercase; letter-spacing: 1px; }

        .backup-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .backup-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            transition: 0.2s;
        }
        .backup-item:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .backup-icon {
            width: 40px;
            height: 40px;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        .backup-info { flex: 1; }
        .backup-name { font-weight: 600; color: white; margin-bottom: 2px; }
        .backup-meta { font-size: 0.75rem; color: rgba(255, 255, 255, 0.4); }
        .backup-actions { display: flex; gap: 10px; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body>
    <?php include_once '../includes/mobile_header.php'; ?>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Database Safety Hub</h1>
                    <p class="text-secondary">Manage backups and ensure data redundancy.</p>
                </div>
                <div class="header-actions">
                    <?php include_once '../includes/theme_switcher.php'; ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button type="submit" name="create_backup" class="btn btn-primary">
                            <i class="fas fa-database"></i> Trigger New Backup
                        </button>
                    </form>
                </div>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo h($success); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-val"><?php echo count($tables); ?></span>
                    <span class="stat-label">Total Tables</span>
                </div>
                <div class="stat-item">
                    <span class="stat-val"><?php echo round($total_size / 1024, 1); ?> KB</span>
                    <span class="stat-label">DB Size</span>
                </div>
                <div class="stat-item">
                    <span class="stat-val"><?php echo count($backups); ?></span>
                    <span class="stat-label">Saved Backups</span>
                </div>
            </div>

            <div class="backup-card">
                <h3 style="margin-bottom: 20px; color: white;"><i class="fas fa-history" style="color: #60a5fa; margin-right: 10px;"></i> Recent Backups</h3>
                
                <div class="backup-list">
                    <?php if (empty($backups)): ?>
                        <div style="text-align: center; padding: 40px; opacity: 0.3;">
                            <i class="fas fa-folder-open" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                            No backup files found.
                        </div>
                    <?php else: ?>
                        <?php foreach($backups as $b): ?>
                        <div class="backup-item">
                            <div class="backup-icon">
                                <i class="fas fa-file-code"></i>
                            </div>
                            <div class="backup-info">
                                <div class="backup-name"><?php echo h($b['name']); ?></div>
                                <div class="backup-meta">
                                    <?php echo date('d M Y, H:i', $b['time']); ?> &bull; <?php echo round($b['size'] / 1024, 2); ?> KB
                                </div>
                            </div>
                            <div class="backup-actions">
                                <a href="../backups/<?php echo $b['name']; ?>" download class="btn btn-outline btn-sm" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="?delete=<?php echo urlencode($b['name']); ?>" class="btn btn-outline btn-sm text-danger" title="Delete" onclick="return confirm('Delete this backup permanently?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 30px; padding: 15px; background: rgba(245, 158, 11, 0.05); border: 1px dashed rgba(245, 158, 11, 0.2); border-radius: 12px; font-size: 0.8rem; color: rgba(255, 255, 255, 0.5);">
                    <i class="fas fa-shield-alt" style="color: #fbbf24; margin-right: 8px;"></i>
                    <strong>Pro-Tip:</strong> Always download backups and store them off-server for maximum security against server failure.
                </div>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>

