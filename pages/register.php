<?php
// pages/register.php - Daftar akun baru sebagai pelanggan
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['id'])) {
    header('Location: ' . BASE_URL . 'katalog.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = htmlspecialchars(trim($_POST['nama_lengkap'] ?? ''));
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $email    = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$nama || !$username || !$email || !$password) {
        $error = 'Semua field harus diisi.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
    
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $cek->bind_param('ss', $username, $email);
        $cek->execute();
        $existing = $cek->get_result()->fetch_assoc();
        $cek->close();

        if ($existing) {
            $error = 'Username atau email sudah terdaftar.';
        } else {
            
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, nama_lengkap, role) VALUES (?,?,?,?,'pelanggan')");
            $stmt->bind_param('ssss', $username, $email, $hash, $nama);
            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan, coba lagi.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%); min-height: 100vh; display:flex; align-items:center; justify-content:center; }
        .register-card { max-width: 480px; width: 100%; border-radius: 20px; border: none; box-shadow: 0 24px 60px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="mx-auto register-card card p-4">
        <div class="text-center mb-4">
            <i class="bi bi-car-front-fill text-primary" style="font-size:2.5rem;"></i>
            <h4 class="fw-bold mt-2"><?= APP_NAME ?></h4>
            <p class="text-muted small">Daftar akun untuk mulai menyewa mobil</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <a href="login.php" class="fw-bold ms-2">Login Sekarang</a>
        </div>
        <?php else: ?>

        <form method="POST" id="formRegister" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap"
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
                       placeholder="Nama sesuai KTP"
                       data-validate="required|minlength:3" data-label="Nama Lengkap" required>
                <div class="invalid-feedback" id="err-nama_lengkap"></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control" name="username" id="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Nama pengguna unik"
                       data-validate="required|minlength:3" data-label="Username" required>
                <div class="invalid-feedback" id="err-username"></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control" name="email" id="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="email@contoh.com"
                       data-validate="required|email" data-label="Email" required>
                <div class="invalid-feedback" id="err-email"></div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" class="form-control" name="password" id="password"
                       placeholder="Minimal 6 karakter"
                       data-validate="required|minlength:6" data-label="Password" required>
                <div class="invalid-feedback" id="err-password"></div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Konfirmasi Password</label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password"
                       placeholder="Ulangi password"
                       data-validate="required" data-label="Konfirmasi Password" required>
                <div class="invalid-feedback" id="err-confirm_password"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-person-plus me-2"></i>Buat Akun
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                Sudah punya akun? <a href="login.php" class="fw-semibold">Login di sini</a>
            </small>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

document.getElementById('formRegister').addEventListener('submit', function(e) {
    let valid = true;

    const fields = [
        { id: 'nama_lengkap', label: 'Nama Lengkap', minLen: 3 },
        { id: 'username',     label: 'Username',     minLen: 3 },
        { id: 'email',        label: 'Email',        isEmail: true },
        { id: 'password',     label: 'Password',     minLen: 6 },
        { id: 'confirm_password', label: 'Konfirmasi Password' }
    ];

    fields.forEach(function(f) {
        const el  = document.getElementById(f.id);
        const err = document.getElementById('err-' + f.id);
        el.classList.remove('is-invalid');
        err.textContent = '';

        if (!el.value.trim()) {
            el.classList.add('is-invalid');
            err.textContent = f.label + ' wajib diisi.';
            valid = false;
        } else if (f.minLen && el.value.trim().length < f.minLen) {
            el.classList.add('is-invalid');
            err.textContent = f.label + ' minimal ' + f.minLen + ' karakter.';
            valid = false;
        } else if (f.isEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim())) {
            el.classList.add('is-invalid');
            err.textContent = 'Format email tidak valid.';
            valid = false;
        }
    });

    const pw  = document.getElementById('password');
    const cpw = document.getElementById('confirm_password');
    const errCpw = document.getElementById('err-confirm_password');
    if (pw.value && cpw.value && pw.value !== cpw.value) {
        cpw.classList.add('is-invalid');
        errCpw.textContent = 'Konfirmasi password tidak cocok.';
        valid = false;
    }

    if (!valid) e.preventDefault();
});

document.querySelectorAll('#formRegister input').forEach(function(input) {
    input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
        const err = document.getElementById('err-' + this.id);
        if (err) err.textContent = '';
    });
});
</script>
</body>
</html>
