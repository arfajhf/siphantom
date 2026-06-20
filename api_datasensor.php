<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

header('Content-Type: application/json');

include 'koneksi.php'; 

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$query = "SELECT * FROM sensors WHERE kode_device = 'JAMUR395' ORDER BY timestamp DESC LIMIT 1";
$result = $conn->query($query);

if (!$result) {
    die("Error Query: " . $conn->error);
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $row]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
}