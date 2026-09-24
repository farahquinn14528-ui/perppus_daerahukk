-- =============================================================
-- PERPUSTAKAAN DAERAH - Database Schema v3.0
-- Sistem Informasi Perpustakaan Daerah
-- Dibuat untuk XAMPP / PHP Native + MySQL
--
-- Hardening:
--   * utf8mb4 + utf8mb4_unicode_ci di seluruh tabel
--   * Foreign key constraint dengan ON DELETE / ON UPDATE eksplisit
--   * Indexes pada kolom yang sering di-WHERE / JOIN
--   * 3NF compliance (tidak ada transitive dependency)
--   * Token login_attempts tersimpan di session, bukan DB
--
-- Compatibility (restricted phpMyAdmin / shared hosting):
--   * DROP DATABASE / CREATE DATABASE dihapus — tidak diizinkan di lingkungan terbatas.
--   * TIDAK ADA statement USE di file ini.
--     Alasan: di shared hosting (mis. InfinityFree) user hosting hanya memiliki
--     akses ke database yang SUDAH DIBUAT di panel hosting. Jika file ini
--     mengandung USE <nama_db>, phpMyAdmin akan mengirim USE ke server dan
--     mendapat error #1044 (Access denied for user 'if0_XXXX' to database
--     'perpustakaan_daerah') karena user hosting TIDAK memiliki akses ke
--     database 'perpustakaan_daerah'.
--   * CARA IMPORT (WAJIB):
--       1. Pilih database target di sidebar phpMyAdmin (database yang sudah
--          Anda buat di panel hosting, contoh: 'if0_XXXXXX_perpustakaan').
--       2. Klik tab Import, pilih file database.sql, lalu Go / Kirim.
--     Atau via CLI:  mysql -u <user> <nama_database> < database.sql
--   * File ini hanya memakai CREATE TABLE IF NOT EXISTS + seed data.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION';

-- =============================================================
-- Tabel User (Admin & Petugas)
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_user (
    id_user     INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_user   VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    level       ENUM('admin','petugas') NOT NULL DEFAULT 'petugas',
    foto        VARCHAR(255) DEFAULT NULL,
    status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_status (status),
    INDEX idx_user_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabel Kategori Buku
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_kategori (
    id_kategori    INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori  VARCHAR(100) NOT NULL,
    keterangan     TEXT DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kategori_nama (nama_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabel Penerbit
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_penerbit (
    id_penerbit   INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_penerbit VARCHAR(150) NOT NULL,
    alamat        TEXT DEFAULT NULL,
    telepon       VARCHAR(20)  DEFAULT NULL,
    email         VARCHAR(100) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_penerbit_nama (nama_penerbit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabel Rak
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_rak (
    id_rak      INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_rak    VARCHAR(50)  NOT NULL,
    lokasi      VARCHAR(100) DEFAULT NULL,
    keterangan  TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rak_nama (nama_rak)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabel Buku
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_buku (
    id_buku         INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    isbn            VARCHAR(30)   DEFAULT NULL,
    judul_buku      VARCHAR(255)  NOT NULL,
    id_kategori     INT(11) UNSIGNED NOT NULL,
    id_penerbit     INT(11) UNSIGNED NOT NULL,
    id_rak          INT(11) UNSIGNED DEFAULT NULL,
    pengarang       VARCHAR(150)  NOT NULL,
    tahun_terbit    YEAR DEFAULT NULL,
    bahasa          VARCHAR(50)   DEFAULT 'Indonesia',
    jumlah_halaman  INT(11)       DEFAULT 0,
    jumlah_buku     INT(11)       NOT NULL DEFAULT 0,
    stok_buku       INT(11)       NOT NULL DEFAULT 0,
    kondisi         ENUM('baik','rusak_ringan','rusak_berat') DEFAULT 'baik',
    foto            VARCHAR(255)  DEFAULT NULL,
    sinopsis        TEXT DEFAULT NULL,
    keterangan      TEXT DEFAULT NULL,
    populer         TINYINT(1)    NOT NULL DEFAULT 0,
    baru            TINYINT(1)    NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_buku_kategori (id_kategori),
    INDEX idx_buku_penerbit (id_penerbit),
    INDEX idx_buku_rak (id_rak),
    INDEX idx_buku_judul (judul_buku),
    INDEX idx_buku_pengarang (pengarang),
    INDEX idx_buku_populer (populer),
    INDEX idx_buku_baru (baru),
    INDEX idx_buku_stok (stok_buku),
    CONSTRAINT fk_buku_kategori  FOREIGN KEY (id_kategori)   REFERENCES tb_kategori(id_kategori)   ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_buku_penerbit  FOREIGN KEY (id_penerbit)   REFERENCES tb_penerbit(id_penerbit)   ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_buku_rak       FOREIGN KEY (id_rak)        REFERENCES tb_rak(id_rak)             ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabel Anggota
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_anggota (
    id_anggota     INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_anggota  VARCHAR(30)  NOT NULL UNIQUE,
    nama_lengkap   VARCHAR(150) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    role           ENUM('anggota','admin') NOT NULL DEFAULT 'anggota',
    jenis_kelamin  ENUM('L','P') DEFAULT NULL,
    kelas          VARCHAR(30)  DEFAULT NULL,
    jurusan        VARCHAR(50)  DEFAULT NULL,
    alamat         TEXT DEFAULT NULL,
    telepon        VARCHAR(20)  DEFAULT NULL,
    foto           VARCHAR(255) DEFAULT NULL,
    status         ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    tanggal_daftar DATE NOT NULL DEFAULT (CURRENT_DATE),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_anggota_role (role),
    INDEX idx_anggota_status (status),
    INDEX idx_anggota_nama (nama_lengkap),
    INDEX idx_anggota_tanggal (tanggal_daftar)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabel Peminjaman (V5: status approval pengembalian + status denda)
-- =============================================================
CREATE TABLE IF NOT EXISTS tb_peminjaman (
    id_peminjaman       INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_peminjaman     VARCHAR(30)  NOT NULL UNIQUE,
    id_anggota          INT(11) UNSIGNED NOT NULL,
    id_buku             INT(11) UNSIGNED NOT NULL,
    id_user             INT(11) UNSIGNED DEFAULT NULL,
    tanggal_pinjam      DATE NOT NULL,
    tanggal_kembali     DATE NOT NULL,
    tanggal_dikembalikan DATE DEFAULT NULL,
    status              ENUM('dipinjam','terlambat','menunggu_pengembalian','dikembalikan','ditolak') NOT NULL DEFAULT 'dipinjam',
    denda               DECIMAL(10,2) DEFAULT 0.00,
    status_denda        ENUM('belum_dibayar','lunas') NOT NULL DEFAULT 'belum_dibayar',
    tanggal_diajukan    DATETIME DEFAULT NULL,
    tanggal_disetujui   DATETIME DEFAULT NULL,
    kondisi_saat_kembali VARCHAR(30) DEFAULT NULL,
    catatan_pengembalian TEXT DEFAULT NULL,
    diproses_oleh       INT(11) UNSIGNED DEFAULT NULL,
    keterangan          TEXT DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pinjam_anggota (id_anggota),
    INDEX idx_pinjam_buku (id_buku),
    INDEX idx_pinjam_user (id_user),
    INDEX idx_pinjam_status (status),
    INDEX idx_pinjam_tanggal (tanggal_pinjam),
    INDEX idx_pinjam_kode (kode_peminjaman),
    INDEX idx_pinjam_status_denda (status_denda),
    CONSTRAINT fk_pinjam_anggota FOREIGN KEY (id_anggota) REFERENCES tb_anggota(id_anggota) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pinjam_buku    FOREIGN KEY (id_buku)    REFERENCES tb_buku(id_buku)       ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pinjam_user    FOREIGN KEY (id_user)    REFERENCES tb_user(id_user)       ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pinjam_proses  FOREIGN KEY (diproses_oleh) REFERENCES tb_user(id_user)    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Tabel V5 — Persistent Login, CMS, Permission
-- =============================================================

-- Sesuai login persistent (remember me)
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

-- Banner / Carousel homepage
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

-- Berita / News
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

-- Pengumuman / Announcements
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

-- Layanan / Services
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

-- FAQ
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

-- Jam Buka / Opening Hours
CREATE TABLE IF NOT EXISTS tb_jam_buka (
    id_jam       INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hari         VARCHAR(20) NOT NULL,
    jam_buka     VARCHAR(20) NOT NULL,
    status       ENUM('buka','tutup') NOT NULL DEFAULT 'buka',
    urutan       INT(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kontak / Pengaturan umum
CREATE TABLE IF NOT EXISTS tb_pengaturan (
    id_pengaturan INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_key     VARCHAR(100) NOT NULL UNIQUE,
    nilai        TEXT DEFAULT NULL,
    tipe         ENUM('text','textarea','file') NOT NULL DEFAULT 'text',
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Management / Permission Matrix
CREATE TABLE IF NOT EXISTS tb_permission (
    id_permission  INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role           ENUM('admin','petugas','anggota') NOT NULL,
    modul          VARCHAR(50) NOT NULL,
    akses          ENUM('baca','tulis','semua') NOT NULL DEFAULT 'baca',
    UNIQUE KEY uq_permission (role, modul),
    INDEX idx_permission_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifikasi Sistem (anggota & staf)
-- Dipakai modul notifikasi (app/notifications.php, api/notifikasi.php).
-- Desain FK final sesuai migrasi_notifikasi.sql (tidak ada FK ganda):
--   * id_target_user  -> tb_anggota(id_anggota)
--   * id_target_staff -> tb_user(id_user)
CREATE TABLE IF NOT EXISTS tb_notifikasi (
    id_notifikasi    INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_target_user   INT(11) UNSIGNED DEFAULT NULL,
    id_target_staff  INT(11) UNSIGNED DEFAULT NULL,
    target_tipe      ENUM('admin','petugas','anggota') NOT NULL DEFAULT 'admin',
    tipe             VARCHAR(40) NOT NULL,
    judul            VARCHAR(200) NOT NULL,
    pesan            TEXT NOT NULL,
    link             VARCHAR(255) DEFAULT NULL,
    ref_table        VARCHAR(50) DEFAULT NULL,
    ref_id           INT(11) UNSIGNED DEFAULT NULL,
    dibaca_at        DATETIME DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notif (id_target_user, target_tipe, tipe, ref_table, ref_id),
    UNIQUE KEY uq_notif_staff (id_target_staff, target_tipe, tipe, ref_table, ref_id),
    INDEX idx_notif_user (id_target_user, target_tipe),
    INDEX idx_notif_unread (id_target_user, dibaca_at),
    INDEX idx_notif_tipe (tipe),
    INDEX idx_notif_created (created_at),
    CONSTRAINT fk_notif_anggota FOREIGN KEY (id_target_user)
        REFERENCES tb_anggota(id_anggota) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notif_staff   FOREIGN KEY (id_target_staff)
        REFERENCES tb_user(id_user) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Insert Data Default
-- =============================================================

-- Default User Admin & Petugas
-- Password default: admin123
INSERT INTO tb_user (nama_user, username, password, level, status) VALUES
('Administrator',         'admin',   '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'admin',   'aktif'),
('Petugas Perpustakaan',  'petugas', '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'petugas', 'aktif');

-- Default Kategori Buku
INSERT INTO tb_kategori (nama_kategori, keterangan) VALUES
('Fiksi',             'Buku cerita fiksi dan novel'),
('Non-Fiksi',         'Buku pengetahuan dan referensi'),
('Pendidikan',        'Buku pelajaran dan pendidikan'),
('Sains & Teknologi', 'Buku sains dan teknologi'),
('Sejarah',           'Buku sejarah dan budaya'),
('Agama',             'Buku agama dan kepercayaan'),
('Bahasa',            'Buku bahasa dan sastra'),
('Seni & Olahraga',   'Buku seni dan olahraga');

-- Default Penerbit
INSERT INTO tb_penerbit (nama_penerbit, alamat, telepon, email) VALUES
('Erlangga',              'Jl. Herman Kemang No.1, Jakarta',     '021-7398388',  'info@erlangga.co.id'),
('Gramedia Pustaka Utama','Jl. Palmerah Barat No.33, Jakarta',  '021-53650110', 'gramedia@gramedia.co.id'),
('Yudistira',             'Jl. Tanah Abang III No.25, Jakarta', '021-3801234',  'yudistira@yudistira.co.id'),
('Tiga Serangkai',        'Jl. Dr. Wahidin No.103, Solo',       '0271-633733',  'ts@tigaserangkai.co.id'),
('Intan Pariwara',        'Jl. Raya Solo-Klaten KM 7, Klaten',  '0272-324555',  'info@intanpariwara.co.id'),
('Mizan Pustaka',         'Jl. Cinere Raya No.1, Depok',        '021-75902020', 'info@mizan.com'),
('Bentang Pustaka',       'Jl. Sukoharjo No.17, Yogyakarta',    '0274-388225',  'bentang@bentangpustaka.com');

-- Default Rak
INSERT INTO tb_rak (nama_rak, lokasi, keterangan) VALUES
('Rak A', 'Lantai 1 - Sayap Kiri',  'Rak untuk buku fiksi dan non-fiksi'),
('Rak B', 'Lantai 1 - Sayap Kanan', 'Rak untuk buku pendidikan'),
('Rak C', 'Lantai 2 - Sayap Kiri',  'Rak untuk buku sains dan teknologi'),
('Rak D', 'Lantai 2 - Sayap Kanan', 'Rak untuk buku sejarah dan agama'),
('Rak E', 'Lantai 3',               'Rak untuk buku referensi dan ensiklopedia');

-- Default Anggota
-- Password: admin123
INSERT INTO tb_anggota (nomor_anggota, nama_lengkap, email, password, role, jenis_kelamin, kelas, jurusan, alamat, telepon, status, tanggal_daftar) VALUES
('AGT-2024-001', 'Ahmad Rizky Pratama', 'ahmad@email.com',  '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'L', 'XII', 'RPL', 'Jl. Merdeka No.10, Jakarta',     '081234567890', 'aktif', '2024-01-15'),
('AGT-2024-002', 'Siti Nurhaliza',      'siti@email.com',   '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'P', 'XI',  'TKJ', 'Jl. Sudirman No.25, Jakarta',   '081234567891', 'aktif', '2024-01-16'),
('AGT-2024-003', 'Budi Santoso',        'budi@email.com',   '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'L', 'XII', 'RPL', 'Jl. Gatot Subroto No.5, Jakarta','081234567892', 'aktif', '2024-02-01'),
('AGT-2024-004', 'Dewi Sartika',        'dewi@email.com',   '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'P', 'X',   'MM',  'Jl. Ahmad Yani No.15, Jakarta',  '081234567893', 'aktif', '2024-02-10'),
('AGT-2024-005', 'Muhammad Farhan',     'farhan@email.com', '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'L', 'XI',  'RPL', 'Jl. Diponegoro No.20, Jakarta',  '081234567894', 'aktif', '2024-03-01'),
('AGT-2024-006', 'Putri Ayu Lestari',   'putri@email.com',  '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'P', 'XII', 'TKJ', 'Jl. Kartini No.8, Jakarta',      '081234567895', 'aktif', '2024-03-05'),
('AGT-2024-007', 'Rendi Saputra',       'rendi@email.com',  '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'L', 'X',   'RPL', 'Jl. Pahlawan No.30, Jakarta',    '081234567896', 'aktif', '2024-03-15'),
('AGT-2024-008', 'Anisa Rahma',         'anisa@email.com',  '$2y$10$7h0Ku3M9lFH0RHmZ.p.wd.bV.Ngnk5YiudlPX7VDJDYsuas//YyTS', 'anggota', 'P', 'XI',  'MM',  'Jl. Cendana No.12, Jakarta',     '081234567897', 'aktif', '2024-04-01');

-- Default Buku (path gambar mengarah ke assets/img/buku/)
INSERT INTO tb_buku (isbn, judul_buku, id_kategori, id_penerbit, id_rak, pengarang, tahun_terbit, bahasa, jumlah_halaman, jumlah_buku, stok_buku, kondisi, foto, sinopsis, keterangan, populer, baru) VALUES
('978-602-291-001-1', 'Laskar Pelangi',           1, 2, 1, 'Andrea Hirata',          2005, 'Indonesia', 529, 5, 5,  'baik', 'assets/img/buku/bk1.jpg',  'Novel bestseller tentang perjuangan anak-anak Belitung dalam meraih pendidikan. Kisah inspiratif tentang persahabatan, mimpi, dan harapan.', 'Novel bestseller tentang pendidikan di Belitung', 1, 1),
('978-602-291-002-8', 'Bumi Manusia',              1, 2, 1, 'Pramoedya Ananta Toer',  1980, 'Indonesia', 535, 3, 3,  'baik', 'assets/img/buku/bk2.jpg',  'Novel sejarah Indonesia yang mengisahkan perjuangan seorang pribumi melawan penjajahan kolonial Belanda.', 'Novel sejarah Indonesia', 1, 0),
('978-602-291-003-5', 'Matematika Kelas X',        3, 1, 2, 'Tim Erlangga',           2023, 'Indonesia', 312, 10, 10, 'baik', 'assets/img/buku/bk3.jpg',  'Buku pelajaran matematika SMA kelas X dengan materi lengkap sesuai kurikulum terbaru.', 'Buku pelajaran matematika SMA', 0, 1),
('978-602-291-004-2', 'Fisika Dasar',              4, 1, 3, 'Halliday & Resnick',     2022, 'Inggris',   928, 5, 5,  'baik', 'assets/img/buku/bk4.jpg',  'Buku referensi fisika tingkat universitas yang membahas mekanika, termodinamika, dan elektromagnetik secara komprehensif.', 'Buku referensi fisika', 0, 0),
('978-602-291-005-9', 'Sejarah Indonesia Modern',  5, 3, 4, 'M.C. Ricklefs',          2019, 'Indonesia', 690, 4, 4,  'baik', 'assets/img/buku/bk5.jpg',  'Buku sejarah Indonesia dari masa kerajaan Hindu-Buddha hingga era reformasi modern.', 'Buku sejarah Indonesia', 0, 0),
('978-602-291-006-6', 'Bahasa Indonesia Kelas XI', 7, 4, 2, 'Tim Tiga Serangkai',     2023, 'Indonesia', 256, 8, 8,  'baik', 'assets/img/buku/bk6.jpg',  'Buku pelajaran Bahasa Indonesia SMA kelas XI dengan materi sastra, tata bahasa, dan menulis.', 'Buku pelajaran Bahasa Indonesia', 0, 1),
('978-602-291-007-3', 'Biologi Kelas XII',         3, 5, 3, 'Campbell',               2023, 'Indonesia', 480, 6, 6,  'baik', 'assets/img/buku/bk7.jpg',  'Buku pelajaran biologi SMA kelas XII dengan pembahasan sel, genetika, dan ekosistem.', 'Buku pelajaran biologi SMA', 0, 0),
('978-602-291-008-0', 'Pendidikan Agama Islam',    6, 1, 4, 'Tim Erlangga',           2023, 'Indonesia', 224, 10, 10, 'baik', 'assets/img/buku/bk8.jpg',  'Buku pelajaran Pendidikan Agama Islam dengan materi akidah, ibadah, dan akhlak.', 'Buku pelajaran PAI', 0, 0),
('978-602-291-009-7', 'Kimia Organik',             4, 1, 3, 'Fessenden',              2021, 'Inggris',   720, 3, 3,  'baik', 'assets/img/buku/bk9.jpg',  'Buku referensi kimia organik yang membahas struktur, reaksi, dan sintesis senyawa karbon.', 'Buku referensi kimia organik', 0, 0),
('978-602-291-010-3', 'Negeri 5 Menara',           1, 2, 1, 'Ahmad Fuadi',            2009, 'Indonesia', 416, 4, 4,  'baik', 'assets/img/buku/bk10.jpg', 'Novel inspiratif tentang perjuangan santri Pondok Madani dalam meraih mimpi. Kisah persahabatan dan dakwah.', 'Novel tentang perjuangan pendidikan', 1, 0),
('978-602-291-011-0', 'Atomic Habits',             2, 6, 1, 'James Clear',            2018, 'Indonesia', 352, 6, 6,  'baik', 'assets/img/buku/bk11.jpg', 'Buku self-development yang mengajarkan cara membangun kebiasaan baik dan menghilangkan kebiasaan buruk dengan perubahan kecil.', 'Buku pengembangan diri internasional', 1, 1);

-- Default Peminjaman
INSERT INTO tb_peminjaman (kode_peminjaman, id_anggota, id_buku, id_user, tanggal_pinjam, tanggal_kembali, tanggal_dikembalikan, status, denda) VALUES
('PMJ-20240601-001', 1, 1,  1, '2024-06-01', '2024-06-15', '2024-06-07', 'dikembalikan', 0.00),
('PMJ-20240605-002', 2, 3,  1, '2024-06-05', '2024-06-19', '2024-06-12', 'dikembalikan', 0.00),
('PMJ-20240610-003', 3, 2,  2, '2024-06-10', '2024-06-24', '2024-06-20', 'dikembalikan', 3000.00),
('PMJ-20240701-004', 4, 5,  1, '2024-07-01', '2024-07-15', NULL, 'dipinjam', 0.00),
('PMJ-20240705-005', 5, 7,  2, '2024-07-05', '2024-07-19', NULL, 'dipinjam', 0.00),
('PMJ-20240625-006', 6, 10, 1, '2024-06-25', '2024-07-09', NULL, 'terlambat', 6000.00),
('PMJ-20240710-007', 1, 4,  2, '2024-07-10', '2024-07-24', NULL, 'dipinjam', 0.00);

-- =============================================================
-- Data Default V5 (CMS, Permission, dll)
-- =============================================================

-- Pengaturan umum / kontak
INSERT INTO tb_pengaturan (nama_key, nilai, tipe) VALUES
('alamat',  'Jl. Contoh Perpustakaan No. 1, Kota Daerah', 'textarea'),
('telepon', '(021) 000-0000', 'text'),
('email',   'info@perpustakaan-daerah.id', 'text'),
('instagram', 'https://instagram.com/perpustakaan', 'text'),
('facebook',  'https://facebook.com/perpustakaan', 'text'),
('tentang', 'Perpustakaan Daerah melayani kebutuhan literasi masyarakat dengan koleksi buku yang lengkap dan modern.', 'textarea'),
('jam_buka_info', 'Senin - Jumat: 08.00 - 20.00', 'text');

-- Jam buka
INSERT INTO tb_jam_buka (hari, jam_buka, status, urutan) VALUES
('Senin',    '08.00 - 20.00', 'buka',  1),
('Selasa',   '08.00 - 20.00', 'buka',  2),
('Rabu',     '08.00 - 20.00', 'buka',  3),
('Kamis',    '08.00 - 20.00', 'buka',  4),
('Jumat',    '08.00 - 20.00', 'buka',  5),
('Sabtu',    '09.00 - 15.00', 'buka',  6),
('Minggu',   'Tutup',         'tutup', 7);

-- Layanan
INSERT INTO tb_layanan (nama, deskripsi, ikon, urutan, status) VALUES
('Peminjaman Buku', 'Pinjam hingga 2 buku selama 14 hari dengan mudah dan cepat.', 'fa-book-open-reader', 1, 'aktif'),
('Koleksi Digital', 'Akses koleksi e-book dan jurnal ilmiah secara online.', 'fa-tablet-screen-button', 2, 'aktif'),
('Ruang Baca', 'Ruang baca nyaman ber-AC untuk belajar dan riset.', 'fa-couch', 3, 'aktif'),
('Rekomendasi Buku', 'Dapatkan rekomendasi bacaan sesuai minat Anda.', 'fa-wand-magic-sparkles', 4, 'aktif'),
('Bimbingan Literasi', 'Program edukasi dan pelatihan literasi untuk semua usia.', 'fa-graduation-cap', 5, 'aktif'),
('Layanan Referensi', 'Bantuan pencarian referensi untuk tugas dan penelitian.', 'fa-magnifying-glass', 6, 'aktif');

-- FAQ
INSERT INTO tb_faq (pertanyaan, jawaban, urutan, status) VALUES
('Berapa lama masa peminjaman buku?', 'Masa peminjaman buku adalah 14 hari. Jika melewati batas waktu, akan dikenakan denda Rp10.000 per hari keterlambatan.', 1, 'aktif'),
('Berapa maksimal buku yang bisa dipinjam?', 'Setiap anggota dapat meminjam maksimal 2 buku dalam satu waktu.', 2, 'aktif'),
('Bagaimana cara menjadi anggota?', 'Pendaftaran anggota hanya dapat dilakukan oleh petugas perpustakaan di bagian layanan.', 3, 'aktif'),
('Bagaimana proses pengembalian buku?', 'Ajukan permintaan pengembalian melalui menu Riwayat Pengembalian, kemudian petugas akan memprosesnya.', 4, 'aktif');

-- Pengumuman
INSERT INTO tb_pengumuman (judul, isi, tipe, status) VALUES
('Selamat Datang di Perpustakaan Daerah', 'Kami hadir dengan sistem layanan digital terbaru. Jelajahi katalog dan kelola peminjaman Anda secara online.', 'info', 'aktif');

-- Permission matrix
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
('anggota', 'pengembalian',     'baca');

-- Trigger untuk auto-update stok_buku konsisten dengan jumlah_buku
DELIMITER //
DROP TRIGGER IF EXISTS trg_buku_before_insert//
CREATE TRIGGER trg_buku_before_insert
BEFORE INSERT ON tb_buku
FOR EACH ROW
BEGIN
    IF NEW.stok_buku > NEW.jumlah_buku THEN
        SET NEW.stok_buku = NEW.jumlah_buku;
    END IF;
END//

DROP TRIGGER IF EXISTS trg_buku_before_update//
CREATE TRIGGER trg_buku_before_update
BEFORE UPDATE ON tb_buku
FOR EACH ROW
BEGIN
    IF NEW.stok_buku > NEW.jumlah_buku THEN
        SET NEW.stok_buku = NEW.jumlah_buku;
    END IF;
END//
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Akhir File Database v3.0
-- =============================================================
