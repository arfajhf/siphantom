<?php
// ========================================
// get-data.php - Ambil Data Real-time
// ========================================

// Set header untuk JSON dan no-cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$device = $_GET['device'] ?? '';

if (empty($device)) {
    http_response_code(400);
    echo json_encode(['error' => 'Device code required']);
    exit;
}

// Validasi akses device
if (!cek_akses_device($username, $device)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Ambil data sensor terbaru dengan timestamp
$query = "SELECT * FROM sensors WHERE kode_device = '$device' ORDER BY timestamp DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);
    
    // Format response dengan data lengkap
    $response = [
        'suhu' => floatval($data['suhu']),
        'soil_moisture' => floatval($data['soil_moisture']),
        'kelembaban' => floatval($data['kelembaban']),
        'tanggal' => date('d/m/Y', strtotime($data['tanggal'])),
        'waktu' => date('H:i:s', strtotime($data['waktu'])),
        'timestamp' => $data['timestamp'],
        'device_code' => $device,
        'status' => 'success'
    ];
    
    echo json_encode($response);
} else {
    // Tidak ada data sensor
    echo json_encode([
        'suhu' => 0,
        'soil_moisture' => 0,
        'kelembaban' => 0,
        'tanggal' => '-',
        'waktu' => '-',
        'timestamp' => null,
        'device_code' => $device,
        'status' => 'no_data',
        'message' => 'No sensor data available'
    ]);
}

mysqli_close($conn);
?>