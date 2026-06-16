<?php
// /api_pump.php di Siphantom
header('Content-Type: application/json');

// 1. Panggil koneksi lo yang udah ada
include 'koneksi.php'; // Pastiin path-nya bener (misal '../koneksi.php')

// 2. Cek API Key (Sama kayak tadi)
$headers = getallheaders();
if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== 'token-rahasia-hydrofarm') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 3. Tangkap data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['status'])) {
    $status = $data['status'] == 'on' ? 1 : 0;
    
    // 4. Pake variabel $conn atau $koneksi (sesuain sama nama variabel di koneksi.php lo)
    // Contoh kalau di koneksi.php lo variabelnya $conn:
    $sql = "UPDATE `relays` SET `status` = ? WHERE `kode_device` = 'JAMUR395'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $status);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Pompa diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update database']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
}
?>