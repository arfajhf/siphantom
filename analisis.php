<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$nama_lengkap = $_SESSION["nama_lengkap"] ?? $username;

// Cek apakah user adalah superadmin
$user_role = $_SESSION["user_role"] ?? 'user';
$is_superadmin = ($user_role === 'superadmin');

// Ambil device milik user (hanya yang approved)
$device_query = "SELECT * FROM devices WHERE pemilik = '$username' AND aktif = 1 AND deleted_at IS NULL AND status_approval = 'approved'";
$device_result = mysqli_query($conn, $device_query);
$devices = [];
while ($device = mysqli_fetch_assoc($device_result)) {
    $devices[] = $device;
}

// Pilih device default atau dari parameter
$selected_device = $_SESSION['selected_device'] ?? ($devices[0]['kode'] ?? null);
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

// Ambil 50 data terbaru untuk analisis
$analysis_data = [];
$avg_suhu = 0;
$avg_kelembaban = 0;
$max_suhu = 0;
$min_suhu = 100;
$max_kelembaban = 0;
$min_kelembaban = 100;
$data_count = 0;

if ($selected_device) {
    $analysis_query = "SELECT suhu, kelembaban, tanggal, waktu FROM sensors 
                      WHERE kode_device = '$selected_device' 
                      ORDER BY timestamp DESC LIMIT 50";
    $analysis_result = mysqli_query($conn, $analysis_query);

    $total_suhu = 0;
    $total_kelembaban = 0;

    while ($row = mysqli_fetch_assoc($analysis_result)) {
        $analysis_data[] = $row;
        $total_suhu += $row['suhu'];
        $total_kelembaban += $row['kelembaban'];

        // Update min/max values
        $max_suhu = max($max_suhu, $row['suhu']);
        $min_suhu = min($min_suhu, $row['suhu']);
        $max_kelembaban = max($max_kelembaban, $row['kelembaban']);
        $min_kelembaban = min($min_kelembaban, $row['kelembaban']);

        $data_count++;
    }

    if ($data_count > 0) {
        $avg_suhu = $total_suhu / $data_count;
        $avg_kelembaban = $total_kelembaban / $data_count;
    }
}

// Fungsi untuk menentukan status berdasarkan nilai
function getStatusSuhu($suhu)
{
    if ($suhu >= 25 && $suhu <= 30) {
        return ['status' => 'optimal', 'text' => 'Optimal', 'class' => 'status-optimal'];
    } elseif (($suhu >= 20 && $suhu < 25) || ($suhu > 30 && $suhu <= 35)) {
        return ['status' => 'warning', 'text' => 'Perlu Perhatian', 'class' => 'status-warning'];
    } else {
        return ['status' => 'danger', 'text' => 'Tidak Optimal', 'class' => 'status-danger'];
    }
}

function getStatusKelembaban($kelembaban)
{
    if ($kelembaban >= 60 && $kelembaban <= 80) {
        return ['status' => 'optimal', 'text' => 'Optimal', 'class' => 'status-optimal'];
    } elseif (($kelembaban >= 50 && $kelembaban < 60) || ($kelembaban > 80 && $kelembaban <= 90)) {
        return ['status' => 'warning', 'text' => 'Perlu Perhatian', 'class' => 'status-warning'];
    } else {
        return ['status' => 'danger', 'text' => 'Tidak Optimal', 'class' => 'status-danger'];
    }
}

// Analisis pola penyiraman berdasarkan data lingkungan
function getRekomendasi($avg_suhu, $avg_kelembaban)
{
    $rekomendasi = [];

    // Rekomendasi berdasarkan suhu
    if ($avg_suhu > 30) {
        $rekomendasi[] = [
            'type' => 'suhu_tinggi',
            'icon' => '🌡️',
            'title' => 'Suhu Tinggi',
            'message' => 'Suhu rata-rata ' . number_format($avg_suhu, 1) . '°C terdeteksi cukup tinggi. Kondisi ini dapat meningkatkan penguapan air pada tanaman.',
            'action' => 'Tingkatkan frekuensi penyiraman dan lakukan penyiraman pada pagi atau sore hari.'
        ];
    } elseif ($avg_suhu < 25) {
        $rekomendasi[] = [
            'type' => 'suhu_rendah',
            'icon' => '❄️',
            'title' => 'Suhu Rendah',
            'message' => 'Suhu rata-rata ' . number_format($avg_suhu, 1) . '°C terdeteksi relatif rendah dan dapat memperlambat pertumbuhan tanaman.',
            'action' => 'Kurangi frekuensi penyiraman dan hindari penyiraman pada malam hari.'
        ];
    }

    // Rekomendasi berdasarkan kelembaban
    if ($avg_kelembaban > 80) {
        $rekomendasi[] = [
            'type' => 'kelembaban_tinggi',
            'icon' => '💧',
            'title' => 'Kelembaban Tinggi',
            'message' => 'Kelembaban rata-rata ' . number_format($avg_kelembaban, 1) . '% terdeteksi cukup tinggi dan berpotensi membuat media tanam terlalu basah.',
            'action' => 'Kurangi frekuensi penyiraman dan pastikan sirkulasi udara berjalan dengan baik.'
        ];
    } elseif ($avg_kelembaban < 60) {
        $rekomendasi[] = [
            'type' => 'kelembaban_rendah',
            'icon' => '🏜️',
            'title' => 'Kelembaban Rendah',
            'message' => 'Kelembaban rata-rata ' . number_format($avg_kelembaban, 1) . '% masih rendah sehingga tanaman membutuhkan tambahan air.',
            'action' => 'Tingkatkan frekuensi penyiraman untuk menjaga kelembaban lingkungan tanaman.'
        ];
    }

    // Kondisi optimal
    if (
        $avg_suhu >= 25 && $avg_suhu <= 30 &&
        $avg_kelembaban >= 60 && $avg_kelembaban <= 80
    ) {
        $rekomendasi[] = [
            'type' => 'optimal',
            'icon' => '✅',
            'title' => 'Kondisi Optimal',
            'message' => 'Kondisi suhu dan kelembaban saat ini sudah sesuai untuk mendukung pertumbuhan tanaman.',
            'action' => 'Pertahankan pola penyiraman yang sedang berjalan.'
        ];
    }

    return $rekomendasi;
}


$status_suhu = getStatusSuhu($avg_suhu);
$status_kelembaban = getStatusKelembaban($avg_kelembaban);
$rekomendasi_list = getRekomendasi($avg_suhu, $avg_kelembaban);

// Jadwal penyiraman optimal berdasarkan analisis
$jadwal_optimal = [];
if ($avg_suhu > 30 || $avg_kelembaban < 60) {
    $jadwal_optimal = [
        ['waktu' => '08:00', 'durasi' => '3-5 menit', 'keterangan' => 'Penyiraman pagi (suhu masih sejuk)'],
        ['waktu' => '12:00', 'durasi' => '1-3 menit', 'keterangan' => 'Penyiraman siang (jika diperlukan)'],
        ['waktu' => '16:00', 'durasi' => '3-5 menit', 'keterangan' => 'Penyiraman sore (suhu mulai turun)']
    ];
} elseif ($avg_suhu < 25 || $avg_kelembaban > 80) {
    $jadwal_optimal = [
        ['waktu' => '08:00', 'durasi' => '1-3 menit', 'keterangan' => 'Penyiraman pagi ringan'],
        ['waktu' => '16:00', 'durasi' => '1-3 menit', 'keterangan' => 'Penyiraman sore ringan']
    ];
} else {
    $jadwal_optimal = [
        ['waktu' => '08:00', 'durasi' => '3-5 menit', 'keterangan' => 'Penyiraman pagi standar'],
        ['waktu' => '16:00', 'durasi' => '3-5 menit', 'keterangan' => 'Penyiraman sore standar']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekomendasi Penyiraman - SiPhantom</title>
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
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
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .analysis-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .analysis-card.temperature {
            border-left: 5px solid #FF6B6B;
        }

        .analysis-card.humidity {
            border-left: 5px solid #4ECDC4;
        }

        .analysis-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            color: #2d3748;
        }

        .analysis-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .analysis-range {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .recommendation-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
        }

        .recommendation-card.optimal {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .recommendation-card.warning {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        }

        .recommendation-card.danger {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
        }

        .schedule-card {
            background: linear-gradient(135deg, #3ccf926a 0%, #2fb7d657 100%);
            color: #2d3748;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
        }

        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            margin: 10px 0;
        }

        .schedule-time {
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
        }

        .schedule-duration {
            font-size: 0.9rem;
            color: #6c757d;
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

        .device-selector {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .analysis-grid {
                grid-template-columns: 1fr;
            }
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
        <?php if (empty($devices)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="text-center">
                        <h4>📱 Belum Ada Device Aktif</h4>
                        <p>Anda belum memiliki device yang disetujui untuk mendapatkan rekomendasi.</p>
                        <a href="daftar-device.php" class="btn btn-primary">Kelola Device</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Device Info -->
            <?php if ($selected_device && $device_name): ?>
                <div class="device-selector">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5><strong>💡 Rekomendasi untuk Device</strong></h5>
                            <p class="mb-0" style="opacity: 0.9;">
                                <?php echo htmlspecialchars($device_name); ?> (<?php echo $selected_device; ?>)
                            </p>
                        </div>
                        <div class="text-end">
                            <small style="opacity: 0.8;">Berdasarkan <?php echo $data_count; ?> data terbaru</small>
                        </div>
                    </div>

                    <?php if ($data_count > 0): ?>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo number_format($avg_suhu, 1); ?>°C</span>
                                <span class="stat-label">Rata-rata Suhu</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo number_format($avg_kelembaban, 1); ?>%</span>
                                <span class="stat-label">Rata-rata Kelembaban</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo number_format($min_suhu, 1); ?>°C</span>
                                <span class="stat-label">Suhu Minimum</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo number_format($max_suhu, 1); ?>°C</span>
                                <span class="stat-label">Suhu Maksimum</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo number_format($min_kelembaban, 1); ?>%</span>
                                <span class="stat-label">Kelembaban Minimum</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo number_format($max_kelembaban, 1); ?>%</span>
                                <span class="stat-label">Kelembaban Maksimum</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($data_count > 0): ?>
                <!-- Analisis Kondisi -->
                <div class="card">
                    <div class="card-header">
                        <h5>📊 Analisis Kondisi Lingkungan</h5>
                    </div>
                    <div class="card-body">
                        <div class="analysis-grid">
                            <div class="analysis-card temperature">
                                <div class="analysis-label">🌡️ SUHU RATA-RATA</div>
                                <div class="analysis-value"><?php echo number_format($avg_suhu, 1); ?>°C</div>
                                <div class="analysis-range">
                                    <span class="status-indicator <?php echo $status_suhu['class']; ?>"></span>
                                    <?php echo $status_suhu['text']; ?>
                                </div>
                                <div class="analysis-range">
                                    Rentang: <?php echo number_format($min_suhu, 1); ?>°C - <?php echo number_format($max_suhu, 1); ?>°C
                                </div>
                            </div>

                            <div class="analysis-card humidity">
                                <div class="analysis-label">💧 KELEMBABAN RATA-RATA</div>
                                <div class="analysis-value"><?php echo number_format($avg_kelembaban, 1); ?>%</div>
                                <div class="analysis-range">
                                    <span class="status-indicator <?php echo $status_kelembaban['class']; ?>"></span>
                                    <?php echo $status_kelembaban['text']; ?>
                                </div>
                                <div class="analysis-range">
                                    Rentang: <?php echo number_format($min_kelembaban, 1); ?>% - <?php echo number_format($max_kelembaban, 1); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rekomendasi -->
                <div class="card">
                    <div class="card-header">
                        <h5>💡 Rekomendasi Penyiraman Berdasarkan Analisis Pola Suhu dan Kelembaban</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($rekomendasi_list)): ?>
                            <?php foreach ($rekomendasi_list as $rekomendasi): ?>
                                <div class="recommendation-card <?php echo $rekomendasi['type'] === 'optimal' ? 'optimal' : ($rekomendasi['type'] === 'suhu_rendah' || $rekomendasi['type'] === 'kelembaban_tinggi' ? 'warning' : 'danger'); ?>">
                                    <h6><?php echo $rekomendasi['icon']; ?> <?php echo $rekomendasi['title']; ?></h6>
                                    <p style="margin: 10px 0;"><?php echo $rekomendasi['message']; ?></p>
                                    <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 8px; margin-top: 10px;">
                                        <strong>Tindakan:</strong> <?php echo $rekomendasi['action']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="recommendation-card optimal">
                                <h6>✅ Kondisi Baik</h6>
                                <p>Kondisi lingkungan dalam rentang normal. Lanjutkan pola penyiraman seperti biasa.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Jadwal Penyiraman Optimal -->
                <div class="card">
                    <div class="card-header">
                        <h5>⏰ Jadwal Penyiraman Berdasarkan Analisis Pola Riwayat Penyiraman</h5>
                    </div>
                    <div class="card-body">
                        <div class="schedule-card">
                            <h6 style="margin-bottom: 15px;">📅 Jadwal Harian Berdasarkan Analisis</h6>
                            <?php foreach ($jadwal_optimal as $jadwal): ?>
                                <div class="schedule-item">
                                    <div>
                                        <div class="schedule-time"><?php echo $jadwal['waktu']; ?></div>
                                        <div class="schedule-duration"><?php echo $jadwal['durasi']; ?></div>
                                    </div>
                                    <div class="text-end">
                                        <small><?php echo $jadwal['keterangan']; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert alert-info mt-3">
                            <strong>💡 Tips:</strong> Jadwal ini berdasarkan analisis hasil dari riwayat penyiraman setiap harinya.
                        </div>
                    </div>
                </div>

                <!-- Kondisi Ideal -->
                <!--                 <div class="card">
                    <div class="card-header">
                        <h5>🎯 Kondisi Ideal untuk Jamur</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="recommendation-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <h6>🌡️ Suhu Optimal</h6>
                                    <p><strong>24°C - 28°C</strong></p>
                                    <p>Suhu ideal untuk pertumbuhan jamur tiram. Hindari suhu di bawah 20°C atau di atas 35°C.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="recommendation-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <h6>💧 Kelembaban Optimal</h6>
                                    <p><strong>80% - 90%</strong></p>
                                    <p>Kelembaban ideal untuk mencegah kekeringan atau pertumbuhan jamur yang tidak diinginkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h4>📊 Belum Ada Data Cukup</h4>
                            <p>Device belum memiliki cukup data untuk memberikan rekomendasi yang akurat.</p>
                            <p>Minimal dibutuhkan beberapa data sensor untuk analisis.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>


        <!-- Status Rekomendasi -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📊 Interpretasi Status</h5>
            </div>
            <div class="card-body">
                <div class="recommendation-card">
                    <h6><strong>🎯 Status Kondisi:</strong></h6>

                    <p>
                        <span class="status-indicator status-optimal"></span>
                        <strong>OPTIMAL:</strong> Kondisi lingkungan sesuai untuk mendukung pertumbuhan tanaman
                    </p>

                    <p>
                        <span class="status-indicator status-warning"></span>
                        <strong>PERHATIAN:</strong> Diperlukan penyesuaian suhu atau kelembaban
                    </p>

                    <p>
                        <span class="status-indicator status-danger"></span>
                        <strong>KRITIS:</strong> Kondisi lingkungan tidak ideal, segera lakukan tindakan koreksi
                    </p>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>