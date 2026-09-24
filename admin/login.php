<?php
/**
 * PERPUSTAKAAN DAERAH - Halaman Login (v4.0 Redesign)
 * File: login.php
 * Deskripsi: Form login untuk anggota dan admin (via email)
 *
 * Hardening (tidak diubah pada v4.0):
 *   * CSRF token wajib
 *   * Rate limiting berbasis IP+email (5 percobaan, lock 15 menit)
 *   * Session regeneration setelah login sukses (anti session fixation)
 *   * Validasi redirect — hanya URL relatif/internal
 *
 * v4.0: Redesign UI/UX glassmorphism premium
 */

require_once __DIR__ . '/config/app.php';

// Jika sudah login, redirect
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi CSRF
    if (!csrf_verify()) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Rate limiting
        $rl = check_login_rate_limit($email);
        if (!$rl['allowed']) {
            $error = $rl['message'];
        } elseif (empty($email) || empty($password)) {
            $error = 'Email dan password harus diisi.';
        } else {
            // Cari user berdasarkan email di tabel tb_anggota
            $stmt = mysqli_prepare($koneksi, "SELECT id_anggota, nomor_anggota, nama_lengkap, email, password, role, status FROM tb_anggota WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                // Cek status akun
                if ($user['status'] !== 'aktif') {
                    $error = 'Akun Anda dinonaktifkan. Hubungi administrator.';
                } elseif (password_verify($password, $user['password'])) {
                    // LOGIN SUKSES
                    clear_login_attempts($email);

                    // Regenerasi session ID untuk mencegah session fixation
                    session_regenerate_id(true);

                    // Set session
                    $_SESSION['id_anggota']    = $user['id_anggota'];
                    $_SESSION['nomor_anggota'] = $user['nomor_anggota'];
                    $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
                    $_SESSION['email']         = $user['email'];
                    $_SESSION['role']          = $user['role'];
                    $_SESSION['_created']      = time();

                    // V5: Persistent login (remember me)
                    if (!empty($_POST['remember'])) {
                        set_remember_cookie($koneksi, (int) $user['id_anggota']);
                    }

                    set_flash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');

                    // Validasi redirect — hanya URL relatif (mulai dengan / atau huruf) atau path internal
                    $redirect = $_GET['redirect'] ?? 'index.php';
                    if (!preg_match('#^/?[a-zA-Z0-9_\-\.\?\=\&\/\:\#]+$#', $redirect)
                        || stripos($redirect, '://') !== false
                        || stripos($redirect, 'javascript:') === 0) {
                        $redirect = 'index.php';
                    }

                    // Redirect ke halaman yang diminta
                    redirect($redirect);
                } else {
                    record_login_failure($email);
                    $remaining_attempts = LOGIN_MAX_ATTEMPTS - ($_SESSION['login_attempts'][md5($email . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'))]['count'] ?? 0);
                    if ($remaining_attempts > 0) {
                        $error = 'Email atau password salah. Sisa percobaan: ' . $remaining_attempts;
                    } else {
                        $error = 'Terlalu banyak percobaan gagal. Akun terkunci sementara.';
                    }
                }
            } else {
                mysqli_stmt_close($stmt);
                record_login_failure($email);
                $error = 'Email atau password salah.';
            }
        }
    }
}

$page_title = 'Masuk';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - Masuk</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo/favicon.svg">
    <meta name="theme-color" content="#0F172A">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=<?= APP_VERSION ?>" rel="stylesheet">
    <link href="assets/css/components/buttons-polish.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">
    <!-- Floating orbs -->
    <div class="auth-orb auth-orb-1"></div>
    <div class="auth-orb auth-orb-2"></div>

    <div class="auth-card">
        <div class="auth-header">
            <img src="assets/img/logo/logo.svg" alt="<?= e(APP_NAME) ?>">
            <h4 class="fw-bold mb-1">Selamat Datang Kembali</h4>
            <p class="mb-0 opacity-75 small">Masuk untuk mengakses layanan perpustakaan</p>
        </div>
        <div class="auth-body">

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= e($error) ?></div>
            </div>
            <?php endif; ?>

            <?php $flash = get_flash(); ?>
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : 'success') ?> d-flex align-items-center gap-2" role="alert">
                <i class="fas fa-info-circle"></i>
                <div><?= e($flash['message']) ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" action="" data-loader="true" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="nama@email.com" required
                               autocomplete="email"
                               value="<?= e($_POST['email'] ?? '') ?>"
                               autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label">Password</label>
                        <a href="#" class="small text-decoration-none" data-bs-toggle="tooltip" title="Hubungi admin untuk reset password">
                            Lupa password?
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Masukkan password" required
                               autocomplete="current-password" minlength="6">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1"
                                data-target="#password" aria-label="Tampilkan password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4 form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                    <label class="form-check-label small text-muted" for="remember">
                        Ingat saya selama 30 hari
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="fas fa-right-to-bracket me-1"></i>Masuk ke Akun
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted mb-0 small">
                    Belum menjadi anggota?
                    <a href="register.php" class="text-primary fw-semibold text-decoration-none">Daftar sekarang</a>
                </p>
            </div>

            <!-- Info akun demo (hanya tampil saat APP_DEBUG aktif) -->
            <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
            <div class="auth-demo-info">
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-info-circle me-1 text-primary"></i>
                    <strong>Akun Demo</strong>
                </small>
                <small class="text-muted">
                    Anggota: <code>ahmad@email.com</code> / <code>admin123</code>
                </small>
            </div>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="index.php" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
// Password toggle (improved)
document.getElementById('togglePassword').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }
});
</script>
</body>
</html>
