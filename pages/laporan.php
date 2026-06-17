<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Laporan & Statistik';

$bulan  = $_GET['bulan'] ?? date('Y-m');
$filter = $bulan ? "WHERE DATE_FORMAT(tanggal_mulai,'%Y-%m') = '$bulan'" : '';

$laporan_result = $conn->query("
    SELECT * FROM v_transaksi_detail $filter
    ORDER BY created_at DESC
");
$laporan_list = [];
while ($row = $laporan_result->fetch_assoc()) $laporan_list[] = $row;

$pendapatan_result = $conn->query("
    SELECT DATE_FORMAT(tanggal_mulai,'%Y-%m') AS bulan,
           DATE_FORMAT(tanggal_mulai,'%M %Y') AS label_bulan,
           COUNT(*) AS jumlah_transaksi,
           SUM(total_harga) AS total_pendapatan
    FROM transaksi
    WHERE status = 'selesai'
    GROUP BY DATE_FORMAT(tanggal_mulai,'%Y-%m')
    ORDER BY bulan DESC
    LIMIT 6
");
$pendapatan_list = [];
while ($row = $pendapatan_result->fetch_assoc()) $pendapatan_list[] = $row;

$stat_mobil_result = $conn->query("
    SELECT * FROM v_statistik_mobil
    ORDER BY total_pendapatan DESC
");
$stat_mobil = [];
while ($row = $stat_mobil_result->fetch_assoc()) $stat_mobil[] = $row;

$total_pendapatan = $conn->query("SELECT COALESCE(SUM(total_harga),0) AS t FROM transaksi WHERE status='selesai'")->fetch_assoc()['t'];
$total_transaksi  = $conn->query("SELECT COUNT(*) AS t FROM transaksi")->fetch_assoc()['t'];
$transaksi_aktif  = $conn->query("SELECT COUNT(*) AS t FROM transaksi WHERE status='aktif'")->fetch_assoc()['t'];

$log_result = $conn->query("SELECT * FROM log_transaksi ORDER BY waktu DESC LIMIT 10");
$log_list = [];
while ($row = $log_result->fetch_assoc()) $log_list[] = $row;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Laporan & Statistik</h4>
            <p class="text-muted mb-0">Analisis performa bisnis rental</p>
        </div>
        <a href="laporan.php?export=1&bulan=<?= urlencode($bulan) ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 text-center border-0 shadow-sm" style="border-radius:14px; background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                <i class="bi bi-cash-stack text-primary fs-2 mb-2"></i>
                <div class="fw-bold fs-5">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                <div class="text-muted small">Total Pendapatan (Selesai)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center border-0 shadow-sm" style="border-radius:14px; background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                <i class="bi bi-receipt text-success fs-2 mb-2"></i>
                <div class="fw-bold fs-5"><?= $total_transaksi ?> Transaksi</div>
                <div class="text-muted small">Total Semua Transaksi</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center border-0 shadow-sm" style="border-radius:14px; background:linear-gradient(135deg,#fefce8,#fef9c3);">
                <i class="bi bi-clock-history text-warning fs-2 mb-2"></i>
                <div class="fw-bold fs-5"><?= $transaksi_aktif ?> Aktif</div>
                <div class="text-muted small">Transaksi Berjalan</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius:14px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Laporan Transaksi</h6>
                    <form class="d-flex gap-2" method="GET">
                        <input type="month" name="bulan" class="form-control form-control-sm" value="<?= htmlspecialchars($bulan) ?>">
                        <button class="btn btn-sm btn-primary">Filter</button>
                        <a href="laporan.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4">Kode</th>
                                    <th>Pelanggan</th>
                                    <th>Mobil</th>
                                    <th>Tgl Sewa</th>
                                    <th>Hari</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($laporan_list)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data</td></tr>
                            <?php else: foreach ($laporan_list as $l): ?>
                                <tr>
                                    <td class="px-4 fw-semibold text-primary small"><?= htmlspecialchars($l['kode_transaksi']) ?></td>
                                    <td><?= htmlspecialchars($l['nama_pelanggan']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($l['telepon']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($l['merek'] . ' ' . $l['nama_mobil']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($l['tanggal_mulai'])) ?></td>
                                    <td class="text-center"><?= $l['jumlah_hari'] ?>h</td>
                                    <td class="fw-semibold">Rp <?= number_format($l['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?=
                                            $l['status'] === 'selesai' ? 'bg-success' :
                                            ($l['status'] === 'aktif' ? 'bg-primary' : 'bg-secondary')
                                        ?>">
                                            <?= ucfirst($l['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
 
            <div class="card shadow-sm border-0 mt-4" style="border-radius:14px;">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-lightning me-2 text-warning"></i>Log Trigger Otomatis</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4">Kode Transaksi</th>
                                    <th>Aksi</th>
                                    <th>Perubahan Status</th>
                                    <th>Keterangan</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($log_list as $log): ?>
                                <tr>
                                    <td class="px-4 fw-semibold text-primary small"><?= htmlspecialchars($log['kode_transaksi']) ?></td>
                                    <td><span class="badge bg-<?= $log['aksi'] === 'INSERT' ? 'success' : ($log['aksi'] === 'UPDATE' ? 'warning text-dark' : 'danger') ?>">
                                        <?= $log['aksi'] ?>
                                    </span></td>
                                    <td class="small">
                                        <?= $log['status_lama'] ? htmlspecialchars($log['status_lama'] . ' → ' . $log['status_baru']) : htmlspecialchars($log['status_baru'] ?? '-') ?>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars(mb_substr($log['keterangan'], 0, 60)) ?>...</td>
                                    <td class="small"><?= date('d/m H:i', strtotime($log['waktu'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
   
            <div class="card shadow-sm border-0 mb-4" style="border-radius:14px;">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-success"></i>Pendapatan 6 Bulan</h6>
                </div>
                <div class="card-body px-4">
                    <?php foreach ($pendapatan_list as $pd): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= htmlspecialchars($pd['label_bulan']) ?></span>
                            <span class="fw-semibold">Rp <?= number_format($pd['total_pendapatan'], 0, ',', '.') ?></span>
                        </div>
                        <?php
                        $max = max(array_column($pendapatan_list, 'total_pendapatan'));
                        $pct = $max > 0 ? round(($pd['total_pendapatan'] / $max) * 100) : 0;
                        ?>
                        <div class="progress" style="height:6px; border-radius:10px;">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%; border-radius:10px;"></div>
                        </div>
                        <small class="text-muted"><?= $pd['jumlah_transaksi'] ?> transaksi</small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius:14px;">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-car-front me-2 text-primary"></i>Performa Armada</h6>
                    <small class="text-muted">dari VIEW v_statistik_mobil</small>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($stat_mobil as $s): ?>
                    <div class="px-4 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($s['merek'] . ' ' . $s['nama_mobil']) ?></div>
                                <div class="text-muted" style="font-size:.75rem;">
                                    <?= $s['total_disewa'] ?>x disewa
                                    <?php if ($s['terakhir_disewa']): ?>
                                    · terakhir <?= date('d/m/Y', strtotime($s['terakhir_disewa'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary small">
                                    Rp <?= number_format($s['total_pendapatan'], 0, ',', '.') ?>
                                </div>
                                <span class="badge <?= $s['status'] === 'tersedia' ? 'bg-success' : 'bg-danger' ?> rounded-pill" style="font-size:.65rem;">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan-' . date('Y-m-d') . '.csv"');
    echo "Kode Transaksi,Pelanggan,Mobil,Tgl Sewa,Tgl Kembali,Hari,Total,Status\n";
    foreach ($laporan_list as $l) {
        echo implode(',', [
            $l['kode_transaksi'], $l['nama_pelanggan'],
            $l['merek'].' '.$l['nama_mobil'],
            $l['tanggal_mulai'], $l['tanggal_selesai'],
            $l['jumlah_hari'], $l['total_harga'], $l['status']
        ]) . "\n";
    }
    exit;
}

require_once __DIR__ . '/../includes/footer.php';
?>
