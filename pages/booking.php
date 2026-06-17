<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['id'])) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$page_title = 'Riwayat Booking Saya';
$sql = "
    SELECT t.*, m.nama_mobil, m.merek, m.kode_mobil,
           p.nama AS nama_pelanggan, p.nik, p.telepon
    FROM transaksi t
    JOIN pelanggan p ON t.pelanggan_id = p.id
    JOIN mobil m ON t.mobil_id = m.id
    WHERE t.id = ?
    ORDER BY t.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$transaksi_list = [];
while ($row = $result->fetch_assoc()) {
    $transaksi_list[] = $row;
}
$stmt->close();

$fotoMobil = [
    'MOB-001' => 'Avanza.jpg',
    'MOB-002' => 'brio.jpg',
    'MOB-003' => 'ertiga.jpg',
    'MOB-005' => 'xpander.jpg',
    'MOB-006' => 'inova.jpg',
    'MOB-007' => 'jazz.jpg',
    'MOB-008' => 'fortuner.jpg',
];
$flash = '';
if (isset($_SESSION['flash_message'])) {
    $flash = '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_type'] ?? 'info') . ' alert-dismissible fade show">
        ' . $_SESSION['flash_message'] . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .page-header { background: linear-gradient(135deg,#0f172a,#1e40af); color: white; padding: 40px 0 20px; }
        .booking-card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .booking-card .card-header { border-radius: 14px 14px 0 0; background: white; border-bottom: 1px solid #f1f5f9; }
        .kode-trx { font-family: monospace; font-size: 0.9rem; color: #0d6efd; font-weight: 700; }
        .badge-status-aktif   { background: #dbeafe; color: #1e40af; }
        .badge-status-selesai { background: #d1fae5; color: #065f46; }
        .badge-status-batal   { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow" style="background:#0f172a;">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-car-front-fill text-primary"></i><?= APP_NAME ?>
        </a>
        <div class="d-flex gap-2 ms-auto">
            <a href="<?= BASE_URL ?>index.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-house me-1"></i>Beranda
            </a>
            <a href="<?= BASE_URL ?>katalog.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-grid me-1"></i>Katalog
            </a>
            <a href="pages/logout.php" class="btn btn-sm btn-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h4 class="fw-bold mb-1">
            <i class="bi bi-receipt me-2"></i>Riwayat Booking Saya
        </h4>
        <p class="text-white-50 mb-0">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> 👋</p>
    </div>
</div>

<div class="container py-4">
    <?= $flash ?>

    <?php if (empty($transaksi_list)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">Belum ada pemesanan</h5>
        <a href="<?= BASE_URL ?>katalog.php" class="btn btn-primary mt-2">
            <i class="bi bi-car-front me-2"></i>Lihat Katalog Mobil
        </a>
    </div>
    <?php else: ?>
    <div class="row mb-3">
        <div class="col">
            <h6 class="text-muted"><?= count($transaksi_list) ?> pemesanan ditemukan</h6>
        </div>
        <div class="col-auto">
            <a href="<?= BASE_URL ?>katalog.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Sewa Lagi
            </a>
        </div>
    </div>

    <?php foreach ($transaksi_list as $t):
        $statusClass = 'badge-status-' . $t['status'];
        $statusIcon  = $t['status'] === 'aktif' ? 'clock' : ($t['status'] === 'selesai' ? 'check-circle' : 'x-circle');
    ?>
    <div class="booking-card card">
        <div class="card-header d-flex justify-content-between align-items-center py-3 px-4">
            <div>
                <span class="kode-trx"><?= htmlspecialchars($t['kode_transaksi']) ?></span>
                <span class="text-muted small ms-2">
                    <?= date('d M Y, H:i', strtotime($t['created_at'])) ?>
                </span>
            </div>
            <span class="badge <?= $statusClass ?> px-3 py-2 rounded-pill">
                <i class="bi bi-<?= $statusIcon ?> me-1"></i>
                <?= ucfirst($t['status']) ?>
            </span>
        </div>
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 overflow-hidden flex-shrink-0"
                             style="width:80px;height:60px;background:#f1f5f9;">
                            <?php $namaFoto = $fotoMobil[$t['kode_mobil']] ?? null; ?>
                            <img src="<?= $namaFoto ? BASE_URL.'assets/img/'.$namaFoto : 'https://via.placeholder.com/80x60/0d6efd/fff?text='.urlencode($t['merek']) ?>"
                            style="width:100%;height:100%;object-fit:cover;" alt="">
                        </div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($t['merek'] . ' ' . $t['nama_mobil']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($t['kode_mobil']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mt-3 mt-md-0">
                    <div class="text-muted small">Periode Sewa</div>
                    <div class="fw-semibold small">
                        <?= date('d M', strtotime($t['tanggal_mulai'])) ?> –
                        <?= date('d M Y', strtotime($t['tanggal_selesai'])) ?>
                    </div>
                    <div class="text-muted small"><?= $t['jumlah_hari'] ?> hari</div>
                </div>
                <div class="col-md-3 mt-3 mt-md-0 text-md-end">
                    <div class="text-muted small">Total Biaya</div>
                    <div class="fw-bold text-primary fs-5">
                        Rp <?= number_format($t['total_harga'], 0, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>