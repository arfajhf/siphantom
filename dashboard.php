<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$nama_lengkap = $_SESSION["nama_lengkap"] ?? $username;

// Cek apakah user adalah superadmin - gunakan session untuk performa lebih baik
$user_role = $_SESSION["user_role"] ?? 'user';
$is_superadmin = ($user_role === 'superadmin');

// Double check dari database jika session kosong
if (empty($_SESSION["user_role"])) {
    $role_query = "SELECT role FROM users WHERE username = '$username'";
    $role_result = mysqli_query($conn, $role_query);
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_data = mysqli_fetch_assoc($role_result);
        $user_role = $role_data['role'] ?? 'user';
        $_SESSION["user_role"] = $user_role; // Update session
        $is_superadmin = ($user_role === 'superadmin');
    }
}

// Ambil device milik user (hanya yang approved)
$device_query = "SELECT * FROM devices WHERE pemilik = '$username' AND aktif = 1 AND deleted_at IS NULL AND status_approval = 'approved'";
$device_result = mysqli_query($conn, $device_query);
$devices = [];
while ($device = mysqli_fetch_assoc($device_result)) {
    $devices[] = $device;
}

// Ambil device pending approval
$pending_query = "SELECT * FROM devices WHERE pemilik = '$username' AND status_approval = 'pending' AND deleted_at IS NULL";
$pending_result = mysqli_query($conn, $pending_query);
$pending_devices = [];
while ($device = mysqli_fetch_assoc($pending_result)) {
    $pending_devices[] = $device;
}

// Proses hapus device oleh user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_my_device'])) {
    $device_kode = $_POST['device_kode'] ?? '';
    $delete_reason = $_POST['delete_reason'] ?? '';

    if (user_delete_device($device_kode, $username, $delete_reason)) {
        echo "<script>alert('Device berhasil dihapus!'); window.location.reload();</script>";
    } else {
        echo "<script>alert('Gagal menghapus device!');</script>";
    }
}

// Pilih device default dari session atau device pertama
$selected_device = $_SESSION['selected_device'] ?? ($devices[0]['kode'] ?? null);

// Jika ada parameter device dari URL, update session
if (isset($_GET['device']) && !empty($_GET['device'])) {
    $selected_device = $_GET['device'];
    $_SESSION['selected_device'] = $selected_device;
}
$device_name = '';

foreach ($devices as $device) {
    if ($device['kode'] === $selected_device) {
        $device_name = $device['nama'];
        break;
    }
}

// Ambil data sensor terbaru
$sensor_data = null;
$relay_status = 0;

if ($selected_device) {
    // Data sensor terbaru
    $sensor_query = "SELECT * FROM sensors WHERE kode_device = '$selected_device' ORDER BY timestamp DESC LIMIT 1";
    $sensor_result = mysqli_query($conn, $sensor_query);
    $sensor_data = mysqli_fetch_assoc($sensor_result);

    // Status relay
    $relay_query = "SELECT status FROM relays WHERE kode_device = '$selected_device'";
    $relay_result = mysqli_query($conn, $relay_query);
    if ($relay_result) {
        $relay_row = mysqli_fetch_assoc($relay_result);
        $relay_status = $relay_row['status'] ?? 0;
    }
}

// Fungsi untuk menentukan status kondisi 
function getMushroomStatus($suhu, $soil_moisture, $kelembaban_udara)
{
    // Rentang ideal untuk  tiram
    $suhu_min = 22;
    $suhu_max = 28;
    $kelembaban_min = 80;
    $kelembaban_max = 90;

    $suhu_status = 'optimal';
    $kelembaban_status = 'optimal';
    $udara_status = 'optimal';

    // Cek status suhu
    if ($suhu < $suhu_min - 2) {
        $suhu_status = 'terlalu_dingin';
    } elseif ($suhu > $suhu_max + 2) {
        $suhu_status = 'terlalu_panas';
    } elseif ($suhu < $suhu_min || $suhu > $suhu_max) {
        $suhu_status = 'kurang_ideal';
    }

    // Cek status kelembaban
    if ($soil_moisture < $kelembaban_min - 10) {
        $kelembaban_status = 'terlalu_kering';
    } elseif ($soil_moisture > $kelembaban_max + 5) {
        $kelembaban_status = 'terlalu_lembab';
    } elseif ($soil_moisture < $kelembaban_min || $soil_moisture > $kelembaban_max) {
        $kelembaban_status = 'kurang_ideal';
    }

    // Cek status kelembaban_udara
    if ($kelembaban_udara < $kelembaban_min - 10) {
        $udara_status = 'terlalu_kering';
    } elseif ($kelembaban_udara > $kelembaban_max + 5) {
        $udara_status = 'terlalu_lembab';
    } elseif ($kelembaban_udara < $kelembaban_min || $kelembaban_udara > $kelembaban_max) {
        $udara_status = 'kurang_ideal';
    }

    return ['suhu' => $suhu_status, 'soil_moisture' => $kelembaban_status, 'kelembaban' => $udara_status];
}

// Fungsi untuk mendapatkan pesan status
function getStatusMessage($status, $type)
{
    $messages = [
        'suhu' => [
            'optimal' => '✅ Ideal untuk pertumbuhan ',
            'kurang_ideal' => '⚠️ Kurang ideal, perlu penyesuaian',
            'terlalu_dingin' => '🧊 Terlalu dingin, perlu pemanasan',
            'terlalu_panas' => '🔥 Terlalu panas, perlu pendinginan'
        ],
        'soil_moisture' => [
            'optimal' => '✅ Ideal untuk pertumbuhan ',
            'kurang_ideal' => '⚠️ Kurang ideal, perlu penyesuaian',
            'terlalu_kering' => '🏜️ Terlalu kering, perlu penyiraman',
            'terlalu_lembab' => '💧 Terlalu lembab, perlu ventilasi'
        ],
        'kelembaban' => [
            'optimal' => '✅ Ideal untuk pertumbuhan ',
            'kurang_ideal' => '⚠️ Kurang ideal, perlu penyesuaian',
            'terlalu_kering' => '🏜️ Terlalu kering, perlu penyiraman',
            'terlalu_lembab' => '💧 Terlalu lembab, perlu ventilasi'
        ]
    ];

    return $messages[$type][$status] ?? 'Status tidak diketahui';
}

// Fungsi untuk mendapatkan class CSS status
function getStatusClass($status)
{
    switch ($status) {
        case 'optimal':
            return 'status-optimal';
        case 'kurang_ideal':
            return 'status-warning';
        case 'terlalu_dingin':
        case 'terlalu_panas':
        case 'terlalu_kering':
        case 'terlalu_lembab':
            return 'status-danger';
        default:
            return 'status-optimal';
    }
}

// Hitung status jika ada data sensor
$mushroom_status = null;
if ($sensor_data) {
    $mushroom_status = getMushroomStatus($sensor_data['suhu'], $sensor_data['soil_moisture'], $sensor_data['kelembaban']);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SiPhantom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2FB7D6;
            --secondary: #3CCF91;
            --green: #09ba70;
            --dark: #1F3A4D;
            --light: #F4FAFC;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(180deg,#ffffff 0%,var(--light) 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
            box-shadow: 0 4px 20px rgba(47, 183, 214, .35);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .container {
            padding: 20px 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .dropdown-menu {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            /* Gunakan 25px karena lebih fleksibel */
        }

        .device-selector {
            background: linear-gradient(135deg,var(--primary),var(--secondary)) !important;
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .data-card {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.3);
            transition: transform 0.3s;
            position: relative;
        }

        .data-card:hover {
            transform: translateY(-5px);
        }

        .data-card.temperature {
            background: linear-gradient(135deg, var(--secondary), var(--green));
            box-shadow: 0 5px 20px rgba(255, 107, 107, 0.3);
        }

        .data-card.humidity {
            background: linear-gradient(135deg, #4ECDC4, #7ED6D1);
            box-shadow: 0 5px 20px rgba(78, 205, 196, 0.3);
        }

        .data-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .data-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .data-unit {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .status-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
            display: inline-block;
        }

        .status-message {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .pump-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }

        .pump-switch {
            width: 60px;
            height: 30px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            position: relative;
            cursor: pointer;
            margin: 20px auto;
            transition: all 0.3s;
        }

        .pump-switch.active {
            background: rgba(255, 255, 255, 0.8);
        }

        .pump-handle {
            width: 26px;
            height: 26px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            left: 2px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .pump-switch.active .pump-handle {
            transform: translateX(30px);
            background: #4CAF50;
        }

        .timestamp-card {
            background: white;
            border: 2px solid #30b8d4;
            color: #66d0ea;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }

        /* Animasi untuk data yang ter-update */
        .data-updated {
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

        /* Status Info Card */
        .status-info {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .status-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .status-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            border-left: 4px solid #28a745;
        }

        .status-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }

        .status-item.danger {
            border-left-color: #dc3545;
            background: #f8d7da;
        }

        .ideal-range {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .ideal-range h6 {
            color: #155724;
            margin-bottom: 10px;
        }

        .range-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #c3e6cb;
        }

        .range-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .data-grid {
                grid-template-columns: 1fr;
            }

            .status-overview {
                grid-template-columns: 1fr;
            }
        }

        /* Style tambahan dari rekomendasi.php */
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .code-display {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
            overflow-x: auto;
            border-left: 4px solid #28a745;
        }

        .recommendation-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }

        .optimal-range {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }

        .watering-schedule {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #2d3748;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }

        .alert-success {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
            border: none;
            border-left: 4px solid #28a745;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-optimal {
            background-color: #28a745;
        }

        .status-warning {
            background-color: #ffc107;
        }

        .status-danger {
            background-color: #dc3545;
        }
    </style>

</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">SiPhantom</a>
            <div class="dropdown">
                <div class="user-profile" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="d-none d-md-block">
                        <div style="font-size: 0.9rem; font-weight: 600;"><?php echo $nama_lengkap; ?></div>
                        <div style="font-size: 0.7rem; opacity: 0.8;">● Online</div>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if ($is_superadmin): ?>
                        <li><a class="dropdown-item" href="superadmin.php">👑 Superadmin Panel</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="dashboard.php">📊 Dashboard</a></li>
                    <li><a class="dropdown-item" href="daftar-device.php">📱 Kelola Device</a></li>
                    <li><a class="dropdown-item" href="pengaturan-jadwal.php">⏰ Jadwal Penyiraman</a></li>
                    <li><a class="dropdown-item" href="analisis.php">📈 Analisis</a></li>
                    <li><a class="dropdown-item" href="tips.php">💡 Tips</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="text-center mb-4">
            <img src="image/sipantol-logo.png" height="100">
            <h4 class="fw-bold mt-2">Dashboard Monitoring</h4>
        </div>

        <?php if (empty($devices)): ?>
            <!-- Tidak Ada Device -->
            <div class="card">
                <div class="card-body">
                    <div class="no-data">
                        <h4>📱 Belum Ada Device Aktif</h4>
                        <p>Anda belum memiliki device yang disetujui atau belum memilih device untuk monitoring.</p>
                        <a href="daftar-device.php" class="btn btn-primary">Kelola Device</a>
                    </div>
                </div>
            </div>

            <!-- Device Pending -->
            <?php if (!empty($pending_devices)): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">⏳ Device Menunggu Approval</h5>
                        <?php foreach ($pending_devices as $device): ?>
                            <div class="alert alert-warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>📱 <?php echo htmlspecialchars($device['nama']); ?></strong>
                                        <br><small>Kode: <?php echo $device['kode']; ?></small>
                                        <br><small class="text-muted">Menunggu persetujuan superadmin...</small>
                                    </div>
                                    <div>
                                        <span class="badge bg-warning">⏳ PENDING</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>

            <!-- Info Device Aktif -->
            <?php if ($selected_device && $device_name): ?>
                <div class="device-selector">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5><strong>📡 Monitoring Device</strong></h5>
                            <p class="mb-0" style="opacity: 0.9;">
                                <?php echo htmlspecialchars($device_name); ?> (<?php echo $selected_device; ?>)
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!--             <?php if ($selected_device): ?> -->
            <!-- Status Kondisi  -->
            <!--                 <?php if ($sensor_data && $mushroom_status): ?>
                <div class="status-info">
                    <h5 class="text-center mb-3">🍄 Status Kondisi Kumbung </h5>
                    <div class="status-overview">
                        <div class="status-item <?php echo ($mushroom_status['suhu'] == 'optimal') ? '' : (($mushroom_status['suhu'] == 'kurang_ideal') ? 'warning' : 'danger'); ?>">
                            <div class="d-flex align-items-center">
                                <span class="status-indicator <?php echo getStatusClass($mushroom_status['suhu']); ?>"></span>
                                <div>
                                    <strong>Suhu: <?php echo number_format($sensor_data['suhu'], 1); ?>°C</strong>
                                    <br><small><?php echo getStatusMessage($mushroom_status['suhu'], 'suhu'); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="status-item <?php echo ($mushroom_status['kelembaban'] == 'optimal') ? '' : (($mushroom_status['kelembaban'] == 'kurang_ideal') ? 'warning' : 'danger'); ?>">
                            <div class="d-flex align-items-center">
                                <span class="status-indicator <?php echo getStatusClass($mushroom_status['kelembaban']); ?>"></span>
                                <div>
                                    <strong>Kelembaban: <?php echo number_format($sensor_data['kelembaban'], 1); ?>%</strong>
                                    <br><small><?php echo getStatusMessage($mushroom_status['kelembaban'], 'kelembaban'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div> -->

            <!-- Rentang Ideal -->
            <!--                     <div class="ideal-range">
                        <h6>📋 Rentang Ideal  Tiram</h6>
                        <div class="range-item">
                            <span>🌡️ Suhu:</span>
                            <strong>22°C - 28°C</strong>
                        </div>
                        <div class="range-item">
                            <span>💧 Kelembaban:</span>
                            <strong>80% - 90%</strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?> -->

            <!-- Data Sensor -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-center mb-4">📊 Data Sensor Terbaru</h5>

                    <?php if ($sensor_data): ?>
                        <div class="data-grid">
                            <div class="data-card temperature">
                                <div class="data-label">🌡️ SUHU UDARA</div>
                                <div class="data-value" id="suhu-display">
                                    <?php echo number_format($sensor_data['suhu'], 1); ?>
                                </div>
                                <div class="data-unit">°C</div>

                                <?php if ($mushroom_status): ?>
                                    <div class="status-badge" id="suhu-status">
                                        <?php
                                            switch ($mushroom_status['suhu']) {
                                                case 'optimal':
                                                    echo '✅ IDEAL';
                                                    break;
                                                case 'kurang_ideal':
                                                    echo '⚠️ KURANG IDEAL';
                                                    break;
                                                case 'terlalu_dingin':
                                                    echo '🧊 TERLALU DINGIN';
                                                    break;
                                                case 'terlalu_panas':
                                                    echo '🔥 TERLALU PANAS';
                                                    break;
                                            }
                                        ?>
                                    </div>
                                    <div class="status-message" id="suhu-message">
                                        <?php echo getStatusMessage($mushroom_status['suhu'], 'suhu'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="data-card humidity">
                                <div class="data-label">💧 KELEMBABAN TANAH</div>
                                <div class="data-value" id="kelembaban-display">
                                    <?php echo number_format($sensor_data['soil_moisture'], 1); ?>
                                </div>
                                <div class="data-unit">%</div>

                                <?php if ($mushroom_status): ?>
                                    <div class="status-badge" id="kelembaban-status">
                                        <?php
                                            switch ($mushroom_status['soil_moisture']) {
                                                case 'optimal':
                                                    echo '✅ IDEAL';
                                                    break;
                                                case 'kurang_ideal':
                                                    echo '⚠️ KURANG IDEAL';
                                                    break;
                                                case 'terlalu_kering':
                                                    echo '🏜️ TERLALU KERING';
                                                    break;
                                                case 'terlalu_lembab':
                                                    echo '💧 TERLALU LEMBAB';
                                                    break;
                                            }
                                        ?>
                                    </div>
                                    <div class="status-message" id="kelembaban-message">
                                        <?php echo getStatusMessage($mushroom_status['soil_moisture'], 'soil_moisture'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="data-card humidity">
                                <div class="data-label">💧 KELEMBABAN UDARA</div>
                                <div class="data-value" id="kelembaban-display">
                                    <?php echo number_format($sensor_data['kelembaban'], 1); ?>
                                </div>
                                <div class="data-unit">%</div>

                                <?php if ($mushroom_status): ?>
                                    <div class="status-badge" id="udara-status">
                                        <?php
                                            switch ($mushroom_status['kelembaban']) {
                                                case 'optimal':
                                                    echo '✅ IDEAL';
                                                    break;
                                                case 'kurang_ideal':
                                                    echo '⚠️ KURANG IDEAL';
                                                    break;
                                                case 'terlalu_kering':
                                                    echo '🏜️ TERLALU KERING';
                                                    break;
                                                case 'terlalu_lembab':
                                                    echo '💧 TERLALU LEMBAB';
                                                    break;
                                            }
                                        ?>
                                    </div>
                                    <div class="status-message" id="udara-message">
                                        <?php echo getStatusMessage($mushroom_status['kelembaban'], 'kelembaban'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="timestamp-card">
                            <strong>📅 Terakhir Update:</strong><br>
                            <span id="timestamp-display">
                                <?php
                                        $tanggal = date('d/m/Y', strtotime($sensor_data['tanggal']));
                                        $waktu = date('H:i:s', strtotime($sensor_data['waktu']));
                                        echo "$tanggal pukul $waktu";
                                ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <h5>📊 Belum Ada Data</h5>
                            <p>Device belum mengirim data sensor.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kontrol Pompa -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-center mb-4">🚰 Kontrol Pompa Air</h5>

                    <div class="pump-card">
                        <h6>💧 Pompa Penyiram</h6>
                        <p style="opacity: 0.8; margin: 10px 0;"><?php echo $device_name; ?></p>

                        <div class="pump-switch <?php echo ($relay_status == 1) ? 'active' : ''; ?>"
                            onclick="togglePompa()">
                            <div class="pump-handle"></div>
                        </div>

                        <div id="pump-status" style="font-weight: 600; font-size: 1.1rem;">
                            Status: <?php echo ($relay_status == 1) ? '🟢 HIDUP' : '🔴 MATI'; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let pumpStatus = <?php echo $relay_status; ?>;
        const deviceCode = '<?php echo $selected_device; ?>';
        let lastSuhu = '<?php echo isset($sensor_data['suhu']) ? number_format($sensor_data['suhu'], 1) : '0'; ?>';
        let lastKelembaban = '<?php echo isset($sensor_data['kelembaban']) ? number_format($sensor_data['kelembaban'], 1) : '0'; ?>';
        let lastSoil_moisture = '<?php echo isset($sensor_data['soil_moisture']) ? number_format($sensor_data['soil_moisture'], 1) : '0'; ?>';
        let lastTimestamp = '<?php echo isset($sensor_data['tanggal']) && isset($sensor_data['waktu']) ? date('d/m/Y', strtotime($sensor_data['tanggal'])) . ' pukul ' . date('H:i:s', strtotime($sensor_data['waktu'])) : ''; ?>';

        function gantiDevice(kode) {
            window.location.href = 'daftar-device.php?select_device=' + kode;
        }

        function togglePompa() {
            if (!deviceCode) return;

            const pumpSwitch = document.querySelector('.pump-switch');
            const pumpStatusDiv = document.getElementById('pump-status');

            pumpStatus = pumpStatus == 1 ? 0 : 1;

            if (pumpStatus == 1) {
                pumpSwitch.classList.add('active');
                pumpStatusDiv.innerHTML = 'Status: 🟢 HIDUP';
            } else {
                pumpSwitch.classList.remove('active');
                pumpStatusDiv.innerHTML = 'Status: 🔴 MATI';
            }

            // Kirim ke server
            fetch('kontrol-relay.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'device=' + deviceCode + '&status=' + pumpStatus
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Pompa berhasil diubah:', data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Kembalikan status jika gagal
                    pumpStatus = pumpStatus == 1 ? 0 : 1;
                    location.reload();
                });
        }

        // Fungsi untuk menambahkan animasi update
        function animateUpdate(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.add('data-updated');
                setTimeout(() => {
                    element.classList.remove('data-updated');
                }, 500);
            }
        }

        // Fungsi untuk menentukan status kondisi 
        function getMushroomStatus(suhu, kelembaban, udara) {
            const suhuMin = 22;
            const suhuMax = 28;
            const kelembabanMin = 80;
            const kelembabanMax = 90;
            const udaraMin = 80;
            const udaraMax = 90;

            let suhuStatus = 'optimal';
            let kelembabanStatus = 'optimal';
            let udaraStatus = 'optimal';

            // Cek status suhu
            if (suhu < suhuMin - 2) {
                suhuStatus = 'terlalu_dingin';
            } else if (suhu > suhuMax + 2) {
                suhuStatus = 'terlalu_panas';
            } else if (suhu < suhuMin || suhu > suhuMax) {
                suhuStatus = 'kurang_ideal';
            }

            // Cek status kelembaban
            if (kelembaban < kelembabanMin - 10) {
                kelembabanStatus = 'terlalu_kering';
            } else if (kelembaban > kelembabanMax + 5) {
                kelembabanStatus = 'terlalu_lembab';
            } else if (kelembaban < kelembabanMin || kelembaban > kelembabanMax) {
                kelembabanStatus = 'kurang_ideal';
            }

            // Cek status kelembaban
            if (udara < udaraMin - 10) {
                udaraStatus = 'terlalu_kering';
            } else if (udara > udaraMax + 5) {
                udaraStatus = 'terlalu_lembab';
            } else if (udara < udaraMin || udara > udaraMax) {
                udaraStatus = 'kurang_ideal';
            }

            return {
                suhu: suhuStatus,
                kelembaban: kelembabanStatus,
                udara: udaraStatus
            };
        }

        // Fungsi untuk mendapatkan pesan status
        function getStatusMessage(status, type) {
            const messages = {
                'suhu': {
                    'optimal': '✅ Ideal untuk pertumbuhan ',
                    'kurang_ideal': '⚠️ Kurang ideal, perlu penyesuaian',
                    'terlalu_dingin': '🧊 Terlalu dingin, perlu pemanasan',
                    'terlalu_panas': '🔥 Terlalu panas, perlu pendinginan'
                },
                'kelembaban': {
                    'optimal': '✅ Ideal untuk pertumbuhan ',
                    'kurang_ideal': '⚠️ Kurang ideal, perlu penyesuaian',
                    'terlalu_kering': '🏜️ Terlalu kering, perlu penyiraman',
                    'terlalu_lembab': '💧 Terlalu lembab, perlu ventilasi'
                },
                'udara': {
                    'optimal': '✅ Ideal untuk pertumbuhan ',
                    'kurang_ideal': '⚠️ Kurang ideal, perlu penyesuaian',
                    'terlalu_kering': '🏜️ Terlalu kering, perlu penyiraman',
                    'terlalu_lembab': '💧 Terlalu lembab, perlu ventilasi'
                }
            };

            return messages[type][status] || 'Status tidak diketahui';
        }

        // Fungsi untuk mendapatkan badge status
        function getStatusBadge(status) {
            switch (status) {
                case 'optimal':
                    return '✅ IDEAL';
                case 'kurang_ideal':
                    return '⚠️ KURANG IDEAL';
                case 'terlalu_dingin':
                    return '🧊 TERLALU DINGIN';
                case 'terlalu_panas':
                    return '🔥 TERLALU PANAS';
                case 'terlalu_kering':
                    return '🏜️ TERLALU KERING';
                case 'terlalu_lembab':
                    return '💧 TERLALU LEMBAB';
                default:
                    return '❓ TIDAK DIKETAHUI';
            }
        }

        // Fungsi untuk update status cards
        function updateStatusCards(suhu, kelembaban, udara) {
            const status = getMushroomStatus(parseFloat(suhu), parseFloat(kelembaban), parseFloat(udara));

            // Update status suhu
            const suhuStatusElement = document.getElementById('suhu-status');
            const suhuMessageElement = document.getElementById('suhu-message');
            if (suhuStatusElement) {
                suhuStatusElement.textContent = getStatusBadge(status.suhu);
            }
            if (suhuMessageElement) {
                suhuMessageElement.textContent = getStatusMessage(status.suhu, 'suhu');
            }

            // Update status kelembaban
            const kelembabanStatusElement = document.getElementById('kelembaban-status');
            const kelembabanMessageElement = document.getElementById('kelembaban-message');
            if (kelembabanStatusElement) {
                kelembabanStatusElement.textContent = getStatusBadge(status.kelembaban);
            }
            if (kelembabanMessageElement) {
                kelembabanMessageElement.textContent = getStatusMessage(status.kelembaban, 'kelembaban');
            }

            // Update status kelembaban
            const udaraStatusElement = document.getElementById('udara-status');
            const udaraMessageElement = document.getElementById('udara-message');
            if (udaraStatusElement) {
                udaraStatusElement.textContent = getStatusBadge(status.udara);
            }
            if (udaraMessageElement) {
                udaraMessageElement.textContent = getStatusMessage(status.udara, 'udara');
            }
        }

        // Auto refresh data setiap 1 detik
        setInterval(() => {
            if (deviceCode) {
                fetch('get-data.php?device=' + deviceCode)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.suhu !== undefined && data.soil_moisture !== undefined && data.kelembaban !== undefined) {
                            const newSuhu = parseFloat(data.suhu).toFixed(1);
                            const newSoil_moisture = parseFloat(data.soil_moisture).toFixed(1);
                            const newKelembaban = parseFloat(data.kelembaban).toFixed(1);

                            // Update suhu jika ada perubahan
                            if (newSuhu !== lastSuhu) {
                                document.getElementById('suhu-display').textContent = newSuhu;
                                animateUpdate('suhu-display');
                                lastSuhu = newSuhu;
                            }

                            // Update kelembaban jika ada perubahan
                            if (newSoil_moisture !== lastSoil_moisture) {
                                document.getElementById('kelembaban-display').textContent = newSoil_moisture;
                                animateUpdate('kelembaban-display');
                                lastSoil_moisture = newSoil_moisture;
                            }

                            // Update kelembaban jika ada perubahan
                            if (newKelembaban !== lastKelembaban) {
                                document.getElementById('kelembaban-display').textContent = newKelembaban;
                                animateUpdate('kelembaban-display');
                                lastKelembaban = newKelembaban;
                            }

                            // Update status cards
                            updateStatusCards(newSuhu, newKelembaban, newSoil_moisture);

                            // Update timestamp jika ada perubahan
                            if (data.tanggal && data.waktu) {
                                const newTimestamp = data.tanggal + ' pukul ' + data.waktu;
                                if (newTimestamp !== lastTimestamp) {
                                    document.getElementById('timestamp-display').textContent = newTimestamp;
                                    animateUpdate('timestamp-display');
                                    lastTimestamp = newTimestamp;
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        }, 1000); // Update setiap 1 detik
    </script>
</body>

</html>