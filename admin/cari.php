<?php
/**
 * PERPUSTAKAAN DAERAH - Cari Buku
 * File: cari.php
 * Deskripsi: Halaman pencarian buku
 */

require_once __DIR__ . '/config/app.php';

$page_title = 'Cari Buku';

$q = trim($_GET['q'] ?? '');
$kategori_id = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

$result = [];
$total  = 0;

if ($q !== '' || $kategori_id > 0) {
    // via app/catalog.php
    $result = buku_cari($koneksi, $q, $kategori_id);
    $total  = count($result);
}

$kategori_list = kategori_list($koneksi);

require_once __DIR__ . '/partials/header.php';
?>

<!-- Search Header -->
<section class="pt-4">
    <div class="container">
        <div class="es-page-head" style="border-bottom: 0; padding-bottom: 0;">
            <div>
                <span class="es-eyebrow">Pencarian</span>
                <h1>Cari Buku</h1>
                <p>Temukan buku favorit Anda dari seluruh koleksi perpustakaan — cari berdasarkan judul, pengarang, atau ISBN.</p>
            </div>
        </div>
        <div class="es-search-hero mt-3">
            <form method="GET" action="cari.php">
                <div class="es-search-box">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="text" name="q" placeholder="Cari judul, pengarang, ISBN..." value="<?= e($q) ?>" aria-label="Kata kunci pencarian" autocomplete="off">
                    <select name="kategori" aria-label="Filter kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= (int) $kat['id_kategori'] ?>" <?= $kategori_id == $kat['id_kategori'] ? 'selected' : '' ?>>
                            <?= e($kat['nama_kategori']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="es-btn es-btn-primary" style="flex-shrink: 0;">
                        <i class="fas fa-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Cari</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Hasil Pencarian -->
<section class="pb-5">
    <div class="container">
        <?php if ($q !== '' || $kategori_id > 0): ?>
            <div class="es-results-head">
                <h4>
                    Hasil Pencarian
                    <?php if ($q): ?>
                        <small>untuk "<strong><?= e($q) ?></strong>"</small>
                    <?php endif; ?>
                </h4>
                <span class="es-count-pill">
                    <i class="fas fa-book-open" aria-hidden="true"></i><strong><?= (int) $total ?></strong> Buku
                </span>
            </div>

            <?php if ($total > 0): ?>
            <div class="row">
                <?php foreach ($result as $buku): ?>
                    <?php include __DIR__ . '/partials/book_card.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="es-empty">
                <div class="es-empty-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></div>
                <h3>Tidak ada buku ditemukan</h3>
                <p>Coba kata kunci lain atau ubah filter kategori pencarian Anda.</p>
                <a href="katalog_buku.php" class="es-btn es-btn-primary">
                    <i class="fas fa-th-large" aria-hidden="true"></i>Lihat Semua Buku
                </a>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="es-empty">
                <div class="es-empty-icon"><i class="fas fa-search" aria-hidden="true"></i></div>
                <h3>Mulai Mencari</h3>
                <p>Ketik judul buku, nama pengarang, atau ISBN di kolom pencarian untuk menemukan koleksi Anda.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
