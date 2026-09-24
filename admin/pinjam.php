<?php
/**
 * PERPUSTAKAAN DAERAH - Form Pinjam Buku
 * File: pinjam.php
 * Deskripsi: Halaman konfirmasi peminjaman buku oleh anggota
 */

require_once __DIR__ . '/config/app.php';

// Harus login sebagai anggota
require_login();

if (!is_anggota()) {
    set_flash('error', 'Admin tidak dapat meminjam buku.');
    redirect('index.php');
}

$id_buku = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_buku <= 0) {
    set_flash('error', 'Buku tidak ditemukan.');
    redirect('katalog_buku.php');
}

// Ambil data buku (via app/catalog.php)
$buku = buku_detail($koneksi, $id_buku);

if ($buku === null) {
    set_flash('error', 'Buku tidak ditemukan.');
    redirect('katalog_buku.php');
}

// Cek stok
if ((int) $buku['stok_buku'] <= 0) {
    set_flash('error', 'Stok buku habis.');
    redirect('detail_buku.php?id=' . $id_buku);
}

// Cek apakah anggota sudah meminjam buku yang sama dan belum dikembalikan
$id_anggota = (int) $_SESSION['id_anggota'];
if (loan_sudah_pinjam($koneksi, $id_anggota, $id_buku)) {
    set_flash('error', 'Anda sudah meminjam buku ini dan belum mengembalikannya.');
    redirect('detail_buku.php?id=' . $id_buku);
}

// Cek batas maksimal peminjaman
$dipinjam = hitung_buku_dipinjam($koneksi, $id_anggota);
if ($dipinjam >= MAX_BORROWED_BOOKS) {
    set_flash('error', 'Anda telah mencapai batas maksimal peminjaman (' . MAX_BORROWED_BOOKS . ' buku).');
    redirect('akun.php');
}

// Hitung tanggal
$tanggal_pinjam   = date('Y-m-d');
$tanggal_kembali  = date('Y-m-d', strtotime('+' . LOAN_PERIOD_DAYS . ' days'));

$page_title = 'Pinjam Buku';
$cover_url = buku_cover($buku['foto']);

require_once __DIR__ . '/partials/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="katalog_buku.php" class="text-decoration-none">Katalog</a></li>
                        <li class="breadcrumb-item"><a href="detail_buku.php?id=<?= $id_buku ?>" class="text-decoration-none">Detail</a></li>
                        <li class="breadcrumb-item active">Pinjam Buku</li>
                    </ol>
                </nav>

                <div class="card border-0 shadow-sm" data-reveal="zoom">
                    <div class="card-header">
                        <i class="fas fa-hand-holding me-2"></i>Konfirmasi Peminjaman Buku
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Cover -->
                            <div class="col-md-3" data-reveal="left">
                                <img src="<?= e($cover_url) ?>" alt="<?= e($buku['judul_buku']) ?>" class="img-fluid rounded shadow-sm w-100">
                            </div>

                            <!-- Info Buku -->
                            <div class="col-md-9" data-reveal="right">
                                <span class="badge bg-primary bg-opacity-10 text-primary mb-2"><?= e($buku['nama_kategori']) ?></span>
                                <h4 class="fw-bold mb-2"><?= e($buku['judul_buku']) ?></h4>
                                <p class="text-muted mb-1"><i class="fas fa-user-pen me-1"></i><?= e($buku['pengarang']) ?></p>
                                <p class="text-muted mb-1"><i class="fas fa-building me-1"></i><?= e($buku['nama_penerbit']) ?> (<?= e($buku['tahun_terbit']) ?>)</p>
                                <p class="text-muted mb-3"><i class="fas fa-barcode me-1"></i>ISBN: <?= e($buku['isbn'] ?: '-') ?></p>

                                <!-- Detail Peminjaman -->
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3"><i class="fas fa-circle-info me-2"></i>Detail Peminjaman</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Tanggal Pinjam</small>
                                                <strong><?= e(format_tanggal($tanggal_pinjam)) ?></strong>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Tanggal Kembali</small>
                                                <strong class="text-danger"><?= e(format_tanggal($tanggal_kembali)) ?></strong>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Durasi Peminjaman</small>
                                                <strong><?= LOAN_PERIOD_DAYS ?> hari</strong>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Denda keterlambatan</small>
                                                <strong><?= format_rupiah(DENDA_PER_HARI) ?>/hari</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Perhatian:</strong>
                                    Kembalikan buku tepat waktu untuk menghindari denda keterlambatan.
                                    Buku yang rusak atau hilang akan dikenakan sanksi sesuai ketentuan.
                                </div>

                                <form method="POST" action="proses_pinjam.php" data-loader="true">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id_buku" value="<?= (int) $id_buku ?>">
                                    <input type="hidden" name="tanggal_pinjam" value="<?= e($tanggal_pinjam) ?>">
                                    <input type="hidden" name="tanggal_kembali" value="<?= e($tanggal_kembali) ?>">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Catatan (opsional)</label>
                                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Catatan tambahan untuk pustakawan..."></textarea>
                                    </div>

                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="submit" class="btn btn-primary btn-lg px-4">
                                            <i class="fas fa-check me-2"></i>Konfirmasi Pinjam
                                        </button>
                                        <a href="detail_buku.php?id=<?= $id_buku ?>" class="btn btn-outline-secondary btn-lg px-4">
                                            <i class="fas fa-times me-2"></i>Batal
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
