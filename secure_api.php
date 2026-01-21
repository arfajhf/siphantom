<?php 
require 'koneksi.php';

ini_set('date.timezone', 'Asia/Jakarta');
header('Content-Type: application/json');

$now = new DateTime();
$datenow = $now->format("Y-m-d H:i:s");

// Cek apakah tabel devices sudah ada
$check_devices = "SHOW TABLES LIKE 'devices'";
$devices_exists = mysqli_query($conn, $check_devices);
$has_device_system = mysqli_num_rows($devices_exists) > 0;

// ========================================
// KOMPATIBILITAS DENGAN SISTEM LAMA
// ========================================

// Format postdemo.php (status1, status2, pelanggan)
if (isset($_POST['status1']) && isset($_POST['status2']) && isset($_POST['pelanggan'])) {
    $seminggu = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
    $hari = date("w");
    $hari_ini = $seminggu[$hari];
    $tgl_sekarang = date("ymd");
    $jam_sekarang = date("H:i:s");
    
    $status1 = floatval($_POST['status1']); // suhu
    $status2 = floatval($_POST['status2']); // kelembaban
    $pelanggan = mysqli_real_escape_string($conn, $_POST['pelanggan']);
    
    // Jika ada sistem device, cari device token
    $device_token = null;
    if ($has_device_system) {
        $device_query = "SELECT device_token FROM devices WHERE owner_username = ? AND is_active = 1 LIMIT 1";
        $device_stmt = mysqli_prepare($conn, $device_query);
        if ($device_stmt) {
            mysqli_stmt_bind_param($device_stmt, "s", $pelanggan);
            mysqli_stmt_execute($device_stmt);
            $device_result = mysqli_stmt_get_result($device_stmt);
            if ($device_row = mysqli_fetch_array($device_result)) {
                $device_token = $device_row['device_token'];
            }
        }
    }
    
    // Insert ke logs (struktur lama)
    if ($has_device_system && $device_token) {
        $sql_logs = "INSERT INTO logs (tanggal, hari, waktu, pelanggan, suhu, kelembapan, device_token)
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_logs = mysqli_prepare($conn, $sql_logs);
        mysqli_stmt_bind_param($stmt_logs, "ssssdds", $tgl_sekarang, $hari_ini, $jam_sekarang, $pelanggan, $status1, $status2, $device_token);
    } else {
        $sql_logs = "INSERT INTO logs (tanggal, hari, waktu, pelanggan, suhu, kelembapan)
                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_logs = mysqli_prepare($conn, $sql_logs);
        mysqli_stmt_bind_param($stmt_logs, "ssssdd", $tgl_sekarang, $hari_ini, $jam_sekarang, $pelanggan, $status1, $status2);
    }
    
    if ($stmt_logs && mysqli_stmt_execute($stmt_logs)) {
        // Juga simpan ke sensor jika ada
        if ($has_device_system) {
            $sql_sensor = "INSERT INTO sensor (suhu, kelembaban, timestamp, device_token) 
                           VALUES (?, ?, ?, ?)";
            $stmt_sensor = mysqli_prepare($conn, $sql_sensor);
            if ($stmt_sensor) {
                mysqli_stmt_bind_param($stmt_sensor, "ddss", $status1, $status2, $datenow, $device_token);
                mysqli_stmt_execute($stmt_sensor);
            }
        }
        
        echo json_encode([
            "status" => "success",
            "message" => "OK",
            "format" => "postdemo",
            "device_token" => $device_token,
            "pelanggan" => $pelanggan
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Error: " . mysqli_error($conn)
        ]);
    }
}

// Format api.php (tegangan, arus, suhu, kelembaban)
elseif (isset($_POST['tegangan']) && isset($_POST['arus']) && isset($_POST['suhu']) && isset($_POST['kelembaban'])) {
    $tegangan = floatval($_POST['tegangan']);
    $arus = floatval($_POST['arus']);
    $suhu = floatval($_POST['suhu']);
    $kelembaban = floatval($_POST['kelembaban']);
    
    // Cek apakah ada pelanggan atau device_token
    $device_token = $_POST['device_token'] ?? null;
    $pelanggan = $_POST['pelanggan'] ?? 'default';
    
    // Jika tidak ada device_token, cari berdasarkan pelanggan
    if (!$device_token && $has_device_system) {
        $device_query = "SELECT device_token FROM devices WHERE owner_username = ? AND is_active = 1 LIMIT 1";
        $device_stmt = mysqli_prepare($conn, $device_query);
        if ($device_stmt) {
            mysqli_stmt_bind_param($device_stmt, "s", $pelanggan);
            mysqli_stmt_execute($device_stmt);
            $device_result = mysqli_stmt_get_result($device_stmt);
            if ($device_row = mysqli_fetch_array($device_result)) {
                $device_token = $device_row['device_token'];
            }
        }
    }
    
    // Insert ke sensor
    if ($has_device_system) {
        $sql_sensor = "INSERT INTO sensor (tegangan, arus, suhu, kelembaban, timestamp, device_token) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_sensor = mysqli_prepare($conn, $sql_sensor);
        mysqli_stmt_bind_param($stmt_sensor, "ddddss", $tegangan, $arus, $suhu, $kelembaban, $datenow, $device_token);
    } else {
        $sql_sensor = "INSERT INTO sensor (tegangan, arus, suhu, kelembaban, timestamp) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_sensor = mysqli_prepare($conn, $sql_sensor);
        mysqli_stmt_bind_param($stmt_sensor, "dddds", $tegangan, $arus, $suhu, $kelembaban, $datenow);
    }
    
    if ($stmt_sensor && mysqli_stmt_execute($stmt_sensor)) {
        echo json_encode([
            "status" => "success",
            "message" => "Data saved successfully",
            "format" => "api",
            "device_token" => $device_token,
            "timestamp" => $datenow
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Failed to save data: " . mysqli_error($conn)
        ]);
    }
}

// ========================================
// FORMAT BARU DENGAN DEVICE TOKEN
// ========================================

// Format baru dengan device token
elseif (isset($_POST['device_token']) && isset($_POST['suhu']) && isset($_POST['kelembaban'])) {
    $device_token = mysqli_real_escape_string($conn, $_POST['device_token']);
    $suhu = floatval($_POST['suhu']);
    $kelembaban = floatval($_POST['kelembaban']);
    $tegangan = isset($_POST['tegangan']) ? floatval($_POST['tegangan']) : null;
    $arus = isset($_POST['arus']) ? floatval($_POST['arus']) : null;
    
    if ($has_device_system) {
        // Validasi device token
        $device_check = "SELECT device_name, owner_username FROM devices WHERE device_token = ? AND is_active = 1";
        $check_stmt = mysqli_prepare($conn, $device_check);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $device_token);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if ($device_info = mysqli_fetch_array($check_result)) {
                // Insert ke sensor
                $sql_sensor = "INSERT INTO sensor (tegangan, arus, suhu, kelembaban, timestamp, device_token) 
                               VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_sensor = mysqli_prepare($conn, $sql_sensor);
                
                if ($stmt_sensor) {
                    mysqli_stmt_bind_param($stmt_sensor, "ddddss", $tegangan, $arus, $suhu, $kelembaban, $datenow, $device_token);
                    
                    if (mysqli_stmt_execute($stmt_sensor)) {
                        // Juga simpan ke logs untuk kompatibilitas
                        $seminggu = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
                        $hari = date("w");
                        $hari_ini = $seminggu[$hari];
                        $tgl_sekarang = date("ymd");
                        $jam_sekarang = date("H:i:s");
                        
                        $sql_logs = "INSERT INTO logs (tanggal, hari, waktu, pelanggan, suhu, kelembapan, device_token)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt_logs = mysqli_prepare($conn, $sql_logs);
                        if ($stmt_logs) {
                            mysqli_stmt_bind_param($stmt_logs, "ssssdds", $tgl_sekarang, $hari_ini, $jam_sekarang, $device_info['owner_username'], $suhu, $kelembaban, $device_token);
                            mysqli_stmt_execute($stmt_logs);
                        }
                        
                        // Update last activity device
                        $update_device = "UPDATE devices SET last_activity = NOW() WHERE device_token = ?";
                        $update_stmt = mysqli_prepare($conn, $update_device);
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, "s", $device_token);
                            mysqli_stmt_execute($update_stmt);
                        }
                        
                        echo json_encode([
                            "status" => "success",
                            "message" => "Data received successfully",
                            "format" => "secure",
                            "device_name" => $device_info['device_name'],
                            "device_token" => $device_token,
                            "timestamp" => $datenow
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode([
                            "status" => "error",
                            "message" => "Failed to save data"
                        ]);
                    }
                }
            } else {
                http_response_code(403);
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid or inactive device token"
                ]);
            }
        }
    } else {
        // Fallback jika belum ada sistem device
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Device system not initialized. Please register devices first."
        ]);
    }
}

// ========================================
// API UNTUK MEMBACA STATUS RELAY
// ========================================

// Membaca status relay (GET request)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $device_token = $_GET['device_token'] ?? null;
    
    if ($device_token && $has_device_system) {
        // Relay berdasarkan device token
        $relay_query = "SELECT r.relayy FROM relay r 
                        JOIN devices d ON r.device_token = d.device_token 
                        WHERE r.device_token = ? AND d.is_active = 1";
        $relay_stmt = mysqli_prepare($conn, $relay_query);
        
        if ($relay_stmt) {
            mysqli_stmt_bind_param($relay_stmt, "s", $device_token);
            mysqli_stmt_execute($relay_stmt);
            $relay_result = mysqli_stmt_get_result($relay_stmt);
            
            if ($relay_data = mysqli_fetch_array($relay_result)) {
                echo $relay_data['relayy']; // Format sesuai bacarelay.php
            } else {
                echo "No data";
            }
        } else {
            echo "Error: Database error";
        }
    } else {
        // Fallback: ambil relay pertama (backward compatibility)
        $relay_query = "SELECT relayy FROM relay ORDER BY id LIMIT 1";
        $relay_result = mysqli_query($conn, $relay_query);
        
        if ($relay_result && $relay_data = mysqli_fetch_array($relay_result)) {
            echo $relay_data['relayy'];
        } else {
            echo "No data";
        }
    }
}

// ========================================
// ERROR HANDLING
// ========================================

else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request parameters",
        "supported_formats" => [
            "Legacy Format 1 (postdemo.php)" => "POST: status1, status2, pelanggan",
            "Legacy Format 2 (api.php)" => "POST: tegangan, arus, suhu, kelembaban, [pelanggan]",
            "New Secure Format" => "POST: device_token, suhu, kelembaban, [tegangan, arus]",
            "Relay Status" => "GET: [device_token] (optional)"
        ],
        "device_system_active" => $has_device_system
    ]);
}

$conn->close();
?>