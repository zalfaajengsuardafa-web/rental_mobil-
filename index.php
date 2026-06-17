<?php
session_start();
require_once __DIR__ . '/includes/config.php';

$page_title = 'Beranda';

$total_mobil     = $conn->query("SELECT COUNT(*) as c FROM mobil")->fetch_assoc()['c'];
$total_tersedia  = $conn->query("SELECT COUNT(*) as c FROM mobil WHERE status='tersedia'")->fetch_assoc()['c'];
$total_pelanggan = $conn->query("SELECT COUNT(*) as c FROM pelanggan")->fetch_assoc()['c'];
$total_selesai   = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE status='selesai'")->fetch_assoc()['c'];

$unggulan_result = $conn->query("
    SELECT * FROM mobil
    WHERE status = 'tersedia'
    ORDER BY harga_per_hari DESC
    LIMIT 3
");
$mobil_unggulan = [];
while ($row = $unggulan_result->fetch_assoc()) {
    $mobil_unggulan[] = $row;
}

function carColorHex($warna) {
    $colors = [
        'Putih'   => '#e2e8f0', 'Merah'  => '#dc3545', 'Silver' => '#9ca3af',
        'Hitam'   => '#343a40', 'Abu-abu'=> '#6c757d', 'Biru'   => '#0d6efd',
        'Kuning'  => '#ffc107', 'Hijau'  => '#198754'
    ];
    return $colors[$warna] ?? '#475569';
}

function carIllustrationSvg($hex, $label) {
    $carPath = 'M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679q.05.242.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.8.8 0 0 0 .381-.404l.792-1.848ZM3 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2m10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2M6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2zM2.906 5.189a.51.51 0 0 0 .497.731c.91-.073 3.35-.17 4.597-.17s3.688.097 4.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 11.691 3H4.309a.5.5 0 0 0-.447.276L2.906 5.19Z';

    return '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="240" viewBox="0 0 400 240">'
         . '<defs><linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">'
         . '<stop offset="0" stop-color="#e0e7ef"/><stop offset="1" stop-color="#f8fafc"/>'
         . '</linearGradient></defs>'
         . '<rect width="400" height="240" fill="url(#bg)"/>'
         . '<rect x="40" y="50" width="320" height="160" rx="18" fill="#0d6efd" opacity="0.06"/>'
         . '<ellipse cx="200" cy="222" rx="130" ry="10" fill="#0f172a" opacity="0.08"/>'
         . '<g transform="translate(100,15) scale(12.5)">'
         . '<path d="' . $carPath . '" fill="' . $hex . '" stroke="#1e293b" stroke-width="0.12" stroke-opacity="0.35"/>'
         . '</g>'
         . '<text x="20" y="36" font-family="Arial, Helvetica, sans-serif" font-size="24" font-weight="800" '
         . 'fill="#0f172a" opacity="0.85">' . htmlspecialchars($label) . '</text>'
         . '</svg>';
}

function getCarImage($merek, $warna, $gambar = null) {
    if ($gambar) return BASE_URL . 'assets/img/' . $gambar;
    return 'data:image/svg+xml;base64,' . base64_encode(carIllustrationSvg(carColorHex($warna), trim($merek)));
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
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }

        /* ===== HERO ===== */
        .home-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #0d6efd 100%);
            padding: 80px 0 100px;
            position: relative;
            overflow: hidden;
        }
        .home-hero::before {
            content: '\F5BB';
            font-family: 'bootstrap-icons';
            font-size: 320px;
            position: absolute;
            right: -50px; top: -50px;
            opacity: 0.05;
            color: white;
        }
        .home-hero h1 { color: white; font-weight: 800; }
        .home-hero p  { color: rgba(255,255,255,0.75); }

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

        /* ===== AKUN DROPDOWN ===== */
        .account-dropdown {
            border: none;
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.16);
            padding: 8px;
            min-width: 220px;
            margin-top: 10px !important;
        }
        .account-dropdown .dropdown-item { border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; }
        .account-dropdown .dropdown-item:hover { background: #f1f5f9; }
        .account-dropdown .dropdown-item.text-danger:hover { background: #fee2e2; }

        /* ===== SECTION HEADING ===== */
        .section-eyebrow {
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #0d6efd;
        }

        /* ===== FEATURE CARDS ===== */
        .feature-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            padding: 28px 24px;
            height: 100%;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.25s ease;
        }
        .feature-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.10); }
        .feature-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 16px;
        }

        /* ===== MOBIL UNGGULAN CARD ===== */
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
            height: 180px;
            background: #f1f5f9;
        }
        .mobil-card .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .mobil-card .card-body { padding: 18px; }
        .mobil-card .mobil-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .mobil-card .harga-sewa {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0d6efd;
        }
        .mobil-card .harga-sewa small { font-size: 0.75rem; font-weight: 400; color: #94a3b8; }

        /* ===== CTA SECTION ===== */
        .cta-section {
            background: linear-gradient(135deg, #0d6efd, #1e40af);
            border-radius: 24px;
            padding: 48px;
            color: white;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow" style="background: #0f172a;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-car-front-fill text-primary fs-5"></i>
            <?= APP_NAME ?>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navHome">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navHome">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="<?= BASE_URL ?>index.php">
                        <i class="bi bi-house me-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>katalog.php">
                        <i class="bi bi-grid me-1"></i>Katalog Mobil
                    </a>
                </li>
                <?php if (isset($_SESSION['id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>pages/booking.php">
                        <i class="bi bi-calendar-check me-1"></i>Booking Saya
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>tentang.php">
                        <i class="bi bi-building me-1"></i>Tentang Kami
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto gap-2 align-items-lg-center">
                <?php if (isset($_SESSION['id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <span>Akun</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown">
                        <li class="px-3 py-2">
                            <div class="fw-semibold text-dark">
                                <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                            </div>
                            <div class="text-muted small">
                                <?= in_array($_SESSION['role'] ?? '', ['admin', 'petugas']) ? 'Owner / Admin' : 'Pelanggan' ?>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'petugas'])): ?>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>pages/dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard Owner
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>pages/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>pages/login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>pages/register.php">
                        <i class="bi bi-person-plus me-1"></i>Daftar
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="home-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <p class="text-white-50 text-uppercase fw-semibold mb-2" style="letter-spacing:2px; font-size:.8rem;">
                    <i class="bi bi-stars me-1"></i> Selamat Datang di <?= APP_NAME ?>
                </p>
                <h1 class="display-4 fw-bold mb-3">
                    Sewa Mobil Jadi<br>
                    <span style="color:#93c5fd;">Lebih Mudah &amp; Transparan</span>
                </h1>
                <p class="lead mb-4">
                    Temukan mobil yang cocok untuk perjalananmu, cek harga sewa
                    secara transparan, dan lakukan pemesanan langsung secara online
                    kapan saja.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="katalog.php" class="btn btn-light fw-semibold px-4">
                        <i class="bi bi-grid me-2"></i>Lihat Katalog Mobil
                    </a>
                    <a href="tentang.php" class="btn btn-outline-light px-4">
                        <i class="bi bi-info-circle me-2"></i>Tentang Kami
                    </a>
                </div>
                <div class="d-flex gap-4 flex-wrap">
                    <div class="hero-stat">
                        <div class="icon-circle bg-success">
                            <i class="bi bi-car-front-fill text-white"></i>
                        </div>
                        <span><?= $total_mobil ?> Unit Armada</span>
                    </div>
                    <div class="hero-stat">
                        <div class="icon-circle bg-primary">
                            <i class="bi bi-check-lg text-white"></i>
                        </div>
                        <span><?= $total_tersedia ?> Siap Disewa</span>
                    </div>
                    <div class="hero-stat">
                        <div class="icon-circle" style="background:#93c5fd;">
                            <i class="bi bi-people-fill text-white"></i>
                        </div>
                        <span><?= $total_pelanggan ?> Pelanggan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="text-center mb-5">
        <p class="section-eyebrow mb-2">Keunggulan</p>
        <h2 class="fw-bold">Kenapa Sewa di <?= APP_NAME ?>?</h2>
        <p class="text-muted">Proses cepat, harga jelas, dan armada yang selalu siap pakai.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background:#dbeafe; color:#1d4ed8;">
                    <i class="bi bi-stars"></i>
                </div>
                <h6 class="fw-bold mb-2">Armada Terawat</h6>
                <p class="text-muted small mb-0">
                    Setiap unit melalui pengecekan rutin agar selalu siap dan nyaman dipakai.
                </p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background:#d1fae5; color:#065f46;">
                    <i class="bi bi-tag-fill"></i>
                </div>
                <h6 class="fw-bold mb-2">Harga Transparan</h6>
                <p class="text-muted small mb-0">
                    Harga per hari ditampilkan jelas di katalog, tanpa biaya tambahan tersembunyi.
                </p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background:#fef3c7; color:#92400e;">
                    <i class="bi bi-laptop"></i>
                </div>
                <h6 class="fw-bold mb-2">Booking Online</h6>
                <p class="text-muted small mb-0">
                    Pilih mobil, isi data, dan konfirmasi pemesanan langsung dari katalog kami.
                </p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background:#fee2e2; color:#991b1b;">
                    <i class="bi bi-headset"></i>
                </div>
                <h6 class="fw-bold mb-2">Layanan Responsif</h6>
                <p class="text-muted small mb-0">
                    Tim kami siap membantu jika ada pertanyaan seputar mobil atau pemesananmu.
                </p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($mobil_unggulan)): ?>
<div class="container my-5">
    <div class="text-center mb-5">
        <p class="section-eyebrow mb-2">Pilihan Terbaik</p>
        <h2 class="fw-bold">Mobil Unggulan Kami</h2>
        <p class="text-muted">Beberapa unit pilihan yang siap kamu sewa hari ini.</p>
    </div>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        
        <?php
        $fotoMobil = [
            8 => 'fortuner.jpg',
            6 => 'inova.jpg',
            5 => 'xpander.jpg',
        ];
        ?>
        <?php foreach ($mobil_unggulan as $mobil): ?>
        <div class="col">
            <div class="mobil-card card">
                <div class="card-img-wrapper">
                    <?php $namaFoto = $fotoMobil[$mobil['id']] ?? 'fortuner.jpg'; ?>
                    <img src="<?= BASE_URL ?>assets/img/<?= $namaFoto ?>"
                         alt="<?= htmlspecialchars($mobil['merek'] . ' ' . $mobil['nama_mobil']) ?>">
                </div>
                <div class="card-body">
                    <div class="mobil-name"><?= htmlspecialchars($mobil['merek'] . ' ' . $mobil['nama_mobil']) ?></div>
                    <div class="text-muted small mb-3">
                        <i class="bi bi-people-fill me-1"></i><?= $mobil['kapasitas'] ?> Kursi
                        &nbsp;|&nbsp;
                        <i class="bi bi-palette me-1"></i><?= htmlspecialchars($mobil['warna']) ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="harga-sewa">
                            Rp <?= number_format($mobil['harga_per_hari'], 0, ',', '.') ?>
                            <small>/hari</small>
                        </div>
                        <a href="katalog.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- CTA -->
<div class="container my-5">
    <div class="cta-section">
        <h3 class="fw-bold mb-2">Siap untuk Perjalanan Berikutnya?</h3>
        <p class="mb-4 opacity-90">Cek armada yang tersedia dan pesan mobil impianmu sekarang.</p>
        <a href="katalog.php" class="btn btn-light fw-semibold px-4">
            <i class="bi bi-grid me-2"></i>Lihat Katalog Mobil
        </a>
    </div>
</div>

<!-- FOOTER -->
<footer class="py-4 mt-5" style="background:#0f172a; color:rgba(255,255,255,0.6);">
    <div class="container text-center">
        <p class="mb-1">
            <i class="bi bi-car-front-fill text-primary me-2"></i>
            <strong class="text-white"><?= APP_NAME ?></strong> Sistem Rental Mobil
        </p>
        <p class="small mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>