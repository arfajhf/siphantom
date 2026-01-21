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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips - SiPhantom</title>
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
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .card-header h5 {
            font-weight: 600;
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

        @media (max-width: 768px) {
            .data-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Style khusus rekomendasi.php */
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
            background: linear-gradient(135deg, #39bbb56a 0%, #2dc36c6c 100%);
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
        <!-- Rekomendasi Kondisi Optimal -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">🌡️ Kondisi Optimal Kumbung Jamur Tiram</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="optimal-range">
                            <h6><strong>🌡️ Suhu Optimal</strong></h6>
                            <p class="mb-2"><strong>24°C - 28°C</strong></p>
                            <small>
                                • Suhu ideal untuk pertumbuhan miselium<br>
                                • Hindari fluktuasi suhu yang ekstrem<br>
                                • Gunakan ventilasi untuk kontrol suhu
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="optimal-range">
                            <h6><strong>💧 Kelembaban Optimal</strong></h6>
                            <p class="mb-2"><strong>80% - 90%</strong></p>
                            <small>
                                • Kelembaban tinggi untuk pertumbuhan<br>
                                • Gunakan humidifier jika perlu<br>
                                • Pastikan sirkulasi udara baik
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Penyiraman -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">💦 Rekomendasi Jadwal Penyiraman</h5>
            </div>
            <div class="card-body">
                <div class="watering-schedule">
                    <h6><strong>⏰ Waktu Penyiraman Optimal:</strong></h6>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>🌅 Pagi:</strong> 08:00 - 09:00</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>🌞 Siang:</strong> 12:00 - 13:00</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>🌙 Sore:</strong> 16:00 - 17:00</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>