<?php
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Filter parameters
$module_filter = $_GET['module'] ?? '';
$user_filter = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query_parts = ["1=1"];
$params = [];

if ($module_filter) {
    $query_parts[] = "module = ?";
    $params[] = $module_filter;
}

if ($user_filter) {
    $query_parts[] = "user_name LIKE ?";
    $params[] = "%$user_filter%";
}

if ($search) {
    $query_parts[] = "(action LIKE ? OR details LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(" AND ", $query_parts);

// Count total for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE $where_clause");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch logs
$stmt = $pdo->prepare("SELECT * FROM audit_logs WHERE $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique modules for filter
$modules = $pdo->query("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Admin Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .audit-table-container {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px;
            overflow-x: auto;
        }
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }
        .module-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-fleet { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }
        .badge-finance { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-marketing { background: rgba(124, 58, 237, 0.1); color: #a78bfa; }
        .badge-maintenance { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-default { background: rgba(255, 255, 255, 0.1); color: #ccc; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
        }
        .page-link {
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
        }
        .page-link.active {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }
        .page-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>System Audit Trail</h1>
                    <p class="text-secondary">Security monitoring and administrative activity logs.</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" class="filter-bar">
                <input type="text" name="search" placeholder="Search action or details..." class="form-control" value="<?php echo h($search); ?>" style="flex: 1; min-width: 250px;">
                <select name="module" class="form-control" style="flex: 0 0 180px;">
                    <option value="">All Modules</option>
                    <?php foreach($modules as $m): ?>
                        <option value="<?php echo $m; ?>" <?php echo ($module_filter == $m) ? 'selected' : ''; ?>><?php echo ucfirst($m); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="user" placeholder="User name..." class="form-control" value="<?php echo h($user_filter); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                <a href="audit-logs.php" class="btn btn-outline" title="Reset Filters"><i class="fas fa-undo"></i></a>
            </form>

            <div class="audit-table-container">
                <table class="data-table admin-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): 
                            $badge_class = 'badge-default';
                            if ($log['module']) {
                                $module_low = strtolower($log['module']);
                                if ($module_low == 'fleet') $badge_class = 'badge-fleet';
                                elseif ($module_low == 'finance') $badge_class = 'badge-finance';
                                elseif ($module_low == 'marketing') $badge_class = 'badge-marketing';
                                elseif ($module_low == 'maintenance') $badge_class = 'badge-maintenance';
                            }
                        ?>
                        <tr>
                            <td data-label="Time" style="white-space: nowrap; font-size: 0.8rem; font-family: monospace;">
                                <?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?>
                            </td>
                            <td data-label="User">
                                <div style="font-weight: 700; color: white;"><?php echo h($log['user_name']); ?></div>
                                <small style="opacity: 0.5; text-transform: uppercase; font-size: 0.65rem;"><?php echo h($log['user_role']); ?></small>
                            </td>
                            <td data-label="Module">
                                <span class="module-badge <?php echo $badge_class; ?>">
                                    <?php echo h($log['module'] ?: 'System'); ?>
                                </span>
                            </td>
                            <td data-label="Action" style="font-weight: 600; color: white;">
                                <?php echo h($log['action']); ?>
                            </td>
                            <td data-label="Details" style="font-size: 0.85rem; max-width: 300px;">
                                <?php echo h($log['details']); ?>
                            </td>
                            <td data-label="IP" style="font-size: 0.75rem; font-family: monospace; opacity: 0.6;">
                                <?php echo h($log['ip_address']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 60px; opacity: 0.3;">
                                <i class="fas fa-history" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                                No audit records found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&module=<?php echo urlencode($module_filter); ?>&user=<?php echo urlencode($user_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo;</a>
                    <?php endif; ?>

                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-link active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&module=<?php echo urlencode($module_filter); ?>&user=<?php echo urlencode($user_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&module=<?php echo urlencode($module_filter); ?>&user=<?php echo urlencode($user_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">&raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include_once '../includes/mobile_nav.php'; ?>
</body>
</html>
