<?php
/**
 * PERPUSTAKAAN DAERAH - Riwayat Pengembalian (V5)
 * File: pengembalian.php
 * Deskripsi: Anggota mengajukan permintaan pengembalian buku.
 *            Proses pengembalian disetujui oleh petugas/admin
 *            (stok bertambah hanya setelah persetujuan).
 */

require_once __DIR__ . '/config/app.php';
require_login();

$id_anggota = (int) $_SESSION['id_anggota'];

// Proses AJUKAN PERMINTAAN PENGEMBALIAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_kembali'])) {
    if (!csrf_verify()) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect('pengembalian.php');
    }

    $id_peminjaman = (int) ($_POST['id_peminjaman'] ?? 0);
    $catatan       = trim((string) ($_POST['catatan_pengembalian'] ?? ''));

    // Ambil data peminjaman (hanya milik anggota ini, status aktif)
    $stmt = mysqli_prepare($koneksi,
        "SELECT p.*, b.judul_buku FROM tb_peminjaman p
         JOIN tb_buku b ON p.id_buku = b.id_buku
         WHERE p.id_peminjaman = ? AND p.id_anggota = ? AND p.status IN ('dipinjam', 'terlambat', 'ditolak')");
    mysqli_stmt_bind_param($stmt, 'ii', $id_peminjaman, $id_anggota);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) === 0) {
        mysqli_stmt_close($stmt);
        set_flash('error', 'Data peminjaman tidak ditemukan.');
        redirect('pengembalian.php');
    }

    $pinjam = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    // Simulasi: denda dihitung pada saat persetujuan oleh petugas.
    // Set status menunggu persetujuan.
    $stmt = mysqli_prepare($koneksi,
        "UPDATE tb_peminjaman
         SET status = 'menunggu_pengembalian', tanggal_diajukan = NOW(), catatan_pengembalian = ?
         WHERE id_peminjaman = ? AND id_anggota = ?");
    $catatan = $catatan !== '' ? $catatan : null;
    mysqli_stmt_bind_param($stmt, 'sii', $catatan, $id_peminjaman, $id_anggota);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Notifikasi: permintaan pengembalian terkirim (menunggu persetujuan admin)
    notif_event_return_request($koneksi, $id_anggota, $id_peminjaman, $pinjam['judul_buku']);

    set_flash('success', 'Permintaan pengembalian "' . $pinjam['judul_buku'] . '" berhasil diajukan. Menunggu persetujuan petugas.');
    redirect('pengembalian.php');
}

// Statistik (via app/loans.php)
$stat = loan_statistik($koneksi, $id_anggota);
$total_aktif    = $stat['total_aktif'];
$total_menunggu = $stat['total_menunggu'];
$total_selesai  = $stat['total_selesai'];
$total_denda    = $stat['total_denda_belum'];

// Buku yang sedang dipinjam (belum diajukan pengembalian)
$sedang_dipinjam = loan_sedang_dipinjam($koneksi, $id_anggota);

// Permintaan menunggu / ditolak
$menunggu = loan_menunggu_pengembalian($koneksi, $id_anggota);

// Riwayat pengembalian yang sudah disetujui
$riwayat = loan_riwayat_dikembalikan($koneksi, $id_anggota, 20);

$page_title = 'Riwayat Pengembalian';
require_once __DIR__ . '/partials/header.php';
?>

<!-- Hero -->
<section class="v5-hero-user mb-4" data-reveal>
    <div class="v5-hero-overline"><i class="fas fa-rotate-left"></i> Riwayat Pengembalian</div>
    <h1 class="v5-hero-title">Pengembalian Buku</h1>
    <p class="v5-hero-sub">Ajukan permintaan pengembalian buku yang Anda pinjam. Petugas akan memverifikasi buku dan menghitung denda (jika terlambat) sebelum persetujuan.</p>
    <div class="v5-hero-chips">
        <span class="v5-hero-chip"><i class="fas fa-book"></i><?= $total_aktif ?> buku aktif</span>
        <span class="v5-hero-chip"><i class="fas fa-clock"></i><?= $total_menunggu ?> menunggu persetujuan</span>
        <span class="v5-hero-chip"><i class="fas fa-circle-check"></i><?= $total_selesai ?> selesai</span>
        <?php if ($total_denda > 0): ?>
        <span class="v5-hero-chip"><i class="fas fa-money-bill-wave"></i>Denda: <strong><?= format_rupiah($total_denda) ?></strong></span>
        <?php endif; ?>
    </div>
</section>

<div class="mb-4" data-reveal>
    <div class="v5-notice <?= $total_aktif > 0 ? 'warn' : 'success' ?>">
        <span class="v5-notice-ic"><i class="fas fa-<?= $total_aktif > 0 ? 'circle-info' : 'circle-check' ?>"></i></span>
        <div>
            <div class="v5-notice-title"><?= $total_aktif > 0 ? 'Buku belum diajukan pengembalian' : 'Tidak ada buku aktif' ?></div>
            <div class="v5-notice-desc">
                <?php if ($total_aktif > 0): ?>
                Ajukan pengembalian melalui tombol di bawah. Stok buku akan bertambah setelah petugas menyetujui.
                <?php else: ?>
                Semua peminjaman Anda sudah diproses. Kunjungi katalog untuk meminjam buku baru.
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Buku yang sedang dipinjam (siap diajukan) -->
<div class="card mb-4" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-hand-holding"></i>Buku Sedang Dipinjam</span>
        <span class="badge-soft-primary"><?= count($sedang_dipinjam) ?> buku</span>
    </div>
    <div class="card-body">
        <?php if (empty($sedang_dipinjam)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-circle-check text-success"></i></div>
            <h5>Tidak ada buku yang sedang dipinjam</h5>
            <p>Tidak ada peminjaman aktif untuk diajukan pengembalian.</p>
            <a href="katalog_buku.php" class="btn btn-primary btn-sm">Lihat Katalog</a>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($sedang_dipinjam as $p): ?>
            <div class="col-lg-6">
                <div class="return-card h-100">
                    <div class="return-card-head">
                        <div class="d-flex align-items-center gap-3">
                            <?php $cover = buku_cover($p['foto']); ?>
                            <?php if (strpos($cover, 'data:') === 0): ?>
                            <div class="v5-timeline-cover placeholder"><i class="fas fa-book-open"></i></div>
                            <?php else: ?>
                            <img src="<?= e($cover) ?>" alt="Cover <?= e($p['judul_buku']) ?>" class="v5-timeline-cover" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <div class="return-card-title"><?= e($p['judul_buku']) ?></div>
                                <div class="return-card-meta">
                                    <span><i class="fas fa-user-pen"></i><?= e($p['pengarang']) ?></span>
                                </div>
                            </div>
                        </div>
                        <span class="loan-badge <?= $p['telat_hari'] > 0 ? 'terlambat' : 'dipinjam' ?>">
                            <i class="fas fa-circle small"></i><?= $p['telat_hari'] > 0 ? 'Terlambat ' . $p['telat_hari'] . ' hari' : 'Dipinjam' ?>
                        </span>
                    </div>
                    <div class="v5-timeline-grid">
                        <div class="v5-tl-cell"><label>Kode</label><strong><code><?= e($p['kode_peminjaman']) ?></code></strong></div>
                        <div class="v5-tl-cell"><label>Tanggal Pinjam</label><strong><?= e(tgl_id($p['tanggal_pinjam'])) ?></strong></div>
                        <div class="v5-tl-cell"><label>Jatuh Tempo</label><strong class="<?= $p['telat_hari'] > 0 ? 'text-danger' : '' ?>"><?= e(tgl_id($p['tanggal_kembali'])) ?></strong></div>
                        <div class="v5-tl-cell"><label>Estimasi Denda</label>
                            <strong class="<?= $p['denda_estimasi'] > 0 ? 'text-danger' : 'text-success' ?>"><?= $p['denda_estimasi'] > 0 ? format_rupiah($p['denda_estimasi']) : 'Rp 0' ?></strong>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary es-return-btn" data-bs-toggle="modal" data-bs-target="#modalAjukan<?= (int) $p['id_peminjaman'] ?>">
                        <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Ajukan Permintaan Pengembalian
                        <i class="fas fa-arrow-right es-return-btn-arrow" aria-hidden="true"></i>
                    </button>

                    <!-- Modal ajukan -->
                    <div class="modal fade" id="modalAjukan<?= (int) $p['id_peminjaman'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" action="" data-loader="true">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Ajukan Pengembalian</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="ajukan_kembali" value="1">
                                        <input type="hidden" name="id_peminjaman" value="<?= (int) $p['id_peminjaman'] ?>">
                                        <p class="mb-3">
                                            Anda akan mengajukan pengembalian buku
                                            <strong><?= e($p['judul_buku']) ?></strong>.
                                            Petugas akan memproses permintaan ini.
                                        </p>
                                        <?php if ($p['denda_estimasi'] > 0): ?>
                                        <div class="alert alert-warning py-2 small">
                                            <i class="fas fa-triangle-exclamation me-1"></i>
                                            Estimasi denda keterlambatan: <strong><?= format_rupiah($p['denda_estimasi']) ?></strong> (<?= $p['telat_hari'] ?> hari × <?= format_rupiah(DENDA_PER_HARI) ?>)
                                        </div>
                                        <?php endif; ?>
                                        <label for="catatan<?= (int) $p['id_peminjaman'] ?>" class="form-label">Catatan (opsional)</label>
                                        <textarea class="form-control" id="catatan<?= (int) $p['id_peminjaman'] ?>" name="catatan_pengembalian" rows="2" placeholder="Contoh: kondisi buku baik"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="ajukan_kembali" class="btn btn-primary es-return-btn es-return-btn-sm">
                                            <i class="fas fa-paper-plane me-1" aria-hidden="true"></i>Ajukan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Permintaan menunggu / ditolak -->
<?php if (!empty($menunggu)): ?>
<div class="card mb-4" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-clock"></i>Status Permintaan</span>
        <span class="badge-soft-warning"><?= count($menunggu) ?> proses</span>
    </div>
    <div class="card-body">
        <div class="v5-timeline">
            <?php foreach ($menunggu as $p): ?>
            <div class="v5-timeline-item">
                <div class="v5-timeline-dot <?= $p['status'] === 'ditolak' ? 'warning' : 'pending' ?>">
                    <?= $p['status'] === 'ditolak' ? '<i class="fas fa-rotate-left"></i>' : '<i class="fas fa-clock"></i>' ?>
                </div>
                <div class="v5-timeline-card">
                    <div class="v5-timeline-top">
                        <div class="d-flex gap-3">
                            <?php $cover = buku_cover($p['foto']); ?>
                            <?php if (strpos($cover, 'data:') === 0): ?>
                            <div class="v5-timeline-cover placeholder"><i class="fas fa-book-open"></i></div>
                            <?php else: ?>
                            <img src="<?= e($cover) ?>" alt="Cover <?= e($p['judul_buku']) ?>" class="v5-timeline-cover" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <div class="v5-timeline-title"><?= e($p['judul_buku']) ?></div>
                                <div class="v5-timeline-sub"><?= e($p['kode_peminjaman']) ?></div>
                                <div class="mt-2">
                                    <?php if ($p['status'] === 'menunggu_pengembalian'): ?>
                                    <span class="loan-badge menunggu"><i class="fas fa-spinner"></i>Menunggu persetujuan petugas</span>
                                    <?php else: ?>
                                    <span class="loan-badge ditolak"><i class="fas fa-rotate-left"></i>Permintaan ditolak</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($p['catatan_pengembalian']): ?>
                                <div class="v5-timeline-sub mt-1"><i class="fas fa-comment me-1"></i><?= e($p['catatan_pengembalian']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="v5-timeline-grid">
                        <div class="v5-tl-cell"><label>Diajukan</label><strong><?= $p['tanggal_diajukan'] ? e(tgl_id(substr($p['tanggal_diajukan'], 0, 10))) : '-' ?></strong></div>
                        <div class="v5-tl-cell"><label>Jatuh Tempo</label><strong><?= e(tgl_id($p['tanggal_kembali'])) ?></strong></div>
                        <div class="v5-tl-cell"><label>Keterangan Ditolak</label><strong class="text-muted"><?= e($p['keterangan'] ?: '-') ?></strong></div>
                    </div>
                    <?php if ($p['status'] === 'ditolak'): ?>
                    <form method="POST" action="" class="mt-3" data-loader="true">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ajukan_kembali" value="1">
                        <input type="hidden" name="id_peminjaman" value="<?= (int) $p['id_peminjaman'] ?>">
                        <button type="submit" name="ajukan_kembali" class="btn btn-outline-primary es-return-btn es-return-btn-outline">
                            <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Ajukan Lagi
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Riwayat Pengembalian (disetujui) -->
<div class="card" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-clock-rotate-left"></i>Riwayat Pengembalian</span>
        <span class="badge-soft-success"><?= count($riwayat) ?> transaksi</span>
    </div>
    <div class="card-body">
        <?php if (empty($riwayat)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-clock-rotate-left"></i></div>
            <h5>Belum ada riwayat pengembalian</h5>
            <p>Buku yang sudah disetujui pengembaliannya akan muncul di sini.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Dikembalikan</th>
                        <th>Denda</th>
                        <th>Status Denda</th>
                        <th>Kondisi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat as $p): ?>
                    <tr>
                        <td><span class="text-truncate d-inline-block" style="max-width:240px;"><?= e($p['judul_buku']) ?></span></td>
                        <td><?= e(tgl_id($p['tanggal_pinjam'])) ?></td>
                        <td><?= e(tgl_id($p['tanggal_kembali'])) ?></td>
                        <td><?= e(tgl_id($p['tanggal_dikembalikan'])) ?></td>
                        <td class="<?= $p['denda'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $p['denda'] > 0 ? format_rupiah($p['denda']) : '-' ?></td>
                        <td>
                            <?php if ($p['denda'] > 0): ?>
                            <span class="fine-badge <?= $p['status_denda'] === 'lunas' ? 'paid' : 'unpaid' ?>">
                                <i class="fas fa-<?= $p['status_denda'] === 'lunas' ? 'check' : 'exclamation' ?>"></i><?= status_denda_label($p['status_denda']) ?>
                            </span>
                            <?php else: ?>
                            <span class="fine-badge zero">Tanpa denda</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-soft-<?= $p['kondisi_saat_kembali'] === 'rusak_berat' ? 'danger' : ($p['kondisi_saat_kembali'] === 'rusak_ringan' ? 'warning' : 'success') ?>"><?= e(format_kondisi($p['kondisi_saat_kembali'] ?? 'baik')) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
