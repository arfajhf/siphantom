<?php
// Tambahin ini di baris paling atas
// header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

// // Kalau request-nya OPTIONS (preflight), langsung exit
// if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

// file_put_contents('mode.txt', 'auto');
// echo json_encode(['status' => 'success', 'message' => 'Mode Reset ke Auto']);

header("Access-Control-Allow-Origin: *");
$file = 'mode.txt';

// Tes apakah folder/file writable
if (is_writable($file)) {
    file_put_contents($file, 'auto');
    echo json_encode(['status' => 'success', 'message' => 'Berhasil nulis auto']);
} else {
    // Kalau ini muncul di response, fix masalahnya di Permission Folder!
    echo json_encode(['status' => 'error', 'message' => 'Gak bisa nulis ke file! Cek Permission 777']);
}
?>