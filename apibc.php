<?php
require 'koneksi.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

// Terima data dari ESP8266
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = $_POST['kode'] ?? '';
    $suhu = $_POST['suhu'] ?? '';
    $kelembaban = $_POST['kelembaban'] ?? '';
    $username = $_POST['username'] ?? '';
    
    // Validasi data
    if (empty($kode) || empty($suhu) || empty($kelembaban) || empty($username)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak lengkap',
            'required' => ['kode', 'suhu', 'kelembaban', 'username']
        ]);
        exit;
    }
    
    // Validasi device dan akses
    if (!cek_akses_device($username, $kode)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Device tidak ditemukan atau akses ditolak'
        ]);
        exit;
    }
    
    // Siapkan data untuk disimpan
    $suhu = floatval($suhu);
    $kelembaban = floatval($kelembaban);
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    
    // Simpan data sensor
    $query = "INSERT INTO sensors (kode_device, suhu, kelembaban, tanggal, waktu) 
              VALUES ('$kode', $suhu, $kelembaban, '$tanggal', '$waktu')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil disimpan',
            'data' => [
                'kode' => $kode,
                'suhu' => $suhu,
                'kelembaban' => $kelembaban,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menyimpan data',
            'error' => mysqli_error($conn)
        ]);
    }
}

// Baca status relay (GET request)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $kode = $_GET['kode'] ?? '';
    
    if (empty($kode)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Kode device diperlukan'
        ]);
        exit;
    }
    
    // Ambil status relay
    $query = "SELECT status FROM relays WHERE kode_device = '$kode'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo $row['status']; // Format sederhana untuk ESP8266
    } else {
        echo "0"; // Default OFF jika tidak ditemukan
    }
}

// Method tidak didukung
else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method tidak didukung',
        'supported' => ['POST untuk kirim data', 'GET untuk baca relay']
    ]);
}

mysqli_close($conn);
?>