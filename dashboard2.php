<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$nama_lengkap = $_SESSION["nama_lengkap"] ?? $username;

// Ambil device milik user
$device_query = "SELECT * FROM devices WHERE pemilik = '$username' AND aktif = 1";
$device_result = mysqli_query($conn, $device_query);
$devices = [];
while ($device = mysqli_fetch_assoc($device_result)) {
    $devices[] = $device;
}

// Pilih device aktif
$selected_device = $_GET['device'] ?? ($devices[0]['kode'] ?? null);
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIMACMUR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        
        .user-profile:hover {
            background: rgba(255,255,255,0.2);
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
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .device-selector {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
        }
        
        .data-card:hover {
            transform: translateY(-5px);
        }
        
        .data-card.temperature {
            background: linear-gradient(135deg, #FF6B6B, #FF8E8E);
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
        
        .pump-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
        
        .timestamp-card {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .data-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <span class="navbar-brand">🍄 SIMACMUR</span>
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
                    <li><a class="dropdown-item" href="daftar-device.php">📱 Kelola Device</a></li>
                    <li><a class="dropdown-item" href="#">⚙️ Pengaturan</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (empty($devices)): ?>
            <!-- Tidak Ada Device -->
            <div class="card">
                <div class="card-body">
                    <div class="no-data">
                        <h4>📱 Belum Ada Device</h4>
                        <p>Anda belum memiliki device IoT. Silakan daftarkan device pertama Anda.</p>
                        <a href="daftar-device.php" class="btn btn-primary">Daftarkan Device</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Selector Device -->
            <div class="device-selector">
                <h5><strong>🔗 Pilih Device IoT</strong></h5>
                <select class="form-select mt-3" onchange="gantiDevice(this.value)">
                    <?php foreach ($devices as $device): ?>
                        <option value="<?php echo $device['kode']; ?>" 
                                <?php echo ($device['kode'] === $selected_device) ? 'selected' : ''; ?>>
                            📡 <?php echo $device['nama']; ?> (<?php echo $device['kode']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_device): ?>
                <!-- Data Sensor -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">📊 Data Sensor Terbaru</h5>
                        
                        <?php if ($sensor_data): ?>
                            <div class="data-grid">
                                <div class="data-card temperature">
                                    <div class="data-label">🌡️ SUHU</div>
                                    <div class="data-value" id="suhu-display">
                                        <?php echo number_format($sensor_data['suhu'], 1); ?>
                                    </div>
                                    <div class="data-unit">°C</div>
                                </div>
                                
                                <div class="data-card humidity">
                                    <div class="data-label">💧 KELEMBABAN</div>
                                    <div class="data-value" id="kelembaban-display">
                                        <?php echo number_format($sensor_data['kelembaban'], 1); ?>
                                    </div>
                                    <div class="data-unit">%</div>
                                </div>
                            </div>
                            
                            <div class="timestamp-card">
                                <strong>📅 Terakhir Update:</strong><br>
                                <?php 
                                $tanggal = date('d/m/Y', strtotime($sensor_data['tanggal']));
                                $waktu = date('H:i:s', strtotime($sensor_data['waktu']));
                                echo "$tanggal pukul $waktu";
                                ?>
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

        function gantiDevice(kode) {
            window.location.href = '?device=' + kode;
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

        // Auto refresh data setiap 30 detik
        setInterval(() => {
            if (deviceCode) {
                fetch('get-data.php?device=' + deviceCode)
                .then(response => response.json())
                .then(data => {
                    if (data.suhu !== undefined) {
                        document.getElementById('suhu-display').textContent = parseFloat(data.suhu).toFixed(1);
                        document.getElementById('kelembaban-display').textContent = parseFloat(data.kelembaban).toFixed(1);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }, 30000);
    </script>
</body>
</html>