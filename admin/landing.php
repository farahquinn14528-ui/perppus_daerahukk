<?php
/**
 * PERPUSTAKAAN DAERAH - Landing Page (Beranda)
 * File: landing.php
 * Deskripsi: Beranda premium dengan hero, pencarian, statistik,
 * layanan, koleksi (populer/terbaru/most-borrowed), kategori,
 * manfaat, pengumuman & agenda, testimoni, FAQ, kontak, dan CTA.
 */
if (!defined('APP_NAME')) {
    die('Akses langsung tidak diizinkan');
}

$page_title = 'Beranda';

// Perbarui status keterlambatan (cron-like)
update_status_terlambat($koneksi);

// --- Data Koleksi (via app/catalog.php) -------------------------------
$featured      = buku_populer($koneksi, 8);
$latest        = buku_terbaru_tandai($koneksi, 4);
$most_borrowed = buku_most_borrowed($koneksi, 4);

// --- Data Kategori (via app/catalog.php) ------------------------------
$kategori_rows = kategori_dengan_jumlah($koneksi);

$kategori_icons = [
    'RPL'            => 'fa-code',
    'TKJ'            => 'fa-network-wired',
    'MM'             => 'fa-photo-film',
    'Animasi'        => 'fa-clapperboard',
    'Matematika'     => 'fa-square-root-variable',
    'B. Inggris'     => 'fa-language',
    'Novel'          => 'fa-book-open',
    'Sejarah'        => 'fa-landmark',
    'Fiksi'          => 'fa-book',
    'Non-Fiksi'      => 'fa-file-lines',
    'Pendidikan'     => 'fa-graduation-cap',
    'Sains & Teknologi' => 'fa-flask',
    'Agama'          => 'fa-star-and-crescent',
    'Bahasa'         => 'fa-language',
    'Seni & Olahraga'=> 'fa-palette',
];

// --- Statistik ----------------------------------------------------------
$stat_total_buku     = (int) db_scalar($koneksi, "SELECT COUNT(*) FROM tb_buku", [], 0);
$stat_total_anggota  = (int) db_scalar($koneksi, "SELECT COUNT(*) FROM tb_anggota WHERE role = 'anggota'", [], 0);
$stat_total_dipinjam = (int) db_scalar($koneksi, "SELECT COUNT(*) FROM tb_peminjaman WHERE status IN ('dipinjam','terlambat')", [], 0);
$stat_total_tersedia = (int) db_scalar($koneksi, "SELECT COALESCE(SUM(stok_buku),0) FROM tb_buku", [], 0);

// --- Konten CMS (via app/content.php) ------------------------------
$layanan_cms = cms_layanan($koneksi);
$layanan_colors = ['blue', 'emerald', 'gold'];
$layanan = [];
$ci = 0;
foreach ($layanan_cms as $l) {
    $layanan[] = [
        'ic'    => $l['ikon'] ?: 'fa-book-open-reader',
        'color' => $layanan_colors[$ci % 3],
        'title' => $l['nama'],
        'desc'  => $l['deskripsi'],
    ];
    $ci++;
}
if (empty($layanan)) {
    $layanan = [
        ['ic' => 'fa-book-open-reader',     'color' => 'blue',    'title' => 'Peminjaman Buku',  'desc' => 'Pinjam hingga ' . MAX_BORROWED_BOOKS . ' buku selama ' . LOAN_PERIOD_DAYS . ' hari dengan mudah dan cepat.'],
        ['ic' => 'fa-tablet-screen-button', 'color' => 'emerald', 'title' => 'Koleksi Digital',   'desc' => 'Akses koleksi e-book dan jurnal ilmiah secara online.'],
        ['ic' => 'fa-couch',                'color' => 'gold',    'title' => 'Ruang Baca',       'desc' => 'Ruang baca nyaman ber-AC untuk belajar dan riset.'],
    ];
}

$benefits = [
    ['Koleksi lengkap & terkurasi', 'Ribuan buku dari berbagai disiplin ilmu tersusun rapi per kategori.'],
    ['Peminjaman real-time', 'Status buku dan jadwal pengembalian selalu terbarui otomatis.'],
    ['Pengingat pengembalian', 'Notifikasi otomatis mendekati tenggat mencegah keterlambatan & denda.'],
    ['Akses 24/7', 'Cari katalog dan kelola akun kapan pun dari perangkat mana saja.'],
    ['Statistik pribadi', 'Pantau aktivitas membaca Anda dalam satu dasbor yang ringkas.'],
];

$pengumuman_cms = cms_pengumuman($koneksi, 3);
$pengumuman = [];
foreach ($pengumuman_cms as $pg) {
    $ts = strtotime($pg['created_at']);
    $pengumuman[] = [
        'day'   => $ts ? date('d', $ts) : '--',
        'month' => $ts ? strtoupper(date('M', $ts)) : '--',
        'title' => $pg['judul'],
        'desc'  => $pg['isi'],
        'tipe'  => $pg['tipe'] ?? 'info',
    ];
}

$agenda_cms = cms_berita($koneksi, 3);
$agenda = [];
foreach ($agenda_cms as $br) {
    $ts = strtotime($br['created_at']);
    $agenda[] = [
        'day'   => $ts ? date('d', $ts) : '--',
        'month' => $ts ? strtoupper(date('M', $ts)) : '--',
        'title' => $br['judul'],
        'desc'  => $br['isi'],
        'chip'  => 'Berita',
    ];
}

$testimoni = [
    ['name' => 'Dinda Puspita', 'role' => 'Mahasiswa', 'initial' => 'D', 'color' => '#1B50C4', 'text' => 'Mencari referensi skripsi jadi jauh lebih cepat. Sistem katalognya rapi dan status stok selalu akurat.'],
    ['name' => 'Bambang Setiawan', 'role' => 'Guru', 'initial' => 'B', 'color' => '#059669', 'text' => 'Peminjaman online sangat membantu. Saya tinggal pesan buku, datang ambil, tanpa antre di meja sirkulasi.'],
    ['name' => 'Ayu Lestari', 'role' => 'Penulis Pemula', 'initial' => 'A', 'color' => '#D9950A', 'text' => 'Ruang bacanya nyaman dan koleksinya terus diperbarui. Pengingat pengembaliannya juga tepat waktu.'],
];

$faq_cms = cms_faq($koneksi);
$faq = [];
foreach ($faq_cms as $fq) {
    $faq[] = ['q' => $fq['pertanyaan'], 'a' => $fq['jawaban']];
}
if (empty($faq)) {
    $faq = [
        ['q' => 'Berapa lama masa peminjaman buku?', 'a' => 'Masa peminjaman buku adalah ' . LOAN_PERIOD_DAYS . ' hari. Jika melewati batas waktu, akan dikenakan denda ' . format_rupiah(DENDA_PER_HARI) . ' per hari keterlambatan.'],
        ['q' => 'Berapa maksimal buku yang bisa dipinjam?', 'a' => 'Setiap anggota dapat meminjam maksimal ' . MAX_BORROWED_BOOKS . ' buku dalam satu waktu.'],
        ['q' => 'Bagaimana cara menjadi anggota?', 'a' => 'Daftar secara online melalui halaman pendaftaran di menu Daftar Anggota, atau langsung datang ke meja layanan perpustakaan untuk dibantu petugas.'],
    ];
}

$kontak_map = cms_pengaturan($koneksi);
$kontak = [
    ['ic' => 'fa-location-dot', 'color' => 'blue',    'title' => 'Alamat',       'value' => $kontak_map['alamat'] ?? 'Jl. Perpustakaan Daerah No. 1, Kota Daerah 12345'],
    ['ic' => 'fa-phone',        'color' => 'emerald', 'title' => 'Telepon',      'value' => $kontak_map['telepon'] ?? '(021) 555-0123'],
    ['ic' => 'fa-envelope',     'color' => 'gold',    'title' => 'Email',        'value' => $kontak_map['email'] ?? 'info@perpustakaan-daerah.id'],
    ['ic' => 'fa-clock',        'color' => 'blue',    'title' => 'Jam Layanan',  'value' => $kontak_map['jam_buka_info'] ?? 'Senin - Jumat: 08.00 - 20.00'],
];

// Hero: gunakan banner aktif pertama (CMS)
$banner_aktif = cms_banner_aktif($koneksi);
$hero = [
    'judul'    => 'Jendela Literasi',
    'accent'   => 'Untuk Semua Kalangan',
    'sub'      => 'Akses ribuan koleksi buku fisik dan digital dengan mudah. Cari judul favorit, pantau status pinjaman, dan luaskan wawasan — di mana saja, kapan saja.',
    'btn_text' => '',
    'btn_url'  => '',
];
if (!empty($banner_aktif)) {
    $bn = $banner_aktif;
    $hero['judul']  = $bn['judul'] ?: $hero['judul'];
    $hero['accent'] = $bn['subjudul'] ?: $hero['accent'];
    $hero['sub']    = $bn['subjudul'] ?: $hero['sub'];
    if (!empty($bn['tombol_text']) && !empty($bn['tombol_url'])) {
        $hero['btn_text'] = $bn['tombol_text'];
        $hero['btn_url']  = $bn['tombol_url'];
    }
}
?>

<div class="landing-page">

    <!-- ============================================================
         HERO
         ============================================================ -->
    <section class="lp-hero" id="beranda">
        <div class="lp-hero-bg" aria-hidden="true"></div>
        <div class="lp-hero-grid" aria-hidden="true"></div>
        <span class="lp-blob lp-blob-1" aria-hidden="true"></span>
        <span class="lp-blob lp-blob-2" aria-hidden="true"></span>
        <span class="lp-blob lp-blob-3" aria-hidden="true"></span>
        <span class="lp-float-icon lp-float-icon-1" aria-hidden="true"><i class="fas fa-book"></i></span>
        <span class="lp-float-icon lp-float-icon-2" aria-hidden="true"><i class="fas fa-feather-pointed"></i></span>
        <span class="lp-float-icon lp-float-icon-3" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>

        <div class="container">
            <div class="lp-hero-inner">
                <div class="lp-hero-copy">
                    <span class="lp-hero-badge">
                        <span class="pulse-dot" aria-hidden="true"></span>
                        <?= e(APP_TAGLINE) ?>
                    </span>

                    <h1>
                        <?= e($hero['judul']) ?><br>
                        <span class="lp-hero-accent"><?= e($hero['accent']) ?></span>
                    </h1>

                    <p class="lp-hero-sub">
                        <?= e($hero['sub']) ?>
                    </p>

                    <!-- Pencarian -->
                    <form class="lp-search" action="cari.php" method="get" role="search">
                        <div class="lp-search-box">
                            <span class="lp-search-ic" aria-hidden="true"><i class="fas fa-search"></i></span>
                            <input type="text" name="q" placeholder="Cari judul, pengarang, atau ISBN…" aria-label="Kata kunci pencarian">
                            <select name="kategori" aria-label="Filter kategori">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($kategori_rows as $kat): ?>
                                    <option value="<?= (int) $kat['id_kategori'] ?>"><?= e($kat['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" aria-label="Cari buku">
                                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">Cari</span>
                            </button>
                        </div>
                        <div class="lp-search-hints">
                            <span>Populer:</span>
                            <a href="cari.php?q=Novel">Novel</a>
                            <a href="cari.php?q=Sejarah">Sejarah</a>
                            <a href="cari.php?q=Pemrograman">Pemrograman</a>
                            <a href="katalog_buku.php?filter=baru">Koleksi Terbaru</a>
                        </div>
                    </form>

                    <div class="lp-hero-cta">
                        <a href="katalog_buku.php" class="lp-btn lp-btn-hero">
                            <i class="fas fa-book-open lp-btn-ic" aria-hidden="true"></i>Jelajahi Katalog
                        </a>
                        <?php if (!empty($hero['btn_text']) && !empty($hero['btn_url'])): ?>
                        <a href="<?= e($hero['btn_url']) ?>" class="lp-btn lp-btn-ghost-hero">
                            <i class="fas fa-arrow-right lp-btn-ic" aria-hidden="true"></i><?= e($hero['btn_text']) ?>
                        </a>
                        <?php elseif (!is_logged_in()): ?>
                        <a href="login.php" class="lp-btn lp-btn-ghost-hero">
                            <i class="fas fa-right-to-bracket lp-btn-ic" aria-hidden="true"></i>Masuk Anggota
                        </a>
                        <?php else: ?>
                        <a href="akun.php" class="lp-btn lp-btn-ghost-hero">
                            <i class="fas fa-user lp-btn-ic" aria-hidden="true"></i>Halaman Akun
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="lp-hero-trust">
                        <div class="lp-hero-trust-item">
                            <span class="lp-ht-icon" aria-hidden="true"><i class="fas fa-book-open"></i></span>
                            <div class="lp-ht-text">
                                <strong>Ribuan Koleksi</strong>
                                <span>Fisik &amp; digital</span>
                            </div>
                        </div>
                        <div class="lp-hero-trust-item">
                            <span class="lp-ht-icon" aria-hidden="true"><i class="fas fa-id-card"></i></span>
                            <div class="lp-ht-text">
                                <strong>Peminjaman Mudah</strong>
                                <span>Hingga <?= MAX_BORROWED_BOOKS ?> buku</span>
                            </div>
                        </div>
                        <div class="lp-hero-trust-item">
                            <span class="lp-ht-icon" aria-hidden="true"><i class="fas fa-headset"></i></span>
                            <div class="lp-ht-text">
                                <strong>Bantuan 24/7</strong>
                                <span>Petugas siaga</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual -->
                <div class="lp-hero-visual">
                    <div class="lp-hero-glass">
                        <img src="assets/img/banner/banner.svg" alt="Ilustrasi Perpustakaan Daerah" loading="eager">
                    </div>
                    <div class="lp-chip lp-chip-1">
                        <span class="lp-chip-ic" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                        <span><?= number_format($stat_total_buku, 0, ',', '.') ?>+ Koleksi</span>
                    </div>
                    <div class="lp-chip lp-chip-2">
                        <span class="lp-chip-ic" aria-hidden="true"><i class="fas fa-users"></i></span>
                        <span><?= number_format($stat_total_anggota, 0, ',', '.') ?> Anggota</span>
                    </div>
                    <div class="lp-chip lp-chip-3">
                        <span class="lp-chip-ic" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        <span>Buka Setiap Hari</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         STATISTIK
         ============================================================ -->
    <div class="container">
        <div class="lp-stats-wrap">
            <div class="lp-stats">
                <div class="lp-stat" data-reveal>
                    <span class="lp-stat-ic lp-stat-ic-blue" aria-hidden="true"><i class="fas fa-book"></i></span>
                    <div>
                        <div class="lp-stat-val" data-counter="<?= (int) $stat_total_buku ?>">0</div>
                        <div class="lp-stat-label">Total Koleksi Buku</div>
                    </div>
                </div>
                <div class="lp-stat" data-reveal data-reveal-delay="80">
                    <span class="lp-stat-ic lp-stat-ic-emerald" aria-hidden="true"><i class="fas fa-users"></i></span>
                    <div>
                        <div class="lp-stat-val" data-counter="<?= (int) $stat_total_anggota ?>">0</div>
                        <div class="lp-stat-label">Anggota Terdaftar</div>
                    </div>
                </div>
                <div class="lp-stat" data-reveal data-reveal-delay="160">
                    <span class="lp-stat-ic lp-stat-ic-gold" aria-hidden="true"><i class="fas fa-bookmark"></i></span>
                    <div>
                        <div class="lp-stat-val" data-counter="<?= (int) $stat_total_dipinjam ?>">0</div>
                        <div class="lp-stat-label">Buku Sedang Dipinjam</div>
                    </div>
                </div>
                <div class="lp-stat" data-reveal data-reveal-delay="240">
                    <span class="lp-stat-ic lp-stat-ic-emerald" aria-hidden="true"><i class="fas fa-circle-check"></i></span>
                    <div>
                        <div class="lp-stat-val" data-counter="<?= (int) $stat_total_tersedia ?>">0</div>
                        <div class="lp-stat-label">Buku Siap Dipinjam</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         LAYANAN
         ============================================================ -->
    <section class="section-pad lp-band" id="layanan">
        <div class="container">
            <div class="lp-section-head is-center mb-5" data-reveal>
                <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Layanan Kami</span>
                <h2>Pengalaman Membaca <span class="lp-gradient-text">Modern &amp; Nyaman</span></h2>
                <p>Seluruh layanan dirancang agar setiap anggota mendapatkan pengalaman berliterasi yang mudah, cepat, dan menyenangkan.</p>
            </div>

            <div class="lp-feature-grid">
                <?php $i = 0; foreach ($layanan as $feat): ?>
                <div class="lp-feature" data-reveal data-reveal-delay="<?= ($i % 3) * 90 ?>">
                    <span class="lp-feature-ic <?= $feat['color'] ?>" aria-hidden="true"><i class="fas <?= $feat['ic'] ?>"></i></span>
                    <h3><?= e($feat['title']) ?></h3>
                    <p><?= e($feat['desc']) ?></p>
                    <a class="lp-feature-link" href="katalog_buku.php">Selengkapnya <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         VIDEO PENGENALAN PERPUSTAKAAN
         ============================================================ -->
    <?php include __DIR__ . '/partials/library_video.php'; ?>

    <!-- ============================================================
         KOLEKSI POPULER (carousel)
         ============================================================ -->
    <section class="section-pad" id="koleksi">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-2" data-reveal>
                <div class="lp-section-head mb-0">
                    <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Paling Banyak Dibaca</span>
                    <h2>Koleksi <span class="lp-gradient-text">Populer</span></h2>
                    <p>Pilihan favorit para anggota minggu ini.</p>
                </div>
                <a href="katalog_buku.php?filter=populer" class="lp-link-more">
                    Lihat Semua <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <?php if (!empty($featured)): ?>
            <div class="lp-carousel-shell">
                <div class="lp-carousel">
                    <?php foreach ($featured as $buku): ?>
                        <?php include __DIR__ . '/partials/landing_book_card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <div class="lp-carousel-nav">
                    <button type="button" class="lp-carousel-btn" data-carousel="prev" aria-label="Geser kiri"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="lp-carousel-btn" data-carousel="next" aria-label="Geser kanan"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="lp-carousel-progress"><span></span></div>
            </div>
            <?php else: ?>
            <div class="lp-band-card py-5 text-center" data-reveal>
                <p class="text-muted mb-0">Belum ada koleksi populer. Segera hadir!</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================================
         KOLEKSI TERBARU
         ============================================================ -->
    <section class="section-pad lp-band-tint">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4" data-reveal>
                <div class="lp-section-head mb-0">
                    <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Baru Di Rak</span>
                    <h2>Koleksi <span class="lp-gradient-text">Terbaru</span></h2>
                    <p>Judul-judul teranyar yang baru masuk katalog.</p>
                </div>
                <a href="katalog_buku.php?filter=baru" class="lp-link-more">
                    Lihat Semua <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="row g-4">
                <?php if (!empty($latest)): ?>
                    <?php $i = 0; foreach ($latest as $buku): ?>
                        <div class="col-md-6 col-lg-3">
                            <?php
                                $tag = 'baru';
                                $reveal = true;
                                $delay = $i * 90;
                                include __DIR__ . '/partials/landing_book_card.php';
                            ?>
                        </div>
                    <?php $i++; endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="lp-band-card py-5 text-center">
                            <p class="text-muted mb-0">Belum ada koleksi terbaru. Segera hadir!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         PALING SERING DIPINJAM
         ============================================================ -->
    <section class="section-pad">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-2" data-reveal>
                <div class="lp-section-head mb-0">
                    <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Rekomendasi Kami</span>
                    <h2>Paling Sering <span class="lp-gradient-text">Dipinjam</span></h2>
                    <p>Judul dengan peminjam terbanyak berdasarkan catatan sirkulasi.</p>
                </div>
                <a href="katalog_buku.php" class="lp-link-more">
                    Semua Buku <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <?php if (!empty($most_borrowed)): ?>
            <div class="lp-carousel-shell">
                <div class="lp-carousel">
                    <?php foreach ($most_borrowed as $buku): ?>
                        <?php include __DIR__ . '/partials/landing_book_card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <div class="lp-carousel-nav">
                    <button type="button" class="lp-carousel-btn" data-carousel="prev" aria-label="Geser kiri"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="lp-carousel-btn" data-carousel="next" aria-label="Geser kanan"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="lp-carousel-progress"><span></span></div>
            </div>
            <?php else: ?>
            <div class="lp-band-card py-5 text-center" data-reveal>
                <p class="text-muted mb-0">Belum ada data peminjaman. Coba lagi nanti.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================================
         KATEGORI
         ============================================================ -->
    <section class="section-pad lp-band-tint" id="kategori">
        <div class="container">
            <div class="lp-section-head is-center mb-5" data-reveal>
                <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Jelajah</span>
                <h2>Telusuri per <span class="lp-gradient-text">Kategori</span></h2>
                <p>Pilih kategori favorit dan temukan koleksi yang paling sesuai dengan minat Anda.</p>
            </div>

            <?php if (!empty($kategori_rows)): ?>
            <div class="lp-cat-grid">
                <?php $i = 0; foreach ($kategori_rows as $kat):
                    $icon = $kategori_icons[$kat['nama_kategori']] ?? 'fa-bookmark';
                ?>
                <a class="lp-cat-card" href="katalog_buku.php?kategori=<?= (int) $kat['id_kategori'] ?>" data-reveal data-reveal-delay="<?= ($i % 4) * 70 ?>">
                    <span class="lp-cat-ic" aria-hidden="true"><i class="fas <?= $icon ?>"></i></span>
                    <span>
                        <span class="lp-cat-name d-block"><?= e($kat['nama_kategori']) ?></span>
                        <span class="lp-cat-count"><?= (int) $kat['jumlah_buku'] ?> Buku</span>
                    </span>
                </a>
                <?php $i++; endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================================
         MANFAAT MEMBACA
         ============================================================ -->
    <section class="section-pad">
        <div class="container">
            <div class="lp-benefits">
                <div class="lp-benefits-visual" data-reveal="left">
                    <div class="lp-bv-card">
                        <img src="assets/img/banner/banner.svg" alt="Suasana membaca di perpustakaan" loading="lazy">
                    </div>
                    <div class="lp-bv-float lp-bv-float-1">
                        <span class="lp-bv-ic" aria-hidden="true"><i class="fas fa-users"></i></span>
                        <div class="lp-bv-txt">
                            <strong><?= number_format($stat_total_anggota, 0, ',', '.') ?>+ Anggota</strong>
                            <span>Terdaftar aktif</span>
                        </div>
                    </div>
                    <div class="lp-bv-float lp-bv-float-2">
                        <span class="lp-bv-ic" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        <div class="lp-bv-txt">
                            <strong>24/7 Online</strong>
                            <span>Akses kapan pun</span>
                        </div>
                    </div>
                </div>

                <div data-reveal="right">
                    <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Kenapa Kami</span>
                    <h2 class="mt-3 mb-2">Membaca Lebih Dekat, <span class="lp-gradient-text">Belajar Lebih Mudah</span></h2>
                    <p class="mb-0" style="color: var(--lp-text-soft); line-height: 1.7;">
                        Kami menggabungkan koleksi berkualitas dengan teknologi untuk mendukung
                        kebiasaan membaca dan belajar masyarakat.
                    </p>
                    <ul class="lp-check-list">
                        <?php foreach ($benefits as $bf): ?>
                        <li>
                            <span class="lp-check-ic" aria-hidden="true"><i class="fas fa-check"></i></span>
                            <span><strong><?= e($bf[0]) ?>.</strong> <?= e($bf[1]) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-4 d-flex gap-2 flex-wrap">
                        <?php if (is_logged_in()): ?>
                        <a href="akun.php" class="lp-btn lp-btn-primary">
                            <i class="fas fa-user lp-btn-ic" aria-hidden="true"></i>Halaman Akun
                        </a>
                        <?php else: ?>
                        <a href="login.php" class="lp-btn lp-btn-primary">
                            <i class="fas fa-right-to-bracket lp-btn-ic" aria-hidden="true"></i>Masuk Anggota
                        </a>
                        <?php endif; ?>
                        <a href="katalog_buku.php" class="lp-btn lp-btn-outline">Lihat Katalog</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         PENGUMUMAN & AGENDA
         ============================================================ -->
    <section class="section-pad lp-band-tint">
        <div class="container">
            <div class="lp-section-head is-center mb-5" data-reveal>
                <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Informasi Terkini</span>
                <h2>Pengumuman &amp; <span class="lp-gradient-text">Agenda Kegiatan</span></h2>
                <p>Ikuti berita terbaru dan jangan lewatkan acara menarik dari perpustakaan.</p>
            </div>

            <div class="lp-news-grid">
                <div class="lp-news-col" data-reveal>
                    <div class="lp-news-col-title">
                        <span class="lp-nc-ic" aria-hidden="true"><i class="fas fa-bullhorn"></i></span>
                        Pengumuman
                    </div>
                    <?php foreach ($pengumuman as $item): ?>
                    <a href="#" class="lp-news-item">
                        <span class="lp-news-date">
                            <strong><?= e($item['day']) ?></strong>
                            <span><?= e($item['month']) ?></span>
                        </span>
                        <div class="lp-news-body">
                            <div class="lp-news-title"><?= e($item['title']) ?></div>
                            <p><?= e($item['desc']) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="lp-news-col" data-reveal data-reveal-delay="120">
                    <div class="lp-news-col-title">
                        <span class="lp-nc-ic" aria-hidden="true"><i class="fas fa-calendar-days"></i></span>
                        Agenda Kegiatan
                    </div>
                    <?php foreach ($agenda as $item): ?>
                    <a href="#" class="lp-news-item">
                        <span class="lp-news-date">
                            <strong><?= e($item['day']) ?></strong>
                            <span><?= e($item['month']) ?></span>
                        </span>
                        <div class="lp-news-body">
                            <div class="lp-news-title"><?= e($item['title']) ?></div>
                            <p><?= e($item['desc']) ?></p>
                            <span class="lp-event-chip"><i class="fas fa-circle"></i> <?= e($item['chip']) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         TESTIMONI
         ============================================================ -->
    <section class="section-pad">
        <div class="container">
            <div class="lp-section-head is-center mb-5" data-reveal>
                <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Kata Mereka</span>
                <h2>Apa Kata <span class="lp-gradient-text">Anggota Kami</span></h2>
                <p>Pengalaman nyata dari para anggota yang telah merasakan layanan kami.</p>
            </div>

            <div class="lp-testi-grid">
                <?php $i = 0; foreach ($testimoni as $t): ?>
                <div class="lp-testi" data-reveal data-reveal-delay="<?= $i * 100 ?>">
                    <span class="lp-testi-quote" aria-hidden="true"><i class="fas fa-quote-right"></i></span>
                    <div class="lp-stars" aria-label="Rating 5 dari 5">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="lp-testi-text">“<?= e($t['text']) ?>”</p>
                    <div class="lp-testi-author">
                        <span class="lp-testi-avatar" style="background: <?= e($t['color']) ?>;"><?= e($t['initial']) ?></span>
                        <span>
                            <strong><?= e($t['name']) ?></strong>
                            <span class="d-block"><?= e($t['role']) ?></span>
                        </span>
                    </div>
                </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         FAQ
         ============================================================ -->
    <section class="section-pad lp-band-tint">
        <div class="container">
            <div class="lp-section-head is-center mb-5" data-reveal>
                <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Butuh Bantuan</span>
                <h2>Pertanyaan yang <span class="lp-gradient-text">Sering Diajukan</span></h2>
                <p>Temukan jawaban cepat seputar keanggotaan dan layanan perpustakaan.</p>
            </div>

            <div class="lp-faq-list">
                <?php $i = 0; foreach ($faq as $f): ?>
                <div class="lp-faq" data-reveal>
                    <button type="button" class="lp-faq-btn" aria-expanded="false" aria-controls="faq-answer-<?= $i ?>" id="faq-question-<?= $i ?>">
                        <span class="lp-faq-ic" aria-hidden="true"><i class="fas fa-circle-question"></i></span>
                        <?= e($f['q']) ?>
                        <span class="lp-faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="lp-faq-answer" id="faq-answer-<?= $i ?>" role="region" aria-labelledby="faq-question-<?= $i ?>">
                        <div class="lp-faq-answer-inner"><?= e($f['a']) ?></div>
                    </div>
                </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         KONTAK & LOKASI
         ============================================================ -->
    <section class="section-pad" id="kontak">
        <div class="container">
            <div class="lp-section-head is-center mb-5" data-reveal>
                <span class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Hubungi Kami</span>
                <h2>Kunjungi <span class="lp-gradient-text">Perpustakaan Kami</span></h2>
                <p>Jangan ragu berkunjung atau menghubungi kami untuk informasi lebih lanjut.</p>
            </div>

            <div class="lp-contact-grid">
                <div class="lp-contact-cards" data-reveal="left">
                    <?php $i = 0; foreach ($kontak as $c): ?>
                    <div class="lp-contact-card">
                        <span class="lp-contact-ic lp-stat-ic-<?= $c['color'] ?>" aria-hidden="true"><i class="fas <?= $c['ic'] ?>"></i></span>
                        <div>
                            <strong><?= e($c['title']) ?></strong>
                            <?php if ($c['title'] === 'Email'): ?>
                                <a href="mailto:<?= e($c['value']) ?>"><?= e($c['value']) ?></a>
                            <?php elseif ($c['title'] === 'Telepon'): ?>
                                <a href="tel:+<?= preg_replace('/\D/', '', $c['value']) ?>"><?= e($c['value']) ?></a>
                            <?php else: ?>
                                <span><?= e($c['value']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $i++; endforeach; ?>
                </div>

                <div class="lp-map" data-reveal="right" aria-label="Peta lokasi perpustakaan">
                    <span class="lp-map-pattern" aria-hidden="true"></span>
                    <span class="lp-map-streets" aria-hidden="true"></span>
                    <span class="lp-map-pin">
                        <i class="fas fa-location-dot" aria-hidden="true"></i>
                        <span class="lp-map-pin-label"><?= e(APP_NAME) ?></span>
                    </span>
                    <a class="lp-map-btn" href="https://www.google.com/maps/search/?api=1&query=Perpustakaan+Daerah" target="_blank" rel="noopener">
                        <i class="fab fa-google" aria-hidden="true"></i>Buka di Google Maps
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         CTA BAND
         ============================================================ -->
    <section class="section-pad-sm">
        <div class="container">
            <div class="lp-cta" data-reveal="zoom">
                <span class="lp-cta-glow lp-cta-glow-1" aria-hidden="true"></span>
                <span class="lp-cta-glow lp-cta-glow-2" aria-hidden="true"></span>
                <h2>Siap Memulai Petualangan Literasi Anda?</h2>
                <p>Bergabunglah menjadi anggota dan nikmati seluruh koleksi serta layanan kami. Gratis, cepat, dan mudah.</p>
                <div class="lp-cta-actions">
                    <?php if (!is_logged_in()): ?>
                    <a href="login.php" class="lp-btn lp-btn-white lp-btn-lg">
                        <i class="fas fa-right-to-bracket lp-btn-ic" aria-hidden="true"></i>Masuk Anggota
                    </a>
                    <?php else: ?>
                    <a href="katalog_buku.php" class="lp-btn lp-btn-white lp-btn-lg">
                        <i class="fas fa-book-open lp-btn-ic" aria-hidden="true"></i>Jelajahi Katalog
                    </a>
                    <?php endif; ?>
                    <a href="#kontak" class="lp-btn lp-btn-line lp-btn-lg">
                        <i class="fas fa-location-dot lp-btn-ic" aria-hidden="true"></i>Kunjungi Kami
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>
