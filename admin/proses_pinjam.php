<?php
/**
 * PERPUSTAKAAN DAERAH - Proses Peminjaman
 * File: proses_pinjam.php
 * Deskripsi: Memproses peminjaman buku oleh anggota
 */

require_once __DIR__ . '/config/app.php';

// Harus login
require_login();

if (!is_anggota()) {
    set_flash('error', 'Admin tidak dapat meminjam buku.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('katalog_buku.php');
}

// Verifikasi CSRF
if (!csrf_verify()) {
    set_flash('error', 'Token keamanan tidak valid.');
    redirect('katalog_buku.php');
}

$id_buku        = (int) ($_POST['id_buku'] ?? 0);
$tanggal_pinjam = $_POST['tanggal_pinjam'] ?? date('Y-m-d');
$tanggal_kembali = $_POST['tanggal_kembali'] ?? date('Y-m-d', strtotime('+' . LOAN_PERIOD_DAYS . ' days'));
$keterangan     = trim($_POST['keterangan'] ?? '');
$id_anggota     = (int) ($_SESSION['id_anggota'] ?? 0);

if ($id_buku <= 0 || $id_anggota <= 0) {
    set_flash('error', 'Data buku atau anggota tidak valid.');
    redirect('katalog_buku.php');
}

// Validasi tanggal
if (!strtotime($tanggal_pinjam) || !strtotime($tanggal_kembali)) {
    set_flash('error', 'Format tanggal tidak valid.');
    redirect('detail_buku.php?id=' . $id_buku);
}

// Cek stok buku
$stmt = mysqli_prepare($koneksi, "SELECT judul_buku, stok_buku FROM tb_buku WHERE id_buku = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_buku);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $judul_buku, $stok);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($stok <= 0) {
    set_flash('error', 'Stok buku habis.');
    redirect('detail_buku.php?id=' . $id_buku);
}

// Cek apakah sudah meminjam buku yang sama
if (loan_sudah_pinjam($koneksi, $id_anggota, $id_buku)) {
    set_flash('error', 'Anda sudah meminjam buku ini dan belum mengembalikannya.');
    redirect('detail_buku.php?id=' . $id_buku);
}

// Cek batas maksimal
$dipinjam = hitung_buku_dipinjam($koneksi, $id_anggota);
if ($dipinjam >= MAX_BORROWED_BOOKS) {
    set_flash('error', 'Anda telah mencapai batas maksimal peminjaman (' . MAX_BORROWED_BOOKS . ' buku).');
    redirect('akun.php');
}

// Generate kode peminjaman
$kode_peminjaman = generate_kode_peminjaman($koneksi);

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {
    // Kunci baris anggota (FOR UPDATE) agar dua permintaan paralel tidak
    // melewati batas maksimal peminjaman secara bersamaan.
    $stmt = mysqli_prepare($koneksi, "SELECT id_anggota FROM tb_anggota WHERE id_anggota = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, 'i', $id_anggota);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $terkunci = mysqli_stmt_num_rows($stmt) === 1;
    mysqli_stmt_close($stmt);
    if (!$terkunci) {
        throw new Exception('Data anggota tidak ditemukan.');
    }

    // Validasi ulang di dalam transaksi (data bisa berubah sejak cek awal)
    if (loan_sudah_pinjam($koneksi, $id_anggota, $id_buku)) {
        throw new Exception('Anda sudah meminjam buku ini dan belum mengembalikannya.');
    }
    if (hitung_buku_dipinjam($koneksi, $id_anggota) >= MAX_BORROWED_BOOKS) {
        throw new Exception('Anda telah mencapai batas maksimal peminjaman (' . MAX_BORROWED_BOOKS . ' buku).');
    }

    // Insert peminjaman
    $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_peminjaman (kode_peminjaman, id_anggota, id_buku, id_user, tanggal_pinjam, tanggal_kembali, status, denda, keterangan) VALUES (?, ?, ?, NULL, ?, ?, 'dipinjam', 0, ?)");
    mysqli_stmt_bind_param($stmt, 'siisss', $kode_peminjaman, $id_anggota, $id_buku, $tanggal_pinjam, $tanggal_kembali, $keterangan);
    mysqli_stmt_execute($stmt);
    $id_peminjaman = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    // Kurangi stok buku (hanya jika stok tersedia saat itu juga)
    $stmt = mysqli_prepare($koneksi, "UPDATE tb_buku SET stok_buku = stok_buku - 1 WHERE id_buku = ? AND stok_buku > 0");
    mysqli_stmt_bind_param($stmt, 'i', $id_buku);
    mysqli_stmt_execute($stmt);
    $stok_berkurang = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($stok_berkurang === 0) {
        throw new Exception('Stok buku habis.');
    }

    mysqli_commit($koneksi);

    // Notifikasi: peminjaman berhasil (setelah commit, non-kritis)
    notif_event_borrowing($koneksi, $id_anggota, $id_peminjaman, $judul_buku, $kode_peminjaman, $tanggal_kembali);

    set_flash('success', 'Buku "' . $judul_buku . '" berhasil dipinjam! Kode peminjaman: ' . $kode_peminjaman);
    redirect('riwayat_peminjaman.php');

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    set_flash('error', $e->getMessage());
    redirect('detail_buku.php?id=' . $id_buku);
}
