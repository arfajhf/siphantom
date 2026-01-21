<?php
// ========================================
// kontrol-relay.php - Kontrol Pompa
// ========================================
?>
<?php
session_start();
if (!isset($_SESSION["login"]) || !isset($_SESSION["username"])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

require 'koneksi.php';

$username = $_SESSION["username"];
$device = $_POST['device'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($device) || $status === '') {
    http_response_code(400);
    echo "Data tidak lengkap";
    exit;
}

// Validasi akses device
if (!cek_akses_device($username, $device)) {
    http_response_code(403);
    echo "Akses ditolak";
    exit;
}

// Update status relay
$status = intval($status);
$query = "UPDATE relays SET status = $status WHERE kode_device = '$device'";

if (mysqli_query($conn, $query)) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Error: " . mysqli_error($conn);
}
?>

<?php