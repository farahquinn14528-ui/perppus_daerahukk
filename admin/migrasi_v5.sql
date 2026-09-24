-- =============================================================
-- PERPUSTAKAAN DAERAH — Migrasi V5.0
-- PRO MAX ENTERPRISE REDESIGN
--
-- Jalankan file ini di phpMyAdmin untuk database EXISTING
-- v4.x / v3.x (instalasi baru: cukup import database.sql yang
-- sudah diperbarui karena database.sql sudah memuat skema V5).
--
-- IDEMPOTENT (MariaDB 10.x):
--   * CREATE TABLE IF NOT EXISTS di semua tabel.
--   * ALTER memakai ADD COLUMN IF NOT EXISTS / ADD INDEX IF NOT EXISTS
--     dan DROP FOREIGN KEY IF EXISTS sebelum ADD CONSTRAINT.
--   * Aman dijalankan pada database baru (sudah V5) maupun lama.
--
-- Isi:
--   1. tb_sesi_login        → persistent login (remember me)
--   2. tb_peminjaman        → status approval pengembalian + status denda
--   3. CMS tables           → banner, berita, pengumuman, layanan, faq, jam buka, kontak, pengaturan
--   4. tb_permission        → role management / permission matrix
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- 1. SESI LOGIN PERSISTENT (remember me)
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_sesi_login (
    id_sesi     INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_anggota  INT(11) UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sesi_anggota (id_anggota),
    INDEX idx_sesi_token (token_hash),
    CONSTRAINT fk_sesi_anggota FOREIGN KEY (id_anggota)
        REFERENCES tb_anggota(id_anggota) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 2. PEMINJAMAN — status approval pengembalian + status denda
-- =============================================================
-- Lepas FK lama bila ada agar ADD CONSTRAINT dapat dijalankan ulang
ALTER TABLE tb_peminjaman DROP FOREIGN KEY IF EXISTS fk_pinjam_proses;

ALTER TABLE tb_peminjaman
    MODIFY COLUMN status ENUM('dipinjam','terlambat','menunggu_pengembalian','dikembalikan','ditolak') NOT NULL DEFAULT 'dipinjam',
    ADD COLUMN IF NOT EXISTS status_denda          ENUM('belum_dibayar','lunas') NOT NULL DEFAULT 'belum_dibayar' AFTER denda,
    ADD COLUMN IF NOT EXISTS tanggal_diajukan      DATETIME DEFAULT NULL AFTER status_denda,
    ADD COLUMN IF NOT EXISTS tanggal_disetujui     DATETIME DEFAULT NULL AFTER tanggal_diajukan,
    ADD COLUMN IF NOT EXISTS kondisi_saat_kembali  VARCHAR(30) DEFAULT NULL AFTER tanggal_disetujui,
    ADD COLUMN IF NOT EXISTS catatan_pengembalian  TEXT DEFAULT NULL AFTER kondisi_saat_kembali,
    ADD COLUMN IF NOT EXISTS diproses_oleh         INT(11) UNSIGNED DEFAULT NULL AFTER catatan_pengembalian,
    ADD INDEX IF NOT EXISTS idx_pinjam_status_denda (status_denda),
    ADD CONSTRAINT fk_pinjam_proses FOREIGN KEY (diproses_oleh)
        REFERENCES tb_user(id_user) ON UPDATE CASCADE ON DELETE SET NULL;

-- Perbaiki data lama: semua yang sudah dikembalikan dianggap lunas jika denda 0
UPDATE tb_peminjaman SET status_denda = 'lunas'
WHERE status = 'dikembalikan' AND (denda IS NULL OR denda = 0);

-- =============================================================
-- 3. TABEL CMS
-- =============================================================

-- 3a. Banner / Carousel homepage
CREATE TABLE IF NOT EXISTS tb_banner (
    id_banner    INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul        VARCHAR(200) NOT NULL,
    subjudul     VARCHAR(255) DEFAULT NULL,
    gambar       VARCHAR(255) DEFAULT NULL,
    tombol_text  VARCHAR(100) DEFAULT NULL,
    tombol_url   VARCHAR(255) DEFAULT NULL,
    urutan       INT(11) NOT NULL DEFAULT 0,
    status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_banner_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3b. Berita / News
CREATE TABLE IF NOT EXISTS tb_berita (
    id_berita    INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul        VARCHAR(200) NOT NULL,
    isi          TEXT NOT NULL,
    gambar       VARCHAR(255) DEFAULT NULL,
    kategori     VARCHAR(100) DEFAULT 'Umum',
    status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_berita_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3c. Pengumuman / Announcements
CREATE TABLE IF NOT EXISTS tb_pengumuman (
    id_pengumuman INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul        VARCHAR(200) NOT NULL,
    isi          TEXT NOT NULL,
    tipe         ENUM('info','peringatan','penting') NOT NULL DEFAULT 'info',
    status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pengumuman_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3d. Layanan / Services
CREATE TABLE IF NOT EXISTS tb_layanan (
    id_layanan   INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama         VARCHAR(150) NOT NULL,
    deskripsi    TEXT NOT NULL,
    ikon         VARCHAR(50) DEFAULT NULL,
    urutan       INT(11) NOT NULL DEFAULT 0,
    status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_layanan_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3e. FAQ
CREATE TABLE IF NOT EXISTS tb_faq (
    id_faq       INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pertanyaan   VARCHAR(255) NOT NULL,
    jawaban      TEXT NOT NULL,
    urutan       INT(11) NOT NULL DEFAULT 0,
    status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_faq_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3f. Jam Buka / Opening Hours
CREATE TABLE IF NOT EXISTS tb_jam_buka (
    id_jam       INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hari         VARCHAR(20) NOT NULL,
    jam_buka     VARCHAR(20) NOT NULL,
    status       ENUM('buka','tutup') NOT NULL DEFAULT 'buka',
    urutan       INT(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3g. Kontak / Pengaturan umum
CREATE TABLE IF NOT EXISTS tb_pengaturan (
    id_pengaturan INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_key     VARCHAR(100) NOT NULL UNIQUE,
    nilai        TEXT DEFAULT NULL,
    tipe         ENUM('text','textarea','file') NOT NULL DEFAULT 'text',
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tb_pengaturan (nama_key, nilai, tipe) VALUES
('alamat',  'Jl. Contoh Perpustakaan No. 1, Kota Daerah', 'textarea'),
('telepon', '(021) 000-0000', 'text'),
('email',   'info@perpustakaan-daerah.id', 'text'),
('instagram', 'https://instagram.com/perpustakaan', 'text'),
('facebook',  'https://facebook.com/perpustakaan', 'text'),
('tentang', 'Perpustakaan Daerah melayani kebutuhan literasi masyarakat dengan koleksi buku yang lengkap dan modern.', 'textarea'),
('jam_buka_info', 'Senin - Jumat: 08.00 - 20.00', 'text')
ON DUPLICATE KEY UPDATE nama_key = VALUES(nama_key);

-- Seed default jam buka
INSERT INTO tb_jam_buka (hari, jam_buka, status, urutan) VALUES
('Senin',    '08.00 - 20.00', 'buka',  1),
('Selasa',   '08.00 - 20.00', 'buka',  2),
('Rabu',     '08.00 - 20.00', 'buka',  3),
('Kamis',    '08.00 - 20.00', 'buka',  4),
('Jumat',    '08.00 - 20.00', 'buka',  5),
('Sabtu',    '09.00 - 15.00', 'buka',  6),
('Minggu',   'Tutup',         'tutup', 7);

-- Seed default layanan
INSERT INTO tb_layanan (nama, deskripsi, ikon, urutan, status) VALUES
('Peminjaman Buku', 'Pinjam hingga 2 buku selama 14 hari dengan mudah dan cepat.', 'fa-book-open-reader', 1, 'aktif'),
('Koleksi Digital', 'Akses koleksi e-book dan jurnal ilmiah secara online.', 'fa-tablet-screen-button', 2, 'aktif'),
('Ruang Baca', 'Ruang baca nyaman ber-AC untuk belajar dan riset.', 'fa-couch', 3, 'aktif'),
('Rekomendasi Buku', 'Dapatkan rekomendasi bacaan sesuai minat Anda.', 'fa-wand-magic-sparkles', 4, 'aktif'),
('Bimbingan Literasi', 'Program edukasi dan pelatihan literasi untuk semua usia.', 'fa-graduation-cap', 5, 'aktif'),
('Layanan Referensi', 'Bantuan pencarian referensi untuk tugas dan penelitian.', 'fa-magnifying-glass', 6, 'aktif');

-- Seed default FAQ
INSERT INTO tb_faq (pertanyaan, jawaban, urutan, status) VALUES
('Berapa lama masa peminjaman buku?', 'Masa peminjaman buku adalah 14 hari. Jika melewati batas waktu, akan dikenakan denda Rp10.000 per hari keterlambatan.', 1, 'aktif'),
('Berapa maksimal buku yang bisa dipinjam?', 'Setiap anggota dapat meminjam maksimal 2 buku dalam satu waktu.', 2, 'aktif'),
('Bagaimana cara menjadi anggota?', 'Pendaftaran anggota hanya dapat dilakukan oleh petugas perpustakaan di bagian layanan.', 3, 'aktif'),
('Bagaimana proses pengembalian buku?', 'Ajukan permintaan pengembalian melalui menu Riwayat Pengembalian, kemudian petugas akan memprosesnya.', 4, 'aktif');

-- Seed default pengumuman
INSERT INTO tb_pengumuman (judul, isi, tipe, status) VALUES
('Selamat Datang di Perpustakaan Daerah', 'Kami hadir dengan sistem layanan digital terbaru. Jelajahi katalog dan kelola peminjaman Anda secara online.', 'info', 'aktif');

-- =============================================================
-- 4. ROLE MANAGEMENT / PERMISSION MATRIX
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_permission (
    id_permission  INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role           ENUM('admin','petugas','anggota') NOT NULL,
    modul          VARCHAR(50) NOT NULL,
    akses          ENUM('baca','tulis','semua') NOT NULL DEFAULT 'baca',
    UNIQUE KEY uq_permission (role, modul),
    INDEX idx_permission_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default permission matrix (idempotent: ON DUPLICATE KEY UPDATE)
INSERT INTO tb_permission (role, modul, akses) VALUES
('admin',   'dashboard',        'semua'),
('admin',   'buku',             'semua'),
('admin',   'kategori',         'semua'),
('admin',   'penerbit',         'semua'),
('admin',   'rak',              'semua'),
('admin',   'anggota',          'semua'),
('admin',   'peminjaman',       'semua'),
('admin',   'pengembalian',     'semua'),
('admin',   'laporan',          'semua'),
('admin',   'cms',              'semua'),
('admin',   'pengaturan',       'semua'),
('admin',   'user',             'semua'),
('admin',   'role',             'semua'),
('petugas', 'dashboard',        'baca'),
('petugas', 'buku',             'semua'),
('petugas', 'kategori',         'semua'),
('petugas', 'penerbit',         'semua'),
('petugas', 'rak',              'semua'),
('petugas', 'anggota',          'semua'),
('petugas', 'peminjaman',       'semua'),
('petugas', 'pengembalian',     'semua'),
('petugas', 'laporan',          'baca'),
('petugas', 'cms',              'semua'),
('petugas', 'pengaturan',       'baca'),
('anggota', 'dashboard',        'baca'),
('anggota', 'buku',             'baca'),
('anggota', 'peminjaman',       'baca'),
('anggota', 'pengembalian',     'baca')
ON DUPLICATE KEY UPDATE akses = VALUES(akses);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Akhir Migrasi V5
-- =============================================================
