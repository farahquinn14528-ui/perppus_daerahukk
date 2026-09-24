<?php
/**
 * PERPUSTAKAAN DAERAH - Riwayat Peminjaman (V5)
 * File: riwayat_peminjaman.php
 * Deskripsi: Riwayat peminjaman anggota dengan premium timeline cards
 *            + responsive table. Menggantikan menu "Cetak Struk".
 */

$page_title = 'Riwayat Peminjaman';
require_once __DIR__ . '/config/app.php';
require_login();

$id_anggota = (int) $_SESSION['id_anggota'];

// Statistik (via app/loans.php)
$stat = loan_statistik($koneksi, $id_anggota);
$total_pinjam      = $stat['total_pinjam'];
$sedang_dipinjam   = $stat['sedang_dipinjam'];
$total_denda       = $stat['total_denda_semua'];
$menunggu_kembali  = $stat['menunggu_kembali'];

// Riwayat peminjaman (paginate 15, via app/loans.php)
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$riwayat_pg = loan_riwayat($koneksi, $id_anggota, $halaman, 15);
$riwayat    = $riwayat_pg['rows'];
$total_data = $riwayat_pg['total'];
$total_hal  = $riwayat_pg['total_halaman'];
$halaman    = $riwayat_pg['halaman'];

// Split aktif vs selesai untuk timeline
$aktif = array_values(array_filter($riwayat, function ($r) { return is_loan_active($r['status']) || is_loan_waiting_return($r['status']) || $r['status'] === LOAN_STATUS_REJECTED; }));
$selesai = array_values(array_filter($riwayat, function ($r) { return $r['status'] === LOAN_STATUS_RETURNED; }));

require_once __DIR__ . '/partials/header.php';
?>

<!-- Hero -->
<section class="v5-hero-user mb-4" data-reveal>
    <div class="v5-hero-overline"><i class="fas fa-book-open"></i> Riwayat Peminjaman</div>
    <h1 class="v5-hero-title">Halo, <?= e(explode(' ', trim($_SESSION['nama_lengkap'] ?? 'Anggota'))[0]) ?> 👋</h1>
    <p class="v5-hero-sub">Pantau semua peminjaman Anda — buku yang sedang dipinjam, riwayat pengembalian, dan total denda — dalam satu tempat.</p>
    <div class="v5-hero-chips">
        <span class="v5-hero-chip"><i class="fas fa-calendar-day"></i><?= e(format_tanggal(date('Y-m-d'))) ?></span>
        <span class="v5-hero-chip"><i class="fas fa-user"></i>Nomor Anggota: <strong><?= e($_SESSION['nomor_anggota'] ?? '-') ?></strong></span>
        <?php if ($menunggu_kembali > 0): ?>
        <span class="v5-hero-chip"><i class="fas fa-clock"></i><?= $menunggu_kembali ?> permintaan pengembalian menunggu</span>
        <?php endif; ?>
    </div>
</section>

<!-- Stat mini cards -->
<div class="v5-stat-row mb-4" data-stagger>
    <div class="v5-mini-stat">
        <span class="v5-mini-stat-ic info"><i class="fas fa-book"></i></span>
        <div>
            <div class="v5-mini-stat-val" data-counter="<?= $total_pinjam ?>"><?= $total_pinjam ?></div>
            <div class="v5-mini-stat-label">Total Peminjaman</div>
        </div>
    </div>
    <div class="v5-mini-stat">
        <span class="v5-mini-stat-ic <?= $sedang_dipinjam >= MAX_BORROWED_BOOKS ? 'danger' : 'primary' ?>"><i class="fas fa-hand-holding"></i></span>
        <div>
            <div class="v5-mini-stat-val" data-counter="<?= $sedang_dipinjam ?>"><?= $sedang_dipinjam ?></div>
            <div class="v5-mini-stat-label">Sedang Dipinjam</div>
        </div>
    </div>
    <div class="v5-mini-stat">
        <span class="v5-mini-stat-ic <?= $total_denda > 0 ? 'danger' : 'success' ?>"><i class="fas fa-money-bill-wave"></i></span>
        <div>
            <div class="v5-mini-stat-val"><?= format_rupiah($total_denda) ?></div>
            <div class="v5-mini-stat-label">Total Denda</div>
        </div>
    </div>
    <div class="v5-mini-stat">
        <span class="v5-mini-stat-ic success"><i class="fas fa-check-circle"></i></span>
        <div>
            <div class="v5-mini-stat-val" data-counter="<?= $total_pinjam - $sedang_dipinjam ?>"><?= $total_pinjam - $sedang_dipinjam ?></div>
            <div class="v5-mini-stat-label">Selesai</div>
        </div>
    </div>
</div>

<!-- Timeline: Peminjaman Aktif -->
<div class="card mb-4" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-bolt"></i>Peminjaman Aktif</span>
        <span class="badge-soft-<?= count($aktif) > 0 ? 'primary' : 'success' ?>"><?= count($aktif) ?> buku</span>
    </div>
    <div class="card-body">
        <?php if (empty($aktif)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-circle-check text-success"></i></div>
            <h5>Tidak ada peminjaman aktif</h5>
            <p>Anda belum meminjam buku saat ini. Jelajahi katalog untuk menemukan bacaan favorit.</p>
            <a href="katalog_buku.php" class="btn btn-primary btn-sm">Jelajahi Katalog</a>
        </div>
        <?php else: ?>
        <div class="v5-timeline">
            <?php foreach ($aktif as $p): ?>
            <?php
                $mapDot = ['dipinjam' => 'info', 'terlambat' => 'danger', 'menunggu_pengembalian' => 'pending', 'ditolak' => 'warning'];
                $dot = $mapDot[$p['status']] ?? 'info';
                $telat = (int) $p['telat_hari'];
            ?>
            <div class="v5-timeline-item">
                <div class="v5-timeline-dot <?= $dot ?>">
                    <?php if ($p['status'] === 'terlambat'): ?><i class="fas fa-exclamation"></i>
                    <?php elseif ($p['status'] === 'menunggu_pengembalian'): ?><i class="fas fa-clock"></i>
                    <?php elseif ($p['status'] === 'ditolak'): ?><i class="fas fa-rotate-left"></i>
                    <?php else: ?><i class="fas fa-book"></i><?php endif; ?>
                </div>
                <div class="v5-timeline-card">
                    <div class="v5-timeline-top">
                        <div class="d-flex gap-3">
                            <?php
                                $cover = buku_cover($p['foto']);
                                if (strpos($cover, 'data:') === 0): ?>
                                <div class="v5-timeline-cover placeholder"><i class="fas fa-book-open"></i></div>
                            <?php else: ?>
                                <img src="<?= e($cover) ?>" alt="Cover <?= e($p['judul_buku']) ?>" class="v5-timeline-cover" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <div class="v5-timeline-title"><?= e($p['judul_buku']) ?></div>
                                <div class="v5-timeline-sub"><?= e($p['pengarang']) ?> · <?= e($p['kode_peminjaman']) ?></div>
                                <div class="mt-2">
                                    <span class="loan-badge <?= loan_status_class($p['status']) ?>">
                                        <i class="fas fa-circle small"></i><?= loan_status_label($p['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end d-none d-sm-block">
                            <a href="pengembalian.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-rotate-left me-1"></i>Ajukan Pengembalian
                            </a>
                        </div>
                    </div>
                    <div class="v5-timeline-grid">
                        <div class="v5-tl-cell">
                            <label>Tanggal Pinjam</label>
                            <strong><?= e(tgl_id($p['tanggal_pinjam'])) ?></strong>
                        </div>
                        <div class="v5-tl-cell">
                            <label>Jatuh Tempo</label>
                            <strong class="<?= $p['status'] === 'terlambat' ? 'text-danger' : '' ?>"><?= e(tgl_id($p['tanggal_kembali'])) ?></strong>
                        </div>
                        <div class="v5-tl-cell">
                            <label>Status Denda</label>
                            <?php if ($telat > 0): ?>
                            <strong class="text-danger"><?= format_rupiah($telat * DENDA_PER_HARI) ?> (<?= $telat ?> hari)</strong>
                            <?php else: ?>
                            <strong class="text-success">Tidak ada</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-block d-sm-none mt-3">
                        <a href="pengembalian.php" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-rotate-left me-1"></i>Ajukan Pengembalian
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Timeline: Riwayat Selesai -->
<div class="card mb-4" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-clock-rotate-left"></i>Riwayat Pengembalian</span>
        <span class="badge-soft-success"><?= count($selesai) ?> selesai</span>
    </div>
    <div class="card-body">
        <?php if (empty($selesai)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-clock-rotate-left"></i></div>
            <h5>Belum ada riwayat</h5>
            <p>Buku yang sudah dikembalikan akan muncul di sini.</p>
        </div>
        <?php else: ?>
        <div class="v5-timeline">
            <?php foreach ($selesai as $p): ?>
            <div class="v5-timeline-item">
                <div class="v5-timeline-dot success"><i class="fas fa-check"></i></div>
                <div class="v5-timeline-card">
                    <div class="v5-timeline-top">
                        <div class="d-flex gap-3">
                            <?php
                                $cover = buku_cover($p['foto']);
                                if (strpos($cover, 'data:') === 0): ?>
                                <div class="v5-timeline-cover placeholder"><i class="fas fa-book-open"></i></div>
                            <?php else: ?>
                                <img src="<?= e($cover) ?>" alt="Cover <?= e($p['judul_buku']) ?>" class="v5-timeline-cover" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <div class="v5-timeline-title"><?= e($p['judul_buku']) ?></div>
                                <div class="v5-timeline-sub"><?= e($p['kode_peminjaman']) ?></div>
                                <div class="mt-2">
                                    <span class="loan-badge dikembalikan"><i class="fas fa-circle small"></i>Dikembalikan</span>
                                    <?php if ((float) $p['denda'] > 0): ?>
                                    <span class="fine-badge <?= $p['status_denda'] === 'lunas' ? 'paid' : 'unpaid' ?>">
                                        <i class="fas fa-<?= $p['status_denda'] === 'lunas' ? 'check' : 'exclamation' ?>"></i>
                                        <?= format_rupiah($p['denda']) ?> · <?= status_denda_label($p['status_denda']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="fine-badge zero"><i class="fas fa-circle-check"></i>Tanpa denda</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-end d-none d-sm-block">
                            <span class="fine-badge zero"><i class="fas fa-circle-check"></i>Selesai</span>
                        </div>
                    </div>
                    <div class="v5-timeline-grid">
                        <div class="v5-tl-cell"><label>Tanggal Pinjam</label><strong><?= e(tgl_id($p['tanggal_pinjam'])) ?></strong></div>
                        <div class="v5-tl-cell"><label>Tanggal Kembali</label><strong><?= e(tgl_id($p['tanggal_kembali'])) ?></strong></div>
                        <div class="v5-tl-cell"><label>Dikembalikan</label><strong><?= e(tgl_id($p['tanggal_dikembalikan'])) ?></strong></div>
                        <div class="v5-tl-cell"><label>Kondisi</label><strong><?= e(format_kondisi($p['kondisi_saat_kembali'] ?? 'baik')) ?></strong></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Responsive Table (semua riwayat) -->
<div class="card" data-reveal>
    <div class="card-header">
        <span class="header-title"><i class="fas fa-table-list"></i>Semua Transaksi</span>
        <span class="badge-soft-primary"><?= $total_data ?> transaksi</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Dikembalikan</th>
                        <th>Denda</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat as $p): ?>
                    <tr>
                        <td><code><?= e($p['kode_peminjaman']) ?></code></td>
                        <td><span class="text-truncate d-inline-block" style="max-width:220px;"><?= e($p['judul_buku']) ?></span></td>
                        <td><?= e(tgl_id($p['tanggal_pinjam'])) ?></td>
                        <td><?= e(tgl_id($p['tanggal_kembali'])) ?></td>
                        <td><?= $p['tanggal_dikembalikan'] ? e(tgl_id($p['tanggal_dikembalikan'])) : '-' ?></td>
                        <td class="<?= $p['denda'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $p['denda'] > 0 ? format_rupiah($p['denda']) : '-' ?></td>
                        <td><span class="loan-badge <?= loan_status_class($p['status']) ?>"><?= loan_status_label($p['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($riwayat)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-book-open fa-2x mb-2 opacity-50 d-block"></i>Belum ada transaksi peminjaman.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_hal > 1): ?>
        <nav class="mt-3" aria-label="Paginasi">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php for ($i = 1; $i <= $total_hal; $i++): ?>
                <li class="page-item <?= $i === $halaman ? 'active' : '' ?>">
                    <a class="page-link" href="?halaman=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
