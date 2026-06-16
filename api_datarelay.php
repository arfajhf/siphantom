<?php
header('Content-Type: application/json');

include 'koneksi.php'; 

// mengambil data json dari relay
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$query = "SELECT * FROM relays WHERE kode_device = 'JAMUR395'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $row]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
}