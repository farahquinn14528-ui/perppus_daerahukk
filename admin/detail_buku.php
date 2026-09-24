<?php
/**
 * PERPUSTAKAAN DAERAH - Detail Buku
 * File: detail_buku.php
 * Deskripsi: Menampilkan detail lengkap buku dan form peminjaman
 */

require_once __DIR__ . '/config/app.php';

// Ambil ID buku
$id_buku = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_buku <= 0) {
    set_flash('error', 'Buku tidak ditemukan.');
    redirect('katalog_buku.php');
}

// Query detail buku (via app/catalog.php)
$buku = buku_detail($koneksi, $id_buku);

if ($buku === null) {
    set_flash('error', 'Buku tidak ditemukan.');
    redirect('katalog_buku.php');
}

$page_title = $buku['judul_buku'];

/* ---------------- AKSI: SIMPAN RATING & KOMENTAR ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_rating'])) {
    if (!is_logged_in() || !is_anggota()) {
        set_flash('error', 'Silakan masuk untuk memberi rating dan komentar.');
        redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    if (!csrf_verify()) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect('detail_buku.php?id=' . $id_buku);
    }

    $rating     = (int) ($_POST['rating'] ?? 0);
    $komentar   = trim((string) ($_POST['komentar'] ?? ''));
    $id_anggota = (int) $_SESSION['id_anggota'];

    $errors = [];
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating harus antara 1 sampai 5 bintang.';
    }
    // Komentar boleh kosong, tetapi jika diisi wajib tidak hanya spasi.
    if (mb_strlen($komentar) > 1000) {
        $errors[] = 'Komentar terlalu panjang (maksimal 1000 karakter).';
    }

    if (!empty($errors)) {
        set_flash('error', implode(' ', $errors));
        redirect('detail_buku.php?id=' . $id_buku);
    }

    if (rating_simpan($koneksi, $id_anggota, $id_buku, $rating, $komentar !== '' ? $komentar : null)) {
        set_flash('success', 'Terima kasih! Rating dan komentar Anda berhasil disimpan.');
    } else {
        set_flash('error', 'Gagal menyimpan rating. Silakan coba lagi.');
    }
    redirect('detail_buku.php?id=' . $id_buku);
}

// Ambil buku terkait (kategori yang sama)
$terkait_result = buku_terkait($koneksi, (int) $buku['id_kategori'], $id_buku, 4);

// URL gambar
$cover_url = buku_cover($buku['foto']);

// Status pinjam anggota (untuk tombol aksi)
$sudah_pinjam = is_logged_in() && is_anggota() ? loan_sudah_pinjam($koneksi, (int) $_SESSION['id_anggota'], $id_buku) : false;

// Data rating & komentar (via app/ratings.php)
$rating_summary = rating_ringkasan($koneksi, $id_buku);
$rating_rata    = $rating_summary['rata'];
$rating_jumlah  = $rating_summary['jumlah'];
$rating_dist    = $rating_summary['distribusi'];
$rating_saya    = is_logged_in() ? rating_user($koneksi, (int) $_SESSION['id_anggota'], $id_buku) : null;
$komentar_saya  = is_logged_in() ? rating_user_komentar($koneksi, (int) $_SESSION['id_anggota'], $id_buku) : null;
$daftar_ulasan  = rating_list($koneksi, $id_buku, 10);

require_once __DIR__ . '/partials/header.php';
?>

<!-- Breadcrumb -->
<nav class="es-breadcrumb" aria-label="breadcrumb" data-reveal>
    <a href="index.php"><i class="fas fa-house" aria-hidden="true"></i>Beranda</a>
    <span class="es-bc-sep"><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
    <a href="katalog_buku.php">Katalog</a>
    <span class="es-bc-sep"><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
    <span class="es-bc-current" aria-current="page"><?= e($buku['judul_buku']) ?></span>
</nav>

<!-- Detail Buku -->
<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            <!-- Cover Buku -->
            <div class="col-lg-4" data-reveal="left">
                <div class="es-detail-cover">
                    <div class="es-detail-badges">
                        <?php if (!empty($buku['populer']) && $buku['populer'] == 1): ?>
                        <span class="es-badge es-badge-hot"><i class="fas fa-fire" aria-hidden="true"></i>Populer</span>
                        <?php endif; ?>
                        <?php if (!empty($buku['baru']) && $buku['baru'] == 1): ?>
                        <span class="es-badge es-badge-new"><i class="fas fa-star" aria-hidden="true"></i>Baru</span>
                        <?php endif; ?>
                    </div>
                    <img src="<?= e($cover_url) ?>" alt="<?= e($buku['judul_buku']) ?>">
                </div>
            </div>

            <!-- Info Buku -->
            <div class="col-lg-8" data-reveal="right">
                <span class="es-detail-cat"><i class="fas fa-tag" aria-hidden="true"></i><?= e($buku['nama_kategori'] ?? '-') ?></span>

                <h2 class="es-detail-title"><?= e($buku['judul_buku']) ?></h2>

                <div class="es-detail-meta">
                    <span><i class="fas fa-user-pen" aria-hidden="true"></i><?= e($buku['pengarang']) ?></span>
                    <?php if (!empty($buku['tahun_terbit'])): ?>
                    <span><i class="fas fa-calendar" aria-hidden="true"></i><?= e($buku['tahun_terbit']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($buku['nama_penerbit'])): ?>
                    <span><i class="fas fa-building" aria-hidden="true"></i><?= e($buku['nama_penerbit']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Rating Ringkas -->
                <div class="es-detail-rating" data-reveal>
                    <?php if ($rating_jumlah > 0): ?>
                        <span class="es-detail-rating-score"><?= number_format((float) $rating_rata, 1, ',', '.') ?></span>
                        <?= rating_stars_display($rating_rata) ?>
                        <span class="es-detail-rating-count"><?= $rating_jumlah ?> ulasan</span>
                    <?php else: ?>
                        <?= rating_stars_display(0) ?>
                        <span class="es-detail-rating-count">Belum ada rating</span>
                    <?php endif; ?>
                </div>

                <!-- Detail Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="es-stat-card">
                            <i class="fas fa-barcode" aria-hidden="true"></i>
                            <span>ISBN</span>
                            <strong><?= e($buku['isbn'] ?: '-') ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="es-stat-card">
                            <i class="fas fa-file-lines" aria-hidden="true"></i>
                            <span>Halaman</span>
                            <strong><?= e($buku['jumlah_halaman'] ?: '-') ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="es-stat-card">
                            <i class="fas fa-language" aria-hidden="true"></i>
                            <span>Bahasa</span>
                            <strong><?= e($buku['bahasa'] ?: '-') ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="es-stat-card">
                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                            <span>Rak</span>
                            <strong><?= e($buku['nama_rak'] ?: '-') ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Kondisi & Stok -->
                <?php
                $kondisi_text  = format_kondisi($buku['kondisi']);
                $kondisi_class_map = ['baik' => 'baik', 'rusak_ringan' => 'rusak_ringan', 'rusak_berat' => 'rusak_berat'];
                $kondisi_class = $kondisi_class_map[$buku['kondisi']] ?? 'baik';
                ?>
                <div class="es-status-row">
                    <div class="es-status-item">
                        <span class="es-status-label">Kondisi</span>
                        <span class="es-book-cond <?= e($kondisi_class) ?>"><?= e($kondisi_text) ?></span>
                    </div>
                    <div class="es-status-item">
                        <span class="es-status-label">Stok</span>
                        <?php if ((int) $buku['stok_buku'] > 0): ?>
                        <span class="es-book-stock in"><i class="fas fa-circle" aria-hidden="true"></i><?= (int) $buku['stok_buku'] ?> tersedia</span>
                        <?php else: ?>
                        <span class="es-book-stock out"><i class="fas fa-circle" aria-hidden="true"></i>Habis</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sinopsis -->
                <div class="es-detail-section">
                    <h5><i class="fas fa-align-left" aria-hidden="true"></i>Sinopsis</h5>
                    <p><?= e($buku['sinopsis'] ?: 'Sinopsis belum tersedia.') ?></p>
                </div>

                <?php if (!empty($buku['keterangan'])): ?>
                <div class="es-detail-section">
                    <h5><i class="fas fa-circle-info" aria-hidden="true"></i>Keterangan</h5>
                    <p><?= e($buku['keterangan']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Tombol Aksi -->
                <div class="es-detail-actions">
                    <?php if ((int) $buku['stok_buku'] > 0): ?>
                        <?php if (is_logged_in()): ?>
                            <?php if (is_anggota()): ?>
                                <?php $dipinjam = hitung_buku_dipinjam($koneksi, (int) $_SESSION['id_anggota']); ?>
                                <?php if ($sudah_pinjam): ?>
                                <button class="es-btn es-btn-lg" disabled>
                                    <i class="fas fa-bookmark" aria-hidden="true"></i>Buku Ini Sedang Anda Pinjam
                                </button>
                                <?php elseif ($dipinjam < MAX_BORROWED_BOOKS): ?>
                                <a href="pinjam.php?id=<?= (int) $buku['id_buku'] ?>" class="es-btn es-btn-primary es-btn-lg">
                                    <i class="fas fa-hand-holding" aria-hidden="true"></i>Pinjam Buku Ini
                                </a>
                                <?php else: ?>
                                <button class="es-btn es-btn-lg" disabled>
                                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>Maks. <?= MAX_BORROWED_BOOKS ?> Buku
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                            <span class="es-btn es-btn-static es-btn-lg" role="status"><i class="fas fa-info-circle" aria-hidden="true"></i>Akun ini tidak dapat meminjam buku</span>
                            <?php endif; ?>
                        <?php else: ?>
                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="es-btn es-btn-primary es-btn-lg">
                            <i class="fas fa-right-to-bracket" aria-hidden="true"></i>Masuk untuk Meminjam
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                    <button class="es-btn es-btn-lg" disabled>
                        <i class="fas fa-times-circle" aria-hidden="true"></i>Stok Habis
                    </button>
                    <?php endif; ?>
                    <a href="katalog_buku.php" class="es-btn es-btn-outline es-btn-lg">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Rating & Komentar -->
        <div class="es-review-section mt-5" data-reveal>
            <div class="row g-4">
                <!-- Ringkasan Rating -->
                <div class="col-lg-4">
                    <div class="es-review-summary">
                        <h4><i class="fas fa-star-half-stroke me-2" aria-hidden="true"></i>Ulasan Pengguna</h4>
                        <div class="es-review-score">
                            <?php if ($rating_jumlah > 0): ?>
                            <span class="es-review-score-num"><?= number_format((float) $rating_rata, 1, ',', '.') ?></span>
                            <div>
                                <?= rating_stars_display($rating_rata) ?>
                                <small class="text-muted d-block mt-1"><?= $rating_jumlah ?> ulasan</small>
                            </div>
                            <?php else: ?>
                            <span class="es-review-score-num">–</span>
                            <div>
                                <?= rating_stars_display(0) ?>
                                <small class="text-muted d-block mt-1">Belum ada ulasan</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="es-review-bars mt-3">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="es-review-bar">
                                <span class="es-review-bar-label"><?= $i ?> <i class="fas fa-star" aria-hidden="true"></i></span>
                                <div class="es-review-bar-track"><div class="es-review-bar-fill" style="width:<?= e($rating_dist['b' . $i . '_pct']) ?>%"></div></div>
                                <span class="es-review-bar-val"><?= $rating_dist['b' . $i] ?></span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Form Rating (login) -->
                <div class="col-lg-8">
                    <div class="es-review-form">
                        <?php if (is_logged_in() && is_anggota()): ?>
                        <h5><i class="fas fa-star me-2" aria-hidden="true"></i><?= $rating_saya ? 'Perbarui Ulasan Anda' : 'Beri Rating & Komentar' ?></h5>
                        <form method="POST" action="" data-loader="true">
                            <?= csrf_field() ?>
                            <input type="hidden" name="simpan_rating" value="1">
                            <div class="mb-2">
                                <label class="form-label fw-semibold small mb-1">Rating Anda</label>
                                <div class="pd-rating-input-wrap">
                                    <?= rating_stars_input_by_name('rating', $rating_saya ?? 0) ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="komentar" class="form-label fw-semibold">Komentar (opsional)</label>
                                <textarea class="form-control" id="komentar" name="komentar" rows="3"
                                          maxlength="1000" placeholder="Bagikan pendapat Anda tentang buku ini…"><?= e($komentar_saya ?? '') ?></textarea>
                            </div>
                            <button type="submit" name="simpan_rating" class="es-btn es-btn-primary">
                                <i class="fas fa-paper-plane me-1" aria-hidden="true"></i><?= $rating_saya ? 'Simpan Perubahan' : 'Kirim Ulasan' ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="es-review-login">
                            <p class="mb-2"><strong>Ingin memberi rating & komentar?</strong></p>
                            <p class="text-muted small mb-3">Masuk dengan akun anggota Anda untuk berbagi ulasan tentang buku ini.</p>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="es-btn es-btn-primary">
                                <i class="fas fa-right-to-bracket me-1" aria-hidden="true"></i>Masuk untuk Memberi Ulasan
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Daftar Komentar -->
            <div class="es-review-list mt-4">
                <h5 class="es-review-list-title"><i class="fas fa-comments me-2" aria-hidden="true"></i>Komentar Pengguna</h5>
                <?php if (empty($daftar_ulasan)): ?>
                <div class="es-empty">
                    <div class="es-empty-icon"><i class="fas fa-comment-dots" aria-hidden="true"></i></div>
                    <h3>Belum ada komentar</h3>
                    <p>Jadilah yang pertama memberikan ulasan untuk buku ini.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($daftar_ulasan as $u): ?>
                    <div class="es-review-item">
                        <div class="es-review-avatar"><?= e(strtoupper(mb_substr($u['nama_lengkap'], 0, 1))) ?></div>
                        <div class="es-review-item-body">
                            <div class="es-review-item-head">
                                <span class="es-review-item-name"><?= e($u['nama_lengkap']) ?></span>
                                <span class="es-review-item-time"><?= e(tgl_id(substr($u['created_at'], 0, 10))) ?></span>
                            </div>
                            <div class="es-review-item-stars"><?= rating_stars_display((int) $u['rating']) ?></div>
                            <?php if (!empty($u['komentar'])): ?>
                            <p class="es-review-item-text"><?= e($u['komentar']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Buku Terkait -->
        <?php if (!empty($terkait_result)): ?>
        <div class="es-related" data-reveal>
            <div class="es-related-head">
                <h4><i class="fas fa-layer-group" aria-hidden="true"></i>Buku Terkait</h4>
                <a href="katalog_buku.php?kategori=<?= (int) $buku['id_kategori'] ?>" class="es-btn es-btn-outline es-btn-sm">
                    Lihat Semua<i class="fas fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="row">
                <?php foreach ($terkait_result as $buku_terkait): ?>
                    <?php $buku = $buku_terkait; ?>
                    <?php include __DIR__ . '/partials/book_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
