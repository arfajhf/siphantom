<?php
// ========================================
// helper-functions.php - Fungsi Pendukung SIMACMUR
// Include file ini di awal setiap file PHP yang memerlukan
// ========================================

// Include di file yang memerlukan:
// require_once 'helper-functions.php';

/**
 * Cek akses device untuk user tertentu
 * Kompatibel dengan struktur database yang sudah ada
 */
function cek_akses_device($username, $device_kode) {
    global $conn;
    
    $query = "SELECT * FROM devices 
              WHERE kode = '$device_kode' 
              AND pemilik = '$username' 
              AND aktif = 1 
              AND deleted_at IS NULL 
              AND status_approval = 'approved'";
    
    $result = mysqli_query($conn, $query);
    return $result && mysqli_num_rows($result) > 0;
}

/**
 * Log aktivitas sistem
 * Kompatibel dengan struktur activity_logs yang sudah ada
 */
function log_activity($username, $action, $target = null, $description = null) {
    global $conn;
    
    $target_sql = $target ? "'$target'" : 'NULL';
    $description_sql = $description ? "'$description'" : 'NULL';
    
    $query = "INSERT INTO activity_logs (user, username, action, target, details, description, timestamp) 
              VALUES ('$username', '$username', '$action', $target_sql, $description_sql, $description_sql, NOW())";
    
    return mysqli_query($conn, $query);
}

/**
 * Cek apakah user adalah superadmin
 */
function is_superadmin($username) {
    global $conn;
    
    $query = "SELECT role FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['role'] === 'superadmin';
    }
    
    return false;
}

/**
 * Get statistik sistem untuk dashboard superadmin
 */
function get_system_stats() {
    global $conn;
    
    $stats = [];
    
    // Total users
    $query = "SELECT COUNT(*) as count FROM users";
    $result = mysqli_query($conn, $query);
    $stats['total_users'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    
    // Total devices aktif
    $query = "SELECT COUNT(*) as count FROM devices WHERE aktif = 1 AND deleted_at IS NULL AND status_approval = 'approved'";
    $result = mysqli_query($conn, $query);
    $stats['total_devices'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    
    // Devices dihapus
    $query = "SELECT COUNT(*) as count FROM devices WHERE deleted_at IS NOT NULL";
    $result = mysqli_query($conn, $query);
    $stats['deleted_devices'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    
    // Data hari ini
    $query = "SELECT COUNT(*) as count FROM sensors WHERE DATE(tanggal) = CURDATE()";
    $result = mysqli_query($conn, $query);
    $stats['today_data'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    
    // Pompa aktif (relay status = 1)
    $query = "SELECT COUNT(*) as count FROM relays WHERE status = 1";
    $result = mysqli_query($conn, $query);
    $stats['active_relays'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    
    // Pending approvals
    $query = "SELECT COUNT(*) as count FROM approval_requests WHERE status = 'pending'";
    $result = mysqli_query($conn, $query);
    $stats['pending_count'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    
    return $stats;
}

/**
 * Get semua users
 */
function get_all_users() {
    global $conn;
    
    $query = "SELECT * FROM users ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    
    $users = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    
    return $users;
}

/**
 * Get semua devices
 */
function get_all_devices($include_deleted = false) {
    global $conn;
    
    $where_clause = $include_deleted ? "" : "WHERE deleted_at IS NULL";
    
    $query = "SELECT d.*, u.nama_lengkap as pemilik_nama 
              FROM devices d 
              LEFT JOIN users u ON d.pemilik = u.username 
              $where_clause
              ORDER BY d.dibuat DESC";
    
    $result = mysqli_query($conn, $query);
    
    $devices = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $devices[] = $row;
        }
    }
    
    return $devices;
}

/**
 * Get pending approvals
 */
function get_pending_approvals() {
    global $conn;
    
    $query = "SELECT ar.*, u.nama_lengkap as requester_name 
              FROM approval_requests ar 
              LEFT JOIN users u ON ar.requester = u.username 
              WHERE ar.status = 'pending' 
              ORDER BY ar.created_at ASC";
    
    $result = mysqli_query($conn, $query);
    
    $approvals = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Tambah info device
            $row['device_kode'] = $row['device_kode'];
            $row['device_nama'] = $row['device_nama'];
            $approvals[] = $row;
        }
    }
    
    return $approvals;
}

/**
 * Approve device
 */
function approve_device($device_kode, $approved_by) {
    global $conn;
    
    mysqli_begin_transaction($conn);
    
    try {
        // Update status approval di tabel devices
        $query1 = "UPDATE devices 
                   SET status_approval = 'approved', 
                       approved_by = '$approved_by', 
                       approved_at = NOW() 
                   WHERE kode = '$device_kode'";
        
        if (!mysqli_query($conn, $query1)) {
            throw new Exception("Failed to update device status");
        }
        
        // Update status di approval_requests
        $query2 = "UPDATE approval_requests 
                   SET status = 'approved', 
                       processed_by = '$approved_by', 
                       processed_at = NOW() 
                   WHERE device_kode = '$device_kode' AND status = 'pending'";
        
        if (!mysqli_query($conn, $query2)) {
            throw new Exception("Failed to update approval request");
        }
        
        // Log activity
        log_activity($approved_by, 'APPROVE_DEVICE', $device_kode, "Device approved");
        
        mysqli_commit($conn);
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error approving device: " . $e->getMessage());
        return false;
    }
}

/**
 * Reject device
 */
function reject_device($device_kode, $rejected_by, $reason = '') {
    global $conn;
    
    mysqli_begin_transaction($conn);
    
    try {
        // Update status approval di tabel devices
        $query1 = "UPDATE devices 
                   SET status_approval = 'rejected', 
                       approved_by = '$rejected_by', 
                       approved_at = NOW(),
                       rejection_reason = '$reason' 
                   WHERE kode = '$device_kode'";
        
        if (!mysqli_query($conn, $query1)) {
            throw new Exception("Failed to update device status");
        }
        
        // Update status di approval_requests
        $query2 = "UPDATE approval_requests 
                   SET status = 'rejected', 
                       processed_by = '$rejected_by', 
                       processed_at = NOW(),
                       response_message = '$reason' 
                   WHERE device_kode = '$device_kode' AND status = 'pending'";
        
        if (!mysqli_query($conn, $query2)) {
            throw new Exception("Failed to update approval request");
        }
        
        // Log activity
        log_activity($rejected_by, 'REJECT_DEVICE', $device_kode, "Device rejected: $reason");
        
        mysqli_commit($conn);
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error rejecting device: " . $e->getMessage());
        return false;
    }
}

/**
 * Hapus device (soft delete)
 */
function hapus_device($device_kode, $deleted_by) {
    global $conn;
    
    $query = "UPDATE devices 
              SET deleted_at = NOW(), 
                  deleted_by = '$deleted_by',
                  aktif = 0 
              WHERE kode = '$device_kode'";
    
    if (mysqli_query($conn, $query)) {
        log_activity($deleted_by, 'DELETE_DEVICE', $device_kode, "Device deleted");
        return true;
    }
    
    return false;
}

/**
 * Restore device
 */
function restore_device($device_kode, $restored_by) {
    global $conn;
    
    $query = "UPDATE devices 
              SET deleted_at = NULL, 
                  deleted_by = NULL,
                  aktif = 1 
              WHERE kode = '$device_kode'";
    
    if (mysqli_query($conn, $query)) {
        log_activity($restored_by, 'RESTORE_DEVICE', $device_kode, "Device restored");
        return true;
    }
    
    return false;
}

/**
 * User delete device (untuk user biasa)
 */
function user_delete_device($device_kode, $username, $reason = '') {
    global $conn;
    
    // Cek akses dulu
    if (!cek_akses_device($username, $device_kode)) {
        return false;
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Soft delete device
        $query1 = "UPDATE devices 
                   SET deleted_at = NOW(), 
                       deleted_by = '$username',
                       aktif = 0 
                   WHERE kode = '$device_kode' AND pemilik = '$username'";
        
        if (!mysqli_query($conn, $query1)) {
            throw new Exception("Failed to delete device");
        }
        
        // Insert ke user_deleted_devices untuk tracking
        $device_query = "SELECT nama FROM devices WHERE kode = '$device_kode'";
        $device_result = mysqli_query($conn, $device_query);
        $device_name = '';
        
        if ($device_result && mysqli_num_rows($device_result) > 0) {
            $device_row = mysqli_fetch_assoc($device_result);
            $device_name = $device_row['nama'];
        }
        
        $query2 = "INSERT INTO user_deleted_devices (device_kode, device_nama, original_owner, deleted_by, deletion_reason) 
                   VALUES ('$device_kode', '$device_name', '$username', '$username', '$reason')";
        
        if (!mysqli_query($conn, $query2)) {
            throw new Exception("Failed to log user deletion");
        }
        
        // Log activity
        log_activity($username, 'USER_DELETE_DEVICE', $device_kode, "User deleted device: $reason");
        
        mysqli_commit($conn);
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error in user_delete_device: " . $e->getMessage());
        return false;
    }
}

/**
 * Update device last seen timestamp
 */
function update_device_last_seen($device_kode) {
    global $conn;
    
    $timestamp = date('Y-m-d H:i:s');
    $query = "UPDATE devices SET last_seen = '$timestamp' WHERE kode = '$device_kode'";
    
    return mysqli_query($conn, $query);
}

/**
 * Format waktu relatif (contoh: "2 menit yang lalu")
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Validasi input untuk keamanan
 */
function sanitize_input($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}

/**
 * Generate kode device unik
 */
function generate_device_code($prefix = 'JAMUR') {
    $random = mt_rand(100, 999);
    return $prefix . $random;
}

/**
 * Cek apakah device code sudah ada
 */
function device_code_exists($device_kode) {
    global $conn;
    
    $query = "SELECT id FROM devices WHERE kode = '$device_kode'";
    $result = mysqli_query($conn, $query);
    
    return $result && mysqli_num_rows($result) > 0;
}

/**
 * Debug function untuk development
 */
function debug_log($message, $data = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] " . $message;
        if ($data !== null) {
            $log_message .= " | Data: " . json_encode($data);
        }
        error_log($log_message);
    }
}

/**
 * Response helper untuk API
 */
function api_response($status, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    header('Content-Type: application/json');
    
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// ========================================
// CONSTANTS & CONFIGURATIONS
// ========================================

// Define constants jika belum ada
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false); // Set true untuk development
}

if (!defined('TIMEZONE')) {
    define('TIMEZONE', 'Asia/Jakarta');
    date_default_timezone_set(TIMEZONE);
}

// Error reporting untuk development
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

?>