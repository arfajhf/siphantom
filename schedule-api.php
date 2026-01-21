<?php
// ========================================
// schedule-api.php - API untuk Jadwal Penyiraman
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
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_schedules':
            getSchedules($conn, $username);
            break;
            
        case 'add_schedule':
            addSchedule($conn, $username);
            break;
            
        case 'update_schedule':
            updateSchedule($conn, $username);
            break;
            
        case 'delete_schedule':
            deleteSchedule($conn, $username);
            break;
            
        case 'toggle_schedule':
            toggleSchedule($conn, $username);
            break;
            
        case 'get_watering_logs':
            getWateringLogs($conn, $username);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}

function getSchedules($conn, $username) {
    $device_kode = $_GET['device_kode'] ?? '';
    
    if (empty($device_kode)) {
        // Ambil device pertama user jika tidak dispesifikasi
        $device_query = "SELECT kode FROM devices WHERE pemilik = '$username' AND aktif = 1 AND deleted_at IS NULL AND status_approval = 'approved' LIMIT 1";
        $device_result = mysqli_query($conn, $device_query);
        if ($device_result && mysqli_num_rows($device_result) > 0) {
            $device_row = mysqli_fetch_assoc($device_result);
            $device_kode = $device_row['kode'];
        } else {
            echo json_encode(['error' => 'No active device found']);
            return;
        }
    }
    
    // Verifikasi akses device
    if (!cek_akses_device($username, $device_kode)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $query = "SELECT * FROM watering_schedules WHERE device_kode = '$device_kode' ORDER BY hour ASC, minute ASC";
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
                'active' => boolval($row['active']),
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
    }
    
    echo json_encode([
        'schedules' => $schedules,
        'device_kode' => $device_kode,
        'status' => 'success'
    ]);
}

function addSchedule($conn, $username) {
    $device_kode = $_POST['device_kode'] ?? '';
    $name = $_POST['name'] ?? '';
    $hour = intval($_POST['hour'] ?? 0);
    $minute = intval($_POST['minute'] ?? 0);
    $duration = intval($_POST['duration'] ?? 60);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validasi input
    if (empty($device_kode) || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Device code and name are required']);
        return;
    }
    
    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid time format']);
        return;
    }
    
    if ($duration < 10 || $duration > 3600) {
        http_response_code(400);
        echo json_encode(['error' => 'Duration must be between 10 and 3600 seconds']);
        return;
    }
    
    // Verifikasi akses device
    if (!cek_akses_device($username, $device_kode)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Cek duplikasi waktu
    $check_query = "SELECT id FROM watering_schedules WHERE device_kode = '$device_kode' AND hour = $hour AND minute = $minute";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Schedule already exists for this time']);
        return;
    }
    
    // Insert jadwal baru
    $insert_query = "INSERT INTO watering_schedules (device_kode, name, hour, minute, duration, active, created_by, created_at) 
                     VALUES ('$device_kode', '$name', $hour, $minute, $duration, $active, '$username', NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        $schedule_id = mysqli_insert_id($conn);
        
        // Log activity
        log_activity($username, 'ADD_SCHEDULE', $device_kode, "Added watering schedule: $name at $hour:$minute");
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule added successfully',
            'schedule_id' => $schedule_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add schedule', 'sql_error' => mysqli_error($conn)]);
    }
}

function updateSchedule($conn, $username) {
    $schedule_id = intval($_POST['schedule_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $hour = intval($_POST['hour'] ?? 0);
    $minute = intval($_POST['minute'] ?? 0);
    $duration = intval($_POST['duration'] ?? 60);
    $active = isset($_POST['active']) ? 1 : 0;
    
    if ($schedule_id <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Schedule ID and name are required']);
        return;
    }
    
    // Verifikasi kepemilikan schedule
    $verify_query = "SELECT ws.*, d.pemilik FROM watering_schedules ws 
                     JOIN devices d ON ws.device_kode = d.kode 
                     WHERE ws.id = $schedule_id";
    $verify_result = mysqli_query($conn, $verify_query);
    
    if (!$verify_result || mysqli_num_rows($verify_result) == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Schedule not found']);
        return;
    }
    
    $schedule = mysqli_fetch_assoc($verify_result);
    if ($schedule['pemilik'] !== $username) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Validasi waktu
    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid time format']);
        return;
    }
    
    // Cek duplikasi waktu (kecuali untuk schedule yang sedang diedit)
    $check_query = "SELECT id FROM watering_schedules WHERE device_kode = '{$schedule['device_kode']}' AND hour = $hour AND minute = $minute AND id != $schedule_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Schedule already exists for this time']);
        return;
    }
    
    // Update schedule
    $update_query = "UPDATE watering_schedules SET 
                     name = '$name', 
                     hour = $hour, 
                     minute = $minute, 
                     duration = $duration, 
                     active = $active,
                     updated_at = NOW()
                     WHERE id = $schedule_id";
    
    if (mysqli_query($conn, $update_query)) {
        // Log activity
        log_activity($username, 'UPDATE_SCHEDULE', $schedule['device_kode'], "Updated watering schedule: $name");
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update schedule', 'sql_error' => mysqli_error($conn)]);
    }
}

function deleteSchedule($conn, $username) {
    $schedule_id = intval($_POST['schedule_id'] ?? $_GET['schedule_id'] ?? 0);
    
    if ($schedule_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Schedule ID is required']);
        return;
    }
    
    // Verifikasi kepemilikan schedule
    $verify_query = "SELECT ws.*, d.pemilik FROM watering_schedules ws 
                     JOIN devices d ON ws.device_kode = d.kode 
                     WHERE ws.id = $schedule_id";
    $verify_result = mysqli_query($conn, $verify_query);
    
    if (!$verify_result || mysqli_num_rows($verify_result) == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Schedule not found']);
        return;
    }
    
    $schedule = mysqli_fetch_assoc($verify_result);
    if ($schedule['pemilik'] !== $username) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Hapus schedule
    $delete_query = "DELETE FROM watering_schedules WHERE id = $schedule_id";
    
    if (mysqli_query($conn, $delete_query)) {
        // Log activity
        log_activity($username, 'DELETE_SCHEDULE', $schedule['device_kode'], "Deleted watering schedule: {$schedule['name']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete schedule', 'sql_error' => mysqli_error($conn)]);
    }
}

function toggleSchedule($conn, $username) {
    $schedule_id = intval($_POST['schedule_id'] ?? 0);
    
    if ($schedule_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Schedule ID is required']);
        return;
    }
    
    // Verifikasi kepemilikan schedule
    $verify_query = "SELECT ws.*, d.pemilik FROM watering_schedules ws 
                     JOIN devices d ON ws.device_kode = d.kode 
                     WHERE ws.id = $schedule_id";
    $verify_result = mysqli_query($conn, $verify_query);
    
    if (!$verify_result || mysqli_num_rows($verify_result) == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Schedule not found']);
        return;
    }
    
    $schedule = mysqli_fetch_assoc($verify_result);
    if ($schedule['pemilik'] !== $username) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Toggle status
    $new_status = $schedule['active'] ? 0 : 1;
    $update_query = "UPDATE watering_schedules SET active = $new_status, updated_at = NOW() WHERE id = $schedule_id";
    
    if (mysqli_query($conn, $update_query)) {
        $status_text = $new_status ? 'activated' : 'deactivated';
        log_activity($username, 'TOGGLE_SCHEDULE', $schedule['device_kode'], "Schedule {$schedule['name']} $status_text");
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule status updated',
            'new_status' => boolval($new_status)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update schedule status']);
    }
}

function getWateringLogs($conn, $username) {
    $device_kode = $_GET['device_kode'] ?? '';
    $limit = intval($_GET['limit'] ?? 20);
    
    if (empty($device_kode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Device code is required']);
        return;
    }
    
    // Verifikasi akses device
    if (!cek_akses_device($username, $device_kode)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $query = "SELECT * FROM watering_logs WHERE device_kode = '$device_kode' ORDER BY executed_at DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    
    $logs = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = [
                'id' => intval($row['id']),
                'schedule_name' => $row['schedule_name'],
                'duration' => intval($row['duration']),
                'executed_at' => $row['executed_at'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    echo json_encode([
        'logs' => $logs,
        'device_kode' => $device_kode,
        'status' => 'success'
    ]);
}

mysqli_close($conn);
?>