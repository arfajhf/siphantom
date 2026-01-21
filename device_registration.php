<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$message = '';
$error = '';

// Proses registrasi device baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_device'])) {
    $device_name = trim($_POST['device_name']);
    $device_type = $_POST['device_type'];
    
    if (!empty($device_name)) {
        // Generate unique device token
        $device_token = strtoupper($device_type) . '_' . strtoupper(substr(md5($username . time()), 0, 8));
        
        // Cek apakah tabel devices sudah ada
        $check_table = "SHOW TABLES LIKE 'devices'";
        $table_exists = mysqli_query($conn, $check_table);
        
        if (mysqli_num_rows($table_exists) == 0) {
            // Buat tabel devices jika belum ada
            $create_devices = "CREATE TABLE devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                device_token VARCHAR(100) UNIQUE NOT NULL,
                device_name VARCHAR(100) NOT NULL,
                owner_username VARCHAR(50) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            mysqli_query($conn, $create_devices);
            
            // Buat tabel device_permissions
            $create_permissions = "CREATE TABLE device_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                device_token VARCHAR(100) NOT NULL,
                username VARCHAR(50) NOT NULL,
                permission_type ENUM('read', 'control', 'admin') DEFAULT 'admin',
                granted_by VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_device (device_token, username)
            )";
            mysqli_query($conn, $create_permissions);
            
            // Tambah kolom device_token ke tabel existing jika belum ada
            $alter_logs = "ALTER TABLE logs ADD COLUMN IF NOT EXISTS device_token VARCHAR(100)";
            mysqli_query($conn, $alter_logs);
            
            $alter_relay = "ALTER TABLE relay ADD COLUMN IF NOT EXISTS device_token VARCHAR(100)";
            mysqli_query($conn, $alter_relay);
        }
        
        // Insert device baru
        $insert_device = "INSERT INTO devices (device_token, device_name, owner_username) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_device);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $device_token, $device_name, $username);
            
            if (mysqli_stmt_execute($stmt)) {
                // Berikan permission admin ke owner
                $insert_permission = "INSERT INTO device_permissions (device_token, username, permission_type, granted_by) VALUES (?, ?, 'admin', ?)";
                $perm_stmt = mysqli_prepare($conn, $insert_permission);
                
                if ($perm_stmt) {
                    mysqli_stmt_bind_param($perm_stmt, "sss", $device_token, $username, $username);
                    mysqli_stmt_execute($perm_stmt);
                }
                
                // Setup relay entry untuk device ini
                $insert_relay = "INSERT INTO relay (device_token, pelanggan, relayy, updated_by) VALUES (?, ?, 0, 'system')";
                $relay_stmt = mysqli_prepare($conn, $insert_relay);
                
                if ($relay_stmt) {
                    mysqli_stmt_bind_param($relay_stmt, "ss", $device_token, $username);
                    mysqli_stmt_execute($relay_stmt);
                }
                
                $message = "Device berhasil didaftarkan! Token: " . $device_token;
            } else {
                $error = "Gagal mendaftarkan device: " . mysqli_error($conn);
            }
        }
    } else {
        $error = "Nama device tidak boleh kosong!";
    }
}

// Ambil daftar device milik user
$my_devices_query = "SELECT d.device_token, d.device_name, d.created_at, d.last_activity, d.is_active
                     FROM devices d 
                     JOIN device_permissions dp ON d.device_token = dp.device_token 
                     WHERE dp.username = ? AND dp.permission_type = 'admin'
                     ORDER BY d.created_at DESC";

$devices_list = [];
$stmt = mysqli_prepare($conn, $my_devices_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_array($result)) {
        $devices_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="jamur2.png" sizes="32x32">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Registration - SIMACMUR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            max-width: 900px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, #00A8A9, #028A8A);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 20px;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00A8A9, #028A8A);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border: none;
            border-radius: 8px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            border: none;
            border-radius: 8px;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .device-token {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            border: 2px dashed #00A8A9;
            margin: 10px 0;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #00A8A9;
            box-shadow: 0 0 0 0.2rem rgba(0, 168, 169, 0.25);
        }

        .device-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .device-card:hover {
            border-color: #00A8A9;
            box-shadow: 0 4px 12px rgba(0, 168, 169, 0.1);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <a href="demo_dashboard.php" class="btn btn-primary back-btn">← Back to Dashboard</a>
    
    <div class="container">
        <div class="text-center mb-4">
            <h1 style="color: white; font-weight: bold;">🔗 Device Registration</h1>
            <p style="color: rgba(255,255,255,0.8);">Kelola perangkat IoT Anda</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <strong>✅ Berhasil!</strong> <?php echo htmlspecialchars($message); ?>
                <div class="device-token">
                    <strong>Device Token:</strong> <?php echo substr($message, strpos($message, 'Token: ') + 7); ?>
                    <br><small>Simpan token ini untuk konfigurasi perangkat IoT Anda!</small>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>❌ Error!</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Form Registrasi Device Baru -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">📱 Daftarkan Device IoT Baru</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="device_name" class="form-label">Nama Device</label>
                                <input type="text" class="form-control" id="device_name" name="device_name" 
                                       placeholder="Contoh: Sensor Jamur Greenhouse A" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="device_type" class="form-label">Tipe Device</label>
                                <select class="form-select" id="device_type" name="device_type" required>
                                    <option value="JAMUR">🍄 Sensor Jamur</option>
                                    <option value="HYDROPONIK">🌱 Hidroponik</option>
                                    <option value="GREENHOUSE">🏠 Greenhouse</option>
                                    <option value="WEATHER">🌤️ Weather Station</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="register_device" class="btn btn-success">
                        ➕ Daftarkan Device
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Device Saya -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">📋 Device Saya (<?php echo count($devices_list); ?>)</h4>
            </div>
            <div class="card-body">
                <?php if (empty($devices_list)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Belum ada device terdaftar. Daftarkan device IoT pertama Anda!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($devices_list as $device): ?>
                        <div class="device-card">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($device['device_name']); ?></h5>
                                    <div class="device-token">
                                        <strong>Token:</strong> <?php echo htmlspecialchars($device['device_token']); ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <span class="status-badge <?php echo $device['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $device['is_active'] ? '🟢 Active' : '🔴 Inactive'; ?>
                                    </span>
                                    <br><small class="text-muted">
                                        Dibuat: <?php echo date('d/m/Y', strtotime($device['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-primary btn-sm" onclick="copyToken('<?php echo $device['device_token']; ?>')">
                                        📋 Copy Token
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panduan Konfigurasi -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">📖 Panduan Konfigurasi Perangkat IoT</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><strong>🔧 Format Lama (Tidak perlu ubah device):</strong></h6>
                        <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 12px;">
POST ke: postdemo.php
Data:
- status1 = suhu
- status2 = kelembaban  
- pelanggan = <?php echo htmlspecialchars($username); ?></pre>
                    </div>
                    <div class="col-md-6">
                        <h6><strong>🔒 Format Baru (Dengan Device Token):</strong></h6>
                        <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 12px;">
POST ke: secure_api.php
Data:
- device_token = [TOKEN_DEVICE]
- suhu = suhu
- kelembaban = kelembaban</pre>
                    </div>
                </div>
                
                <hr>
                
                <h6><strong>📝 Contoh Kode Arduino/ESP32:</strong></h6>
                <pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 8px; font-size: 12px;">
#include &lt;HTTPClient.h&gt;

// Opsi 1: Format Lama (Mudah)
void sendDataOld() {
    HTTPClient http;
    http.begin("http://yourserver.com/postdemo.php");
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String data = "status1=" + String(temperature) + 
                 "&status2=" + String(humidity) + 
                 "&pelanggan=<?php echo $username; ?>";
    
    http.POST(data);
    http.end();
}

// Opsi 2: Format Baru (Aman)
void sendDataNew() {
    HTTPClient http;
    http.begin("http://yourserver.com/secure_api.php");
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String data = "device_token=YOUR_DEVICE_TOKEN" + 
                 "&suhu=" + String(temperature) + 
                 "&kelembaban=" + String(humidity);
    
    http.POST(data);
    http.end();
}</pre>
            </div>
        </div>
    </div>

    <script>
        function copyToken(token) {
            navigator.clipboard.writeText(token).then(function() {
                alert('✅ Token berhasil disalin ke clipboard!');
            });
        }
    </script>
</body>
</html>