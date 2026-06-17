<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$user_role    = $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow" style="background:var(--nav-bg, #0f172a);">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>pages/dashboard.php">
            <i class="bi bi-car-front-fill text-primary fs-5"></i>
            <?php echo APP_NAME; ?>
            <?php if ($user_role === 'admin'): ?>
            <span class="badge bg-primary ms-1" style="font-size:.6rem;">OWNER</span>
            <?php endif; ?>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <?php if (isset($_SESSION['id'])): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['dashboard.php']) ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'mobil.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/mobil.php">
                        <i class="bi bi-car-front me-1"></i>Armada
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'pelanggan.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/pelanggan.php">
                        <i class="bi bi-people me-1"></i>Pelanggan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'transaksi.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/transaksi.php">
                        <i class="bi bi-receipt me-1"></i>Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'laporan.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/laporan.php">
                        <i class="bi bi-bar-chart-line me-1"></i>Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>katalog.php" target="_blank">
                        <i class="bi bi-shop me-1"></i>Lihat Katalog
                        <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.7rem;"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <span class="nav-link text-white-50 small">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User') ?>
                        <span class="badge bg-secondary ms-1" style="font-size:.6rem;">
                            <?= ucfirst($user_role) ?>
                        </span>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>pages/logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
            <?php else: ?>
            <ul class="navbar-nav ms-auto gap-2">
                <li class="nav-item">
                    <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>pages/login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login Owner
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Flash Message -->
<?php if (isset($_SESSION['flash_message'])): ?>
<div class="container-fluid px-4 mt-3" id="flash-container">
    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= ($_SESSION['flash_type'] ?? '') === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= $_SESSION['flash_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
endif;
?>

<main class="py-4">