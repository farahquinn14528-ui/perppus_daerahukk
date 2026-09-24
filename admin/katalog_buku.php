<?php
/**
 * PERPUSTAKAAN DAERAH - Katalog Buku
 * File: katalog_buku.php
 * Deskripsi: Menampilkan daftar semua buku dengan filter dan sorting
 */

require_once __DIR__ . '/config/app.php';

$page_title = 'Katalog Buku';

// Ambil parameter filter
$kategori_id = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$penerbit_id = isset($_GET['penerbit']) ? (int) $_GET['penerbit'] : 0;
$filter       = $_GET['filter'] ?? '';
$sort         = $_GET['sort'] ?? 'terbaru';
$halaman      = isset($_GET['halaman']) ? max(1, (int) $_GET['halaman']) : 1;
$per_halaman  = 12;

// Data katalog (via app/catalog.php)
$katalog = buku_katalog($koneksi, [
    'kategori' => $kategori_id,
    'penerbit' => $penerbit_id,
    'filter'   => $filter,
    'sort'     => $sort,
], $halaman, $per_halaman);

$result       = $katalog['rows'];
$total_data   = $katalog['total'];
$total_halaman= $katalog['total_halaman'];
$halaman      = $katalog['halaman'];

// Opsi filter (via app/catalog.php)
$kategori_list = kategori_list($koneksi);
$penerbit_list = penerbit_list($koneksi);

require_once __DIR__ . '/partials/header.php';
?>

<!-- Page Header -->
<div class="es-page-head" data-reveal>
    <div>
        <span class="es-eyebrow">Koleksi</span>
        <h1>Katalog Buku</h1>
        <p>Jelajahi seluruh koleksi buku perpustakaan daerah kami, urutkan sesuai kebutuhan Anda.</p>
    </div>
    <span class="es-count-pill">
        <i class="fas fa-book-open" aria-hidden="true"></i><strong><?= (int) $total_data ?></strong> Buku
    </span>
</div>

<section class="pb-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-3 mb-4">
                <div class="es-filter-card" data-reveal="left">
                    <div class="es-filter-head"><i class="fas fa-sliders" aria-hidden="true"></i>Filter Buku</div>
                    <div class="es-filter-body">
                        <form method="GET" action="">
                            <!-- Kategori -->
                            <div class="es-filter-field">
                                <label for="f-kategori">Kategori</label>
                                <select name="kategori" id="f-kategori">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= (int) $kat['id_kategori'] ?>" <?= $kategori_id == $kat['id_kategori'] ? 'selected' : '' ?>>
                                        <?= e($kat['nama_kategori']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Penerbit -->
                            <div class="es-filter-field">
                                <label for="f-penerbit">Penerbit</label>
                                <select name="penerbit" id="f-penerbit">
                                    <option value="">Semua Penerbit</option>
                                    <?php foreach ($penerbit_list as $pen): ?>
                                    <option value="<?= (int) $pen['id_penerbit'] ?>" <?= $penerbit_id == $pen['id_penerbit'] ? 'selected' : '' ?>>
                                        <?= e($pen['nama_penerbit']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter Cepat -->
                            <div class="es-filter-field">
                                <span class="es-filter-label">Filter Cepat</span>
                                <div class="es-filter-radio">
                                    <label class="es-chip">
                                        <input type="radio" name="filter" value="" <?= !$filter ? 'checked' : '' ?>>
                                        <i class="fas fa-layer-group" aria-hidden="true"></i>Semua
                                    </label>
                                    <label class="es-chip">
                                        <input type="radio" name="filter" value="populer" <?= $filter === 'populer' ? 'checked' : '' ?>>
                                        <i class="fas fa-fire" aria-hidden="true"></i>Populer
                                    </label>
                                    <label class="es-chip">
                                        <input type="radio" name="filter" value="baru" <?= $filter === 'baru' ? 'checked' : '' ?>>
                                        <i class="fas fa-star" aria-hidden="true"></i>Baru
                                    </label>
                                </div>
                            </div>

                            <!-- Urutkan -->
                            <div class="es-filter-field">
                                <label for="f-sort">Urutkan</label>
                                <select name="sort" id="f-sort">
                                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                                    <option value="terlama" <?= $sort === 'terlama' ? 'selected' : '' ?>>Terlama</option>
                                    <option value="judul_az" <?= $sort === 'judul_az' ? 'selected' : '' ?>>Judul A-Z</option>
                                    <option value="judul_za" <?= $sort === 'judul_za' ? 'selected' : '' ?>>Judul Z-A</option>
                                    <option value="tahun_terbaru" <?= $sort === 'tahun_terbaru' ? 'selected' : '' ?>>Tahun Terbaru</option>
                                    <option value="tahun_terlama" <?= $sort === 'tahun_terlama' ? 'selected' : '' ?>>Tahun Terlama</option>
                                </select>
                            </div>

                            <div class="es-filter-actions">
                                <button type="submit" class="es-btn es-btn-primary">
                                    <i class="fas fa-search" aria-hidden="true"></i>Terapkan Filter
                                </button>
                                <a href="katalog_buku.php" class="es-btn es-btn-outline">
                                    <i class="fas fa-rotate-left" aria-hidden="true"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Buku Grid -->
            <div class="col-lg-9">
                <?php if (!empty($result)): ?>
                <div class="row">
                    <?php foreach ($result as $buku): ?>
                        <?php include __DIR__ . '/partials/book_card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_halaman > 1): ?>
                <nav class="es-pagination" aria-label="Navigasi halaman">
                    <?php if ($halaman > 1): ?>
                    <a class="es-page-link" href="?<?= http_build_query(array_merge($_GET, ['halaman' => $halaman - 1])) ?>" aria-label="Halaman sebelumnya">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                    <a class="es-page-link <?= $i === $halaman ? 'active' : '' ?>" <?= $i === $halaman ? 'aria-current="page"' : '' ?> href="?<?= http_build_query(array_merge($_GET, ['halaman' => $i])) ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($halaman < $total_halaman): ?>
                    <a class="es-page-link" href="?<?= http_build_query(array_merge($_GET, ['halaman' => $halaman + 1])) ?>" aria-label="Halaman berikutnya">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="es-empty">
                    <div class="es-empty-icon"><i class="fas fa-book-open" aria-hidden="true"></i></div>
                    <h3>Tidak ada buku ditemukan</h3>
                    <p>Coba ubah filter pencarian Anda, atau reset untuk melihat semua koleksi.</p>
                    <a href="katalog_buku.php" class="es-btn es-btn-primary">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>Reset Filter
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
