<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Dashboard';

require_once __DIR__ . '/../includes/header.php';

$total_mobil      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mobil"))['total'];
$mobil_tersedia   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mobil WHERE status='tersedia'"))['total'];
$mobil_disewa     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mobil WHERE status='disewa'"))['total'];
$transaksi_aktif  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status='aktif'"))['total'];
$total_transaksi  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi"))['total'];
$total_pelanggan  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pelanggan"))['total'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi WHERE status='selesai'"))['total'];

$pendapatan_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(total_harga), 0) as total
    FROM transaksi
    WHERE status = 'selesai' AND DATE_FORMAT(tanggal_mulai, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
"))['total'];

$recent = mysqli_query($conn, "
    SELECT t.*, m.merek, m.nama_mobil, m.kode_mobil, m.gambar, p.nama AS nama_pelanggan
    FROM transaksi t
    JOIN mobil m ON t.mobil_id = m.id
    JOIN pelanggan p ON t.pelanggan_id = p.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
?>

<div class="container-fluid px-4">

    <div class="page-header">
        <div>
            <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
            <div class="breadcrumb-sub">Ringkasan performa bisnis rental hari ini</div>
        </div>
        <div class="d-flex gap-2">
            <a href="laporan.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-bar-chart-line me-1"></i>Lihat Laporan
            </a>
            <a href="transaksi.php?action=tambah" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Transaksi Baru
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 fade-in-up">
            <div class="stat-card" style="--stat-color:#4f46e5; --stat-icon-bg:#eef2ff;">
                <div class="stat-top">
                    <div>
                        <div class="stat-number"><?= $total_mobil ?></div>
                        <div class="stat-label">Total Armada</div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up">
            <div class="stat-card" style="--stat-color:#059669; --stat-icon-bg:#ecfdf5;">
                <div class="stat-top">
                    <div>
                        <div class="stat-number"><?= $mobil_tersedia ?></div>
                        <div class="stat-label">Mobil Tersedia</div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up">
            <div class="stat-card" style="--stat-color:#d97706; --stat-icon-bg:#fffbeb;">
                <div class="stat-top">
                    <div>
                        <div class="stat-number"><?= $mobil_disewa ?></div>
                        <div class="stat-label">Sedang Disewa</div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up">
            <div class="stat-card" style="--stat-color:#dc2626; --stat-icon-bg:#fef2f2;">
                <div class="stat-top">
                    <div>
                        <div class="stat-number"><?= $transaksi_aktif ?></div>
                        <div class="stat-label">Transaksi Aktif</div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card revenue-card h-100">
                <div class="card-body">
                    <div class="revenue-label">Total Pendapatan</div>
                    <div class="revenue-amount">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                    <div class="revenue-sub">dari seluruh transaksi yang selesai</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-sm-6">
            <div class="stat-card h-100" style="--stat-color:#2563eb; --stat-icon-bg:#eff6ff;">
                <div class="stat-top">
                    <div>
                        <div class="stat-number" style="font-size:1.4rem;">
                            Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.') ?>
                        </div>
                        <div class="stat-label">Pendapatan Bulan Ini</div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-sm-6">
            <div class="stat-card h-100" style="--stat-color:#7c3aed; --stat-icon-bg:#f5f3ff;">
                <div class="stat-top">
                    <div>
                        <div class="stat-number"><?= $total_pelanggan ?></div>
                        <div class="stat-label">Pelanggan Terdaftar</div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Total Transaksi</span>
                    <span class="fw-700"><?= $total_transaksi ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h6><i class="bi bi-clock-history me-2 text-primary"></i>Transaksi Terbaru</h6>
                    <a href="transaksi.php" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Pelanggan</th>
                                    <th>Mobil</th>
                                    <th>Periode</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent && $recent->num_rows > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                                    <tr>
                                        <td class="fw-700 text-primary"><?= htmlspecialchars($row['kode_transaksi']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php 
                                                if (!empty($row['gambar'])) {
                                                    $nama_gambar = htmlspecialchars($row['gambar']);
                                                } else {
                                                    $kode = strtoupper($row['kode_mobil']);
                                                    if ($kode === 'MOB-001') $nama_gambar = 'Avanza.jpg';
                                                    elseif ($kode === 'MOB-002') $nama_gambar = 'brio.jpg';
                                                    elseif ($kode === 'MOB-003') $nama_gambar = 'ertiga.jpg';
                                                    elseif ($kode === 'MOB-005') $nama_gambar = 'xpander.jpg';
                                                    elseif ($kode === 'MOB-006') $nama_gambar = 'inova.jpg';
                                                    elseif ($kode === 'MOB-007') $nama_gambar = 'jazz.jpg';
                                                    else {
                                                        $nama_gambar = strtolower($row['merek']) . '.jpg'; 
                                                    }
                                                }
                                                ?>
                                                <img src="../assets/img/<?= $nama_gambar ?>" 
                                                    alt="Mobil" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;"
                                                    onerror="this.src='https://via.placeholder.com/60x40/0d6efd/fff?text=<?= urlencode($row['merek']) ?>';">
                                                <div>
                                                    <?= htmlspecialchars($row['merek'] . ' ' . $row['nama_mobil']) ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($row['kode_mobil']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small">
                                            <?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?> -
                                            <?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?>
                                            <br><span class="text-muted"><?= $row['jumlah_hari'] ?> hari</span>
                                        </td>
                                        <td class="fw-700">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge status-<?= htmlspecialchars($row['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <p>Belum ada transaksi</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6><i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Aksi Cepat</h6>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="mobil.php?action=tambah" class="quick-action-btn">
                        <div class="qa-icon" style="background:#eef2ff; color:#4f46e5;">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <div>
                            <div>Tambah Mobil</div>
                            <small class="text-muted">Daftarkan unit armada baru</small>
                        </div>
                    </a>
                    <a href="pelanggan.php?action=tambah" class="quick-action-btn">
                        <div class="qa-icon" style="background:#ecfdf5; color:#059669;">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div>
                            <div>Daftarkan Pelanggan</div>
                            <small class="text-muted">Tambah data pelanggan baru</small>
                        </div>
                    </a>
                    <a href="transaksi.php?action=tambah" class="quick-action-btn">
                        <div class="qa-icon" style="background:#fffbeb; color:#d97706;">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <div>Buat Transaksi Baru</div>
                            <small class="text-muted">Catat sewa mobil baru</small>
                        </div>
                    </a>
                    <a href="laporan.php" class="quick-action-btn">
                        <div class="qa-icon" style="background:#eff6ff; color:#2563eb;">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
                        <div>
                            <div>Lihat Laporan</div>
                            <small class="text-muted">Statistik & rekap pendapatan</small>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>katalog.php" target="_blank" class="quick-action-btn">
                        <div class="qa-icon" style="background:#f5f3ff; color:#7c3aed;">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div>
                            <div>Lihat Katalog Publik</div>
                            <small class="text-muted">Tampilan untuk pelanggan</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<button id="scrollTopBtn" class="d-flex" title="Kembali ke atas">
    <i class="bi bi-arrow-up"></i>
</button>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>