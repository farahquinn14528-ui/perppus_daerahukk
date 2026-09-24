<?php
/**
 * PERPUSTAKAAN DAERAH - Dashboard Anggota (V5)
 * File: akun.php
 * Deskripsi: Dashboard premium anggota — kuota peminjaman, buku aktif,
 *            status denda, riwayat, notifikasi, rekomendasi & profil.
 */

require_once __DIR__ . '/config/app.php';
require_login();

$id_anggota = (int) $_SESSION['id_anggota'];

/* ---------------- AKSI: UPDATE PROFIL ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    if (!csrf_verify()) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect('akun.php');
    }
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $telepon      = trim($_POST['telepon'] ?? '');
    $alamat       = trim($_POST['alamat'] ?? '');

    if (empty($nama_lengkap) || empty($email)) {
        set_flash('error', 'Nama lengkap dan email harus diisi.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Format email tidak valid.');
    } else {
        if (anggota_email_dipakai($koneksi, $email, $id_anggota)) {
            set_flash('error', 'Email sudah digunakan oleh anggota lain.');
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE tb_anggota SET nama_lengkap = ?, email = ?, telepon = ?, alamat = ? WHERE id_anggota = ?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $nama_lengkap, $email, $telepon, $alamat, $id_anggota);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['email'] = $email;
                set_flash('success', 'Profil berhasil diperbarui.');
            } else {
                set_flash('error', 'Gagal memperbarui profil.');
            }
            mysqli_stmt_close($stmt);
        }
    }
    redirect('akun.php');
}

/* ---------------- AKSI: GANTI PASSWORD ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    if (!csrf_verify()) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect('akun.php');
    }
    $password_lama   = $_POST['password_lama'] ?? '';
    $password_baru   = $_POST['password_baru'] ?? '';
    $konfirmasi_pass = $_POST['konfirmasi_password'] ?? '';

    if (empty($password_lama) || empty($password_baru)) {
        set_flash('error', 'Password lama dan baru harus diisi.');
    } elseif (strlen($password_baru) < 6) {
        set_flash('error', 'Password baru minimal 6 karakter.');
    } elseif ($password_baru !== $konfirmasi_pass) {
        set_flash('error', 'Konfirmasi password tidak cocok.');
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT password FROM tb_anggota WHERE id_anggota = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id_anggota);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $hash);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if (!password_verify($password_lama, $hash)) {
            set_flash('error', 'Password lama salah.');
        } else {
            $new_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "UPDATE tb_anggota SET password = ? WHERE id_anggota = ?");
            mysqli_stmt_bind_param($stmt, 'si', $new_hash, $id_anggota);
            if (mysqli_stmt_execute($stmt)) {
                set_flash('success', 'Password berhasil diubah.');
            } else {
                set_flash('error', 'Gagal mengubah password.');
            }
            mysqli_stmt_close($stmt);
        }
    }
    redirect('akun.php');
}

/* ---------------- DATA ANGGOTA ---------------- */
$anggota = anggota_by_id($koneksi, $id_anggota);
if (!$anggota) redirect('logout.php');

/* ---------------- STATISTIK ---------------- */
$stat = loan_statistik($koneksi, $id_anggota);
$total_pinjam       = $stat['total_pinjam'];
$sedang_dipinjam    = $stat['sedang_dipinjam'];
$sisa_kuota         = max(0, MAX_BORROWED_BOOKS - $sedang_dipinjam);
$menunggu_kembali   = $stat['menunggu_kembali'];
$total_denda_belum  = $stat['total_denda_belum'];
$total_denda_semua  = $stat['total_denda_semua'];
$total_hari_telat   = $stat['total_hari_telat'];

/* ---------------- BUKU SEDANG DIPINJAM ---------------- */
$buku_dipinjam = loan_anggota_aktif($koneksi, $id_anggota);

/* ---------------- RIWAYAT TERBARU ---------------- */
$riwayat_terbaru = loan_terbaru($koneksi, $id_anggota, 5);

/* ---------------- REKOMENDASI (populer, kecuali yang sedang dipinjam) ---------------- */
$rekomendasi = buku_rekomendasi($koneksi, $id_anggota, 6);

/* ---------------- NOTIFIKASI SISTEM (tb_notifikasi) ---------------- */
notif_sync_events($koneksi, $id_anggota);
$notif_dashboard      = notif_list($koneksi, $id_anggota, 10);
$notif_unread_dash    = notif_unread_count($koneksi, $id_anggota);

/* ---------------- BUKU TERBARU ---------------- */
$buku_baru = buku_baru_dashboard($koneksi, 6);

/* ---------------- BUKU POPULER ---------------- */
$buku_populer = buku_populer_dashboard($koneksi, 6);

/* ---------------- BADGE / PENCAPAIAN (V5.5) ---------------- */
badge_sync($koneksi, $id_anggota);
$badge_dash    = badge_anggota($koneksi, $id_anggota);
$badge_dash_count = count($badge_dash);
$badge_dash_show  = array_slice($badge_dash, 0, 6);

$page_title = 'Dashboard Anggota';
require_once __DIR__ . '/partials/header.php';
?>

<!-- HERO / WELCOME -->
<section class="v5-hero-user mb-4">
    <div class="v5-hero-overline"><i class="fas fa-wand-magic-sparkles"></i> Dashboard Anggota</div>
    <h1 class="v5-hero-title">Selamat datang, <?= e(explode(' ', trim($anggota['nama_lengkap']))[0]) ?>! 📚</h1>
    <p class="v5-hero-sub">Kelola peminjaman, pantau kuota, dan temukan bacaan favorit Anda — semuanya dalam satu dashboard.</p>
    <div class="v5-hero-chips">
        <span class="v5-hero-chip"><i class="fas fa-id-card"></i><?= e($anggota['nomor_anggota']) ?></span>
        <span class="v5-hero-chip"><i class="fas fa-calendar-day"></i><?= e(format_tanggal(date('Y-m-d'))) ?></span>
        <span class="v5-hero-chip"><i class="fas fa-book"></i><?= $sedang_dipinjam ?>/<?= MAX_BORROWED_BOOKS ?> buku aktif</span>
        <span class="v5-hero-chip"><i class="fas fa-tag"></i><?= e(ucfirst($anggota['status'] ?? 'aktif')) ?></span>
    </div>
</section>

<div class="container">

<!-- KUOTA PEMINJAMAN -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <?php $quota_class = $sedang_dipinjam >= MAX_BORROWED_BOOKS ? 'full' : ($sisa_kuota === 1 ? 'warn' : ''); ?>
        <div class="quota-card <?= $quota_class ?> h-100" data-reveal="left">
            <div class="quota-card-head">
                <span class="quota-card-title"><i class="fas fa-gauge-high me-2"></i>Kuota Peminjaman</span>
                <span class="quota-card-val"><?= $sedang_dipinjam ?><small>/<?= MAX_BORROWED_BOOKS ?></small></span>
            </div>
            <div class="quota-track" role="progressbar" aria-valuenow="<?= $sedang_dipinjam ?>" aria-valuemin="0" aria-valuemax="<?= MAX_BORROWED_BOOKS ?>">
                <div class="quota-track-fill" style="width: <?= min(100, ($sedang_dipinjam / MAX_BORROWED_BOOKS) * 100) ?>%;"></div>
            </div>
            <div class="quota-meta">
                <span><?= $sisa_kuota ?> kuota tersisa</span>
                <span>Maks. <?= MAX_BORROWED_BOOKS ?> buku</span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="v5-stat-row h-100" data-stagger>
            <div class="v5-mini-stat">
                <span class="v5-mini-stat-ic info"><i class="fas fa-book"></i></span>
                <div>
                    <div class="v5-mini-stat-val" data-counter="<?= $total_pinjam ?>"><?= $total_pinjam ?></div>
                    <div class="v5-mini-stat-label">Total Peminjaman</div>
                </div>
            </div>
            <div class="v5-mini-stat">
                <span class="v5-mini-stat-ic <?= $sedang_dipinjam > 0 ? 'primary' : 'success' ?>"><i class="fas fa-hand-holding"></i></span>
                <div>
                    <div class="v5-mini-stat-val" data-counter="<?= $sedang_dipinjam ?>"><?= $sedang_dipinjam ?></div>
                    <div class="v5-mini-stat-label">Buku Dipinjam</div>
                </div>
            </div>
            <div class="v5-mini-stat">
                <span class="v5-mini-stat-ic <?= $menunggu_kembali > 0 ? 'warning' : 'success' ?>"><i class="fas fa-clock"></i></span>
                <div>
                    <div class="v5-mini-stat-val" data-counter="<?= $menunggu_kembali ?>"><?= $menunggu_kembali ?></div>
                    <div class="v5-mini-stat-label">Menunggu Persetujuan</div>
                </div>
            </div>
            <div class="v5-mini-stat">
                <span class="v5-mini-stat-ic <?= $total_denda_belum > 0 ? 'danger' : 'success' ?>"><i class="fas fa-money-bill-wave"></i></span>
                <div>
                    <div class="v5-mini-stat-val"><?= format_rupiah($total_denda_belum) ?></div>
                    <div class="v5-mini-stat-label">Denda Belum Bayar</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($badge_dash_count > 0): ?>
<div class="card mb-4">
    <div class="card-header">
        <span class="header-title"><i class="fas fa-medal"></i>Pencapaian Badge</span>
        <a href="badge.php" class="btn btn-sm btn-ghost">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <?php foreach ($badge_dash_show as $bd): ?>
            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3"
                 style="background:<?= e($bd['warna']) ?>1a;border:1px solid <?= e($bd['warna']) ?>44;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                      style="width:40px;height:40px;color:#fff;background:<?= e($bd['warna']) ?>;font-size:1.1rem;">
                    <i class="fas <?= e($bd['ikon']) ?>" aria-hidden="true"></i>
                </span>
                <div>
                    <div class="fw-semibold small lh-sm"><?= e($bd['nama']) ?></div>
                    <small class="text-muted">Diraih <?= e(format_tanggal($bd['earned_at'])) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if ($badge_dash_count > count($badge_dash_show)): ?>
            <a href="badge.php" class="btn btn-ghost btn-sm">+<?= $badge_dash_count - count($badge_dash_show) ?> lainnya</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($sedang_dipinjam >= MAX_BORROWED_BOOKS): ?>
<div class="mb-4">
    <div class="v5-notice danger">
        <span class="v5-notice-ic"><i class="fas fa-ban"></i></span>
        <div>
            <div class="v5-notice-title">Kuota peminjaman sudah penuh</div>
            <div class="v5-notice-desc">Anda telah mencapai batas maksimal <?= MAX_BORROWED_BOOKS ?> buku. Kembalikan buku terlebih dahulu sebelum meminjam yang baru.</div>
        </div>
    </div>
</div>
<?php elseif ($menunggu_kembali > 0): ?>
<div class="mb-4">
    <div class="v5-notice">
        <span class="v5-notice-ic"><i class="fas fa-hourglass-half"></i></span>
        <div>
            <div class="v5-notice-title"><?= $menunggu_kembali ?> permintaan pengembalian sedang diproses</div>
            <div class="v5-notice-desc">Petugas akan memverifikasi buku dan menyetujui pengembalian Anda.</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- BUKU SEDANG DIPINJAM -->
<div class="card mb-4">
    <div class="card-header">
        <span class="header-title"><i class="fas fa-book-bookmark"></i>Buku Sedang Dipinjam</span>
        <a href="riwayat_peminjaman.php" class="btn btn-sm btn-ghost">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body">
        <?php if (empty($buku_dipinjam)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-book-open text-primary"></i></div>
            <h5>Belum ada buku yang dipinjam</h5>
            <p>Jelajahi katalog dan temukan bacaan favorit Anda.</p>
            <a href="katalog_buku.php" class="btn btn-primary btn-sm"><i class="fas fa-th-large me-1"></i>Lihat Katalog</a>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($buku_dipinjam as $b): ?>
            <div class="col-md-6 col-xl-4">
                <div class="return-card h-100">
                    <div class="d-flex align-items-center gap-3">
                        <?php $cover = buku_cover($b['foto']); ?>
                        <?php if (strpos($cover, 'data:') === 0): ?>
                        <div class="v5-timeline-cover placeholder"><i class="fas fa-book-open"></i></div>
                        <?php else: ?>
                        <img src="<?= e($cover) ?>" alt="Cover <?= e($b['judul_buku']) ?>" class="v5-timeline-cover" loading="lazy">
                        <?php endif; ?>
                        <div class="min-width-0">
                            <div class="return-card-title text-truncate" style="max-width:220px;"><?= e($b['judul_buku']) ?></div>
                            <div class="return-card-meta"><i class="fas fa-user-pen"></i><?= e($b['pengarang']) ?></div>
                            <div class="mt-1">
                                <?php $st = $b['status']; ?>
                                <span class="loan-badge <?= loan_status_class($st) ?>"><i class="fas fa-circle small"></i><?= loan_status_label($st) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="v5-timeline-grid">
                        <div class="v5-tl-cell"><label>Jatuh Tempo</label><strong class="<?= $b['telat'] > 0 ? 'text-danger' : '' ?>"><?= e(tgl_id($b['tanggal_kembali'])) ?></strong></div>
                        <div class="v5-tl-cell"><label>Denda Aktif</label><strong class="<?= $b['telat'] > 0 ? 'text-danger' : 'text-success' ?>"><?= $b['telat'] > 0 ? format_rupiah($b['telat'] * DENDA_PER_HARI) : 'Rp 0' ?></strong></div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="pengembalian.php" class="btn btn-sm btn-primary flex-grow-1"><i class="fas fa-rotate-left me-1"></i>Pengembalian</a>
                        <a href="detail_buku.php?id=<?= (int) $b['id_buku'] ?>" class="btn btn-sm btn-ghost"><i class="fas fa-eye"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- STATUS DENDA -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <span class="header-title"><i class="fas fa-coins"></i>Status Denda</span>
                <?php if ($total_denda_belum > 0): ?>
                <span class="badge bg-danger"><?= format_rupiah($total_denda_belum) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($total_denda_semua == 0 && $total_hari_telat == 0): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-circle-check text-success"></i></div>
                    <h5>Bersih dari denda</h5>
                    <p>Semua peminjaman Anda tepat waktu. Pertahankan! 💪</p>
                </div>
                <?php else: ?>
                <div class="v5-mini-stat mb-3">
                    <span class="v5-mini-stat-ic <?= $total_hari_telat > 0 ? 'danger' : 'success' ?>"><i class="fas fa-hourglass-half"></i></span>
                    <div>
                        <div class="v5-mini-stat-val"><?= $total_hari_telat ?></div>
                        <div class="v5-mini-stat-label">Total Hari Keterlambatan</div>
                    </div>
                </div>
                <div class="v5-mini-stat mb-3">
                    <span class="v5-mini-stat-ic <?= $total_denda_belum > 0 ? 'danger' : 'success' ?>"><i class="fas fa-money-bill-wave"></i></span>
                    <div>
                        <div class="v5-mini-stat-val"><?= format_rupiah($total_denda_belum) ?></div>
                        <div class="v5-mini-stat-label">Total Denda (Belum Bayar)</div>
                    </div>
                </div>
                <div class="v5-mini-stat">
                    <span class="v5-mini-stat-ic info"><i class="fas fa-percent"></i></span>
                    <div>
                        <div class="v5-mini-stat-val">Rp <?= number_format(DENDA_PER_HARI, 0, ',', '.') ?></div>
                        <div class="v5-mini-stat-label">Denda per Hari Keterlambatan</div>
                    </div>
                </div>
                <?php if ($total_denda_belum > 0): ?>
                <div class="alert alert-warning mt-3 mb-0 py-2 small">
                    <i class="fas fa-circle-info me-1"></i>Hubungi petugas untuk pelunasan denda.
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <span class="header-title"><i class="fas fa-bell"></i>Notifikasi</span>
                <?php if ($notif_unread_dash > 0): ?>
                <span class="badge bg-danger"><?= $notif_unread_dash > 99 ? '99+' : $notif_unread_dash ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notif_dashboard) && $menunggu_kembali == 0): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-bell-slash text-muted"></i></div>
                    <h5>Tidak ada notifikasi</h5>
                    <p>Anda tidak memiliki pemberitahuan baru.</p>
                </div>
                <?php else: ?>
                <div class="d-flex flex-column es-notif-dash-body">
                    <?php if ($menunggu_kembali > 0): ?>
                    <div class="v5-notice m-3 mb-2">
                        <span class="v5-notice-ic"><i class="fas fa-hourglass-half"></i></span>
                        <div>
                            <div class="v5-notice-title">Pengembalian sedang diproses</div>
                            <div class="v5-notice-desc"><?= $menunggu_kembali ?> buku menunggu persetujuan petugas.</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (empty($notif_dashboard)): ?>
                    <div class="es-notif-empty">
                        <span class="es-notif-empty-ic"><i class="fas fa-bell-slash" aria-hidden="true"></i></span>
                        <strong>Tidak ada notifikasi</strong>
                        <p>Aktivitas peminjaman dan pengembalian Anda akan muncul di sini.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($notif_dashboard as $nd): ?>
                        <a class="es-notif-item <?= $nd['is_read'] ? '' : 'unread' ?>" href="<?= e($nd['link']) ?>">
                            <span class="es-notif-item-ic <?= e($nd['class']) ?>"><i class="fas <?= e($nd['icon']) ?>" aria-hidden="true"></i></span>
                            <span class="es-notif-item-body">
                                <span class="es-notif-item-title"><?= e($nd['judul']) ?></span>
                                <span class="es-notif-item-msg"><?= e($nd['pesan']) ?></span>
                                <span class="es-notif-item-time"><?= e($nd['waktu']) ?></span>
                            </span>
                            <?php if (!$nd['is_read']): ?>
                            <span class="es-notif-dot" aria-hidden="true"></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- RIWAYAT TERBARU -->
<div class="card mb-4">
    <div class="card-header">
        <span class="header-title"><i class="fas fa-clock-rotate-left"></i>Riwayat Terbaru</span>
        <a href="riwayat_peminjaman.php" class="btn btn-sm btn-ghost">Riwayat Peminjaman <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Dikembalikan</th>
                        <th>Denda</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat_terbaru as $r): ?>
                    <tr>
                        <td><span class="text-truncate d-inline-block" style="max-width:220px;"><?= e($r['judul_buku']) ?></span></td>
                        <td><?= e(tgl_id($r['tanggal_pinjam'])) ?></td>
                        <td><?= e(tgl_id($r['tanggal_kembali'])) ?></td>
                        <td><?= $r['tanggal_dikembalikan'] ? e(tgl_id($r['tanggal_dikembalikan'])) : '-' ?></td>
                        <td class="<?= $r['denda'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $r['denda'] > 0 ? format_rupiah($r['denda']) : '-' ?></td>
                        <td><span class="loan-badge <?= loan_status_class($r['status']) ?>"><?= loan_status_label($r['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($riwayat_terbaru)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-book-open fa-2x mb-2 opacity-50 d-block"></i>Belum ada transaksi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- REKOMENDASI -->
<?php if (!empty($rekomendasi)): ?>
<div class="card mb-4">
    <div class="card-header">
        <span class="header-title"><i class="fas fa-wand-magic-sparkles"></i>Rekomendasi untuk Anda</span>
        <a href="katalog_buku.php?filter=populer" class="btn btn-sm btn-ghost">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body">
        <div class="v5-book-strip">
            <?php foreach ($rekomendasi as $rb): ?>
            <a href="detail_buku.php?id=<?= (int) $rb['id_buku'] ?>" class="v5-book-strip-card text-decoration-none">
                <?php $cover = buku_cover($rb['foto']); ?>
                <?php if (strpos($cover, 'data:') === 0): ?>
                <div class="v5-book-strip-cover placeholder"><i class="fas fa-book-open"></i></div>
                <?php else: ?>
                <img src="<?= e($cover) ?>" alt="Cover <?= e($rb['judul_buku']) ?>" class="v5-book-strip-cover" loading="lazy">
                <?php endif; ?>
                <div class="v5-book-strip-title"><?= e($rb['judul_buku']) ?></div>
                <div class="v5-book-strip-cat"><?= e($rb['nama_kategori'] ?? 'Umum') ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- TERBARU + POPULER -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="header-title"><i class="fas fa-sparkles"></i>Buku Terbaru</span>
                <a href="katalog_buku.php" class="btn btn-sm btn-ghost">Katalog <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body">
                <div class="v5-book-strip">
                    <?php foreach ($buku_baru as $bb): ?>
                    <a href="detail_buku.php?id=<?= (int) $bb['id_buku'] ?>" class="v5-book-strip-card text-decoration-none">
                        <?php $cover = buku_cover($bb['foto']); ?>
                        <?php if (strpos($cover, 'data:') === 0): ?>
                        <div class="v5-book-strip-cover placeholder"><i class="fas fa-book-open"></i></div>
                        <?php else: ?>
                        <img src="<?= e($cover) ?>" alt="Cover <?= e($bb['judul_buku']) ?>" class="v5-book-strip-cover" loading="lazy">
                        <?php endif; ?>
                        <div class="v5-book-strip-title"><?= e($bb['judul_buku']) ?></div>
                        <div class="v5-book-strip-cat"><?= e($bb['nama_kategori'] ?? 'Umum') ?></div>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($buku_baru)): ?><div class="text-muted small py-4">Belum ada koleksi baru.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="header-title"><i class="fas fa-trophy"></i>Buku Populer</span>
                <a href="katalog_buku.php" class="btn btn-sm btn-ghost">Katalog <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body">
                <div class="v5-book-strip">
                    <?php foreach ($buku_populer as $bp): ?>
                    <a href="detail_buku.php?id=<?= (int) $bp['id_buku'] ?>" class="v5-book-strip-card text-decoration-none">
                        <?php $cover = buku_cover($bp['foto']); ?>
                        <?php if (strpos($cover, 'data:') === 0): ?>
                        <div class="v5-book-strip-cover placeholder"><i class="fas fa-book-open"></i></div>
                        <?php else: ?>
                        <img src="<?= e($cover) ?>" alt="Cover <?= e($bp['judul_buku']) ?>" class="v5-book-strip-cover" loading="lazy">
                        <?php endif; ?>
                        <div class="v5-book-strip-title"><?= e($bp['judul_buku']) ?></div>
                        <div class="v5-book-strip-cat"><?= e($bp['nama_kategori'] ?? 'Umum') ?></div>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($buku_populer)): ?><div class="text-muted small py-4">Belum ada data popularitas.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PROFIL & PENGATURAN -->
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card h-100" id="profil">
            <div class="card-header">
                <span class="header-title"><i class="fas fa-id-card"></i>Profil Saya</span>
                <span class="badge-soft-<?= $anggota['status'] === 'aktif' ? 'success' : 'danger' ?>"><?= e(ucfirst($anggota['status'])) ?></span>
            </div>
            <div class="card-body text-center">
                <?php
                $foto_anggota = $anggota['foto'] ?? '';
                $foto_anggota_norm = ltrim((string) $foto_anggota, '/\\');
                $foto_anggota_exists = $foto_anggota_norm !== '' && file_exists(__DIR__ . '/' . $foto_anggota_norm);
                ?>
                <?php if ($foto_anggota_exists): ?>
                <img src="<?= e(BASE_URL . $foto_anggota_norm) ?>" alt="Foto <?= e($anggota['nama_lengkap']) ?>" class="rounded-circle mb-3" width="110" height="110" style="object-fit: cover; box-shadow: var(--shadow-md);">
                <?php else: ?>
                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center text-white mb-3" style="width:110px;height:110px;font-size:2.6rem;background:linear-gradient(135deg,var(--color-accent),var(--color-success)) !important;">
                    <?= e(strtoupper(mb_substr($anggota['nama_lengkap'], 0, 1))) ?>
                </div>
                <?php endif; ?>
                <h5 class="fw-bold mb-1"><?= e($anggota['nama_lengkap']) ?></h5>
                <p class="text-muted mb-3 small"><i class="fas fa-envelope me-1"></i><?= e($anggota['email']) ?></p>
                <div class="row g-2 text-start">
                    <div class="col-6"><div class="v5-tl-cell"><label>Nomor Anggota</label><strong><?= e($anggota['nomor_anggota']) ?></strong></div></div>
                    <div class="col-6"><div class="v5-tl-cell"><label>Terdaftar</label><strong><?= e(format_tanggal($anggota['tanggal_daftar'])) ?></strong></div></div>
                    <div class="col-6"><div class="v5-tl-cell"><label>No. HP</label><strong><?= e($anggota['telepon'] ?: '-') ?></strong></div></div>
                    <div class="col-6"><div class="v5-tl-cell"><label>Jenis Kelamin</label><strong><?= $anggota['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($anggota['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></strong></div></div>
                    <div class="col-12"><div class="v5-tl-cell"><label>Alamat</label><strong><?= e($anggota['alamat'] ?: '-') ?></strong></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7" id="pengaturan">
        <!-- Edit Profil -->
        <div class="card mb-4">
            <div class="card-header">
                <span class="header-title"><i class="fas fa-user-edit"></i>Edit Profil</span>
            </div>
            <div class="card-body">
                <form method="POST" action="" data-loader="true">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_profil" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" required value="<?= e($anggota['nama_lengkap']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email" required value="<?= e($anggota['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. HP</label>
                            <input type="text" class="form-control" name="telepon" value="<?= e($anggota['telepon'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2"><?= e($anggota['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update_profil" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ganti Password -->
        <div class="card">
            <div class="card-header">
                <span class="header-title"><i class="fas fa-key"></i>Ganti Password</span>
            </div>
            <div class="card-body">
                <form method="POST" action="" data-loader="true">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ganti_password" value="1">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Password Lama</label>
                            <input type="password" class="form-control" name="password_lama" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <input type="password" class="form-control" name="password_baru" required minlength="6">
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <input type="password" class="form-control" name="konfirmasi_password" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="ganti_password" class="btn btn-warning"><i class="fas fa-key me-1"></i>Ubah Password</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</div><!-- /.container -->

<?php require_once __DIR__ . '/partials/footer.php'; ?>
