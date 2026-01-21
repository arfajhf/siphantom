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

// Get statistik sistem
$stats = get_system_stats();
$all_users = get_all_users();
$all_devices = get_all_devices(true); // Include deleted devices
$pending_approvals = get_pending_approvals(); // Get pending approvals

// Proses aksi superadmin
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'approve_device':
            $kode = $_POST['kode'] ?? '';
            if (approve_device($kode, $username)) {
                $message = "Device $kode berhasil disetujui!";
            } else {
                $error = "Gagal menyetujui device!";
            }
            break;

        case 'reject_device':
            $kode = $_POST['kode'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if (reject_device($kode, $username, $reason)) {
                $message = "Device $kode berhasil ditolak!";
            } else {
                $error = "Gagal menolak device!";
            }
            break;

        case 'delete_device':
            $kode = $_POST['kode'] ?? '';
            if (hapus_device($kode, $username)) {
                $message = "Device $kode berhasil dihapus!";
            } else {
                $error = "Gagal menghapus device!";
            }
            break;

        case 'restore_device':
            $kode = $_POST['kode'] ?? '';
            if (restore_device($kode, $username)) {
                $message = "Device $kode berhasil direstore!";
            } else {
                $error = "Gagal restore device!";
            }
            break;

        case 'toggle_device':
            $kode = $_POST['kode'] ?? '';
            $status = $_POST['status'] ?? '';
            $new_status = ($status == '1') ? 0 : 1;

            $query = "UPDATE devices SET aktif = $new_status WHERE kode = '$kode'";
            if (mysqli_query($conn, $query)) {
                $status_text = $new_status ? 'diaktifkan' : 'dinonaktifkan';
                $message = "Device $kode berhasil $status_text!";
                log_activity($username, 'TOGGLE_DEVICE', $kode, "Device $status_text");
            } else {
                $error = "Gagal mengubah status device!";
            }
            break;
    }

    // Refresh data setelah aksi
    $stats = get_system_stats();
    $all_devices = get_all_devices(true);
    $pending_approvals = get_pending_approvals();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Panel - SiPhantom</title>
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
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .superadmin-badge {
            background: linear-gradient(45deg, #ffd700, #ffed4a);
            color: #333;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3c72;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .device-row {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }

        .device-row:hover {
            background-color: #f8f9fa;
        }

        .device-row:last-child {
            border-bottom: none;
        }

        .device-code {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .device-deleted {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.8rem;
            margin: 2px;
        }

        .user-role {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .role-superadmin {
            background: #ffd700;
            color: #333;
        }

        .role-admin {
            background: #4CAF50;
            color: white;
        }

        .role-user {
            background: #2196F3;
            color: white;
        }

        /* Animasi untuk data yang ter-update */
        .stat-updated {
            animation: pulse 0.5s ease-in-out;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Status pompa */
        .pump-status {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 10px;
        }

        .pump-on {
            background: #4CAF50;
            color: white;
        }

        .pump-off {
            background: #f44336;
            color: white;
        }

        /* Device online status */
        .device-online {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.6rem;
            font-weight: bold;
            margin-left: 5px;
        }

        .status-online {
            background: #4CAF50;
            color: white;
        }

        .status-offline {
            background: #f44336;
            color: white;
        }

        /* Auto-update indicator */
        .update-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }

        .update-indicator.show {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>
    <!-- Auto-update indicator -->
    <div id="update-indicator" class="update-indicator">
        📡 Auto-updating...
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <span class="navbar-brand">
                👑 SIMACMUR SUPERADMIN
                <span class="superadmin-badge">SUPER ADMIN</span>
            </span>
            <div class="navbar-nav ms-auto">
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

        <!-- Statistik Sistem -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number" id="stat-users"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📱</div>
                <div class="stat-number" id="stat-devices"><?php echo $stats['total_devices']; ?></div>
                <div class="stat-label">Device Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🗑️</div>
                <div class="stat-number" id="stat-deleted"><?php echo $stats['deleted_devices']; ?></div>
                <div class="stat-label">Device Dihapus</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number" id="stat-data"><?php echo $stats['today_data']; ?></div>
                <div class="stat-label">Data Hari Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚰</div>
                <div class="stat-number" id="stat-pumps"><?php echo $stats['active_relays']; ?></div>
                <div class="stat-label">Pompa Aktif</div>
            </div>
        </div>

        <!-- Real-time Pump Status -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🚰 Status Pompa Real-time</h5>
                    </div>
                    <div class="card-body" id="pump-status-container">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div>Loading pump status...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Approvals -->
            <?php if (!empty($pending_approvals)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">⏳ Device Menunggu Approval (<span id="pending-count"><?php echo count($pending_approvals); ?></span>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($pending_approvals as $approval): ?>
                                <div class="device-row">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($approval['device_nama']); ?>
                                                <span class="badge bg-warning ms-2">PENDING</span>
                                            </h6>
                                            <div class="mb-2">
                                                <span class="device-code">
                                                    <?php echo $approval['device_kode']; ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                👤 Diminta oleh: <?php echo $approval['requester_name'] ?? $approval['requester']; ?> •
                                                📅 <?php echo date('d/m/Y H:i', strtotime($approval['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve_device">
                                                <input type="hidden" name="kode" value="<?php echo $approval['device_kode']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm"
                                                    onclick="return confirm('Setujui device ini?')">
                                                    ✅ Setujui
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $approval['id']; ?>">
                                                ❌ Tolak
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Reject -->
                                <div class="modal fade" id="rejectModal<?php echo $approval['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">❌ Tolak Device</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <p>Tolak device: <strong><?php echo $approval['device_nama']; ?></strong></p>
                                                    <div class="mb-3">
                                                        <label class="form-label">Alasan penolakan:</label>
                                                        <textarea name="reason" class="form-control" rows="3" required
                                                            placeholder="Berikan alasan mengapa device ditolak..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <input type="hidden" name="action" value="reject_device">
                                                    <input type="hidden" name="kode" value="<?php echo $approval['device_kode']; ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">❌ Tolak Device</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Kelola Semua Device -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📱 Kelola Semua Device (<?php echo count($all_devices); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($all_devices)): ?>
                            <div class="text-center py-4">
                                <h6>📱 Belum Ada Device</h6>
                                <p class="text-muted">Sistem belum memiliki device terdaftar.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($all_devices as $device): ?>
                                <div class="device-row" data-device-code="<?php echo $device['kode']; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($device['nama']); ?>
                                                <?php if ($device['deleted_at']): ?>
                                                    <span class="badge bg-danger ms-2">DIHAPUS</span>
                                                <?php else: ?>
                                                    <span class="device-online status-offline" id="online-<?php echo $device['kode']; ?>">
                                                        OFFLINE
                                                    </span>
                                                <?php endif; ?>
                                            </h6>
                                            <div class="mb-2">
                                                <span class="device-code <?php echo $device['deleted_at'] ? 'device-deleted' : ''; ?>">
                                                    <?php echo $device['kode']; ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                👤 <?php echo $device['pemilik_nama'] ?? $device['pemilik']; ?> •
                                                📅 <?php echo date('d/m/Y', strtotime($device['dibuat'])); ?>
                                                <?php if ($device['deleted_at']): ?>
                                                    • 🗑️ Dihapus: <?php echo date('d/m/Y H:i', strtotime($device['deleted_at'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if ($device['deleted_at']): ?>
                                                <!-- Device Dihapus - Tombol Restore -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="restore_device">
                                                    <input type="hidden" name="kode" value="<?php echo $device['kode']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Restore device ini?')">
                                                        🔄 Restore
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Device Aktif - Tombol Aksi -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_device">
                                                    <input type="hidden" name="kode" value="<?php echo $device['kode']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $device['aktif']; ?>">
                                                    <button type="submit" class="btn btn-<?php echo $device['aktif'] ? 'warning' : 'success'; ?> btn-sm">
                                                        <?php echo $device['aktif'] ? '⏸️ Nonaktif' : '▶️ Aktifkan'; ?>
                                                    </button>
                                                </form>

                                                <a href="monitor.php?device=<?php echo $device['kode']; ?>"
                                                    class="btn btn-info btn-sm">📊 Monitor</a>

                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_device">
                                                    <input type="hidden" name="kode" value="<?php echo $device['kode']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Yakin hapus device ini? Data tidak akan hilang tapi device tidak bisa digunakan.')">
                                                        🗑️ Hapus
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kelola Users -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">👥 Semua Users (<?php echo count($all_users); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($all_users as $user): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                <div>
                                    <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong><br>
                                    <small class="text-muted">@<?php echo $user['username']; ?></small><br>
                                    <span class="user-role role-<?php echo $user['role']; ?>">
                                        <?php echo strtoupper($user['role']); ?>
                                    </span>
                                </div>
                                <div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">⚡ Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="logs.php" class="btn btn-primary w-100 mb-2">📊 View Activity Logs</a>
                        <a href="backup.php" class="btn btn-warning w-100 mb-2">💾 Backup Database</a>
                        <a href="settings.php" class="btn btn-secondary w-100">⚙️ System Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables untuk tracking perubahan
        let lastStats = {};
        let lastPumps = {};
        let lastDevicesStatus = {};

        // Fungsi untuk menampilkan indikator update
        function showUpdateIndicator() {
            const indicator = document.getElementById('update-indicator');
            indicator.classList.add('show');
            setTimeout(() => {
                indicator.classList.remove('show');
            }, 1500);
        }

        // Fungsi untuk animasi update
        function animateUpdate(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.add('stat-updated');
                setTimeout(() => {
                    element.classList.remove('stat-updated');
                }, 500);
            }
        }

        // Update statistik sistem
        function updateStats(newStats) {
            const statsToUpdate = [{
                    id: 'stat-users',
                    key: 'total_users'
                },
                {
                    id: 'stat-devices',
                    key: 'total_devices'
                },
                {
                    id: 'stat-deleted',
                    key: 'deleted_devices'
                },
                {
                    id: 'stat-data',
                    key: 'today_data'
                },
                {
                    id: 'stat-pumps',
                    key: 'active_relays'
                }
            ];

            statsToUpdate.forEach(stat => {
                const element = document.getElementById(stat.id);
                if (element && newStats[stat.key] !== lastStats[stat.key]) {
                    element.textContent = newStats[stat.key];
                    animateUpdate(stat.id);
                }
            });

            // Update pending count
            const pendingElement = document.getElementById('pending-count');
            if (pendingElement && newStats.pending_count !== lastStats.pending_count) {
                pendingElement.textContent = newStats.pending_count;
                animateUpdate('pending-count');
            }

            lastStats = {
                ...newStats
            };
        }

        // Update status pompa
        function updatePumpStatus(pumps) {
            const container = document.getElementById('pump-status-container');

            if (pumps.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-3 text-muted">
                        <h6>🚰 Tidak Ada Device Aktif</h6>
                        <p>Belum ada device yang aktif untuk monitoring pompa.</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="row">';

            pumps.forEach(pump => {
                const statusClass = pump.pump_status ? 'pump-on' : 'pump-off';
                const statusText = pump.pump_status ? '🟢 HIDUP' : '🔴 MATI';

                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">${pump.device_nama}</h6>
                                <div class="device-code mb-2">${pump.device_kode}</div>
                                <div class="pump-status ${statusClass}" id="pump-${pump.device_kode}">
                                    ${statusText}
                                </div>
                                <small class="text-muted d-block mt-2">
                                    👤 ${pump.pemilik_nama}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;

            // Check untuk perubahan status pompa
            pumps.forEach(pump => {
                if (lastPumps[pump.device_kode] !== undefined &&
                    lastPumps[pump.device_kode] !== pump.pump_status) {
                    animateUpdate(`pump-${pump.device_kode}`);
                }
                lastPumps[pump.device_kode] = pump.pump_status;
            });
        }

        // Update status device online/offline
        function updateDeviceStatus(devicesStatus) {
            Object.keys(devicesStatus).forEach(deviceCode => {
                const device = devicesStatus[deviceCode];
                const onlineElement = document.getElementById(`online-${deviceCode}`);

                if (onlineElement) {
                    const isOnline = device.is_online;
                    const newClass = isOnline ? 'device-online status-online' : 'device-online status-offline';
                    const newText = isOnline ? 'ONLINE' : 'OFFLINE';

                    if (lastDevicesStatus[deviceCode] !== isOnline) {
                        onlineElement.className = newClass;
                        onlineElement.textContent = newText;
                        onlineElement.classList.add('stat-updated');
                        setTimeout(() => {
                            onlineElement.classList.remove('stat-updated');
                        }, 500);
                    }

                    lastDevicesStatus[deviceCode] = isOnline;
                }
            });
        }

        // Fetch data real-time
        function fetchRealtimeData() {
            fetch('get-superadmin-data.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        updateStats(data.stats);
                        updatePumpStatus(data.pumps);
                        updateDeviceStatus(data.devices_status);
                        showUpdateIndicator();
                    }
                })
                .catch(error => {
                    console.error('Error fetching realtime data:', error);
                });
        }

        // Auto-update setiap 3 detik
        setInterval(fetchRealtimeData, 3000);

        // Load initial data
        fetchRealtimeData();
    </script>
</body>

</html>