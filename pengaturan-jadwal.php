<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$nama_lengkap = $_SESSION["nama_lengkap"] ?? $username;

// Ambil device milik user (hanya yang approved)
$device_query = "SELECT * FROM devices WHERE pemilik = '$username' AND aktif = 1 AND deleted_at IS NULL AND status_approval = 'approved'";
$device_result = mysqli_query($conn, $device_query);
$devices = [];
while ($device = mysqli_fetch_assoc($device_result)) {
    $devices[] = $device;
}

// Pilih device default
$selected_device = $_GET['device'] ?? ($devices[0]['kode'] ?? null);
$device_name = '';

foreach ($devices as $device) {
    if ($device['kode'] === $selected_device) {
        $device_name = $device['nama'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Jadwal Penyiraman - SiPhantom</title>
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
            margin-bottom: 20px;
        }
        
        .device-selector {
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .schedule-card {
            border: 2px solid #e9ecef;
            border-radius: 0px;
            padding: 20px;
            margin-bottom: 5px;
            transition: all 0.3s;
            background: white;
        }
        
        .schedule-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .schedule-card.active {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        
        .schedule-card.inactive {
            border-color: #ffc107;
            background: #fff9e6;
        }
        
        .schedule-time {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .schedule-duration {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .schedule-status {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .status-inactive {
            background: #ffc107;
            color: #333;
        }
        
        .btn-schedule {
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 2px;
        }
        
        .time-input {
            font-size: 1.2rem;
            text-align: center;
            border-radius: 10px;
        }
        
        .add-schedule-btn {
            background: linear-gradient(135deg, #FF6B6B, #FF8E8E);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
            transition: all 0.3s;
        }
        
        .add-schedule-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
            color: white;
        }
        
        .log-item {
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
            margin-bottom: 10px;
        }
        
        .loading {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        /* Animasi untuk update */
        .updated {
            animation: highlight 0.5s ease-in-out;
        }

        .card-header{
            background: var(--primary);
            border-radius: 15px;
            --bs-text-opacity: 1;
        }

        .bg-green{
            --bs-text-opacity: 1;
            background: var(--secondary);
        }
        
        @keyframes highlight {
            0% { background-color: #fff3cd; }
            100% { background-color: transparent; }
        }
        
        @media (max-width: 768px) {
            .schedule-time {
                font-size: 1.5rem;
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
        <?php if (!empty($devices)): ?>
            <!-- Device Selector -->
            <div class="device-selector">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5><strong>⏰ Pengaturan Jadwal Penyiraman</strong></h5>
                        <p class="mb-0" style="opacity: 0.9;">
                            <?php echo htmlspecialchars($device_name); ?> (<?php echo $selected_device; ?>)
                        </p>
                    </div>
                    <?php if (count($devices) > 1): ?>
                    <div>
                        <select class="form-select" id="deviceSelector" style="min-width: 200px;">
                            <?php foreach ($devices as $device): ?>
                                <option value="<?php echo $device['kode']; ?>" <?php echo ($device['kode'] === $selected_device) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($device['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <!-- Jadwal Penyiraman -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">📅 Jadwal Penyiraman Aktif</h5>
                                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                    ➕ Tambah Jadwal
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="schedulesList">
                                <div class="loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div>Loading jadwal penyiraman...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log Penyiraman -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-green text-white">
                            <h5 class="mb-0">📊 Log Penyiraman Terbaru</h5>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <div id="wateringLogs">
                                <div class="loading">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div>Loading log penyiraman...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Tidak Ada Device -->
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <h4>📱 Belum Ada Device Aktif</h4>
                        <p>Anda belum memiliki device yang disetujui untuk mengatur jadwal penyiraman.</p>
                        <a href="daftar-device.php" class="btn btn-primary">Kelola Device</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Tambah Jadwal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">➕ Tambah Jadwal Penyiraman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addScheduleForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">📝 Nama Jadwal</label>
                            <input type="text" class="form-control" name="name" required placeholder="Contoh: Penyiraman Pagi">
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">🕐 Jam</label>
                                <select class="form-select time-input" name="hour" required>
                                    <?php for ($h = 0; $h < 24; $h++): ?>
                                        <option value="<?php echo $h; ?>"><?php echo sprintf('%02d', $h); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">🕐 Menit</label>
                                <select class="form-select time-input" name="minute" required>
                                    <?php for ($m = 0; $m < 60; $m += 5): ?>
                                        <option value="<?php echo $m; ?>"><?php echo sprintf('%02d', $m); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label">⏱️ Durasi Penyiraman (detik)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="duration" min="10" max="3600" value="300" required>
                                <span class="input-group-text">detik</span>
                            </div>
                            <div class="form-text">Minimum 10 detik, maksimum 3600 detik (1 jam)</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="active" id="scheduleActive" checked>
                                <label class="form-check-label" for="scheduleActive">
                                    ✅ Aktifkan jadwal ini
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">➕ Tambah Jadwal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Jadwal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Edit Jadwal Penyiraman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editScheduleForm">
                    <input type="hidden" name="schedule_id" id="editScheduleId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">📝 Nama Jadwal</label>
                            <input type="text" class="form-control" name="name" id="editScheduleName" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">🕐 Jam</label>
                                <select class="form-select time-input" name="hour" id="editScheduleHour" required>
                                    <?php for ($h = 0; $h < 24; $h++): ?>
                                        <option value="<?php echo $h; ?>"><?php echo sprintf('%02d', $h); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">🕐 Menit</label>
                                <select class="form-select time-input" name="minute" id="editScheduleMinute" required>
                                    <?php for ($m = 0; $m < 60; $m += 5): ?>
                                        <option value="<?php echo $m; ?>"><?php echo sprintf('%02d', $m); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label">⏱️ Durasi Penyiraman (detik)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="duration" id="editScheduleDuration" min="10" max="3600" required>
                                <span class="input-group-text">detik</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="active" id="editScheduleActive">
                                <label class="form-check-label" for="editScheduleActive">
                                    ✅ Aktifkan jadwal ini
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">💾 Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const selectedDevice = '<?php echo $selected_device; ?>';
        let schedules = [];
        let logs = [];

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadSchedules();
            loadWateringLogs();
            
            // Auto refresh every 30 seconds
            setInterval(() => {
                loadSchedules();
                loadWateringLogs();
            }, 30000);
        });

        // Device selector change
        document.getElementById('deviceSelector')?.addEventListener('change', function() {
            window.location.href = `pengaturan-jadwal.php?device=${this.value}`;
        });

        // Load schedules
        function loadSchedules() {
            if (!selectedDevice) return;
            
            fetch(`schedule-api.php?action=get_schedules&device_kode=${selectedDevice}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        schedules = data.schedules;
                        renderSchedules();
                    } else {
                        showError('Gagal memuat jadwal: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading schedules:', error);
                    showError('Error loading schedules');
                });
        }

        // Render schedules list
        function renderSchedules() {
            const container = document.getElementById('schedulesList');
            
            if (schedules.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <h6>⏰ Belum Ada Jadwal</h6>
                        <p>Klik "Tambah Jadwal" untuk membuat jadwal penyiraman pertama Anda.</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            schedules.forEach(schedule => {
                const timeStr = String(schedule.hour).padStart(2, '0') + ':' + String(schedule.minute).padStart(2, '0');
                const durationMin = Math.floor(schedule.duration / 60);
                const durationSec = schedule.duration % 60;
                const durationStr = durationMin > 0 ? `${durationMin}m ${durationSec}s` : `${durationSec}s`;
                
                html += `
                    <div class="schedule-card ${schedule.active ? 'active' : 'inactive'}" data-schedule-id="${schedule.id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-2">
                                    ${schedule.name}
                                    <span class="schedule-status ${schedule.active ? 'status-active' : 'status-inactive'}">
                                        ${schedule.active ? 'AKTIF' : 'NONAKTIF'}
                                    </span>
                                </h6>
                                <div class="schedule-time mb-2">${timeStr}</div>
                                <div class="mb-2">
                                    <span class="schedule-duration">⏱️ ${durationStr}</span>
                                </div>
                                <small class="text-muted">
                                    Dibuat: ${new Date(schedule.created_at).toLocaleDateString('id-ID')}
                                </small>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-sm btn-outline-primary btn-schedule" onclick="editSchedule(${schedule.id})">
                                    ✏️ Edit
                                </button>
                                <button class="btn btn-sm btn-outline-${schedule.active ? 'warning' : 'success'} btn-schedule" onclick="toggleSchedule(${schedule.id})">
                                    ${schedule.active ? '⏸️ Nonaktif' : '▶️ Aktif'}
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-schedule" onclick="deleteSchedule(${schedule.id})">
                                    🗑️ Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Load watering logs
        function loadWateringLogs() {
            if (!selectedDevice) return;
            
            fetch(`schedule-api.php?action=get_watering_logs&device_kode=${selectedDevice}&limit=10`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        logs = data.logs;
                        renderLogs();
                    } else {
                        document.getElementById('wateringLogs').innerHTML = `
                            <div class="empty-state">
                                <h6>📊 Belum Ada Log</h6>
                                <p>Log penyiraman akan muncul setelah jadwal dijalankan.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading logs:', error);
                });
        }

        // Render logs
        function renderLogs() {
            const container = document.getElementById('wateringLogs');
            
            if (logs.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <h6>📊 Belum Ada Log</h6>
                        <p>Log penyiraman akan muncul setelah jadwal dijalankan.</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            logs.forEach(log => {
                const executedAt = new Date(log.executed_at);
                const timeStr = executedAt.toLocaleString('id-ID');
                const durationMin = Math.floor(log.duration / 60);
                const durationStr = durationMin > 0 ? `${durationMin} menit` : `${log.duration} detik`;
                
                html += `
                    <div class="log-item">
                        <div class="fw-bold">${log.schedule_name}</div>
                        <div class="text-muted small">⏱️ ${durationStr}</div>
                        <div class="text-muted small">📅 ${timeStr}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Add schedule form
        document.getElementById('addScheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_schedule');
            formData.append('device_kode', selectedDevice);
            
            fetch('schedule-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Jadwal berhasil ditambahkan!');
                    bootstrap.Modal.getInstance(document.getElementById('addScheduleModal')).hide();
                    this.reset();
                    loadSchedules();
                } else {
                    showError('Gagal menambah jadwal: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error adding schedule:', error);
                showError('Error adding schedule');
            });
        });

        // Edit schedule
        function editSchedule(scheduleId) {
            const schedule = schedules.find(s => s.id === scheduleId);
            if (!schedule) return;
            
            document.getElementById('editScheduleId').value = schedule.id;
            document.getElementById('editScheduleName').value = schedule.name;
            document.getElementById('editScheduleHour').value = schedule.hour;
            document.getElementById('editScheduleMinute').value = schedule.minute;
            document.getElementById('editScheduleDuration').value = schedule.duration;
            document.getElementById('editScheduleActive').checked = schedule.active;
            
            new bootstrap.Modal(document.getElementById('editScheduleModal')).show();
        }

        // Edit schedule form
        document.getElementById('editScheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_schedule');
            
            fetch('schedule-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Jadwal berhasil diupdate!');
                    bootstrap.Modal.getInstance(document.getElementById('editScheduleModal')).hide();
                    loadSchedules();
                } else {
                    showError('Gagal update jadwal: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error updating schedule:', error);
                showError('Error updating schedule');
            });
        });

        // Toggle schedule
        function toggleSchedule(scheduleId) {
            const formData = new FormData();
            formData.append('action', 'toggle_schedule');
            formData.append('schedule_id', scheduleId);
            
            fetch('schedule-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Status jadwal berhasil diubah!');
                    loadSchedules();
                } else {
                    showError('Gagal mengubah status: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error toggling schedule:', error);
                showError('Error toggling schedule');
            });
        }

        // Delete schedule
        function deleteSchedule(scheduleId) {
            const schedule = schedules.find(s => s.id === scheduleId);
            if (!schedule) return;
            
            if (!confirm(`Yakin ingin menghapus jadwal "${schedule.name}"?`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_schedule');
            formData.append('schedule_id', scheduleId);
            
            fetch('schedule-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Jadwal berhasil dihapus!');
                    loadSchedules();
                } else {
                    showError('Gagal menghapus jadwal: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting schedule:', error);
                showError('Error deleting schedule');
            });
        }

        // Utility functions
        function showSuccess(message) {
            // Create and show success alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <strong>✅ Berhasil!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        function showError(message) {
            // Create and show error alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <strong>❌ Error!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
    </script>
</body>
</html>