<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}
header('Content-Type: application/json');

include 'koneksi.php';

// --- FITUR LOG: Biar lo tau siapa yang nembak API ---
$logMessage = date('Y-m-d H:i:s') . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | Data: " . file_get_contents('php://input') . "\n";
file_put_contents('akses_api.log', $logMessage, FILE_APPEND);

// --- CEK API KEY ---
$headers = getallheaders();
$apiKey = $headers['X-API-KEY'] ?? '';
if ($apiKey !== 'token-rahasia-hydrofarm') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// --- TANGKAP DAN VALIDASI DATA ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['status'])) {
    $statusInput = $data['status'];

    if ($statusInput !== 'on' && $statusInput !== 'off') {
        echo json_encode(['status' => 'error', 'message' => 'Status harus on atau off']);
        exit;
    }

    $statusDB = ($statusInput == 'on') ? 1 : 0;

    $query = "UPDATE relays SET status = ?, terakhir_update = NOW() WHERE kode_device = 'JAMUR395'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $statusDB);

    if ($stmt->execute()) {
        if (isset($data['mode']) && $data['mode'] == 'manual') {
            $conn->query("UPDATE modeset SET value = 'manual' WHERE id = 1");
        }
        echo json_encode(['status' => 'success', 'message' => 'Pompa diupdate ke ' . $statusInput . ' (Mode Manual)']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
}
