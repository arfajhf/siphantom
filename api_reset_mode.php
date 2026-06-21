<?php
// Tambahin ini di baris paling atas
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

header('Content-Type: application/json');

include 'koneksi.php';

$query = "UPDATE settings SET value = 'auto' WHERE `key` = 'mode'";
if ($conn->query($query) === TRUE) {
    echo json_encode(['status' => 'success', 'message' => 'Mode direset ke otomatis']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}