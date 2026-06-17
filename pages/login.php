<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['id'])) {
    header('Location: ' . BASE_URL . (in_array($_SESSION['role'] ?? '', ['admin','petugas']) ? 'pages/dashboard.php' : 'katalog.php'));
    exit;
}

$error = '';
$username_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password       = $_POST['password'] ?? '';

    if (empty($username_input) || empty($password)) {
        $error = 'Username dan password harus diisi.';
    } else {
 
        $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username_input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $pass_ok = false;

  
            if (!empty($user['password']) && password_verify($password, $user['password'])) {
                $pass_ok = true;
            }
    
            elseif (!empty($user['password']) && $user['password'] === md5($password)) {
                $pass_ok = true;
                // Auto-upgrade: simpan sebagai password_hash mulai sekarang
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $new_hash, $user['id']);
                $upd->execute();
                $upd->close();
            }

            if ($pass_ok) {
                // Set session
                $_SESSION['id']            = $user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
                $_SESSION['role']          = $user['role'] ?? 'pelanggan';
                $_SESSION['flash_message'] = 'Selamat datang, ' . htmlspecialchars($user['nama_lengkap']) . '!';
                $_SESSION['flash_type']    = 'success';

                header('Location: ' . BASE_URL . (in_array($_SESSION['role'], ['admin','petugas']) ? 'pages/dashboard.php' : 'katalog.php'));
                exit;
            } else {
                $error = 'Password salah. Periksa kembali.';
            }
        } else {
            $error = 'Username tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }

        body {
            display: flex;
            min-height: 100vh;
            background: #0f172a;
        }

        /* ===== PANEL KIRI ===== */
        .auth-left {
            flex: 1.15;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #312e81 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 56px 64px;
            position: relative;
            overflow: hidden;
            color: #fff;
        }
        /* Orb animasi */
        .auth-orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(80px);
            animation: orbFloat 10s ease-in-out infinite;
        }
        .auth-orb-1 {
            width: 380px; height: 380px;
            background: rgba(79,70,229,0.25);
            top: -100px; right: -80px;
        }
        .auth-orb-2 {
            width: 260px; height: 260px;
            background: rgba(124,58,237,0.18);
            bottom: -80px; left: -60px;
            animation-delay: -5s; animation-duration: 12s;
        }
        @keyframes orbFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            33%     { transform: translate(18px,-14px) scale(1.05); }
            66%     { transform: translate(-12px,10px) scale(0.97); }
        }

        /* Dot grid */
        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(99,102,241,0.15);
            border: 1px solid rgba(165,180,252,0.25);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #a5b4fc;
            margin-bottom: 24px;
            position: relative; z-index: 1;
        }
        .auth-badge .dot {
            width: 6px; height: 6px;
            background: #a5b4fc; border-radius: 50%;
            animation: dotBlink 1.5s ease-in-out infinite;
        }
        @keyframes dotBlink {
            0%,100% { opacity: 1; }
            50%     { opacity: 0.3; }
        }

        .auth-headline {
            font-size: clamp(1.9rem, 3.5vw, 2.6rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.7px;
            margin-bottom: 16px;
            position: relative; z-index: 1;
        }
        .auth-headline .hl {
            background: linear-gradient(90deg, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .auth-desc {
            color: rgba(255,255,255,0.5);
            font-size: .92rem;
            max-width: 420px;
            line-height: 1.8;
            margin-bottom: 36px;
            position: relative; z-index: 1;
        }

        /* Feature pills */
        .auth-features {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            position: relative; z-index: 1;
        }
        .feat-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 9px 14px;
            font-size: .82rem;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s cubic-bezier(0.34,1.56,0.64,1);
            cursor: default;
        }
        .feat-pill:hover {
            background: rgba(99,102,241,0.18);
            border-color: rgba(165,180,252,0.3);
            transform: translateY(-2px);
        }
        .feat-pill i { color: #a5b4fc; font-size: 1rem; }

        .auth-footer {
            color: rgba(255,255,255,0.3);
            font-size: .75rem;
            position: relative; z-index: 1;
        }

        /* ===== PANEL KANAN ===== */
        .auth-right {
            flex: 1;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }
        .auth-form-box { width: 100%; max-width: 380px; }

        .brand-logo {
            width: 52px; height: 52px;
            background: #4f46e5;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff;
            box-shadow: 0 8px 20px rgba(79,70,229,0.35), inset 0 1px 0 rgba(255,255,255,0.15);
            margin: 0 auto 16px;
            animation: logoBounce 0.6s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes logoBounce {
            from { opacity: 0; transform: scale(0.6) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Input interaktif */
        .auth-input-wrap { position: relative; }
        .auth-input-wrap .icon {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #a3a3a3;
            font-size: .9rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }
        .auth-input-wrap input {
            padding-left: 36px;
            border-radius: 8px;
            border: 1.5px solid #e5e5e4;
            font-size: .875rem;
            height: 44px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            width: 100%;
            background: #fafaf9;
            color: #171717;
            outline: none;
            -webkit-appearance: none;
        }
        .auth-input-wrap input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
            background: #fff;
        }
        .auth-input-wrap input:focus + .icon,
        .auth-input-wrap input:focus ~ .icon { color: #4f46e5; }
        .auth-input-wrap input.is-invalid {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
        }
        .err-msg {
            font-size: .75rem; font-weight: 500;
            color: #dc2626; margin-top: 4px;
            display: none;
        }
        .err-msg.show { display: block; }

        /* Divider */
        .or-divider {
            display: flex; align-items: center; gap: 10px;
            margin: 18px 0; color: #a3a3a3; font-size: .78rem;
        }
        .or-divider hr { flex: 1; border-color: #e5e5e4; margin: 0; }

        /* Submit btn */
        .btn-login {
            background: #4f46e5;
            color: #fff; border: none;
            border-radius: 8px;
            font-weight: 700; font-size: .875rem;
            height: 44px; width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer;
            transition: background 0.15s ease, transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s ease;
            box-shadow: 0 4px 12px rgba(79,70,229,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .btn-login:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79,70,229,0.35), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .btn-login:active { transform: translateY(0); }

        .demo-box {
            background: #f8f8f7;
            border: 1px dashed #d4d4d2;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: .78rem;
            color: #737373;
            text-align: center;
            margin-top: 18px;
        }

        @media (max-width: 991.98px) {
            .auth-left { display: none; }
            .auth-right { flex: 1 1 100%; background: linear-gradient(135deg, #0f172a, #1e2d4a); }
            .auth-form-box {
                background: #fff;
                border-radius: 20px;
                padding: 32px 28px;
                box-shadow: 0 24px 56px rgba(0,0,0,0.4);
            }
        }
        @media (max-width: 375px) {
            .auth-form-box { padding: 24px 20px; }
        }
    </style>
</head>
<body>

    <div class="auth-left">
        <div class="auth-orb auth-orb-1"></div>
        <div class="auth-orb auth-orb-2"></div>

        <div>
            <div class="auth-badge"><span class="dot"></span> RentWheels Platform</div>
            <h1 class="auth-headline">
                Kelola Rental Mobil<br>
                <span class="hl">Lebih Cerdas & Efisien.</span>
            </h1>
            <p class="auth-desc">
                Masuk ke dashboard untuk mengelola armada kendaraan,
                memantau transaksi real-time, dan melihat laporan pendapatan
                kapan saja, di mana saja.
            </p>
            <div class="auth-features">
                <span class="feat-pill"><i class="bi bi-car-front-fill"></i> Manajemen Armada</span>
                <span class="feat-pill"><i class="bi bi-receipt"></i> Transaksi Real-time</span>
                <span class="feat-pill"><i class="bi bi-bar-chart-line"></i> Laporan Lengkap</span>
                <span class="feat-pill"><i class="bi bi-shield-check"></i> Aman & Terenkripsi</span>
            </div>
        </div>

        <div class="auth-footer">© <?= date('Y') ?> <?= APP_NAME ?> · Sekolah Vokasi UGM</div>
    </div>

    <div class="auth-right">
        <div class="auth-form-box">

            <div class="text-center mb-4">
                <div class="brand-logo"><i class="bi bi-car-front-fill"></i></div>
                <h4 style="font-size:1.15rem; font-weight:800; letter-spacing:-0.3px; margin:0 0 4px;">
                    <?= APP_NAME ?>
                </h4>
                <p style="font-size:.82rem; color:#a3a3a3; margin:0;">Masuk ke akun Anda</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4"
                 style="border-radius:8px; font-size:.84rem; padding:10px 14px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Google Login -->
            <div id="g_id_onload"
                 data-client_id="<?= GOOGLE_CLIENT_ID ?>"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleGoogleLogin"
                 data-auto_select="false">
            </div>
            <div class="d-flex justify-content-center mb-2">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left"
                     data-width="320">
                </div>
            </div>

            <div class="or-divider"><hr><span>atau dengan username</span><hr></div>

            <!-- Form Login -->
            <form id="formLogin" method="POST" novalidate>
                <div class="mb-3">
                    <label style="font-size:.8rem; font-weight:600; color:#525252; margin-bottom:5px; display:block;">
                        Username
                    </label>
                    <div class="auth-input-wrap">
                        <input type="text" id="username" name="username"
                               value="<?= htmlspecialchars($username_input) ?>"
                               placeholder="Masukkan username" autocomplete="username" autofocus>
                        <i class="bi bi-person icon"></i>
                    </div>
                    <div class="err-msg" id="err-username"></div>
                </div>
                <div class="mb-4">
                    <label style="font-size:.8rem; font-weight:600; color:#525252; margin-bottom:5px; display:block;">
                        Password
                    </label>
                    <div class="auth-input-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Masukkan password" autocomplete="current-password">
                        <i class="bi bi-lock icon"></i>
                    </div>
                    <div class="err-msg" id="err-password"></div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>

            <div class="text-center mt-3" style="font-size:.8rem; color:#a3a3a3;">
                Belum punya akun?
                <a href="register.php" style="font-weight:600; color:#4f46e5; text-decoration:none;">Daftar di sini</a>
            </div>

            <div class="demo-box">
                Demo: <strong>admin</strong> / <strong>admin123</strong>
            </div>
        </div>
    </div>

<script>

document.getElementById('formLogin').addEventListener('submit', function (e) {
    const user = document.getElementById('username');
    const pass = document.getElementById('password');
    const errU = document.getElementById('err-username');
    const errP = document.getElementById('err-password');
    let valid = true;

    [user, pass].forEach(function (el) { el.classList.remove('is-invalid'); });
    [errU, errP].forEach(function (el) { el.classList.remove('show'); el.textContent = ''; });

    if (user.value.trim().length < 1) {
        user.classList.add('is-invalid');
        errU.textContent = 'Username wajib diisi.';
        errU.classList.add('show');
        valid = false;
    }
    if (pass.value.length < 1) {
        pass.classList.add('is-invalid');
        errP.textContent = 'Password wajib diisi.';
        errP.classList.add('show');
        valid = false;
    }
    if (!valid) e.preventDefault();
});

['username', 'password'].forEach(function (id) {
    document.getElementById(id).addEventListener('input', function () {
        this.classList.remove('is-invalid');
        const err = document.getElementById('err-' + id);
        if (err) { err.classList.remove('show'); err.textContent = ''; }
    });
});

function handleGoogleLogin(response) {
    const form  = document.createElement('form');
    form.method = 'POST';
    form.action = 'google-login.php';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'credential';
    input.value = response.credential;
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>