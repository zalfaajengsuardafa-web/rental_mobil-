<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['id'])) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'katalog.php');
    exit;
}

$mobil_id    = (int)($_POST['mobil_id'] ?? 0);
$nama        = htmlspecialchars(trim($_POST['nama_penyewa'] ?? ''));
$nik         = htmlspecialchars(trim($_POST['nik'] ?? ''));
$no_telp     = htmlspecialchars(trim($_POST['no_telp'] ?? ''));
$tgl_mulai   = htmlspecialchars(trim($_POST['tgl_mulai'] ?? ''));
$tgl_kembali = htmlspecialchars(trim($_POST['tgl_kembali'] ?? ''));

if (!$mobil_id || !$nama || !$nik || !$no_telp || !$tgl_mulai || !$tgl_kembali) {
    $_SESSION['flash_message'] = 'Semua field harus diisi!';
    $_SESSION['flash_type']    = 'danger';
    header('Location: ' . BASE_URL . 'katalog.php');
    exit;
}

$stmt = $conn->prepare("SELECT hitung_hari(?, ?) AS hari");
$stmt->bind_param('ss', $tgl_mulai, $tgl_kembali);
$stmt->execute();
$jumlah_hari = (int) $stmt->get_result()->fetch_assoc()['hari'];
$stmt->close();

if ($jumlah_hari < 1) {
    $_SESSION['flash_message'] = 'Tanggal kembali harus setelah tanggal mulai!';
    $_SESSION['flash_type']    = 'danger';
    header('Location: ' . BASE_URL . 'katalog.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM mobil WHERE id = ? AND status = 'tersedia'");
$stmt->bind_param('i', $mobil_id);
$stmt->execute();
$mobil = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mobil) {
    $_SESSION['flash_message'] = 'Mobil tidak tersedia atau tidak ditemukan.';
    $_SESSION['flash_type']    = 'danger';
    header('Location: ' . BASE_URL . 'katalog.php');
    exit;
}

$stmt = $conn->prepare("SELECT hitung_total_sewa(?, ?) AS total");
$stmt->bind_param('di', $mobil['harga_per_hari'], $jumlah_hari);
$stmt->execute();
$total_harga = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT id FROM pelanggan WHERE nik = ?");
$stmt->bind_param('s', $nik);
$stmt->execute();
$pelanggan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($pelanggan) {
    $pelanggan_id = $pelanggan['id'];
    $stmt = $conn->prepare("UPDATE pelanggan SET nama=?, telepon=?, user_id=? WHERE id=?");
    $stmt->bind_param('ssii', $nama, $no_telp, $_SESSION['id'], $pelanggan_id);
    $stmt->execute();
    $stmt->close();
} else {
    $user_id = $_SESSION['id'];
    $email = $_SESSION['email'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO pelanggan (user_id, nama, nik, telepon, email) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $user_id, $nama, $nik, $no_telp, $email);
    $stmt->execute();
    
    $pelanggan_id = $conn->insert_id; 
    $stmt->close();
}

$tanggal_kode = date('Ymd');
$like = "TRX-{$tanggal_kode}-%";
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM transaksi WHERE kode_transaksi LIKE ?");
$stmt->bind_param('s', $like);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['total'] + 1;
$stmt->close();
$kode_transaksi = 'TRX-' . $tanggal_kode . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

$stmt = $conn->prepare("
    INSERT INTO transaksi 
    (kode_transaksi, pelanggan_id, mobil_id, tanggal_mulai, tanggal_selesai, jumlah_hari, harga_per_hari, total_harga, status)
    VALUES (?,?,?,?,?,?,?,?,'aktif')
");

$stmt->bind_param(
    'siisiddd',
    $kode_transaksi,
    $pelanggan_id,
    $mobil_id,
    $tgl_mulai,
    $tgl_kembali,
    $jumlah_hari,
    $mobil['harga_per_hari'],
    $total_harga
);
$stmt->execute();
$stmt->close();

$_SESSION['flash_message'] = "Pemesanan berhasil! Kode: <strong>$kode_transaksi</strong>. Total: <strong>Rp " . number_format($total_harga, 0, ',', '.') . "</strong>. Hubungi kami untuk konfirmasi.";
$_SESSION['flash_type']    = 'success';

header('Location: ' . BASE_URL . 'pages/booking.php');
exit;