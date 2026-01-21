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

$message = '';
$error = '';
$backup_file = '';

// Function untuk membuat backup database
function createBackup($conn, $database_name) {
    $backup_dir = 'backups/';
    
    // Buat folder backup jika belum ada
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $filename = 'backup_' . $database_name . '_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Get all tables
    $tables = array();
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $sql_content = '';
    $sql_content .= "-- Database Backup\n";
    $sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Database: " . $database_name . "\n\n";
    $sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_content .= "SET time_zone = \"+00:00\";\n\n";
    
    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $create_table = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $create_row = mysqli_fetch_row($create_table);
        
        $sql_content .= "\n-- Table structure for table `$table`\n";
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_content .= $create_row[1] . ";\n\n";
        
        // Get table data
        $data_query = mysqli_query($conn, "SELECT * FROM `$table`");
        $num_rows = mysqli_num_rows($data_query);
        
        if ($num_rows > 0) {
            $sql_content .= "-- Dumping data for table `$table`\n";
            
            while ($row = mysqli_fetch_assoc($data_query)) {
                $sql_content .= "INSERT INTO `$table` (";
                $columns = array_keys($row);
                $sql_content .= "`" . implode("`, `", $columns) . "`";
                $sql_content .= ") VALUES (";
                
                $values = array();
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                    }
                }
                
                $sql_content .= implode(", ", $values);
                $sql_content .= ");\n";
            }
            $sql_content .= "\n";
        }
    }
    
    // Save to file
    if (file_put_contents($filepath, $sql_content)) {
        return array('success' => true, 'filename' => $filename, 'filepath' => $filepath, 'size' => filesize($filepath));
    } else {
        return array('success' => false, 'error' => 'Gagal menulis file backup');
    }
}

// Function untuk mendapatkan daftar backup
function getBackupList($backup_dir = 'backups/') {
    $backups = array();
    
    if (is_dir($backup_dir)) {
        $files = scandir($backup_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $filepath = $backup_dir . $file;
                $backups[] = array(
                    'filename' => $file,
                    'filepath' => $filepath,
                    'size' => filesize($filepath),
                    'date' => filemtime($filepath)
                );
            }
        }
        
        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return $b['date'] - $a['date'];
        });
    }
    
    return $backups;
}

// Function untuk format ukuran file
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

// Proses backup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            // Get database name from connection
            $db_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db_name"))['db_name'];
            
            $backup_result = createBackup($conn, $db_name);
            
            if ($backup_result['success']) {
                $backup_file = $backup_result['filename'];
                $message = "Backup berhasil dibuat! File: " . $backup_file . " (" . formatBytes($backup_result['size']) . ")";
                
                // Log aktivitas backup
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $detail = "Database backup created: " . $backup_file;
                
                $log_query = "INSERT INTO activity_logs (user, action, target, detail, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, NOW())";
                $log_stmt = mysqli_prepare($conn, $log_query);
                mysqli_stmt_bind_param($log_stmt, "sssss", $username, $log_action, $db_name, $detail, $ip_address);
                $log_action = 'BACKUP_CREATE';
                mysqli_stmt_execute($log_stmt);
                
            } else {
                $error = $backup_result['error'];
            }
            break;
            
        case 'delete_backup':
            $filename = $_POST['filename'] ?? '';
            $filepath = 'backups/' . $filename;
            
            if (file_exists($filepath) && unlink($filepath)) {
                $message = "Backup $filename berhasil dihapus!";
                
                // Log aktivitas
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $detail = "Backup file deleted: " . $filename;
                
                $log_query = "INSERT INTO activity_logs (user, action, target, detail, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, NOW())";
                $log_stmt = mysqli_prepare($conn, $log_query);
                mysqli_stmt_bind_param($log_stmt, "sssss", $username, $log_action, $filename, $detail, $ip_address);
                $log_action = 'BACKUP_DELETE';
                mysqli_stmt_execute($log_stmt);
                
            } else {
                $error = "Gagal menghapus backup!";
            }
            break;
    }
}

// Handle download
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $filename = $_GET['download'];
    $filepath = 'backups/' . $filename;
    
    if (file_exists($filepath)) {
        // Log aktivitas download
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $detail = "Backup file downloaded: " . $filename;
        
        $log_query = "INSERT INTO activity_logs (user, action, target, detail, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, NOW())";
        $log_stmt = mysqli_prepare($conn, $log_query);
        mysqli_stmt_bind_param($log_stmt, "sssss", $username, $log_action, $filename, $detail, $ip_address);
        $log_action = 'BACKUP_DOWNLOAD';
        mysqli_stmt_execute($log_stmt);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

$backup_list = getBackupList();

// Get database info
$db_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db_name"))['db_name'];
$db_size_query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = '$db_name'";
$db_size_result = mysqli_query($conn, $db_size_query);
$db_size = mysqli_fetch_assoc($db_size_result)['DB Size in MB'] ?? '0';

$table_count_query = "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$db_name'";
$table_count = mysqli_fetch_assoc(mysqli_query($conn, $table_count_query))['table_count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - SiPhantom</title>
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
        
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3c72;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .backup-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }
        
        .backup-item:hover {
            background-color: #f8f9fa;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-filename {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .btn-backup {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn-backup:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <span class="navbar-brand">💾 Database Backup</span>
            <div class="navbar-nav ms-auto">
                <a href="superadmin.php" class="nav-link">⬅️ Kembali</a>
                <a href="logout.php" class="nav-link">🚪 Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong>✅ Berhasil!</strong> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>❌ Error!</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Database Info -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">🗄️</div>
                    <div class="stats-number"><?php echo $db_name; ?></div>
                    <div class="stats-label">Database</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">📊</div>
                    <div class="stats-number"><?php echo $db_size; ?> MB</div>
                    <div class="stats-label">Ukuran Database</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">📋</div>
                    <div class="stats-number"><?php echo $table_count; ?></div>
                    <div class="stats-label">Total Tabel</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">💾</div>
                    <div class="stats-number"><?php echo count($backup_list); ?></div>
                    <div class="stats-label">File Backup</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Create Backup -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🔄 Buat Backup Baru</h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted mb-4">
                            Backup akan menyimpan seluruh data database termasuk struktur tabel dan data.
                        </p>
                        
                        <form method="POST" id="backupForm">
                            <input type="hidden" name="action" value="create_backup">
                            <button type="submit" class="btn btn-backup w-100" id="backupBtn">
                                <span class="btn-text">💾 Buat Backup Sekarang</span>
                                <div class="loading spinner-border spinner-border-sm ms-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                        </form>
                        
                        <small class="text-muted mt-3 d-block">
                            ⏱️ Proses backup membutuhkan waktu beberapa detik
                        </small>
                    </div>
                </div>

                <!-- Backup Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">ℹ️ Informasi Backup</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">✅ <strong>Format:</strong> SQL File</li>
                            <li class="mb-2">📁 <strong>Lokasi:</strong> /backups/</li>
                            <li class="mb-2">🔒 <strong>Keamanan:</strong> Server Only</li>
                            <li class="mb-2">⬬ <strong>Download:</strong> Tersedia</li>
                            <li class="mb-2">🗑️ <strong>Auto Delete:</strong> Manual</li>
                        </ul>
                        
                        <div class="alert alert-warning mt-3">
                            <small>
                                <strong>⚠️ Peringatan:</strong> 
                                Backup file berisi data sensitif. Pastikan untuk menyimpan di tempat yang aman.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup List -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">📁 Daftar File Backup</h5>
                            <small>Total: <?php echo count($backup_list); ?> file</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($backup_list)): ?>
                            <div class="text-center py-5">
                                <h6>📂 Belum Ada Backup</h6>
                                <p class="text-muted">Belum ada file backup yang tersedia. Buat backup pertama Anda!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($backup_list as $backup): ?>
                                <div class="backup-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="mb-2">
                                                <span class="backup-filename">
                                                    <?php echo htmlspecialchars($backup['filename']); ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                📅 <?php echo date('d/m/Y H:i:s', $backup['date']); ?> • 
                                                📦 <?php echo formatBytes($backup['size']); ?> • 
                                                ⏰ <?php echo date('H:i:s', $backup['date'] - time() + time()); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <a href="?download=<?php echo urlencode($backup['filename']); ?>" 
                                               class="btn btn-success btn-sm">
                                                ⬇️ Download
                                            </a>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Yakin hapus backup ini?')">
                                                <input type="hidden" name="action" value="delete_backup">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    🗑️ Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('backupForm').addEventListener('submit', function() {
            const btn = document.getElementById('backupBtn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            btn.disabled = true;
            btnText.textContent = '⏳ Membuat Backup...';
            loading.classList.add('show');
        });
    </script>
</body>
</html>