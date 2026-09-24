<?php
/**
 * PERPUSTAKAAN DAERAH - Pusat Bantuan (Help User)
 * File: help.php
 * Deskripsi: Panduan penggunaan layanan perpustakaan untuk anggota —
 *            cara login, registrasi, mencari, meminjam, mengajukan
 *            pengembalian, riwayat, notifikasi, denda, dan FAQ.
 */

require_once __DIR__ . '/config/app.php';

$page_title = 'Pusat Bantuan';

// FAQ dinamis dari CMS (tb_faq), fallback statis bila kosong
$faq_list = cms_faq($koneksi);
$faq_fallback = [
    ['pertanyaan' => 'Berapa lama masa peminjaman buku?', 'jawaban' => 'Masa peminjaman buku adalah ' . LOAN_PERIOD_DAYS . ' hari. Jika melewati batas waktu, akan dikenakan denda ' . format_rupiah(DENDA_PER_HARI) . ' per hari keterlambatan.'],
    ['pertanyaan' => 'Berapa maksimal buku yang bisa dipinjam?', 'jawaban' => 'Setiap anggota dapat meminjam maksimal ' . MAX_BORROWED_BOOKS . ' buku dalam satu waktu.'],
    ['pertanyaan' => 'Bagaimana cara menjadi anggota?', 'jawaban' => 'Daftar secara online melalui halaman pendaftaran di menu Daftar Anggota, atau langsung datang ke meja layanan perpustakaan untuk dibantu petugas.'],
    ['pertanyaan' => 'Bagaimana proses pengembalian buku?', 'jawaban' => 'Ajukan permintaan pengembalian melalui menu Riwayat Pengembalian, kemudian petugas akan memprosesnya.'],
];
if (empty($faq_list)) {
    $faq_list = $faq_fallback;
}

require_once __DIR__ . '/partials/header.php';
?>

<!-- Hero -->
<section class="v5-hero-user mb-4" data-reveal>
    <div class="v5-hero-overline"><i class="fas fa-circle-question"></i> Pusat Bantuan</div>
    <h1 class="v5-hero-title">Bagaimana kami bisa membantu? 👋</h1>
    <p class="v5-hero-sub">Panduan lengkap untuk menggunakan layanan perpustakaan daerah — mulai dari pendaftaran, peminjaman, hingga pengembalian buku.</p>
    <div class="v5-hero-chips">
        <span class="v5-hero-chip"><i class="fas fa-clock"></i><?= LOAN_PERIOD_DAYS ?> hari masa pinjam</span>
        <span class="v5-hero-chip"><i class="fas fa-book"></i>Maks. <?= MAX_BORROWED_BOOKS ?> buku</span>
        <span class="v5-hero-chip"><i class="fas fa-money-bill-wave"></i>Denda <?= format_rupiah(DENDA_PER_HARI) ?>/hari</span>
    </div>
</section>

<div class="container">

<!-- Quick links / nav bantuan -->
<div class="row g-3 mb-4" data-stagger>
    <div class="col-md-3 col-6"><a class="help-quick" href="#pendaftaran"><span class="help-quick-ic"><i class="fas fa-user-plus"></i></span><span>Registrasi</span></a></div>
    <div class="col-md-3 col-6"><a class="help-quick" href="#pencarian"><span class="help-quick-ic"><i class="fas fa-magnifying-glass"></i></span><span>Mencari Buku</span></a></div>
    <div class="col-md-3 col-6"><a class="help-quick" href="#peminjaman"><span class="help-quick-ic"><i class="fas fa-hand-holding"></i></span><span>Meminjam</span></a></div>
    <div class="col-md-3 col-6"><a class="help-quick" href="#pengembalian"><span class="help-quick-ic"><i class="fas fa-rotate-left"></i></span><span>Pengembalian</span></a></div>
    <div class="col-md-3 col-6"><a class="help-quick" href="#notifikasi"><span class="help-quick-ic"><i class="fas fa-bell"></i></span><span>Notifikasi</span></a></div>
    <div class="col-md-3 col-6"><a class="help-quick" href="#riwayat"><span class="help-quick-ic"><i class="fas fa-clock-rotate-left"></i></span><span>Riwayat</span></a></div>
    <div class="col-md-3 col-6"><a class="help-quick" href="#denda"><span class="help-quick-ic"><i class="fas fa-money-bill-wave"></i></span><span>Denda</span></a></div>
    <div class="col-md-3 col-6"><a class="help-quick" href="#faq"><span class="help-quick-ic"><i class="fas fa-circle-question"></i></span><span>FAQ</span></a></div>
</div>

<!-- ==================== PENDAFTARAN & LOGIN ==================== -->
<div class="card help-card mb-4" id="pendaftaran" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-user-plus"></i>Cara Registrasi</span>
    </div>
    <div class="card-body">
        <ol class="help-steps">
            <li>Buka halaman <a href="register.php">Daftar Anggota</a> dari tombol <em>Daftar</em> di pojok kanan atas.</li>
            <li>Isi formulir pendaftaran (nama lengkap, email, password, dll.) dengan data yang benar.</li>
            <li>Klik tombol <strong>Daftar</strong>. Sistem akan membuatkan nomor anggota otomatis.</li>
            <li>Setelah berhasil, silakan <a href="login.php">masuk</a> menggunakan email dan password yang didaftarkan.</li>
        </ol>
        <div class="alert alert-info py-2 small mb-0">
            <i class="fas fa-circle-info me-1"></i> Email Anda menjadi identitas login. Pastikan email aktif untuk menerima informasi layanan.
        </div>
    </div>
</div>

<div class="card help-card mb-4" id="login" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-right-to-bracket"></i>Cara Login</span>
    </div>
    <div class="card-body">
        <ol class="help-steps">
            <li>Klik tombol <strong>Masuk</strong> di pojok kanan atas halaman.</li>
            <li>Masukkan <strong>email</strong> dan <strong>password</strong> anggota Anda.</li>
            <li>Centang <em>Ingat saya selama 30 hari</em> agar tetap login di perangkat ini (opsional).</li>
            <li>Klik <strong>Masuk ke Akun</strong>. Anda akan diarahkan ke dashboard anggota.</li>
        </ol>
        <div class="alert alert-warning py-2 small mb-0">
            <i class="fas fa-triangle-exclamation me-1"></i> Setelah 5 percobaan gagal, akun terkunci selama 15 menit demi keamanan. Hubungi petugas jika lupa password.
        </div>
    </div>
</div>

<!-- ==================== PENCARIAN ==================== -->
<div class="card help-card mb-4" id="pencarian" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-magnifying-glass"></i>Cara Mencari Buku</span>
    </div>
    <div class="card-body">
        <ol class="help-steps">
            <li>Gunakan menu <a href="cari.php">Pencarian</a> di navigasi atas, atau kolom <em>Cari buku…</em>.</li>
            <li>Ketik <strong>judul, pengarang, ISBN, atau sinopsis</strong> buku.</li>
            <li>Tekan <strong>Enter</strong> atau klik ikon pencarian.</li>
            <li>Gunakan <a href="katalog_buku.php">Katalog Buku</a> untuk menjelajah seluruh koleksi dengan filter kategori, penerbit, status populer/baru, dan urutan terbaru.</li>
        </ol>
        <p class="small text-muted mb-0"><i class="fas fa-keyboard me-1"></i> Pintasan <strong>Ctrl + K</strong> langsung memindahkan kursor ke pencarian.</p>
    </div>
</div>

<!-- ==================== PEMINJAMAN ==================== -->
<div class="card help-card mb-4" id="peminjaman" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-hand-holding"></i>Cara Meminjam Buku</span>
    </div>
    <div class="card-body">
        <ol class="help-steps">
            <li>Login sebagai anggota, lalu buka halaman <a href="detail_buku.php?id=1">detail buku</a> yang diinginkan.</li>
            <li>Pastikan stok tersedia dan klik <strong>Pinjam Buku Ini</strong>.</li>
            <li>Periksa ringkasan peminjaman (tanggal pinjam, jatuh tempo, durasi <?= LOAN_PERIOD_DAYS ?> hari).</li>
            <li>Klik <strong>Konfirmasi Pinjam</strong>. Kode peminjaman otomatis dibuat.</li>
            <li>Buku tercatat pada dashboard dan Anda menerima notifikasi peminjaman.</li>
        </ol>
        <div class="alert alert-warning py-2 mb-2">
            <i class="fas fa-triangle-exclamation me-1"></i> <strong>Batas maksimal <?= MAX_BORROWED_BOOKS ?> buku</strong> secara bersamaan. Selesaikan peminjaman yang aktif terlebih dahulu sebelum meminjam buku baru.
        </div>
        <div class="alert alert-info py-2 small mb-0">
            <i class="fas fa-circle-info me-1"></i> Buku yang statusnya dipinjam/terlambat/menunggu pengembalian tidak dapat dipinjam ganda oleh anggota yang sama.
        </div>
    </div>
</div>

<!-- ==================== PENGEMBALIAN ==================== -->
<div class="card help-card mb-4" id="pengembalian" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-rotate-left"></i>Cara Mengajukan Pengembalian</span>
    </div>
    <div class="card-body">
        <ol class="help-steps">
            <li>Buka menu <a href="pengembalian.php">Pengembalian</a> di navigasi.</li>
            <li>Pada buku yang sedang dipinjam, klik <strong>Ajukan Permintaan Pengembalian</strong>.</li>
            <li>Isi catatan kondisi buku (opsional), lalu klik <strong>Ajukan</strong>.</li>
            <li>Status berubah menjadi <em>Menunggu Persetujuan</em> — petugas memverifikasi buku.</li>
            <li>Setelah disetujui, Anda menerima <strong>notifikasi</strong> dan status menjadi <em>Dikembalikan</em>; stok buku bertambah kembali.</li>
        </ol>
        <div class="alert alert-info py-2 small mb-0">
            <i class="fas fa-circle-info me-1"></i> Stok buku baru bertambah setelah petugas menyetujui. Denda (jika ada) dihitung oleh petugas saat persetujuan.
        </div>
    </div>
</div>

<!-- ==================== RIWAYAT ==================== -->
<div class="card help-card mb-4" id="riwayat" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-clock-rotate-left"></i>Cara Melihat Riwayat</span>
    </div>
    <div class="card-body">
        <p>Menu <a href="riwayat_peminjaman.php">Riwayat</a> menampilkan seluruh transaksi Anda:</p>
        <ul class="help-list">
            <li><strong>Peminjaman Aktif</strong> — buku yang sedang dipinjam, terlambat, atau menunggu persetujuan.</li>
            <li><strong>Riwayat Pengembalian</strong> — buku yang sudah disetujui pengembaliannya beserta kondisi dan denda.</li>
            <li><strong>Semua Transaksi</strong> — tabel lengkap kode, tanggal, dan status dengan paginasi.</li>
        </ul>
        <p class="small text-muted mb-0">Dashboard (<a href="akun.php">Profil &amp; Dashboard</a>) juga menampilkan riwayat terbaru dan rekomendasi buku.</p>
    </div>
</div>

<!-- ==================== NOTIFIKASI ==================== -->
<div class="card help-card mb-4" id="notifikasi" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-bell"></i>Cara Melihat Notifikasi</span>
    </div>
    <div class="card-body">
        <p>Klik ikon <strong>lonceng</strong> di kanan atas (hanya muncul saat login) untuk membuka panel notifikasi.</p>
        <ul class="help-list">
            <li>Angka merah pada lonceng menunjukkan jumlah <strong>notifikasi belum dibaca</strong>.</li>
            <li>Klik satu notifikasi untuk menandai sebagai <strong>dibaca</strong> dan membuka tautannya.</li>
            <li>Klik <em>Tandai dibaca</em> untuk menandai semua sekaligus.</li>
            <li>Notifikasi diterima untuk: peminjaman berhasil, pengajuan pengembalian, pengembalian disetujui/ditolak, pengingat jatuh tempo, keterlambatan, dan informasi denda.</li>
        </ul>
        <p class="small text-muted mb-0"><i class="fas fa-bell-slash me-1"></i> Panel memuat otomatis setiap 30 detik — Anda tidak perlu memuat ulang halaman.</p>
    </div>
</div>

<!-- ==================== DENDA ==================== -->
<div class="card help-card mb-4" id="denda" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-money-bill-wave"></i>Informasi Denda</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="help-fact">
                    <span class="help-fact-ic danger"><i class="fas fa-money-bill-wave"></i></span>
                    <strong><?= format_rupiah(DENDA_PER_HARI) ?></strong>
                    <small>Denda per hari keterlambatan</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="help-fact">
                    <span class="help-fact-ic warn"><i class="fas fa-clock"></i></span>
                    <strong><?= LOAN_PERIOD_DAYS ?> hari</strong>
                    <small>Masa peminjaman standar</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="help-fact">
                    <span class="help-fact-ic success"><i class="fas fa-check-circle"></i></span>
                    <strong>Maks. <?= MAX_BORROWED_BOOKS ?> buku</strong>
                    <small>Batas peminjaman aktif</small>
                </div>
            </div>
        </div>
        <div class="alert alert-warning py-2 small mb-0 mt-3">
            <i class="fas fa-circle-info me-1"></i> Denda dihitung otomatis oleh petugas saat pengembalian disetujui. Status denda dapat dilihat di dashboard dan riwayat. Untuk pelunasan, hubungi petugas layanan perpustakaan.
        </div>
    </div>
</div>

<!-- ==================== FAQ ==================== -->
<div class="card help-card mb-4" id="faq" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-circle-question"></i>Pertanyaan yang Sering Diajukan (FAQ)</span>
    </div>
    <div class="card-body">
        <div class="accordion" id="faqAccordion">
            <?php $idx = 0; foreach ($faq_list as $faq): $idx++; ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqH<?= $idx ?>">
                    <button class="accordion-button <?= $idx > 1 ? 'collapsed' : '' ?>" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faqC<?= $idx ?>" aria-expanded="<?= $idx === 1 ? 'true' : 'false' ?>" aria-controls="faqC<?= $idx ?>">
                        <?= e($faq['pertanyaan']) ?>
                    </button>
                </h2>
                <div id="faqC<?= $idx ?>" class="accordion-collapse collapse <?= $idx === 1 ? 'show' : '' ?>" aria-labelledby="faqH<?= $idx ?>" data-bs-parent="#faqAccordion">
                    <div class="accordion-body"><?= nl2br(e($faq['jawaban'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Kontak -->
<div class="card help-card mb-4" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-headset"></i>Masih Butuh Bantuan?</span>
    </div>
    <div class="card-body text-center">
        <p class="text-muted mb-3">Hubungi petugas perpustakaan atau kunjungi kami di lokasi.</p>
        <?php $kontak = cms_kontak($koneksi); ?>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <span class="badge-soft-primary fs-6 px-3 py-2"><i class="fas fa-location-dot me-2"></i><?= e($kontak['alamat']) ?></span>
            <span class="badge-soft-success fs-6 px-3 py-2"><i class="fas fa-phone me-2"></i><?= e($kontak['telepon']) ?></span>
            <span class="badge-soft-info fs-6 px-3 py-2"><i class="fas fa-envelope me-2"></i><?= e($kontak['email']) ?></span>
            <span class="badge-soft-warning fs-6 px-3 py-2"><i class="fas fa-clock me-2"></i><?= e($kontak['jam_buka']) ?></span>
        </div>
    </div>
</div>

</div><!-- /.container -->

<?php require_once __DIR__ . '/partials/footer.php'; ?>
