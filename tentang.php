<?php
session_start();
require_once __DIR__ . '/includes/config.php';

$page_title = 'Tentang Kami';

$total_mobil     = $conn->query("SELECT COUNT(*) as c FROM mobil")->fetch_assoc()['c'];
$total_pelanggan = $conn->query("SELECT COUNT(*) as c FROM pelanggan")->fetch_assoc()['c'];
$total_selesai   = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE status='selesai'")->fetch_assoc()['c'];

$tahun_berdiri   = 2021;
$lama_beroperasi = date('Y') - $tahun_berdiri;

$foto_cerita    = BASE_URL . 'assets/img/tim.jpg';       

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

        /* ===== HERO ===== */
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
            right: -40px; top: -40px;
            opacity: 0.05;
            color: white;
        }
        .katalog-hero h1 { color: white; font-weight: 800; }
        .katalog-hero p  { color: rgba(255,255,255,0.75); }

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

        /* ===== STORY / FOTO CERITA ===== */
        .story-art {
            border-radius: 24px;
            overflow: hidden;
            min-height: 320px;
            position: relative;
        }
        /* Foto utama cerita kami */
        .story-art img {
            width: 100%;
            height: 100%;
            min-height: 320px;
            object-fit: cover;
            display: block;
        }
        /* Fallback jika foto gagal dimuat */
        .story-art-fallback {
            background: linear-gradient(135deg, #0f172a, #0d6efd);
            border-radius: 24px;
            min-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .story-art-fallback i { font-size: 9rem; opacity: 0.9; }
        .story-art-fallback::after {
            content: '';
            position: absolute;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            right: -60px; bottom: -80px;
        }

        /* ===== VISI MISI CARDS ===== */
        .vm-card {
            border: none;
            border-radius: 18px;
            padding: 32px;
            height: 100%;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.25s ease;
        }
        .vm-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.10); }
        .vm-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 18px;
        }

        /* ===== VALUE CARDS ===== */
        .value-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            padding: 28px 24px;
            height: 100%;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.25s ease;
        }
        .value-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.10); }
        .value-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 16px;
        }

        /* ===== STAT SECTION ===== */
        .stat-section {
            background: #0f172a;
            border-radius: 24px;
            padding: 48px 32px;
            color: white;
        }
        .stat-box { text-align: center; }
        .stat-box .num   { font-size: 2.5rem; font-weight: 800; color: #93c5fd; }
        .stat-box .label { font-size: 0.9rem; color: rgba(255,255,255,0.7); }

        /* ===== CTA SECTION ===== */
        .cta-section {
            background: linear-gradient(135deg, #0d6efd, #1e40af);
            border-radius: 24px;
            padding: 48px;
            color: white;
            text-align: center;
        }

        /* ===== CONTACT CARDS ===== */
        .contact-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            padding: 24px;
            height: 100%;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .contact-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            background: #eff6ff; color: #0d6efd;
            margin-bottom: 12px;
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
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navTentang">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navTentang">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php">
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
                    <a class="nav-link active" href="tentang.php">
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
<section class="katalog-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <p class="text-white-50 text-uppercase fw-semibold mb-2" style="letter-spacing:2px; font-size:.8rem;">
                    <i class="bi bi-info-circle me-1"></i> Tentang Kami
                </p>
                <h1 class="display-5 fw-bold mb-3">
                    Mengenal <?= APP_NAME ?>,<br>
                    <span style="color:#93c5fd;">Mitra Perjalanan Andamu</span>
                </h1>
                <p class="lead mb-0">
                    <?= APP_NAME ?> hadir untuk membuat sewa mobil jadi mudah, transparan, dan
                    bisa diandalkan kapan pun dan ke mana pun kamu pergi.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CERITA KAMI -->
<div class="container my-5">
    <div class="row g-5 align-items-center">
        <div class="col-lg-6 order-lg-2">
            <div class="story-art">
                <img src="<?= $foto_cerita ?>"
                     alt="Cerita Kami"
                     onerror="this.style.display='none'; document.getElementById('fallback-cerita').style.display='flex';">
            </div>
            <!-- Fallback jika foto tidak ada -->
            <div class="story-art-fallback" id="fallback-cerita" style="display:none;">
                <i class="bi bi-car-front-fill"></i>
            </div>
        </div>

        <div class="col-lg-6 order-lg-1">
            <p class="section-eyebrow mb-2">Cerita Kami</p>
            <h2 class="fw-bold mb-3">Berawal dari Kebutuhan Sederhana</h2>
            <p class="text-muted" style="line-height:1.8;">
                <?= APP_NAME ?> dimulai dari hal yang sederhana: banyak orang butuh kendaraan
                yang nyaman untuk perjalanan keluarga, urusan kerja, hingga liburan, tapi proses
                sewa mobil sering terasa rumit dan kurang transparan.
            </p>
            <p class="text-muted" style="line-height:1.8;">
                Sejak <?= $tahun_berdiri ?>, kami membangun sistem rental mobil berbasis web agar
                pelanggan bisa melihat armada yang tersedia secara <em>real-time</em>, mengecek
                detail dan harga setiap unit, lalu melakukan pemesanan langsung secara online
                tanpa perlu datang ke kantor terlebih dahulu.
            </p>
            <p class="text-muted mb-0" style="line-height:1.8;">
                Hari ini, kami terus merawat armada, menjaga harga tetap wajar, dan menghadirkan
                layanan yang ramah agar setiap perjalananmu berjalan lancar.
            </p>
        </div>
    </div>
</div>

<!-- VISI & MISI -->
<div class="container my-5">
    <div class="text-center mb-5">
        <p class="section-eyebrow mb-2">Arah Kami</p>
        <h2 class="fw-bold">Visi &amp; Misi</h2>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="vm-card bg-white">
                <div class="vm-icon" style="background:#dbeafe; color:#1d4ed8;">
                    <i class="bi bi-eye-fill"></i>
                </div>
                <h5 class="fw-bold mb-2">Visi</h5>
                <p class="text-muted mb-0" style="line-height:1.8;">
                    Menjadi platform rental mobil yang paling dipercaya dan mudah diakses,
                    dengan armada yang terawat dan layanan yang membuat setiap pelanggan merasa
                    aman dan nyaman dalam setiap perjalanan.
                </p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="vm-card bg-white">
                <div class="vm-icon" style="background:#d1fae5; color:#065f46;">
                    <i class="bi bi-bullseye"></i>
                </div>
                <h5 class="fw-bold mb-2">Misi</h5>
                <ul class="text-muted mb-0" style="line-height:1.8; padding-left:1.1rem;">
                    <li>Menyediakan armada berkualitas dengan perawatan rutin dan berkala.</li>
                    <li>Menjaga harga sewa yang transparan tanpa biaya tersembunyi.</li>
                    <li>Memudahkan proses booking melalui sistem online yang cepat dan praktis.</li>
                    <li>Memberikan layanan pelanggan yang responsif dan ramah.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- KEUNGGULAN -->
<div class="container my-5">
    <div class="text-center mb-5">
        <p class="section-eyebrow mb-2">Keunggulan</p>
        <h2 class="fw-bold">Kenapa Pilih <?= APP_NAME ?>?</h2>
        <p class="text-muted">Beberapa alasan pelanggan kami selalu kembali menyewa di sini.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="value-card">
                <div class="value-icon" style="background:#dbeafe; color:#1d4ed8;">
                    <i class="bi bi-stars"></i>
                </div>
                <h6 class="fw-bold mb-2">Armada Terawat</h6>
                <p class="text-muted small mb-0">
                    Setiap unit melalui pengecekan rutin agar selalu siap dan nyaman dipakai.
                </p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="value-card">
                <div class="value-icon" style="background:#d1fae5; color:#065f46;">
                    <i class="bi bi-tag-fill"></i>
                </div>
                <h6 class="fw-bold mb-2">Harga Transparan</h6>
                <p class="text-muted small mb-0">
                    Harga per hari ditampilkan jelas di katalog, tanpa biaya tambahan tersembunyi.
                </p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="value-card">
                <div class="value-icon" style="background:#fef3c7; color:#92400e;">
                    <i class="bi bi-laptop"></i>
                </div>
                <h6 class="fw-bold mb-2">Booking Online</h6>
                <p class="text-muted small mb-0">
                    Pilih mobil, isi data, dan konfirmasi pemesanan langsung dari katalog kami.
                </p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="value-card">
                <div class="value-icon" style="background:#fee2e2; color:#991b1b;">
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

<!-- STATISTIK -->
<div class="container my-5">
    <div class="stat-section">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="num"><?= $total_mobil ?></div>
                    <div class="label">Unit Armada</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="num"><?= $total_pelanggan ?></div>
                    <div class="label">Pelanggan Terdaftar</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="num"><?= $total_selesai ?></div>
                    <div class="label">Transaksi Selesai</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="num"><?= max($lama_beroperasi, 1) ?>+</div>
                    <div class="label">Tahun Beroperasi</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KONTAK -->
<div class="container my-5">
    <div class="text-center mb-5">
        <p class="section-eyebrow mb-2">Hubungi Kami</p>
        <h2 class="fw-bold">Ada Pertanyaan?</h2>
        <p class="text-muted">Tim kami siap membantu seputar armada, harga, dan pemesanan.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="contact-card">
                <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <h6 class="fw-bold mb-1">Lokasi</h6>
                <p class="text-muted small mb-0">Yogyakarta, Indonesia</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="contact-card">
                <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                <h6 class="fw-bold mb-1">Telepon / WhatsApp</h6>
                <p class="text-muted small mb-0">+62 895-7044-27090</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="contact-card">
                <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                <h6 class="fw-bold mb-1">Email</h6>
                <p class="text-muted small mb-0">rentWheels@gmail.com</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="container my-5">
    <div class="cta-section">
        <h3 class="fw-bold mb-2">Siap untuk Perjalanan Berikutnya?</h3>
        <p class="mb-4 opacity-90">Cek armada yang tersedia dan pesan mobil impianmu sekarang.</p>
        <a href="<?= BASE_URL ?>katalog.php" class="btn btn-light fw-semibold px-4">
            <i class="bi bi-grid me-2"></i>Lihat Katalog Mobil
        </a>
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
</body>
</html>