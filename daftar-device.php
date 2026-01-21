<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$nama_lengkap = $_SESSION["nama_lengkap"] ?? $username;
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
            // Redirect dengan pesan sukses untuk mencegah resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success&kode=" . urlencode($kode));
            exit;
        } else {
            // Redirect dengan pesan error
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=error&msg=" . urlencode("Gagal mendaftarkan device!"));
            exit;
        }
    } else {
        // Redirect dengan pesan error
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=error&msg=" . urlencode("Nama device tidak boleh kosong!"));
        exit;
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
        
        // Redirect dengan pesan sukses
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=selected&device=" . urlencode($selected_kode));
        exit;
    } else {
        // Redirect dengan pesan error
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=error&msg=" . urlencode("Device tidak valid atau belum disetujui!"));
        exit;
    }
}

// Proses hapus device oleh user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_my_device'])) {
    $device_kode = $_POST['device_kode'] ?? '';
    $delete_reason = $_POST['delete_reason'] ?? '';
    
    if (user_delete_device($device_kode, $username, $delete_reason)) {
        // Jika device yang dihapus adalah device yang sedang dipilih, reset selection
        if (isset($_SESSION['selected_device']) && $_SESSION['selected_device'] === $device_kode) {
            unset($_SESSION['selected_device']);
        }
        
        // Redirect dengan pesan sukses
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=deleted&device=" . urlencode($device_kode));
        exit;
    } else {
        // Redirect dengan pesan error
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=error&msg=" . urlencode("Gagal menghapus device!"));
        exit;
    }
}

// Handle pesan dari redirect (PRG pattern)
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success':
            $kode = $_GET['kode'] ?? '';
            $message = "Device berhasil didaftarkan dan sedang menunggu approval dari superadmin. Kode device: $kode";
            break;
        case 'selected':
            $device = $_GET['device'] ?? '';
            $message = "Device ($device) berhasil dipilih untuk monitoring!";
            break;
        case 'deleted':
            $device = $_GET['device'] ?? '';
            $message = "Device ($device) berhasil dihapus! Superadmin dapat memulihkannya jika diperlukan.";
            break;
        case 'error':
            $error = $_GET['msg'] ?? 'Terjadi kesalahan!';
            break;
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
    <title>Kelola Device - SiPhantom</title>
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
            background: var(--light);   
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
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
            margin-bottom: 25px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
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
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: none;
            border-radius: 10px;
            color: white;
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
                    <li><a class="dropdown-item" href="dashboard.php">📊 Dashboard</a></li>
                    <li><a class="dropdown-item" href="daftar-device.php">📱 Kelola Device</a></li>
                    <li><a class="dropdown-item" href="pengaturan-jadwal.php">⏰ Jadwal Penyiraman</a></li>
                    <li><a class="dropdown-item" href="analisis.php">📈 Analisis</a></li>
                    <li><a class="dropdown-item" href="tips.php">💡 Tips</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>
        <div class="container mt-4">
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
            <div class="alert alert-success alert-dismissible fade show">
                <strong>✅ Berhasil!</strong> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php if (isset($_GET['status']) && $_GET['status'] === 'selected'): ?>
                    <hr>
                    <div class="mb-0">
                        <a href="dashboard.php" class="btn btn-sm btn-success">
                            📊 Lihat Dashboard Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>❌ Error!</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                                    
                                    <?php if ($device['status_approval'] === 'approved'): ?>
                                        <span class="badge bg-success mb-2 ms-1">✅ Disetujui</span>
                                    <?php elseif ($device['status_approval'] === 'pending'): ?>
                                        <span class="badge bg-warning mb-2 ms-1">⏳ Menunggu</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger mb-2 ms-1">❌ Ditolak</span>
                                    <?php endif; ?>
                                    
                                    <br>
                                    
                                    <!-- Tombol Copy Kode -->
                                    <button class="btn btn-sm btn-primary mb-1" 
                                            onclick="copyKode('<?php echo $device['kode']; ?>')">
                                        📋 Copy Kode
                                    </button>
                                    <br>
                                    
                                    <!-- Tombol Pilih untuk Monitoring (hanya untuk device approved dan aktif) -->
                                    <?php if ($device['status_approval'] === 'approved' && $device['aktif']): ?>
                                        <?php if (isset($_SESSION['selected_device']) && $_SESSION['selected_device'] === $device['kode']): ?>
                                            <button class="btn btn-sm btn-success mb-1" disabled>
                                                ✅ Sedang Dipilih
                                            </button>
                                        <?php else: ?>
                                            <a href="?select_device=<?php echo $device['kode']; ?>" 
                                               class="btn btn-sm btn-info mb-1">
                                                📡 Pilih untuk Monitoring
                                            </a>
                                        <?php endif; ?>
                                        <br>
                                    <?php endif; ?>
                                    
                                    <!-- Tombol Hapus (untuk device approved atau rejected) -->
                                    <?php if ($device['status_approval'] === 'approved' || $device['status_approval'] === 'rejected'): ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="openDeleteModal('<?php echo $device['kode']; ?>', '<?php echo htmlspecialchars($device['nama']); ?>')">
                                            🗑️ Hapus
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
                            <label class="form-label">Device yang akan dihapus:</label>
                            <input type="text" id="deviceInfo" class="form-control" readonly>
                            <input type="hidden" name="device_kode" id="deviceKode">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alasan menghapus (opsional):</label>
                            <textarea name="delete_reason" class="form-control" rows="3" 
                                      placeholder="Contoh: Sudah tidak digunakan, rusak, dll..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="delete_my_device" class="btn btn-danger">
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

        function openDeleteModal(kode, nama) {
            document.getElementById('deviceKode').value = kode;
            document.getElementById('deviceInfo').value = nama + ' (' + kode + ')';
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>