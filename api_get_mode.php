<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Content-Type");

header('Content-Type: application/json');

include 'koneksi.php'; // Pastikan koneksi DB lo ke database Siphantom udah bener

// Ambil nilai mode dari database
$result = $conn->query("SELECT value FROM modeset WHERE id = 1");
$row = $result->fetch_assoc();

// Kirim balik ke Laravel
echo json_encode(['mode' => $row['value']]);
?>