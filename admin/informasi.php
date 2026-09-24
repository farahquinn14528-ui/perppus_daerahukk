<?php
/**
 * PERPUSTAKAAN DAERAH - Halaman Informasi (Berita & Pengumuman)
 * File: informasi.php
 * Deskripsi: Daftar berita & pengumuman aktif dari CMS, plus tampilan detail.
 */

require_once __DIR__ . '/config/app.php';

$page_title = 'Informasi';

$jenis      = $_GET['jenis'] ?? '';
$filter_tipe = in_array($_GET['tipe'] ?? '', ['berita', 'pengumuman'], true) ? $_GET['tipe'] : '';
$detail_id  = (int) ($_GET['id'] ?? 0);

$detail = null;
if ($jenis === 'berita' && $detail_id > 0) {
    $detail = cms_berita_by_id($koneksi, $detail_id);
} elseif ($jenis === 'pengumuman' && $detail_id > 0) {
    $detail = cms_pengumuman_by_id($koneksi, $detail_id);
}

$berita     = cms_semua_berita($koneksi, null);
$pengumuman = cms_semua_pengumuman($koneksi, null);

require_once __DIR__ . '/partials/header.php';
?>

<section class="v5-hero-user mb-4">
    <div class="v5-hero-overline"><i class="fas fa-newspaper"></i> Informasi Terkini</div>
    <h1 class="v5-hero-title">Berita &amp; <span style="color:var(--color-accent)">Pengumuman</span></h1>
    <p class="v5-hero-sub">Pantau pengumuman resmi dan agenda kegiatan dari perpustakaan daerah.</p>
    <div class="v5-hero-chips">
        <a class="v5-hero-chip" href="informasi.php"><i class="fas fa-layer-group"></i>Semua</a>
        <a class="v5-hero-chip" href="informasi.php?tipe=berita"><i class="fas fa-newspaper"></i>Berita</a>
        <a class="v5-hero-chip" href="informasi.php?tipe=pengumuman"><i class="fas fa-bullhorn"></i>Pengumuman</a>
    </div>
</section>

<div class="container">
    <?php if ($detail): ?>
    <?php $is_berita = $jenis === 'berita'; ?>
    <div class="card mb-4">
        <div class="card-body p-4">
            <nav class="mb-3" aria-label="breadcrumb">
                <a href="informasi.php" class="text-muted text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Kembali ke semua informasi</a>
            </nav>
            <span class="badge <?= $is_berita ? 'text-bg-primary' : 'text-bg-warning' ?> mb-3"><?= $is_berita ? 'Berita' : 'Pengumuman' ?></span>
            <h2 class="fw-bold mb-2"><?= e($detail['judul']) ?></h2>
            <div class="text-muted small mb-3">
                <i class="fas fa-calendar-day me-1"></i><?= e(tgl_id($detail['created_at'] ?? date('Y-m-d'))) ?>
                <?php if ($is_berita && !empty($detail['kategori'])): ?>&nbsp;·&nbsp;<i class="fas fa-tag me-1"></i><?= e($detail['kategori']) ?><?php endif; ?>
            </div>
            <hr>
            <div class="lh-lg" style="white-space:pre-line;"><?= nl2br(e($detail['isi'])) ?></div>
        </div>
    </div>
    <?php else: ?>
        <?php if ($filter_tipe === 'berita'): $daftar = $berita; ?>
        <?php elseif ($filter_tipe === 'pengumuman'): $daftar = $pengumuman; ?>
        <?php else: $daftar = array_merge($pengumuman, $berita); ?>
        <?php endif; ?>

        <?php usort($daftar, function ($a, $b) {
            return strtotime($b['created_at'] ?? '') <=> strtotime($a['created_at'] ?? '');
        }); ?>

        <?php if (empty($daftar)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-newspaper text-muted"></i></div>
                    <h5>Belum ada informasi</h5>
                    <p>Berita dan pengumuman akan muncul di sini.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($daftar as $item): ?>
            <?php
                $is_ber = !empty($item['id_berita']);
                $link = $is_ber
                    ? 'informasi.php?jenis=berita&id=' . (int) $item['id_berita']
                    : 'informasi.php?jenis=pengumuman&id=' . (int) $item['id_pengumuman'];
            ?>
            <div class="col-md-6 col-xl-4">
                <a href="<?= e($link) ?>" class="text-decoration-none">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge <?= $is_ber ? 'text-bg-primary' : 'text-bg-warning' ?>"><?= $is_ber ? 'Berita' : 'Pengumuman' ?></span>
                                <small class="text-muted"><i class="fas fa-calendar-day me-1"></i><?= e(tgl_id($item['created_at'] ?? date('Y-m-d'))) ?></small>
                            </div>
                            <h6 class="fw-bold mb-0"><?= e($item['judul']) ?></h6>
                            <p class="text-muted small flex-grow-1 mb-0" style="white-space:pre-line;"><?= e(mb_strimwidth($item['isi'], 0, 120, '…')) ?></p>
                            <span class="fw-semibold" style="color:var(--color-accent)">Baca selengkapnya <i class="fas fa-arrow-right ms-1"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>