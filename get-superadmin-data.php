<?php
// ========================================
// get-superadmin-data.php - Real-time Superadmin Data
// ========================================

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

// Cek apakah user adalah superadmin
if (!is_superadmin($username)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    // Get statistik sistem terbaru
    $stats = get_system_stats();
    
    // Get status pompa semua device
    $pump_query = "
        SELECT d.kode as device_kode, d.nama as device_nama, 
               r.status as pump_status, d.pemilik,
               u.nama_lengkap as pemilik_nama
        FROM devices d 
        LEFT JOIN relays r ON d.kode = r.kode_device 
        LEFT JOIN users u ON d.pemilik = u.username
        WHERE d.deleted_at IS NULL AND d.aktif = 1 AND d.status_approval = 'approved'
        ORDER BY d.nama ASC
    ";
    
    $pump_result = mysqli_query($conn, $pump_query);
    $pumps = [];
    
    if ($pump_result) {
        while ($pump = mysqli_fetch_assoc($pump_result)) {
            $pumps[] = [
                'device_kode' => $pump['device_kode'],
                'device_nama' => $pump['device_nama'],
                'pump_status' => intval($pump['pump_status'] ?? 0),
                'pemilik' => $pump['pemilik'],
                'pemilik_nama' => $pump['pemilik_nama'] ?? $pump['pemilik']
            ];
        }
    }
    
    // Get device status terbaru
    $device_query = "
        SELECT d.kode, d.aktif, 
               (SELECT COUNT(*) FROM sensors s WHERE s.kode_device = d.kode AND DATE(s.timestamp) = CURDATE()) as today_data,
               (SELECT MAX(s.timestamp) FROM sensors s WHERE s.kode_device = d.kode) as last_data
        FROM devices d 
        WHERE d.deleted_at IS NULL AND d.status_approval = 'approved'
    ";
    
    $device_result = mysqli_query($conn, $device_query);
    $devices_status = [];
    
    if ($device_result) {
        while ($device = mysqli_fetch_assoc($device_result)) {
            $devices_status[$device['kode']] = [
                'aktif' => intval($device['aktif']),
                'today_data' => intval($device['today_data']),
                'last_data' => $device['last_data'],
                'is_online' => $device['last_data'] && (strtotime($device['last_data']) > (time() - 300)) // 5 menit terakhir
            ];
        }
    }
    
    // Get pending approvals count
    $pending_count_query = "SELECT COUNT(*) as count FROM device_approvals WHERE status = 'pending'";
    $pending_result = mysqli_query($conn, $pending_count_query);
    $pending_count = 0;
    if ($pending_result) {
        $pending_row = mysqli_fetch_assoc($pending_result);
        $pending_count = intval($pending_row['count']);
    }
    
    // Response data
    $response = [
        'stats' => $stats,
        'pumps' => $pumps,
        'devices_status' => $devices_status,
        'pending_count' => $pending_count,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'success'
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'status' => 'error'
    ]);
}

mysqli_close($conn);
?>