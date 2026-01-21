<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];

// Cek apakah user adalah superadmin
if (!is_superadmin($username)) {
    header("Location: dashboard.php");
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filter
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($filter_user)) {
    $where_conditions[] = "user LIKE ?";
    $params[] = "%$filter_user%";
}

if (!empty($filter_action)) {
    $where_conditions[] = "action = ?";
    $params[] = $filter_action;
}

if (!empty($filter_date)) {
    $where_conditions[] = "DATE(timestamp) = ?";
    $params[] = $filter_date;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM activity_logs $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total_result = mysqli_stmt_get_result($count_stmt);
$total_logs = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_logs / $limit);

// Get logs
$logs_query = "SELECT * FROM activity_logs $where_clause ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$logs_stmt = mysqli_prepare($conn, $logs_query);

// Prepare parameters for main query
$all_params = $params;
$all_params[] = $limit;
$all_params[] = $offset;
$types = str_repeat('s', count($params)) . 'ii';

mysqli_stmt_bind_param($logs_stmt, $types, ...$all_params);
mysqli_stmt_execute($logs_stmt);
$logs_result = mysqli_stmt_get_result($logs_stmt);
$logs = mysqli_fetch_all($logs_result, MYSQLI_ASSOC);

// Get unique actions for filter
$actions_query = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
$actions_result = mysqli_query($conn, $actions_query);
$available_actions = mysqli_fetch_all($actions_result, MYSQLI_ASSOC);

// Function to get action icon and color
function getActionIcon($action) {
    $icons = [
        'LOGIN' => ['icon' => '🔑', 'color' => 'success'],
        'LOGOUT' => ['icon' => '🚪', 'color' => 'secondary'],
        'CREATE_DEVICE' => ['icon' => '📱', 'color' => 'primary'],
        'DELETE_DEVICE' => ['icon' => '🗑️', 'color' => 'danger'],
        'RESTORE_DEVICE' => ['icon' => '🔄', 'color' => 'success'],
        'TOGGLE_DEVICE' => ['icon' => '⏯️', 'color' => 'warning'],
        'APPROVE_DEVICE' => ['icon' => '✅', 'color' => 'success'],
        'REJECT_DEVICE' => ['icon' => '❌', 'color' => 'danger'],
        'UPDATE_DEVICE' => ['icon' => '✏️', 'color' => 'info'],
        'RELAY_ON' => ['icon' => '🔛', 'color' => 'success'],
        'RELAY_OFF' => ['icon' => '🔴', 'color' => 'danger'],
        'DATA_INSERT' => ['icon' => '📊', 'color' => 'info'],
        'BACKUP' => ['icon' => '💾', 'color' => 'warning'],
        'SETTINGS' => ['icon' => '⚙️', 'color' => 'secondary'],
        'USER_CREATE' => ['icon' => '👤', 'color' => 'primary'],
        'USER_UPDATE' => ['icon' => '👤', 'color' => 'info'],
        'PASSWORD_CHANGE' => ['icon' => '🔒', 'color' => 'warning']
    ];
    
    return $icons[$action] ?? ['icon' => '📝', 'color' => 'secondary'];
}

// Function to format relative time
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit lalu';
    if ($time < 86400) return floor($time/3600) . ' jam lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari lalu';
    
    return date('d/m/Y H:i', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - SiPhantom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2FB7D6;
            --secondary: #3CCF91;
            --dark: #1F3A4D;
            --light: #F4FAFC;
            --danger: #E74C3C;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #35a4bd, #30d5c8);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .log-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }
        
        .log-item:hover {
            background-color: #f8f9fa;
        }
        
        .log-item:last-child {
            border-bottom: none;
        }
        
        .action-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .device-code {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            padding: 3px 8px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }
        
        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .stats-row {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 25px;
        }
        
        .pagination {
            justify-content: center;
        }
        
        .pagination .page-link {
            border-radius: 10px;
            margin: 0 2px;
            border: none;
            color: #1e3c72;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border: none;
        }
        
        @media (max-width: 768px) {
            .log-item {
                padding: 10px;
            }
            
            .d-flex {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
            
            .ms-auto {
                margin-left: 0 !important;
                margin-top: 10px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <span class="navbar-brand">📊 Activity Logs</span>
            <div class="navbar-nav ms-auto">
                <a href="superadmin.php" class="nav-link">⬅️ Kembali</a>
                <a href="logout.php" class="nav-link">🚪 Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Stats -->
        <div class="stats-row">
            <div class="row text-center">
                <div class="col-md-3">
                    <h4 class="text-primary"><?php echo number_format($total_logs); ?></h4>
                    <small class="text-muted">Total Aktivitas</small>
                </div>
                <div class="col-md-3">
                    <h4 class="text-success">
                        <?php 
                        $today_query = "SELECT COUNT(*) as today FROM activity_logs WHERE DATE(timestamp) = CURDATE()";
                        $today_result = mysqli_query($conn, $today_query);
                        echo mysqli_fetch_assoc($today_result)['today'];
                        ?>
                    </h4>
                    <small class="text-muted">Hari Ini</small>
                </div>
                <div class="col-md-3">
                    <h4 class="text-warning">
                        <?php 
                        $week_query = "SELECT COUNT(*) as week FROM activity_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        $week_result = mysqli_query($conn, $week_query);
                        echo mysqli_fetch_assoc($week_result)['week'];
                        ?>
                    </h4>
                    <small class="text-muted">7 Hari Terakhir</small>
                </div>
                <div class="col-md-3">
                    <h4 class="text-info">
                        <?php 
                        $users_query = "SELECT COUNT(DISTINCT user) as users FROM activity_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        $users_result = mysqli_query($conn, $users_query);
                        echo mysqli_fetch_assoc($users_result)['users'];
                        ?>
                    </h4>
                    <small class="text-muted">User Aktif (7 hari)</small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">🔍 Cari User</label>
                    <input type="text" name="user" class="form-control" placeholder="Username..." 
                           value="<?php echo htmlspecialchars($filter_user); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">⚡ Jenis Aktivitas</label>
                    <select name="action" class="form-select">
                        <option value="">Semua Aktivitas</option>
                        <?php foreach ($available_actions as $action): ?>
                            <option value="<?php echo $action['action']; ?>" 
                                    <?php echo ($filter_action === $action['action']) ? 'selected' : ''; ?>>
                                <?php echo $action['action']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">📅 Tanggal</label>
                    <input type="date" name="date" class="form-control" 
                           value="<?php echo $filter_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        <a href="logs.php" class="btn btn-secondary">🔄 Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Activity Logs -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📋 Activity Logs</h5>
                    <small>Total: <?php echo number_format($total_logs); ?> aktivitas</small>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                    <div class="text-center py-5">
                        <h6>📋 Tidak Ada Aktivitas</h6>
                        <p class="text-muted">Tidak ditemukan aktivitas dengan filter yang dipilih.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php $action_info = getActionIcon($log['action']); ?>
                        <div class="log-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="action-badge bg-<?php echo $action_info['color']; ?>">
                                            <?php echo $action_info['icon']; ?>
                                            <?php echo $log['action']; ?>
                                        </span>
                                        <span class="user-badge ms-2">
                                            👤 <?php echo htmlspecialchars($log['user']); ?>
                                        </span>
                                        <?php if (!empty($log['target'])): ?>
                                            <span class="device-code ms-2">
                                                🎯 <?php echo htmlspecialchars($log['target']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($log['detail'])): ?>
                                        <p class="mb-1 text-dark">
                                            <?php echo htmlspecialchars($log['detail']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted">
                                        🌐 IP: <?php echo $log['ip_address'] ?? 'Unknown'; ?>
                                    </small>
                                </div>
                                <div class="ms-3 text-end">
                                    <div class="text-muted">
                                        <small><?php echo timeAgo($log['timestamp']); ?></small>
                                    </div>
                                    <div class="text-muted">
                                        <small><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <!-- Previous -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&user=<?php echo urlencode($filter_user); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo $filter_date; ?>">
                            ⬅️ Prev
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&user=<?php echo urlencode($filter_user); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo $filter_date; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Next -->
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&user=<?php echo urlencode($filter_user); ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo $filter_date; ?>">
                            Next ➡️
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>