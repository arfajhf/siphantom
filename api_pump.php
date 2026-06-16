<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

// Tambahin ini: Kalau request-nya OPTIONS (pre-flight), langsung berhentiin
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');


include 'koneksi.php'; 

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
    
    $query = "UPDATE relays SET status = $status WHERE kode_device = 'JAMUR395'";
    $stmt = $conn->prepare($query);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Pompa diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
}
?>