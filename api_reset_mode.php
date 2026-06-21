<?php
// Tambahin ini di baris paling atas
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

// Kalau request-nya OPTIONS (preflight), langsung exit
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

file_put_contents('mode.txt', 'auto');
echo json_encode(['status' => 'success', 'message' => 'Mode Reset ke Auto']);
?>