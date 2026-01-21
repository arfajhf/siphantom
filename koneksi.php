<?php 
// koneksi.php - Database Connection dengan Superadmin Features

$server = "localhost";
$username_db = "root";
$password_db = "root";
$database = "sipantom";

$conn = mysqli_connect($server, $username_db, $password_db, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi untuk registrasi user baru
function daftar_user($data) {
    global $conn;
    
    $username = strtolower(trim($data["username"]));
    $password = $data["password"];
    $password2 = $data["password2"];
    $nama_lengkap = $data["nama_lengkap"];
    $role = $data["role"] ?? 'user';
    
    // Validasi username sudah ada atau belum
    $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
    if (mysqli_fetch_assoc($cek)) {
        echo "<script>alert('Username sudah digunakan!');</script>";
        return false;
    }
    
    // Validasi password match
    if ($password !== $password2) {
        echo "<script>alert('Konfirmasi password tidak sama!');</script>";
        return false;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user baru
    $query = "INSERT INTO users (username, password, nama_lengkap, role) VALUES('$username', '$password_hash', '$nama_lengkap', '$role')";
    mysqli_query($conn, $query);
    
    return mysqli_affected_rows($conn);
}

// Fungsi untuk mendaftarkan device baru dengan sistem approval
function daftar_device($kode, $nama, $pemilik) {
    global $conn;
    
    // Cek apakah kode device sudah ada
    $cek = mysqli_query($conn, "SELECT kode FROM devices WHERE kode = '$kode' AND deleted_at IS NULL");
    if (mysqli_fetch_assoc($cek)) {
        return false; // Kode sudah ada
    }
    
    // Insert device baru dengan status pending
    $query = "INSERT INTO devices (kode, nama, pemilik, status_approval) VALUES('$kode', '$nama', '$pemilik', 'pending')";
    mysqli_query($conn, $query);
    
    if (mysqli_affected_rows($conn) > 0) {
        // Buat approval request
        $request_query = "INSERT INTO approval_requests (device_kode, device_nama, requester, request_message) 
                          VALUES('$kode', '$nama', '$pemilik', 'Permintaan approval device baru')";
        mysqli_query($conn, $request_query);
        
        // Log aktivitas
        log_activity($pemilik, 'REQUEST_DEVICE', $kode, "Device '$nama' diajukan untuk approval");
        
        return 'pending'; // Return status pending
    }
    
    return false;
}

// Fungsi untuk validasi akses device (hanya approved device)
function cek_akses_device($username, $kode_device) {
    global $conn;
    
    // Superadmin bisa akses semua device
    $user_role = get_user_role($username);
    if ($user_role === 'superadmin') {
        $query = "SELECT * FROM devices WHERE kode = '$kode_device' AND deleted_at IS NULL";
    } else {
        // User biasa hanya bisa akses device yang approved
        $query = "SELECT * FROM devices WHERE kode = '$kode_device' AND pemilik = '$username' 
                  AND aktif = 1 AND deleted_at IS NULL AND status_approval = 'approved'";
    }
    
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Fungsi untuk mendapatkan role user
function get_user_role($username) {
    global $conn;
    
    $query = "SELECT role FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['role'];
    }
    
    return 'user'; // Default role
}

// Fungsi untuk cek apakah user adalah superadmin
function is_superadmin($username) {
    return get_user_role($username) === 'superadmin';
}

// Fungsi untuk soft delete device
function hapus_device($kode, $username) {
    global $conn;
    
    // Hanya superadmin yang bisa hapus device
    if (!is_superadmin($username)) {
        return false;
    }
    
    $query = "UPDATE devices SET deleted_at = NOW(), deleted_by = '$username' WHERE kode = '$kode'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        // Nonaktifkan relay terkait
        mysqli_query($conn, "UPDATE relays SET status = 0 WHERE kode_device = '$kode'");
        
        // Log aktivitas
        log_activity($username, 'DELETE_DEVICE', $kode, "Device dihapus oleh superadmin");
        
        return true;
    }
    
    return false;
}

// Fungsi untuk restore device yang dihapus
function restore_device($kode, $username) {
    global $conn;
    
    if (!is_superadmin($username)) {
        return false;
    }
    
    $query = "UPDATE devices SET deleted_at = NULL, deleted_by = NULL WHERE kode = '$kode'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        log_activity($username, 'RESTORE_DEVICE', $kode, "Device direstore oleh superadmin");
        return true;
    }
    
    return false;
}

// Fungsi untuk log aktivitas
function log_activity($username, $action, $target = null, $details = null) {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $target = $target ? "'$target'" : 'NULL';
    $details = $details ? "'$details'" : 'NULL';
    
    $query = "INSERT INTO activity_logs (user, action, target, details, ip_address) 
              VALUES('$username', '$action', $target, $details, '$ip')";
    mysqli_query($conn, $query);
}

// Fungsi untuk get semua users (superadmin only)
function get_all_users() {
    global $conn;
    
    $query = "SELECT username, nama_lengkap, role, created_at FROM users ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    return $users;
}

// Fungsi untuk get semua devices (superadmin only)
function get_all_devices($include_deleted = false) {
    global $conn;
    
    if ($include_deleted) {
        $query = "SELECT d.*, u.nama_lengkap as pemilik_nama FROM devices d 
                  LEFT JOIN users u ON d.pemilik = u.username 
                  ORDER BY d.dibuat DESC";
    } else {
        $query = "SELECT d.*, u.nama_lengkap as pemilik_nama FROM devices d 
                  LEFT JOIN users u ON d.pemilik = u.username 
                  WHERE d.deleted_at IS NULL 
                  ORDER BY d.dibuat DESC";
    }
    
    $result = mysqli_query($conn, $query);
    
    $devices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $devices[] = $row;
    }
    
    return $devices;
}

// Fungsi untuk approve device (superadmin only)
function approve_device($kode, $superadmin_username) {
    global $conn;
    
    if (!is_superadmin($superadmin_username)) {
        return false;
    }
    
    // Update device status
    $query = "UPDATE devices SET status_approval = 'approved', approved_by = '$superadmin_username', 
              approved_at = NOW(), aktif = 1 WHERE kode = '$kode'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        // Buat relay entry
        $relay_check = mysqli_query($conn, "SELECT * FROM relays WHERE kode_device = '$kode'");
        if (mysqli_num_rows($relay_check) == 0) {
            mysqli_query($conn, "INSERT INTO relays (kode_device, status) VALUES('$kode', 0)");
        }
        
        // Update approval request
        mysqli_query($conn, "UPDATE approval_requests SET status = 'approved', 
                            processed_by = '$superadmin_username', processed_at = NOW() WHERE device_kode = '$kode'");
        
        log_activity($superadmin_username, 'APPROVE_DEVICE', $kode, "Device disetujui oleh superadmin");
        return true;
    }
    
    return false;
}

// Fungsi untuk reject device (superadmin only)
function reject_device($kode, $superadmin_username, $reason = null) {
    global $conn;
    
    if (!is_superadmin($superadmin_username)) {
        return false;
    }
    
    // Update device status
    $reason_sql = $reason ? "'$reason'" : 'NULL';
    $query = "UPDATE devices SET status_approval = 'rejected', approved_by = '$superadmin_username', 
              approved_at = NOW(), rejection_reason = $reason_sql WHERE kode = '$kode'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        // Update approval request
        $response_msg = $reason ? "'$reason'" : "'Device ditolak oleh superadmin'";
        mysqli_query($conn, "UPDATE approval_requests SET status = 'rejected', 
                            processed_by = '$superadmin_username', processed_at = NOW(),
                            response_message = $response_msg WHERE device_kode = '$kode'");
        
        log_activity($superadmin_username, 'REJECT_DEVICE', $kode, "Device ditolak: " . ($reason ?? 'Tidak ada alasan'));
        return true;
    }
    
    return false;
}

// Fungsi untuk user menghapus device sendiri
function user_delete_device($kode, $username, $reason = null) {
    global $conn;
    
    // Cek apakah user adalah pemilik device
    $check_query = "SELECT * FROM devices WHERE kode = '$kode' AND pemilik = '$username' AND deleted_at IS NULL";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        return false; // Bukan pemilik atau device tidak ada
    }
    
    $device_info = mysqli_fetch_assoc($check_result);
    
    // Soft delete device
    $delete_query = "UPDATE devices SET deleted_at = NOW(), deleted_by = '$username' WHERE kode = '$kode'";
    $result = mysqli_query($conn, $delete_query);
    
    if ($result) {
        // Matikan relay
        mysqli_query($conn, "UPDATE relays SET status = 0 WHERE kode_device = '$kode'");
        
        // Insert ke user_deleted_devices untuk tracking
        $reason_sql = $reason ? "'$reason'" : 'NULL';
        $track_query = "INSERT INTO user_deleted_devices (device_kode, device_nama, original_owner, deleted_by, deletion_reason) 
                        VALUES('$kode', '{$device_info['nama']}', '$username', '$username', $reason_sql)";
        mysqli_query($conn, $track_query);
        
        log_activity($username, 'USER_DELETE_DEVICE', $kode, "User menghapus device sendiri");
        return true;
    }
    
    return false;
}

// Fungsi untuk get pending approvals (superadmin only)
function get_pending_approvals() {
    global $conn;
    
    $query = "SELECT ar.*, d.nama as device_nama, u.nama_lengkap as requester_name 
              FROM approval_requests ar 
              JOIN devices d ON ar.device_kode = d.kode 
              JOIN users u ON ar.requester = u.username 
              WHERE ar.status = 'pending' 
              ORDER BY ar.created_at DESC";
    
    $result = mysqli_query($conn, $query);
    $approvals = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $approvals[] = $row;
    }
    
    return $approvals;
}
function get_system_stats() {
    global $conn;
    
    $stats = [];
    
    // Total users
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = mysqli_fetch_assoc($result)['total'];
    
    // Total devices aktif
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM devices WHERE aktif = 1 AND deleted_at IS NULL");
    $stats['total_devices'] = mysqli_fetch_assoc($result)['total'];
    
    // Total devices dihapus
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM devices WHERE deleted_at IS NOT NULL");
    $stats['deleted_devices'] = mysqli_fetch_assoc($result)['total'];
    
    // Data sensor hari ini
    $today = date('Y-m-d');
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM sensors WHERE tanggal = '$today'");
    $stats['today_data'] = mysqli_fetch_assoc($result)['total'];
    
    // Relay aktif
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM relays WHERE status = 1");
    $stats['active_relays'] = mysqli_fetch_assoc($result)['total'];
    
    return $stats;
}
?>