<?php
require 'koneksi.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

// Cek action untuk fitur baru
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle fitur scheduling baru
if (!empty($action)) {
    handleSchedulingActions($conn, $action);
}

// Terima data dari ESP8266 (POST)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = $_POST['kode'] ?? '';
    $suhu = $_POST['suhu'] ?? '';
    $soil_moisture = $_POST['soil_moisture'] ?? '';
    $kelembaban = $_POST['kelembaban'] ?? '';
    $username = $_POST['username'] ?? '';
    
    // Parameter baru untuk RTC
    $relay_status = $_POST['relay_status'] ?? 0;
    $relay_mode = $_POST['relay_mode'] ?? 'manual';
    $rtc_time = $_POST['rtc_time'] ?? null;
    
    // Validasi data
    if (empty($kode) || empty($suhu) || empty($soil_moisture) || empty($kelembaban) || empty($username)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak lengkap',
            'required' => ['kode', 'suhu', 'kelembaban', 'soil_moisture', 'username']
        ]);
        exit;
    }
    
    // Validasi device dan akses
    if (!cek_akses_device($username, $kode)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Device tidak ditemukan atau akses ditolak'
        ]);
        exit;
    }
    
    // Siapkan data untuk disimpan
    $suhu = floatval($suhu);
    $soil_moisture = floatval($soil_moisture);
    $kelembaban = floatval($kelembaban);
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    $timestamp = date('Y-m-d H:i:s');
    
    // Simpan data sensor dengan parameter baru
    $rtc_time_sql = $rtc_time ? "'$rtc_time'" : 'NULL';
    $query = "INSERT INTO sensors (kode_device, suhu, soil_moisture, kelembaban, tanggal, waktu, timestamp, relay_mode, rtc_time) 
              VALUES ('$kode', $suhu, $soil_moisture, $kelembaban, '$tanggal', '$waktu', '$timestamp', '$relay_mode', $rtc_time_sql)";
    
    if (mysqli_query($conn, $query)) {
        // Update device last seen
        updateDeviceLastSeen($conn, $kode);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil disimpan',
            'data' => [
                'kode' => $kode,
                'suhu' => $suhu,
                'soil_moisture' => $soil_moisture,
                'kelembaban' => $kelembaban,
                'relay_status' => intval($relay_status),
                'relay_mode' => $relay_mode,
                'rtc_time' => $rtc_time,
                'timestamp' => $timestamp
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menyimpan data',
            'error' => mysqli_error($conn)
        ]);
    }
}

// Baca status relay (GET request)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $kode = $_GET['kode'] ?? '';
    
    if (empty($kode)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Kode device diperlukan'
        ]);
        exit;
    }
    
    // Ambil status relay
    $query = "SELECT status FROM relays WHERE kode_device = '$kode'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo $row['status']; // Format sederhana untuk ESP8266 (backward compatibility)
    } else {
        echo "0"; // Default OFF jika tidak ditemukan
    }
}

// Method tidak didukung
else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method tidak didukung',
        'supported' => ['POST untuk kirim data', 'GET untuk baca relay']
    ]);
}

mysqli_close($conn);

// ================================================
// FUNGSI UNTUK HANDLE SCHEDULING ACTIONS
// ================================================

function handleSchedulingActions($conn, $action) {
    switch ($action) {
        case 'get_schedules':
            getSchedulesForESP($conn);
            break;
            
        case 'log_watering':
            logWateringFromESP($conn);
            break;
            
        case 'get_relay_status':
            getRelayStatusJSON($conn);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Action tidak dikenal',
                'available_actions' => ['get_schedules', 'log_watering', 'get_relay_status']
            ]);
            break;
    }
    exit;
}

function getSchedulesForESP($conn) {
    $kode = $_GET['kode'] ?? '';
    $username = $_GET['username'] ?? '';
    
    if (empty($kode) || empty($username)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Kode device dan username diperlukan'
        ]);
        return;
    }
    
    // Validasi akses
    if (!cek_akses_device($username, $kode)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses ditolak'
        ]);
        return;
    }
    
    // Ambil jadwal aktif
    $query = "SELECT id, name, hour, minute, duration, active 
              FROM watering_schedules 
              WHERE device_kode = '$kode' AND active = 1 
              ORDER BY hour ASC, minute ASC";
    $result = mysqli_query($conn, $query);
    
    $schedules = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = [
                'id' => intval($row['id']),
                'name' => $row['name'],
                'hour' => intval($row['hour']),
                'minute' => intval($row['minute']),
                'duration' => intval($row['duration']),
                'active' => boolval($row['active'])
            ];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'schedules' => $schedules,
        'device_code' => $kode,
        'count' => count($schedules),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function logWateringFromESP($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'POST method required']);
        return;
    }
    
    $kode = $_POST['kode'] ?? '';
    $username = $_POST['username'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? null;
    $schedule_name = $_POST['schedule_name'] ?? '';
    $duration = $_POST['duration'] ?? 0;
    $executed_at = $_POST['executed_at'] ?? date('Y-m-d H:i:s');
    
    if (empty($kode) || empty($username) || empty($schedule_name) || $duration <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak lengkap',
            'required' => ['kode', 'username', 'schedule_name', 'duration']
        ]);
        return;
    }
    
    // Validasi akses
    if (!cek_akses_device($username, $kode)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses ditolak'
        ]);
        return;
    }
    
    // Insert log penyiraman
    $schedule_id_sql = $schedule_id ? "'$schedule_id'" : 'NULL';
    $query = "INSERT INTO watering_logs (device_kode, schedule_id, schedule_name, duration, executed_at) 
              VALUES ('$kode', $schedule_id_sql, '$schedule_name', '$duration', '$executed_at')";
    
    if (mysqli_query($conn, $query)) {
        $log_id = mysqli_insert_id($conn);
        
        // Log activity jika fungsi tersedia
        if (function_exists('log_activity')) {
            log_activity($username, 'AUTO_WATERING', $kode, "Automatic watering: $schedule_name for {$duration}s");
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Log penyiraman berhasil disimpan',
            'log_id' => $log_id,
            'device_code' => $kode,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menyimpan log',
            'error' => mysqli_error($conn)
        ]);
    }
}

function getRelayStatusJSON($conn) {
    $kode = $_GET['kode'] ?? '';
    
    if (empty($kode)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Kode device diperlukan'
        ]);
        return;
    }
    
    // Ambil status relay
    $query = "SELECT status FROM relays WHERE kode_device = '$kode'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode([
            'status' => 'success',
            'relay_status' => intval($row['status']),
            'device_code' => $kode,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // Buat record relay baru jika belum ada
        $insert_query = "INSERT INTO relays (kode_device, status) VALUES ('$kode', 0) ON DUPLICATE KEY UPDATE status = status";
        mysqli_query($conn, $insert_query);
        
        echo json_encode([
            'status' => 'success',
            'relay_status' => 0,
            'device_code' => $kode,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function updateDeviceLastSeen($conn, $device_code) {
    $timestamp = date('Y-m-d H:i:s');
    $query = "UPDATE devices SET last_seen = '$timestamp' WHERE kode = '$device_code'";
    mysqli_query($conn, $query);
}

?>