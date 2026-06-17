<?php
session_start();
require_once __DIR__ . '/includes/config.php';

$page_title = 'Katalog Mobil';

$filter = $_GET['filter'] ?? 'semua';
$search = htmlspecialchars(trim($_GET['search'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($filter === 'tersedia') {
    $where[] = "m.status = 'tersedia'";
} elseif ($filter === 'disewa') {
    $where[] = "m.status = 'disewa'";
}

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = "(m.nama_mobil LIKE ? OR m.merek LIKE ? OR m.warna LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT m.*, 
        (SELECT COUNT(*) FROM transaksi t WHERE t.mobil_id = m.id AND t.status='selesai') AS total_disewa
        FROM mobil m $where_sql ORDER BY m.id LIMIT 7";

if ($types) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$mobil_list = [];
while ($row = $result->fetch_assoc()) {
    $mobil_list[] = $row;
}

$total_tersedia = $conn->query("SELECT COUNT(*) as c FROM mobil WHERE status='tersedia'")->fetch_assoc()['c'];
$total_disewa_now = $conn->query("SELECT COUNT(*) as c FROM mobil WHERE status='disewa'")->fetch_assoc()['c'];

function getCarImage($merek, $warna, $gambar = null) {
    if ($gambar) return BASE_URL . 'assets/img/' . $gambar;

    $colors = [
        'Putih'   => '#e2e8f0', 'Merah'  => '#dc3545', 'Silver' => '#9ca3af',
        'Hitam'   => '#343a40', 'Abu-abu'=> '#6c757d', 'Biru'   => '#0d6efd',
        'Kuning'  => '#ffc107', 'Hijau'  => '#198754'
    ];
    $hex = $colors[$warna] ?? '#475569';
    $light = ['Putih', 'Kuning', 'Silver'];
    $textColor = in_array($warna, $light) ? '#1e293b' : '#ffffff';

    $label = trim($merek);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="240" viewBox="0 0 400 240">'
         . '<rect width="400" height="240" fill="' . $hex . '"/>'
         . '<text x="50%" y="46%" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="700" '
         . 'fill="' . $textColor . '" text-anchor="middle" dominant-baseline="middle">' . htmlspecialchars($label) . '</text>'
         . '<text x="50%" y="62%" font-family="Arial, Helvetica, sans-serif" font-size="15" '
         . 'fill="' . $textColor . '" opacity="0.75" text-anchor="middle" dominant-baseline="middle">Foto belum tersedia</text>'
         . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function getCarFallback($merek) {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="320" viewBox="0 0 800 320">'
         . '<rect width="800" height="320" fill="#0d6efd"/>'
         . '<text x="50%" y="50%" font-family="Arial, Helvetica, sans-serif" font-size="40" font-weight="700" '
         . 'fill="#ffffff" text-anchor="middle" dominant-baseline="middle">' . htmlspecialchars($merek) . '</text>'
         . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
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

        /* ===== KATALOG PAGE STYLES ===== */
        .katalog-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #0d6efd 100%);
            padding: 60px 0 80px;
            position: relative;
            overflow: hidden;
        }
        .katalog-hero::before {
            content: '\F5BB';
            font-family: 'bootstrap-icons';
            font-size: 280px;
            position: absolute;
            right: -40px;
            top: -40px;
            opacity: 0.05;
            color: white;
        }
        .katalog-hero h1 { color: white; font-weight: 800; }
        .katalog-hero p { color: rgba(255,255,255,0.75); }

        /* Statistik unit (FIX: warna teks pasti putih & kontras) */
        .hero-stat {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .hero-stat .icon-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .hero-stat span {
            color: rgba(255,255,255,0.85);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ===== FILTER BAR ===== */
        .filter-bar {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
            padding: 20px 24px;
            margin-top: -32px;
            position: relative;
            z-index: 10;
        }
        .filter-btn {
            border-radius: 20px;
            padding: 6px 18px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1.5px solid #dee2e6;
            background: white;
            color: #495057;
            transition: all 0.18s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .filter-btn:hover { color: #495057; border-color: #0d6efd; }
        .filter-btn.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: white !important;
        }
        .filter-btn.active-success {
            background: #198754;
            border-color: #198754;
            color: white !important;
        }
        .filter-btn.active-danger {
            background: #dc3545;
            border-color: #dc3545;
            color: white !important;
        }

        /* ===== MOBIL CARD ===== */
        .mobil-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
            height: 100%;
            background: #fff;
        }
        .mobil-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.14);
        }
        .mobil-card .card-img-wrapper {
            position: relative;
            overflow: hidden;
            height: 200px;
            background: #f1f5f9;
        }
        .mobil-card .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }
        .mobil-card:hover .card-img-wrapper img {
            transform: scale(1.06);
        }
        .status-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge-tersedia { background: #d1fae5; color: #065f46; }
        .badge-disewa   { background: #fee2e2; color: #991b1b; }

        .mobil-card .card-body { padding: 20px; }
        .mobil-card .mobil-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;  
            margin-bottom: 4px;
        }
        .mobil-card .mobil-merek {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 12px;
        }
        .mobil-card .mobil-specs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .spec-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.78rem;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }
        .harga-sewa {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0d6efd;
        }
        .harga-sewa small { font-size: 0.75rem; font-weight: 400; color: #94a3b8; }
        .btn-sewa {
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 9px 0;
        }
        .btn-detail {
            border-radius: 10px;
            font-size: 0.875rem;
            padding: 9px 0;
        }

        /* ===== MODAL DETAIL ===== */
        .modal-mobil .modal-content { border-radius: 20px; overflow: hidden; border: none; }
        .modal-mobil .modal-img-wrapper { height: 260px; overflow: hidden; background: #f1f5f9; }
        .modal-mobil .modal-img-wrapper img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .modal-mobil .modal-body { padding: 28px; }
        .spec-table td:first-child { width: 40%; color: #64748b; font-size: 0.875rem; }
        .spec-table td:last-child { font-weight: 600; font-size: 0.875rem; color: #0f172a; }

        /* ===== FORM SEWA MODAL ===== */
        .modal-sewa .modal-content { border-radius: 20px; border: none; }
        .modal-sewa .modal-header { background: linear-gradient(135deg, #0f172a, #0d6efd); color: white; border: none; }
        .modal-sewa .modal-header .btn-close { filter: invert(1); }
        .total-preview {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .total-preview .total-harga {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0d6efd;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state { padding: 60px 20px; text-align: center; }
        .empty-state i { font-size: 4rem; color: #cbd5e1; margin-bottom: 16px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow" style="background: #0f172a;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>katalog.php">
            <i class="bi bi-car-front-fill text-primary fs-5"></i>
            <?= APP_NAME ?>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navKatalog">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navKatalog">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="katalog.php">
                        <i class="bi bi-grid me-1"></i>Katalog Mobil
                    </a>
                </li>
                <?php if (isset($_SESSION['id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="pages/booking.php">
                        <i class="bi bi-calendar-check me-1"></i>Booking Saya
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto gap-2 align-items-lg-center">
                <?php if (isset($_SESSION['id'])): ?>
                <li class="nav-item">
                    <span class="nav-link text-white-50">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                    </span>
                </li>
                <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'petugas'])): ?>
                <li class="nav-item">
                    <a class="btn btn-sm btn-outline-light" href="pages/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard Owner
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="btn btn-sm btn-danger" href="pages/logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="btn btn-sm btn-primary" href="pages/login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-outline-light" href="pages/register.php">
                        <i class="bi bi-person-plus me-1"></i>Daftar
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<section class="katalog-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <p class="text-white-50 text-uppercase fw-semibold mb-2" style="letter-spacing:2px; font-size:.8rem;">
                    <i class="bi bi-stars me-1"></i> Armada Terbaik Kami
                </p>
                <h1 class="display-5 fw-bold mb-3">
                    Pilih Mobil Impianmu,<br>
                    <span style="color:#93c5fd;">Nikmati Perjalananmu</span>
                </h1>
                <p class="lead mb-4">
                    Tersedia <?= $total_tersedia ?> unit siap disewa harga terjangkau, kondisi prima.
                </p>
                <div class="d-flex gap-4 flex-wrap">
                    <div class="hero-stat">
                        <div class="icon-circle bg-success">
                            <i class="bi bi-check-lg text-white"></i>
                        </div>
                        <span><?= $total_tersedia ?> Unit Tersedia</span>
                    </div>
                    <div class="hero-stat">
                        <div class="icon-circle bg-danger">
                            <i class="bi bi-clock text-white"></i>
                        </div>
                        <span><?= $total_disewa_now ?> Sedang Disewa</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="filter-bar mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0"
                               placeholder="Cari merek, nama, warna..."
                               value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary px-3" type="submit">Cari</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="katalog.php?filter=semua&search=<?= urlencode($search) ?>"
                       class="filter-btn <?= $filter === 'semua' ? 'active' : '' ?>">
                        <i class="bi bi-grid me-1"></i>Semua
                    </a>
                    <a href="katalog.php?filter=tersedia&search=<?= urlencode($search) ?>"
                       class="filter-btn <?= $filter === 'tersedia' ? 'active-success' : '' ?>">
                        <i class="bi bi-check-circle me-1"></i>Tersedia
                    </a>
                    <a href="katalog.php?filter=disewa&search=<?= urlencode($search) ?>"
                       class="filter-btn <?= $filter === 'disewa' ? 'active-danger' : '' ?>">
                        <i class="bi bi-clock me-1"></i>Sedang Disewa
                    </a>
                    <span class="ms-auto text-muted small">
                        <?= count($mobil_list) ?> mobil ditemukan
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <?php if (empty($mobil_list)): ?>
    <div class="empty-state">
        <i class="bi bi-car-front d-block"></i>
        <h5 class="text-muted">Tidak ada mobil ditemukan</h5>
        <a href="katalog.php" class="btn btn-outline-primary mt-2">Reset Filter</a>
    </div>
    <?php else: ?>

    <?php
    $row1 = array_slice($mobil_list, 0, 3); 
    $row2 = array_slice($mobil_list, 3, 3);
    $row3 = array_slice($mobil_list, 6, 1); 

    function renderMobilCard($mobil) {
        $imgUrl = getCarImage($mobil['merek'], $mobil['warna'], $mobil['gambar'] ?? null);
        $fallbackUrl = getCarFallback($mobil['merek']);
        $tersedia = $mobil['status'] === 'tersedia';
        $badgeClass = $tersedia ? 'badge-tersedia' : 'badge-disewa';
        $badgeLabel = $tersedia ? '✓ Tersedia' : '⏳ Sedang Disewa';
        ?>
        <div class="col">
            <div class="mobil-card card">
          
                <div class="card-img-wrapper">
                    <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($mobil['merek'] . ' ' . $mobil['nama_mobil']) ?>"
                         onerror="this.onerror=null;this.src='<?= $fallbackUrl ?>';">
                    <span class="status-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                </div>

                <div class="card-body d-flex flex-column">
                    <div class="mobil-name"><?= htmlspecialchars($mobil['merek'] . ' ' . $mobil['nama_mobil']) ?></div>
                    <div class="mobil-merek">
                        <i class="bi bi-tag me-1"></i><?= htmlspecialchars($mobil['kode_mobil']) ?>
                        &nbsp;|&nbsp;
                        <i class="bi bi-calendar3 me-1"></i><?= $mobil['tahun'] ?>
                    </div>

                    <!-- Spesifikasi -->
                    <div class="mobil-specs">
                        <span class="spec-item">
                            <i class="bi bi-people-fill text-primary"></i>
                            <?= $mobil['kapasitas'] ?> Kursi
                        </span>
                        <span class="spec-item">
                            <i class="bi bi-palette text-primary"></i>
                            <?= htmlspecialchars($mobil['warna']) ?>
                        </span>
                        <span class="spec-item">
                            <i class="bi bi-star-fill text-warning"></i>
                            <?= $mobil['total_disewa'] ?>x disewa
                        </span>
                    </div>

                    <!-- Deskripsi singkat -->
                    <p class="text-muted small mb-3" style="line-height:1.5; flex-grow:1;">
                        <?= htmlspecialchars(mb_substr($mobil['deskripsi'] ?? 'Mobil berkualitas tinggi.', 0, 80)) ?>...
                    </p>

                    <!-- Harga -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="harga-sewa">
                            Rp <?= number_format($mobil['harga_per_hari'], 0, ',', '.') ?>
                            <small>/hari</small>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="row g-2 mt-auto">
                        <div class="col-6">
                            <button class="btn btn-outline-primary btn-detail w-100"
                                    onclick="lihatDetail(<?= $mobil['id'] ?>)"
                                    data-id="<?= $mobil['id'] ?>">
                                <i class="bi bi-info-circle me-1"></i>Detail
                            </button>
                        </div>
                        <div class="col-6">
                            <?php if ($tersedia): ?>
                            <button class="btn btn-primary btn-sewa w-100"
                                    onclick="bukaSewa(<?= htmlspecialchars(json_encode($mobil), ENT_QUOTES) ?>)">
                                <i class="bi bi-cart-plus me-1"></i>Sewa
                            </button>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sewa w-100" disabled>
                                <i class="bi bi-x-circle me-1"></i>Tidak Tersedia
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <!-- Baris 1: 3 mobil -->
    <?php if (!empty($row1)): ?>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($row1 as $mobil): renderMobilCard($mobil); endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Baris 2: 3 mobil -->
    <?php if (!empty($row2)): ?>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($row2 as $mobil): renderMobilCard($mobil); endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Baris 3: 1 mobil (tengah) -->
    <?php if (!empty($row3)): ?>
    <div class="row justify-content-center g-4 mb-4">
        <div class="col-12 col-md-4">
            <?php renderMobilCard($row3[0]); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<!-- MODAL DETAIL MOBIL -->
<div class="modal fade modal-mobil" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-img-wrapper">
                <img id="detailImg" src="" alt="">
                <button class="btn-close position-absolute top-0 end-0 m-3 bg-white rounded-circle p-2"
                        data-bs-dismiss="modal" style="opacity:1;"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold mb-1" id="detailNama">-</h4>
                        <span class="text-muted small" id="detailKode">-</span>
                    </div>
                    <span id="detailBadge" class="status-badge fs-6">-</span>
                </div>
                <p class="text-muted mb-4" id="detailDesk">-</p>
                <table class="table spec-table table-borderless">
                    <tbody>
                        <tr><td><i class="bi bi-tag me-2 text-primary"></i>Merek</td><td id="d-merek">-</td></tr>
                        <tr><td><i class="bi bi-calendar3 me-2 text-primary"></i>Tahun</td><td id="d-tahun">-</td></tr>
                        <tr><td><i class="bi bi-palette me-2 text-primary"></i>Warna</td><td id="d-warna">-</td></tr>
                        <tr><td><i class="bi bi-people me-2 text-primary"></i>Kapasitas</td><td id="d-kapasitas">-</td></tr>
                        <tr><td><i class="bi bi-cash me-2 text-primary"></i>Harga Sewa</td><td id="d-harga" class="text-primary fw-bold fs-5">-</td></tr>
                    </tbody>
                </table>
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button class="btn btn-primary flex-grow-1" id="btnSewaFromDetail">
                        <i class="bi bi-cart-plus me-2"></i>Sewa Mobil Ini
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORM SEWA -->
<div class="modal fade modal-sewa" id="modalSewa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-car-front me-2"></i>Form Pemesanan Sewa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info Mobil -->
                <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded-3" style="background:#f8fafc; border: 1.5px solid #e2e8f0;">
                    <i class="bi bi-car-front-fill text-primary fs-3"></i>
                    <div>
                        <div class="fw-bold" id="sewa-nama-mobil">-</div>
                        <div class="text-muted small" id="sewa-harga-info">-</div>
                    </div>
                </div>

                <?php if (!isset($_SESSION['id'])): ?>
                <!-- Peringatan harus login -->
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div>
                        Kamu harus <a href="pages/login.php" class="fw-bold">login</a> atau
                        <a href="pages/register.php" class="fw-bold">daftar</a> dulu untuk menyewa mobil.
                    </div>
                </div>
                <?php else: ?>
                <form method="POST" action="pages/booking_action.php" id="formSewa">
                    <input type="hidden" name="mobil_id" id="sewa-mobil-id">
                    <input type="hidden" name="harga_per_hari" id="sewa-harga-day">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Penyewa</label>
                        <input type="text" class="form-control" name="nama_penyewa"
                               value="<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. KTP / NIK</label>
                        <input type="text" class="form-control" name="nik" placeholder="16 digit NIK" maxlength="16" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Telepon</label>
                        <input type="text" class="form-control" name="no_telp"
                               value="<?= htmlspecialchars($_SESSION['no_telp'] ?? '') ?>" placeholder="08xxxxxxxx" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="tgl_mulai" id="tgl-mulai"
                                   min="<?= date('Y-m-d') ?>" required onchange="hitungTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tanggal Kembali</label>
                            <input type="date" class="form-control" name="tgl_kembali" id="tgl-kembali"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="hitungTotal()">
                        </div>
                    </div>

                    <!-- Preview Total -->
                    <div class="total-preview mb-4" id="totalPreview">
                        <div class="text-muted small mb-1">Estimasi Total Biaya</div>
                        <div class="total-harga" id="previewTotal">Rp 0</div>
                        <div class="text-muted small" id="previewHari">Pilih tanggal terlebih dahulu</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-check-circle me-2"></i>Konfirmasi Pemesanan
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="py-4 mt-auto" style="background:#0f172a; color:rgba(255,255,255,0.6);">
    <div class="container text-center">
        <p class="mb-1">
            <i class="bi bi-car-front-fill text-primary me-2"></i>
            <strong class="text-white"><?= APP_NAME ?></strong> Sistem Rental Mobil
        </p>
        <p class="small mb-0">© <?= date('Y') ?> RentWheels. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalDetail = new bootstrap.Modal(document.getElementById('modalDetail'));
const modalSewa   = new bootstrap.Modal(document.getElementById('modalSewa'));
let currentMobil  = null;

function carPlaceholder(merek, w = 800, h = 320) {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
        <rect width="${w}" height="${h}" fill="#0d6efd"/>
        <text x="50%" y="50%" font-family="Arial, Helvetica, sans-serif" font-size="${Math.round(h*0.12)}"
              font-weight="700" fill="#ffffff" text-anchor="middle" dominant-baseline="middle">${merek}</text>
    </svg>`;
    return 'data:image/svg+xml;base64,' + btoa(svg);
}

function lihatDetail(id) {
    fetch('pages/get_mobil.php?id=' + id)
        .then(r => r.json())
        .then(m => {
            if (m.error) { alert(m.error); return; }
            currentMobil = m;
            const tersedia = m.status === 'tersedia';
            document.getElementById('detailImg').src = m.gambar
                ? '<?= BASE_URL ?>assets/img/' + m.gambar
                : carPlaceholder(m.merek);
            document.getElementById('detailImg').onerror = function() {
                this.onerror = null;
                this.src = carPlaceholder(m.merek);
            };
            document.getElementById('detailNama').textContent = m.merek + ' ' + m.nama_mobil;
            document.getElementById('detailKode').textContent = m.kode_mobil;
            document.getElementById('detailDesk').textContent = m.deskripsi || 'Tidak ada deskripsi.';
            document.getElementById('detailBadge').textContent = tersedia ? '✓ Tersedia' : '⏳ Sedang Disewa';
            document.getElementById('detailBadge').className = 'status-badge fs-6 ' + (tersedia ? 'badge-tersedia' : 'badge-disewa');
            document.getElementById('d-merek').textContent = m.merek;
            document.getElementById('d-tahun').textContent = m.tahun;
            document.getElementById('d-warna').textContent = m.warna;
            document.getElementById('d-kapasitas').textContent = m.kapasitas + ' orang';
            document.getElementById('d-harga').textContent = 'Rp ' + parseInt(m.harga_per_hari).toLocaleString('id-ID') + '/hari';
            const btnSewa = document.getElementById('btnSewaFromDetail');
            if (tersedia) {
                btnSewa.disabled = false;
                btnSewa.classList.remove('btn-secondary');
                btnSewa.classList.add('btn-primary');
                btnSewa.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Sewa Mobil Ini';
                btnSewa.onclick = () => { modalDetail.hide(); setTimeout(() => bukaSewa(m), 400); };
            } else {
                btnSewa.disabled = true;
                btnSewa.classList.remove('btn-primary');
                btnSewa.classList.add('btn-secondary');
                btnSewa.textContent = 'Tidak Tersedia';
            }
            modalDetail.show();
        })
        .catch(() => alert('Gagal memuat data mobil.'));
}

function bukaSewa(mobil) {
    currentMobil = mobil;
    document.getElementById('sewa-nama-mobil').textContent = mobil.merek + ' ' + mobil.nama_mobil;
    document.getElementById('sewa-harga-info').textContent =
        'Rp ' + parseInt(mobil.harga_per_hari).toLocaleString('id-ID') + ' / hari';
    document.getElementById('sewa-mobil-id').value = mobil.id;
    document.getElementById('sewa-harga-day').value = mobil.harga_per_hari;
    document.getElementById('previewTotal').textContent = 'Rp 0';
    document.getElementById('previewHari').textContent = 'Pilih tanggal terlebih dahulu';
    modalSewa.show();
}

function hitungTotal() {
    const mulai  = document.getElementById('tgl-mulai')?.value;
    const kembali = document.getElementById('tgl-kembali')?.value;
    const harga  = parseFloat(document.getElementById('sewa-harga-day')?.value || 0);
    if (!mulai || !kembali) return;
    const d1 = new Date(mulai), d2 = new Date(kembali);
    let hari = Math.ceil((d2 - d1) / 86400000);
    if (hari < 1) {
        document.getElementById('previewHari').textContent = '⚠️ Tanggal tidak valid!';
        document.getElementById('previewTotal').textContent = 'Rp 0';
        return;
    }
    const total = harga * hari;
    document.getElementById('previewTotal').textContent =
        'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('previewHari').textContent =
        hari + ' hari × Rp ' + harga.toLocaleString('id-ID') + '/hari';
}
</script>
</body>
</html>