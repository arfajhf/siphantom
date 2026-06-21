<?php
// Tambahin ini di baris paling atas
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

header('Content-Type: application/json');

include 'koneksi.php';
$conn->query("UPDATE settings SET value = 'auto' WHERE `key` = 'mode'");
echo json_encode(['status' => 'success']);