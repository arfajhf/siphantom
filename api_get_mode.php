<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include 'koneksi.php'; // Pastikan koneksi DB lo ke database Siphantom udah bener

// Ambil nilai mode dari database
$result = $conn->query("SELECT value FROM settings WHERE `key` = 'mode'");
$row = $result->fetch_assoc();

// Kirim balik ke Laravel
echo json_encode(['mode' => $row['value']]);
?>