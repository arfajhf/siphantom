<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

header('Content-Type: application/json');

include 'koneksi.php';
$query = $conn->query("SELECT status FROM relays WHERE kode_device = 'JAMUR395'");
$row = $query->fetch_assoc();
// 1 = on, 0 = off
echo json_encode(['status' => ($row['status'] == 1 ? 'on' : 'off')]);
?>