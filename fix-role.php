<?php
// fix-roles.php - File untuk memperbaiki role user

require 'koneksi.php';

echo "<h2>🔧 Perbaikan Role User</h2>";

// Cek struktur tabel users
$check_column = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $check_column);

if (mysqli_num_rows($result) == 0) {
    echo "<p>❌ Kolom 'role' belum ada di tabel users</p>";
    echo "<p>🔧 Menambahkan kolom role...</p>";
    
    $add_column = "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'superadmin') DEFAULT 'user'";
    if (mysqli_query($conn, $add_column)) {
        echo "<p>✅ Kolom role berhasil ditambahkan</p>";
    } else {
        echo "<p>❌ Gagal menambahkan kolom role: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>✅ Kolom 'role' sudah ada</p>";
}

// Lihat semua user dan role mereka
echo "<h3>👥 Daftar User dan Role:</h3>";
$users_query = "SELECT username, nama_lengkap, role FROM users";
$users_result = mysqli_query($conn, $users_query);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Aksi</th></tr>";

while ($user = mysqli_fetch_assoc($users_result)) {
    $role_color = '';
    switch ($user['role']) {
        case 'superadmin':
            $role_color = 'style="background-color: #ffd700; font-weight: bold;"';
            break;
        case 'admin':
            $role_color = 'style="background-color: #4CAF50; color: white;"';
            break;
        default:
            $role_color = 'style="background-color: #2196F3; color: white;"';
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
    echo "<td>" . htmlspecialchars($user['nama_lengkap'] ?? '-') . "</td>";
    echo "<td $role_color>" . strtoupper($user['role'] ?? 'user') . "</td>";
    echo "<td>";
    
    if ($user['username'] !== 'superadmin') {
        echo "<a href='?fix_user=" . $user['username'] . "' style='margin: 2px; padding: 5px; background: #2196F3; color: white; text-decoration: none;'>Set as USER</a>";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Proses perbaikan user
if (isset($_GET['fix_user'])) {
    $fix_username = $_GET['fix_user'];
    
    if ($fix_username !== 'superadmin') {
        $fix_query = "UPDATE users SET role = 'user' WHERE username = '$fix_username'";
        if (mysqli_query($conn, $fix_query)) {
            echo "<p>✅ User '$fix_username' berhasil diset sebagai USER</p>";
            echo "<script>setTimeout(() => { window.location.href = 'fix-roles.php'; }, 2000);</script>";
        } else {
            echo "<p>❌ Gagal update user: " . mysqli_error($conn) . "</p>";
        }
    }
}

// Pastikan ada superadmin
echo "<h3>👑 Setup Superadmin:</h3>";

$superadmin_check = "SELECT * FROM users WHERE role = 'superadmin'";
$superadmin_result = mysqli_query($conn, $superadmin_check);

if (mysqli_num_rows($superadmin_result) == 0) {
    echo "<p>⚠️ Belum ada superadmin</p>";
    echo "<p>🔧 Membuat akun superadmin...</p>";
    
    $password_hash = password_hash('password', PASSWORD_DEFAULT);
    $create_superadmin = "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('superadmin', '$password_hash', 'Super Administrator', 'superadmin')";
    
    if (mysqli_query($conn, $create_superadmin)) {
        echo "<p>✅ Akun superadmin berhasil dibuat</p>";
        echo "<p><strong>Username:</strong> superadmin</p>";
        echo "<p><strong>Password:</strong> password</p>";
    } else {
        echo "<p>❌ Gagal membuat superadmin: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>✅ Superadmin sudah ada</p>";
    while ($sa = mysqli_fetch_assoc($superadmin_result)) {
        echo "<p><strong>Username:</strong> " . $sa['username'] . "</p>";
    }
}

echo "<hr>";
echo "<h3>🔗 Navigation:</h3>";
echo "<a href='dashboard.php' style='padding: 10px; background: #4CAF50; color: white; text-decoration: none; margin: 5px;'>🏠 Dashboard</a>";
echo "<a href='login.php' style='padding: 10px; background: #2196F3; color: white; text-decoration: none; margin: 5px;'>🔐 Login</a>";

mysqli_close($conn);
?>