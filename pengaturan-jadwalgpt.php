<?php
include 'koneksi.php';

// Proses tambah jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jam'], $_POST['durasi'], $_POST['kode_device'])) {
    $jam = $_POST['jam'];
    $durasi = intval($_POST['durasi']);
    $kode = $_POST['kode_device'];
    
    $stmt = $conn->prepare("INSERT INTO jadwal_penyiraman (kode_device, jam, durasi) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $kode, $jam, $durasi);
    $stmt->execute();
    $stmt->close();
}

// Proses hapus jadwal
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM jadwal_penyiraman WHERE id = $id");
}

$kode_device = $_GET['kode'] ?? '';
$jadwal = [];
if ($kode_device !== '') {
    $result = $conn->query("SELECT * FROM jadwal_penyiraman WHERE kode_device = '$kode_device' ORDER BY jam ASC");
    while ($row = $result->fetch_assoc()) {
        $jadwal[] = $row;
    }
}

// Ambil daftar device
$devices = $conn->query("SELECT kode, nama FROM devices WHERE aktif = 1");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pengaturan Jadwal Penyiraman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Pengaturan Jadwal Penyiraman</h2>

    <form method="GET">
        <label>Pilih Device:</label>
        <select name="kode" onchange="this.form.submit()">
            <option value="">-- Pilih --</option>
            <?php while ($d = $devices->fetch_assoc()) { ?>
                <option value="<?= $d['kode'] ?>" <?= $kode_device == $d['kode'] ? 'selected' : '' ?>>
                    <?= $d['nama'] ?> (<?= $d['kode'] ?>)
                </option>
            <?php } ?>
        </select>
    </form>

    <?php if ($kode_device !== ''): ?>
        <h3>Tambah Jadwal untuk <?= $kode_device ?></h3>
        <form method="POST">
            <input type="hidden" name="kode_device" value="<?= $kode_device ?>">
            <label>Jam:</label>
            <input type="time" name="jam" required>
            <label>Durasi (detik):</label>
            <input type="number" name="durasi" min="1" required>
            <button type="submit">Tambah Jadwal</button>
        </form>

        <h3>Daftar Jadwal</h3>
        <table border="1" cellpadding="8" cellspacing="0">
            <tr><th>Jam</th><th>Durasi (detik)</th><th>Aksi</th></tr>
            <?php foreach ($jadwal as $j): ?>
                <tr>
                    <td><?= $j['jam'] ?></td>
                    <td><?= $j['durasi'] ?></td>
                    <td>
                        <a href="?kode=<?= $kode_device ?>&hapus=<?= $j['id'] ?>" onclick="return confirm('Hapus jadwal ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>