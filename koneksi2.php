<?php 
// koneksi.php - Koneksi Database Sederhana

$server = "localhost";
$username_db = "root";
$password_db = "root";
$database = "sipantom";

$conn = mysqli_connect($server, $username_db, $password_db, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi untuk registrasi user baru
function daftar_user($data) {
    global $conn;
    
    $username = strtolower(trim($data["username"]));
    $password = $data["password"];
    $password2 = $data["password2"];
    $nama_lengkap = $data["nama_lengkap"];
    
    // Validasi username sudah ada atau belum
    $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
    if (mysqli_fetch_assoc($cek)) {
        echo "<script>alert('Username sudah digunakan!');</script>";
        return false;
    }
    
    // Validasi password match
    if ($password !== $password2) {
        echo "<script>alert('Konfirmasi password tidak sama!');</script>";
        return false;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user baru
    $query = "INSERT INTO users (username, password, nama_lengkap) VALUES('$username', '$password_hash', '$nama_lengkap')";
    mysqli_query($conn, $query);
    
    return mysqli_affected_rows($conn);
}

// Fungsi untuk mendaftarkan device baru
function daftar_device($kode, $nama, $pemilik) {
    global $conn;
    
    // Cek apakah kode device sudah ada
    $cek = mysqli_query($conn, "SELECT kode FROM devices WHERE kode = '$kode'");
    if (mysqli_fetch_assoc($cek)) {
        return false; // Kode sudah ada
    }
    
    // Insert device baru
    $query = "INSERT INTO devices (kode, nama, pemilik) VALUES('$kode', '$nama', '$pemilik')";
    mysqli_query($conn, $query);
    
    // Buat relay entry untuk device ini
    if (mysqli_affected_rows($conn) > 0) {
        $relay_query = "INSERT INTO relays (kode_device, status) VALUES('$kode', 0)";
        mysqli_query($conn, $relay_query);
        return true;
    }
    
    return false;
}

// Fungsi untuk validasi akses device
function cek_akses_device($username, $kode_device) {
    global $conn;
    
    $query = "SELECT * FROM devices WHERE kode = '$kode_device' AND pemilik = '$username' AND aktif = 1";
    $result = mysqli_query($conn, $query);
    
    return mysqli_num_rows($result) > 0;
}
?>