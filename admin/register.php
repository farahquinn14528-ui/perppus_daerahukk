<?php
/**
 * PERPUSTAKAAN DAERAH - Registrasi Anggota (V5)
 * File: register.php
 * Deskripsi: Pendaftaran anggota publik. Data disimpan ke tb_anggota
 *            (satu-satunya sumber data anggota), kompatibel dengan panel admin.
 */

require_once __DIR__ . '/config/app.php';

// Jika sudah login, redirect
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$old   = [
    'nama_lengkap' => '',
    'email'        => '',
    'jenis_kelamin'=> '',
    'kelas'        => '',
    'jurusan'      => '',
    'alamat'       => '',
    'telepon'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot: field tersembunyi untuk bot — harus kosong
    if (trim($_POST['website'] ?? '') !== '') {
        redirect('index.php');
    }

    if (!csrf_verify()) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $password      = $_POST['password'] ?? '';
        $konfirmasi    = $_POST['konfirmasi_password'] ?? '';
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
        $kelas         = trim($_POST['kelas'] ?? '');
        $jurusan       = trim($_POST['jurusan'] ?? '');
        $alamat        = trim($_POST['alamat'] ?? '');
        $telepon       = trim($_POST['telepon'] ?? '');

        $old = [
            'nama_lengkap' => $nama_lengkap,
            'email'        => $email,
            'jenis_kelamin'=> $jenis_kelamin,
            'kelas'        => $kelas,
            'jurusan'      => $jurusan,
            'alamat'       => $alamat,
            'telepon'      => $telepon,
        ];

        // Rate limit pendaftaran per IP (maks 5/jam) untuk cegah spam
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $reg_key = 'reg_' . md5($ip);
        if (empty($_SESSION['reg_attempts'][$reg_key])) {
            $_SESSION['reg_attempts'][$reg_key] = ['count' => 0, 'first' => time()];
        }
        $ra = $_SESSION['reg_attempts'][$reg_key];
        if (time() - $ra['first'] > 3600) {
            $ra = ['count' => 0, 'first' => time()];
        }
        if ($ra['count'] >= 5) {
            $error = 'Terlalu banyak percobaan pendaftaran. Coba lagi nanti.';
        } elseif (mb_strlen($nama_lengkap) < 3) {
            $error = 'Nama lengkap minimal 3 karakter.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (mb_strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif ($jenis_kelamin !== '' && $jenis_kelamin !== 'L' && $jenis_kelamin !== 'P') {
            $error = 'Jenis kelamin tidak valid.';
        } else {
            // Cek email sudah terdaftar
            $stmt = mysqli_prepare($koneksi, "SELECT id_anggota FROM tb_anggota WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $email_ada = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($email_ada) {
                $error = 'Email sudah terdaftar. Silakan masuk menggunakan email ini.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $jenis_kelamin_db = ($jenis_kelamin === 'L' || $jenis_kelamin === 'P') ? $jenis_kelamin : null;
                $kelas_db   = $kelas   !== '' ? $kelas   : null;
                $jurusan_db = $jurusan !== '' ? $jurusan : null;
                $alamat_db  = $alamat  !== '' ? $alamat  : null;
                $telepon_db = $telepon !== '' ? $telepon : null;

                $inserted = false;
                // Retry bila nomor anggota bentrok (duplikat berurutan jarang terjadi)
                for ($percobaan = 0; $percobaan < 5 && !$inserted; $percobaan++) {
                    $nomor_anggota = generate_nomor_anggota($koneksi);
                    $stmt = mysqli_prepare($koneksi,
                        "INSERT INTO tb_anggota
                         (nomor_anggota, nama_lengkap, email, password, role, jenis_kelamin, kelas, jurusan, alamat, telepon, status)
                         VALUES (?, ?, ?, ?, 'anggota', ?, ?, ?, ?, ?, 'aktif')");
                    mysqli_stmt_bind_param($stmt, 'sssssssss',
                        $nomor_anggota, $nama_lengkap, $email, $hash,
                        $jenis_kelamin_db, $kelas_db, $jurusan_db, $alamat_db, $telepon_db);
                    if (mysqli_stmt_execute($stmt)) {
                        $id_anggota = (int) mysqli_insert_id($koneksi);
                        mysqli_stmt_close($stmt);
                        $inserted = true;
                        break;
                    }
                    $errno = mysqli_errno($koneksi);
                    mysqli_stmt_close($stmt);
                    if ($errno !== 1062) {
                        break; // error lain (bukan duplikat)
                    }
                }

                if ($inserted) {
                    $_SESSION['reg_attempts'][$reg_key]['count']++;
                    // Auto-login anggota baru
                    session_regenerate_id(true);
                    $_SESSION['id_anggota']    = $id_anggota;
                    $_SESSION['nomor_anggota'] = $nomor_anggota;
                    $_SESSION['nama_lengkap']  = $nama_lengkap;
                    $_SESSION['email']         = $email;
                    $_SESSION['role']          = 'anggota';
                    $_SESSION['_created']      = time();

                    set_flash('success', 'Pendaftaran berhasil! Nomor anggota Anda: ' . $nomor_anggota);

                    // Notifikasi: registrasi berhasil
                    notif_event_registration($koneksi, $id_anggota, (string) $nomor_anggota);

                    redirect('akun.php');
                } elseif (mysqli_errno($koneksi) === 1062) {
                    $error = 'Email sudah terdaftar. Silakan masuk menggunakan email ini.';
                } else {
                    $error = 'Gagal menyimpan data. Silakan coba lagi.';
                }
            }
        }
    }
}

$page_title = 'Daftar Anggota';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - Daftar Anggota</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo/favicon.svg">
    <meta name="theme-color" content="#0F172A">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css?v=<?= APP_VERSION ?>" rel="stylesheet">
    <link href="assets/css/components/buttons-polish.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-orb auth-orb-1"></div>
    <div class="auth-orb auth-orb-2"></div>

    <div class="auth-card" style="max-width: 620px;">
        <div class="auth-header">
            <img src="assets/img/logo/logo.svg" alt="<?= e(APP_NAME) ?>">
            <h4 class="fw-bold mb-1">Daftar Anggota Baru</h4>
            <p class="mb-0 opacity-75 small">Bergabunglah untuk meminjam koleksi perpustakaan</p>
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
                <!-- Honeypot anti-bot -->
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off"
                       style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">

                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                               placeholder="Nama lengkap sesuai identitas" required minlength="3"
                               autocomplete="name" autofocus
                               value="<?= e($old['nama_lengkap']) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="nama@email.com" required
                               autocomplete="email"
                               value="<?= e($old['email']) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Minimal 6 karakter" required minlength="6"
                                   autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" tabindex="-1"
                                    data-toggle-password="#password" aria-label="Tampilkan password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password"
                                   placeholder="Ulangi password" required minlength="6"
                                   autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" tabindex="-1"
                                    data-toggle-password="#konfirmasi_password" aria-label="Tampilkan password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block mt-1" id="passwordMatchHint" hidden>
                            <i class="fas fa-circle-exclamation me-1"></i>Password tidak cocok
                        </div>
                    </div>
                </div>

                <hr class="my-4 opacity-25">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-muted">(opsional)</span></label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                            <option value="" <?= $old['jenis_kelamin'] === '' ? 'selected' : '' ?>>-- Pilih --</option>
                            <option value="L" <?= $old['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $old['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="telepon" class="form-label">No. HP <span class="text-muted">(opsional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="telepon" name="telepon"
                                   placeholder="08xx xxxx xxxx" autocomplete="tel"
                                   value="<?= e($old['telepon']) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="kelas" class="form-label">Kelas <span class="text-muted">(opsional)</span></label>
                        <input type="text" class="form-control" id="kelas" name="kelas"
                               placeholder="Contoh: XI-IPA 2"
                               value="<?= e($old['kelas']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="jurusan" class="form-label">Jurusan <span class="text-muted">(opsional)</span></label>
                        <input type="text" class="form-control" id="jurusan" name="jurusan"
                               placeholder="Contoh: Teknik Informatika"
                               value="<?= e($old['jurusan']) ?>">
                    </div>
                    <div class="col-12">
                        <label for="alamat" class="form-label">Alamat <span class="text-muted">(opsional)</span></label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="2"
                                  placeholder="Alamat tempat tinggal"><?= e($old['alamat']) ?></textarea>
                    </div>
                </div>

                <div class="form-text mb-3">
                    <i class="fas fa-circle-info me-1"></i>
                    Nomor anggota akan dibuat otomatis setelah pendaftaran berhasil.
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="fas fa-user-plus me-1"></i>Daftar Anggota
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted mb-0 small">
                    Sudah menjadi anggota?
                    <a href="login.php" class="text-primary fw-semibold text-decoration-none">Masuk sekarang</a>
                </p>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = document.querySelector(this.getAttribute('data-toggle-password'));
        if (!target) { return; }
        var icon = this.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

var pwd = document.getElementById('password');
var pwd2 = document.getElementById('konfirmasi_password');
var hint = document.getElementById('passwordMatchHint');
if (pwd && pwd2 && hint) {
    var check = function () {
        if (pwd2.value.length > 0 && pwd.value !== pwd2.value) {
            hint.hidden = false;
            pwd2.classList.add('is-invalid');
        } else {
            hint.hidden = true;
            pwd2.classList.remove('is-invalid');
        }
    };
    pwd.addEventListener('input', check);
    pwd2.addEventListener('input', check);
    pwd2.addEventListener('blur', check);
}
</script>
</body>
</html>
