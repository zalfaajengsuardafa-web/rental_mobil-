<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mobil.php');
    exit;
}

$action        = $_POST['action'] ?? '';
$id            = (int)($_POST['id'] ?? 0);
$kode_mobil    = htmlspecialchars(trim($_POST['kode_mobil']    ?? ''));
$nama_mobil    = htmlspecialchars(trim($_POST['nama_mobil']    ?? ''));
$merek         = htmlspecialchars(trim($_POST['merek']         ?? ''));
$tahun         = (int)($_POST['tahun']         ?? 0);
$warna         = htmlspecialchars(trim($_POST['warna']         ?? ''));
$harga_per_hari = (float)($_POST['harga_per_hari'] ?? 0);
$kapasitas     = (int)($_POST['kapasitas']     ?? 5);
$status        = htmlspecialchars(trim($_POST['status']        ?? 'tersedia'));
$deskripsi     = htmlspecialchars(trim($_POST['deskripsi']     ?? ''));

if (empty($kode_mobil) || empty($nama_mobil) || empty($merek) || $tahun < 2000 || empty($warna) || $harga_per_hari <= 0) {
    $_SESSION['flash_message'] = 'Semua field wajib diisi dengan benar.';
    $_SESSION['flash_type']    = 'danger';
    header('Location: mobil.php');
    exit;
}

if ($action === 'create') {
    $stmt = $conn->prepare(
        "INSERT INTO mobil (kode_mobil, nama_mobil, merek, tahun, warna, harga_per_hari, kapasitas, status, deskripsi)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssisdiss', $kode_mobil, $nama_mobil, $merek, $tahun, $warna, $harga_per_hari, $kapasitas, $status, $deskripsi);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Mobil baru berhasil ditambahkan!';
        $_SESSION['flash_type']    = 'success';
    } else {
        $_SESSION['flash_message'] = 'Gagal menambahkan data mobil: ' . $conn->error;
        $_SESSION['flash_type']    = 'danger';
    }
    $stmt->close();

} elseif ($action === 'update' && $id > 0) {
    $stmt = $conn->prepare(
        "UPDATE mobil
         SET kode_mobil=?, nama_mobil=?, merek=?, tahun=?, warna=?, harga_per_hari=?, kapasitas=?, status=?, deskripsi=?
         WHERE id=?"
    );
    $stmt->bind_param('sssisdissi', $kode_mobil, $nama_mobil, $merek, $tahun, $warna, $harga_per_hari, $kapasitas, $status, $deskripsi, $id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Data mobil berhasil diperbarui!';
        $_SESSION['flash_type']    = 'success';
    } else {
        $_SESSION['flash_message'] = 'Gagal memperbarui data mobil: ' . $conn->error;
        $_SESSION['flash_type']    = 'danger';
    }
    $stmt->close();

} else {
    $_SESSION['flash_message'] = 'Aksi tidak valid.';
    $_SESSION['flash_type']    = 'danger';
}

header('Location: mobil.php');
exit;