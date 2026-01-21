<?php
// check-database.php - Debug tool untuk cek database

require 'koneksi.php';

echo "<h2>🔍 Database Structure Check</h2>";

// Cek tabel yang dibutuhkan
$required_tables = ['users', 'devices', 'relays', 'sensors', 'approval_requests'];

echo "<h3>📋 Tabel Status:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Tabel</th><th>Status</th><th>Jumlah Record</th></tr>";

foreach ($required_tables as $table) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        $count_query = "SELECT COUNT(*) as total FROM $table";
        $count_result = mysqli_query($conn, $count_query);
        $count = mysqli_fetch_assoc($count_result)['total'];
        
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td style='color: green;'>✅ Ada</td>";
        echo "<td>$count</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td style='color: red;'>❌ Tidak Ada</td>";
        echo "<td>-</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Cek struktur tabel devices
echo "<h3>🏗️ Struktur Tabel Devices:</h3>";
$devices_structure = "DESCRIBE devices";
$structure_result = mysqli_query($conn, $devices_structure);

if ($structure_result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($structure_result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Tabel devices tidak ditemukan!</p>";
}

// Cek function yang diperlukan
echo "<h3>🔧 Function Status:</h3>";
$functions = ['daftar_device', 'is_superadmin', 'log_activity'];

foreach ($functions as $func) {
    $status = function_exists($func) ? '✅ Ada' : '❌ Tidak Ada';
    $color = function_exists($func) ? 'green' : 'red';
    echo "<p style='color: $color;'>$func: $status</p>";
}

// Test insert simple
echo "<h3>🧪 Test Simple Insert:</h3>";
$test_kode = 'TEST' . rand(100, 999);
$test_query = "INSERT INTO devices (kode, nama, pemilik, status_approval) VALUES ('$test_kode', 'Test Device', 'test_user', 'pending')";

if (mysqli_query($conn, $test_query)) {
    echo "<p style='color: green;'>✅ Test insert berhasil! (Kode: $test_kode)</p>";
    
    // Hapus test data
    mysqli_query($conn, "DELETE FROM devices WHERE kode = '$test_kode'");
    echo "<p style='color: blue;'>🗑️ Test data dihapus</p>";
} else {
    echo "<p style='color: red;'>❌ Test insert gagal: " . mysqli_error($conn) . "</p>";
}

// Cek data existing devices
echo "<h3>📊 Data Devices Existing:</h3>";
$existing_query = "SELECT kode, nama, pemilik, status_approval, dibuat FROM devices ORDER BY dibuat DESC LIMIT 10";
$existing_result = mysqli_query($conn, $existing_query);

if ($existing_result && mysqli_num_rows($existing_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Kode</th><th>Nama</th><th>Pemilik</th><th>Status</th><th>Dibuat</th></tr>";
    
    while ($row = mysqli_fetch_assoc($existing_result)) {
        echo "<tr>";
        echo "<td>{$row['kode']}</td>";
        echo "<td>{$row['nama']}</td>";
        echo "<td>{$row['pemilik']}</td>";
        echo "<td>{$row['status_approval']}</td>";
        echo "<td>{$row['dibuat']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tidak ada data devices</p>";
}

echo "<hr>";
echo "<h3>🔗 Navigation:</h3>";
echo "<a href='daftar-device.php' style='padding: 10px; background: #4CAF50; color: white; text-decoration: none; margin: 5px;'>🔙 Kembali ke Daftar Device</a>";
echo "<a href='dashboard.php' style='padding: 10px; background: #2196F3; color: white; text-decoration: none; margin: 5px;'>🏠 Dashboard</a>";
?>

<style>
table {
    font-family: Arial, sans-serif;
    margin: 10px 0;
}

th {
    background-color: #4CAF50;
    color: white;
    padding: 8px;
    text-align: left;
}

td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}

tr:hover {
    background-color: #f5f5f5;
}
</style>