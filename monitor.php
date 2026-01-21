<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$device_code = $_GET['device'] ?? '';

// Hanya superadmin yang bisa akses
if (!is_superadmin($username)) {
    header("Location: dashboard.php");
    exit;
}

if (empty($device_code)) {
    header("Location: superadmin.php");
    exit;
}

// Ambil info device
$device_query = "SELECT d.*, u.nama_lengkap as pemilik_nama FROM devices d 
                 LEFT JOIN users u ON d.pemilik = u.username 
                 WHERE d.kode = '$device_code'";
$device_result = mysqli_query($conn, $device_query);
$device_info = mysqli_fetch_assoc($device_result);

if (!$device_info) {
    echo "<script>alert('Device tidak ditemukan!'); window.location='superadmin.php';</script>";
    exit;
}

// Ambil data sensor terbaru
$sensor_query = "SELECT * FROM sensors WHERE kode_device = '$device_code' ORDER BY timestamp DESC LIMIT 1";
$sensor_result = mysqli_query($conn, $sensor_query);
$latest_sensor = mysqli_fetch_assoc($sensor_result);

// Ambil 10 data sensor terakhir untuk grafik
$history_query = "SELECT * FROM sensors WHERE kode_device = '$device_code' ORDER BY timestamp DESC LIMIT 10";
$history_result = mysqli_query($conn, $history_query);
$sensor_history = [];
while ($row = mysqli_fetch_assoc($history_result)) {
    $sensor_history[] = $row;
}
$sensor_history = array_reverse($sensor_history); // Urutkan dari lama ke baru

// Ambil status relay
$relay_query = "SELECT status FROM relays WHERE kode_device = '$device_code'";
$relay_result = mysqli_query($conn, $relay_query);
$relay_data = mysqli_fetch_assoc($relay_result);
$relay_status = $relay_data['status'] ?? 0;

// Proses kontrol relay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_relay'])) {
    $new_status = ($relay_status == 1) ? 0 : 1;
    $update_query = "UPDATE relays SET status = $new_status WHERE kode_device = '$device_code'";
    
    if (mysqli_query($conn, $update_query)) {
        $relay_status = $new_status;
        $action_text = $new_status ? 'dihidupkan' : 'dimatikan';
        log_activity($username, 'CONTROL_RELAY', $device_code, "Relay $action_text oleh superadmin");
        
        echo "<script>
            alert('Relay berhasil $action_text!');
            window.location.reload();
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor <?php echo $device_info['nama']; ?> - SiPhantom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }
        
        .data-card:hover {
            transform: translateY(-5px);
        }
        
        .data-card.temperature {
            background: linear-gradient(135deg, #45b7d0, #0a93b1);
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
        
        .device-info {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .pump-card {
            background: linear-gradient(135deg, #FF9500, #FF7A00);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .pump-switch {
            width: 60px;
            height: 30px;
            background: rgba(255,255,255,0.3);
            border-radius: 15px;
            position: relative;
            cursor: pointer;
            margin: 20px auto;
            transition: all 0.3s;
        }
        
        .pump-switch.active {
            background: rgba(255,255,255,0.8);
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .pump-switch.active .pump-handle {
            transform: translateX(30px);
            background: #4CAF50;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .mb3{
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <span class="navbar-brand">👑 Monitor Device</span>
            <div class="navbar-nav ms-auto">
                <a href="superadmin.php" class="nav-link">← Kembali ke Superadmin</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Info Device -->
        <div class="device-info">
            <div class="row">
                <div class="col-md-8">
                    <h4>📱 <?php echo htmlspecialchars($device_info['nama']); ?></h4>
                    <p class="mb-2">
                        <strong>Kode:</strong> <?php echo $device_info['kode']; ?> | 
                        <strong>Pemilik:</strong> <?php echo $device_info['pemilik_nama'] ?? $device_info['pemilik']; ?>
                    </p>
                    <p class="mb-0">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php echo $device_info['aktif'] ? 'success' : 'danger'; ?>">
                            <?php echo $device_info['aktif'] ? '🟢 Aktif' : '🔴 Nonaktif'; ?>
                        </span>
                        <?php if ($device_info['deleted_at']): ?>
                            <span class="badge bg-warning">🗑️ Dihapus</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <small>📅 Dibuat: <?php echo date('d/m/Y H:i', strtotime($device_info['dibuat'])); ?></small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Data Real-time -->
            <div class="col-lg-8">
                <!-- Sensor Data -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">📊 Data Sensor Real-time</h5>
                        
                        <?php if ($latest_sensor): ?>
                            <div class="data-grid">
                                <div class="data-card temperature">
                                    <div class="data-label">🌡️ SUHU</div>
                                    <div class="data-value" id="suhu-display">
                                        <?php echo number_format($latest_sensor['suhu'], 1); ?>
                                    </div>
                                    <div class="data-unit">°C</div>
                                </div>
                                
                                <div class="data-card humidity">
                                    <div class="data-label">💧 KELEMBABAN</div>
                                    <div class="data-value" id="kelembaban-display">
                                        <?php echo number_format($latest_sensor['kelembaban'], 1); ?>
                                    </div>
                                    <div class="data-unit">%</div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    📅 Terakhir Update: <?php echo date('d/m/Y H:i:s', strtotime($latest_sensor['timestamp'])); ?>
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <h6>📊 Belum Ada Data</h6>
                                <p class="text-muted">Device belum mengirim data sensor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Grafik Historis -->
                <?php if (!empty($sensor_history)): ?>
                <div class="chart-container">
                    <h5>📈 Grafik Historis (10 Data Terakhir)</h5>
                    <canvas id="sensorChart" width="400" height="200"></canvas>
                </div>
                <?php endif; ?>
            </div>

            <!-- Kontrol & Info -->
            <!-- <div class="col-lg-4"> -->
                <!-- Kontrol Relay bukan kode -->
                <!-- <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">🚰 Kontrol Pompa</h5>
                        
                        <div class="pump-card">
                            <h6>💧 Pompa Penyiram</h6>
                            
                            <form method="POST">
                                <div class="pump-switch <?php echo ($relay_status == 1) ? 'active' : ''; ?>" 
                                     onclick="this.closest('form').submit();">
                                    <div class="pump-handle"></div>
                                </div>
                                <input type="hidden" name="toggle_relay" value="1">
                            </form>
                            
                            <div style="font-weight: 600; font-size: 1.1rem;">
                                Status: <?php echo ($relay_status == 1) ? '🟢 HIDUP' : '🔴 MATI'; ?>
                            </div>
                            
                            <div style="font-size: 0.9rem; margin-top: 10px; opacity: 0.8;">
                                Kontrol: Superadmin
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Statistik Device -->
                <div class="card mb3">
                    <div class="card-body">
                        <h5 class="card-title">📊 Statistik Device</h5>
                        
                        <?php
                        // Hitung statistik device
                        $today = date('Y-m-d');
                        $week_ago = date('Y-m-d', strtotime('-7 days'));
                        
                        $today_count = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(*) as count FROM sensors WHERE kode_device = '$device_code' AND tanggal = '$today'"))['count'];
                        
                        $week_count = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(*) as count FROM sensors WHERE kode_device = '$device_code' AND tanggal >= '$week_ago'"))['count'];
                        
                        $total_count = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(*) as count FROM sensors WHERE kode_device = '$device_code'"))['count'];
                        ?>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>📅 Data Hari Ini:</span>
                                <strong><?php echo $today_count; ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>📊 Data 7 Hari:</span>
                                <strong><?php echo $week_count; ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>💾 Total Data:</span>
                                <strong><?php echo $total_count; ?></strong>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>🚰 Status Pompa:</span>
                                <strong class="text-<?php echo ($relay_status == 1) ? 'success' : 'danger'; ?>">
                                    <?php echo ($relay_status == 1) ? 'HIDUP' : 'MATI'; ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh data setiap 30 detik
        setInterval(() => {
            fetch('get-data.php?device=<?php echo $device_code; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.suhu !== undefined) {
                    document.getElementById('suhu-display').textContent = parseFloat(data.suhu).toFixed(1);
                    document.getElementById('kelembaban-display').textContent = parseFloat(data.kelembaban).toFixed(1);
                }
            })
            .catch(error => console.error('Error:', error));
        }, 30000);

        // Grafik Sensor
        <?php if (!empty($sensor_history)): ?>
        const ctx = document.getElementById('sensorChart').getContext('2d');
        const sensorChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($sensor_history as $data): ?>
                        '<?php echo date('H:i', strtotime($data['waktu'])); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Suhu (°C)',
                    data: [
                        <?php foreach ($sensor_history as $data): ?>
                            <?php echo $data['suhu']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Kelembaban (%)',
                    data: [
                        <?php foreach ($sensor_history as $data): ?>
                            <?php echo $data['kelembaban']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>