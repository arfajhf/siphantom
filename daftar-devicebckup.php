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

// Proses tambah device baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_device'])) {
    $nama_device = trim($_POST['nama_device']);
    $tipe = $_POST['tipe'];
    
    if (!empty($nama_device)) {
        // Generate kode device sederhana
        $kode = $tipe . sprintf('%03d', rand(100, 999));
        
        // Pastikan kode unik
        while (true) {
            $cek = mysqli_query($conn, "SELECT kode FROM devices WHERE kode = '$kode'");
            if (mysqli_num_rows($cek) == 0) break;
            $kode = $tipe . sprintf('%03d', rand(100, 999));
        }
        
        if (daftar_device($kode, $nama_device, $username)) {
            $message = "Device berhasil didaftarkan dan sedang menunggu approval dari superadmin. Kode device: $kode";
        } else {
            $error = "Gagal mendaftarkan device!";
        }
    } else {
        $error = "Nama device tidak boleh kosong!";
    }
}

// Proses pilih device untuk monitoring
if (isset($_GET['select_device'])) {
    $selected_kode = $_GET['select_device'];
    
    // Validasi device milik user dan sudah approved
    $validate_query = "SELECT * FROM devices WHERE kode = '$selected_kode' AND pemilik = '$username' 
                       AND status_approval = 'approved' AND aktif = 1 AND deleted_at IS NULL";
    $validate_result = mysqli_query($conn, $validate_query);
    
    if (mysqli_num_rows($validate_result) > 0) {
        $_SESSION['selected_device'] = $selected_kode;
        $message = "Device berhasil dipilih untuk monitoring. Anda akan diarahkan ke dashboard.";
        echo "<script>
                alert('Device berhasil dipilih untuk monitoring!');
                setTimeout(() => { window.location.href = 'dashboard.php'; }, 1000);
              </script>";
    } else {
        $error = "Device tidak valid atau belum disetujui!";
    }
}

// Proses hapus device oleh user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_my_device'])) {
    $device_kode = $_POST['device_kode'] ?? '';
    $delete_reason = $_POST['delete_reason'] ?? '';
    
    if (user_delete_device($device_kode, $username, $delete_reason)) {
        $message = "Device berhasil dihapus! Superadmin dapat memulihkannya jika diperlukan.";
        
        // Jika device yang dihapus adalah device yang sedang dipilih, reset selection
        if (isset($_SESSION['selected_device']) && $_SESSION['selected_device'] === $device_kode) {
            unset($_SESSION['selected_device']);
        }
    } else {
        $error = "Gagal menghapus device!";
    }
}

// Ambil daftar device milik user (approved + pending)
$devices_query = "SELECT * FROM devices WHERE pemilik = '$username' AND deleted_at IS NULL ORDER BY dibuat DESC";
$devices_result = mysqli_query($conn, $devices_query);
$devices = [];
$approved_devices = [];
while ($device = mysqli_fetch_assoc($devices_result)) {
    $devices[] = $device;
    if ($device['status_approval'] === 'approved') {
        $approved_devices[] = $device;
    }
}

// Get selected device info
$selected_device_info = null;
if (isset($_SESSION['selected_device'])) {
    foreach ($approved_devices as $device) {
        if ($device['kode'] === $_SESSION['selected_device']) {
            $selected_device_info = $device;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Device - SIMACMUR</title>
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
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .device-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .device-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        
        .device-code {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border: none;
            border-radius: 10px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .code-display {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">🍄 SIMACMUR</a>
            <span class="navbar-text text-white">
                Kelola Device IoT
            </span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <h2>📱 Kelola Device IoT</h2>
                <p class="text-muted">Kelola perangkat IoT Anda dengan mudah</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="dashboard.php" class="btn btn-primary me-2">
                    ← Kembali ke Dashboard
                </a>
                <!-- <?php if (!empty($approved_devices)): ?>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        🗑️ Hapus Device
                    </button>
                <?php endif; ?> -->
            </div>
        </div>

        <!-- Device Aktif untuk Monitoring -->
        <?php if ($selected_device_info): ?>
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #4CAF50, #45a049);">
                <h5 class="mb-0 text-white">📡 Device Aktif untuk Monitoring</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6><?php echo htmlspecialchars($selected_device_info['nama']); ?></h6>
                        <div class="device-code" style="background: #4CAF50;">
                            <?php echo $selected_device_info['kode']; ?>
                        </div>
                        <small class="text-muted">
                            Device ini sedang dipilih untuk monitoring di dashboard
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="dashboard.php" class="btn btn-success">
                            📊 Lihat Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif (!empty($approved_devices)): ?>
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>💡 Info:</strong> Pilih device yang ingin dimonitor dengan mengklik tombol "Pilih untuk Monitoring" di bawah.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <strong>✅ Berhasil!</strong> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>❌ Error!</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Device -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">➕ Daftarkan Device Baru</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Nama Device</label>
                                <input type="text" name="nama_device" class="form-control" 
                                       placeholder="Contoh: Sensor Jamur Greenhouse A" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tipe Device</label>
                                <select name="tipe" class="form-select" required>
                                    <option value="JAMUR">🍄 Jamur</option>
                                    <option value="SAYUR">🥬 Sayuran</option>
                                    <option value="BUAH">🍎 Buah-buahan</option>
                                    <option value="TANAMAN">🌱 Tanaman Hias</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="tambah_device" class="btn btn-success">
                        ➕ Daftarkan Device
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Device -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📋 Device Anda (<?php echo count($devices); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($devices)): ?>
                    <div class="text-center py-4">
                        <h5>📱 Belum Ada Device</h5>
                        <p class="text-muted">Daftarkan device IoT pertama Anda!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <div class="device-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($device['nama']); ?></h6>
                                    <div class="device-code">
                                        <?php echo $device['kode']; ?>
                                    </div>
                                    <small class="text-muted">
                                        📅 Dibuat: <?php echo date('d/m/Y H:i', strtotime($device['dibuat'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-<?php echo $device['aktif'] ? 'success' : 'secondary'; ?> mb-2">
                                        <?php echo $device['aktif'] ? '🟢 Aktif' : '🔴 Nonaktif'; ?>
                                    </span>
                                    <br>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="copyKode('<?php echo $device['kode']; ?>')">
                                        📋 Copy Kode
                                    </button>
                                    <br>
                                    <?php if (!empty($approved_devices)): ?>
                                        <button type="button" class="btn btn-danger mt-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            🗑️ Hapus Device
                                        </button>
                                    <?php endif; ?>
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
                <h5 class="mb-0">📖 Panduan Konfigurasi ESP8266</h5>
            </div>
            <div class="card-body">
                <h6>🔧 Konfigurasi di kode Arduino:</h6>
                <div class="code-display">
// Ganti dengan kode device Anda
const char* kodeDevice = "JAMUR123";

// Ganti dengan username akun Anda  
const char* username = "<?php echo $username; ?>";

// URL server (ganti dengan domain Anda)
const char* serverURL = "http://domain-anda.com/api.php";
                </div>
                
                <h6>📤 Format data yang dikirim:</h6>
                <div class="code-display">
String postData = "kode=" + String(kodeDevice) + 
                 "&suhu=" + String(temperature) + 
                 "&kelembaban=" + String(humidity) + 
                 "&username=" + String(username);
                </div>
                
                <div class="alert alert-info">
                    <strong>💡 Tips:</strong> Setelah mendapatkan kode device, masukkan kode tersebut ke dalam program ESP8266 Anda, lalu upload ke perangkat.
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Hapus Device -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

            
                <div class="modal-header">
                    <h5 class="modal-title">🗑️ Hapus Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong>⚠️ Peringatan:</strong> Device yang dihapus tidak akan bisa digunakan lagi, namun superadmin dapat memulihkannya jika diperlukan.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Device yang akan dihapus:</label>
                            <select name="device_kode" class="form-select" required>
                                <option value="">-- Pilih Device --</option>
                                <?php foreach ($approved_devices as $device): ?>
                                    <option value="<?php echo $device['kode']; ?>">
                                        <?php echo $device['nama']; ?> (<?php echo $device['kode']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alasan menghapus (opsional):</label>
                            <textarea name="delete_reason" class="form-control" rows="3" 
                                      placeholder="Contoh: Sudah tidak digunakan, rusak, dll..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="delete_my_device" class="btn btn-danger" 
                                onclick="return confirm('Yakin ingin menghapus device ini?')">
                            🗑️ Hapus Device
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyKode(kode) {
            navigator.clipboard.writeText(kode).then(() => {
                alert('✅ Kode device berhasil disalin: ' + kode);
            });
        }
    </script>
</body>
</html>