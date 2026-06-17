<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transaksi.php');
    exit;
}

$action          = $_POST['action']          ?? '';
$id              = (int)($_POST['id']              ?? 0);
$pelanggan_id    = (int)($_POST['pelanggan_id']    ?? 0);
$mobil_id        = (int)($_POST['mobil_id']        ?? 0);
$tanggal_mulai   = htmlspecialchars(trim($_POST['tanggal_mulai']   ?? ''));
$tanggal_selesai = htmlspecialchars(trim($_POST['tanggal_selesai'] ?? ''));
$status          = htmlspecialchars(trim($_POST['status']          ?? 'aktif'));
$catatan         = htmlspecialchars(trim($_POST['catatan']         ?? ''));


if ($pelanggan_id <= 0 || $mobil_id <= 0 || empty($tanggal_mulai) || empty($tanggal_selesai)) {
    $_SESSION['flash_message'] = 'Pelanggan, mobil, dan tanggal wajib diisi.';
    $_SESSION['flash_type']    = 'danger';
    header('Location: transaksi.php');
    exit;
}

if ($tanggal_selesai < $tanggal_mulai) {
    $_SESSION['flash_message'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    $_SESSION['flash_type']    = 'danger';
    header('Location: transaksi.php');
    exit;
}

$jumlah_hari = max(1, (int)round((strtotime($tanggal_selesai) - strtotime($tanggal_mulai)) / 86400) + 1);

$stmtH = $conn->prepare("SELECT harga_per_hari FROM mobil WHERE id = ?");
$stmtH->bind_param('i', $mobil_id);
$stmtH->execute();
$mobilData = $stmtH->get_result()->fetch_assoc();
$stmtH->close();

$harga_per_hari = (float)($mobilData['harga_per_hari'] ?? 0);
$total_harga    = $jumlah_hari * $harga_per_hari;

// ── CREATE ───────────────────────────────────────────────────
if ($action === 'create') {
    $kode_transaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    $stmt = $conn->prepare(
        "INSERT INTO transaksi
         (kode_transaksi, pelanggan_id, mobil_id, tanggal_mulai, tanggal_selesai,
          jumlah_hari, harga_per_hari, total_harga, status, catatan)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'siissiddss',
        $kode_transaksi, $pelanggan_id, $mobil_id,
        $tanggal_mulai, $tanggal_selesai,
        $jumlah_hari, $harga_per_hari, $total_harga,
        $status, $catatan
    );

    if ($stmt->execute()) {
        // Update status mobil jadi disewa
        $stmt2 = $conn->prepare("UPDATE mobil SET status = 'disewa' WHERE id = ?");
        $stmt2->bind_param('i', $mobil_id);
        $stmt2->execute();
        $stmt2->close();

        $_SESSION['flash_message'] = 'Transaksi berhasil ditambahkan!';
        $_SESSION['flash_type']    = 'success';
    } else {
        $_SESSION['flash_message'] = 'Gagal menambahkan transaksi: ' . $conn->error;
        $_SESSION['flash_type']    = 'danger';
    }
    $stmt->close();

// ── UPDATE ───────────────────────────────────────────────────
} elseif ($action === 'update' && $id > 0) {
    $stmt = $conn->prepare(
        "UPDATE transaksi
         SET pelanggan_id=?, mobil_id=?, tanggal_mulai=?, tanggal_selesai=?,
             jumlah_hari=?, harga_per_hari=?, total_harga=?, status=?, catatan=?
         WHERE id=?"
    );
    $stmt->bind_param(
        'iissiddssi',
        $pelanggan_id, $mobil_id,
        $tanggal_mulai, $tanggal_selesai,
        $jumlah_hari, $harga_per_hari, $total_harga,
        $status, $catatan,
        $id
    );

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Transaksi berhasil diperbarui!';
        $_SESSION['flash_type']    = 'success';
    } else {
        $_SESSION['flash_message'] = 'Gagal memperbarui transaksi: ' . $conn->error;
        $_SESSION['flash_type']    = 'danger';
    }
    $stmt->close();

} else {
    $_SESSION['flash_message'] = 'Aksi tidak valid.';
    $_SESSION['flash_type']    = 'danger';
}

header('Location: transaksi.php');
exit;