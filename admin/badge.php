<?php
/**
 * PERPUSTAKAAN DAERAH - Halaman Badge / Pencapaian Anggota
 * File: badge.php
 * Deskripsi: Menampilkan seluruh badge yang diraih & terkunci,
 *            lengkap dengan progres menuju badge berikutnya.
 */

require_once __DIR__ . '/config/app.php';
require_login();

$id_anggota = (int) $_SESSION['id_anggota'];

badge_sync($koneksi, $id_anggota);

$badges        = badge_koleksi_pribadi($koneksi, $id_anggota);
$badge_earned  = badge_anggota($koneksi, $id_anggota);
$jumlah_punya  = count($badge_earned);
$jumlah_total  = count($badges);
$metric        = badge_metric($koneksi, $id_anggota);

$page_title = 'Badge & Pencapaian';
require_once __DIR__ . '/partials/header.php';
?>

<section class="v5-hero-user mb-4">
    <div class="v5-hero-overline"><i class="fas fa-medal"></i> Pencapaian Anggota</div>
    <h1 class="v5-hero-title">Badge &amp; <span style="color:var(--color-accent)">Pencapaian</span> 🏅</h1>
    <p class="v5-hero-sub">Badge diraih otomatis dari aktivitas Anda: peminjaman selesai, ulasan yang diberikan, dan ketepatan waktu pengembalian.</p>
    <div class="v5-hero-chips">
        <span class="v5-hero-chip"><i class="fas fa-award"></i><?= $jumlah_punya ?>/<?= $jumlah_total ?> badge diraih</span>
        <span class="v5-hero-chip"><i class="fas fa-book"></i><?= $metric['total_pinjam'] ?> buku selesai</span>
        <span class="v5-hero-chip"><i class="fas fa-star"></i><?= $metric['total_review'] ?> ulasan</span>
        <span class="v5-hero-chip"><i class="fas fa-stopwatch"></i><?= $metric['tepat_waktu'] ?> tepat waktu</span>
    </div>
</section>

<div class="container">
    <div class="card mb-4">
        <div class="card-header">
            <span class="header-title"><i class="fas fa-trophy"></i>Koleksi Badge</span>
            <span class="badge bg-primary"><?= $jumlah_total - $jumlah_punya ?> terkunci</span>
        </div>
        <div class="card-body">
            <?php if ($jumlah_total === 0): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-medal text-muted"></i></div>
                <h5>Belum ada badge yang tersedia</h5>
                <p>Sistem badge belum dikonfigurasi oleh admin.</p>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($badges as $bdg): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="h-100 d-flex flex-column border rounded-4 p-3 text-center"
                         style="<?= $bdg['dimiliki']
                             ? 'background:linear-gradient(135deg,' . e($bdg['warna']) . '1a,' . e($bdg['warna']) . '08);border-color:' . e($bdg['warna']) . '55;'
                             : 'opacity:.72;background:var(--surface-2);border-color:var(--border);' ?>">
                        <div class="mx-auto mb-2 d-inline-flex align-items-center justify-content-center rounded-circle"
                             style="width:74px;height:74px;font-size:2rem;color:#fff;background:<?= $bdg['dimiliki'] ? e($bdg['warna']) : 'linear-gradient(135deg,#94a3b8,#64748b)' ?>;box-shadow:0 6px 16px <?= $bdg['dimiliki'] ? e($bdg['warna']) : '#64748b' ?>33;">
                            <i class="fas <?= e($bdg['ikon']) ?>" aria-hidden="true"></i>
                        </div>
                        <h6 class="fw-bold mb-1"><?= e($bdg['nama']) ?></h6>
                        <p class="small text-muted flex-grow-1 mb-2" style="font-size:.82rem;"><?= e($bdg['deskripsi']) ?></p>
                        <?php if ($bdg['dimiliki']): ?>
                        <span class="badge text-bg-success align-self-center">Sudah diraih</span>
                        <?php else: ?>
                        <div class="progress mb-1" style="height:6px;">
                            <div class="progress-bar" style="width:<?= (int) $bdg['progres'] ?>%;background:<?= e($bdg['warna']) ?>;"></div>
                        </div>
                        <small class="text-muted"><?= min((int) $bdg['nilai'], (int) $bdg['syarat']) ?>/<?= (int) $bdg['syarat'] ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>