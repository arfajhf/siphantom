<?php
// Tambahin ini di baris paling atas
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");


include 'koneksi.php';
$conn->query("UPDATE settings SET value = 'auto' WHERE `key` = 'mode'");
echo json_encode(['status' => 'success']);