-- =============================================================
-- Migrasi: Sistem Badge / Pencapaian Anggota (USER)
-- -------------------------------------------------------------
-- Menambahkan tabel master badge dan relasinya ke anggota.
-- Badge diraih otomatis berdasar aktivitas nyata:
--   - total_pinjam  → jumlah peminjaman selesai (dikembalikan)
--   - total_review  → jumlah rating/komentar yang diberikan
--   - tepat_waktu   → jumlah pengembalian tepat waktu (denda = 0)
--
-- ENFORCEMENT (additif / backward-compatible):
--   * tabel BARU; TIDAK mengubah tabel yang sudah ada.
--   * tidak bertabrakan dengan modul Admin (tb_buku / tb_peminjaman).
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- 1. MASTER BADGE (definisi pencapaian)
-- =============================================================
CREATE TABLE IF NOT EXISTS `tb_badge` (
    `id_badge`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode`          VARCHAR(50)  NOT NULL,
    `nama`          VARCHAR(100) NOT NULL,
    `deskripsi`     VARCHAR(255) NOT NULL,
    `ikon`          VARCHAR(50)  NOT NULL DEFAULT 'fa-medal',
    `warna`         VARCHAR(20)  NOT NULL DEFAULT '#0e7490',
    `metrik`        ENUM('total_pinjam','total_review','tepat_waktu') NOT NULL,
    `syarat`        INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `urutan`        INT(11)      NOT NULL DEFAULT 0,
    `status`        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_badge`),
    UNIQUE KEY `uq_badge_kode` (`kode`),
    INDEX `idx_badge_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed badge default
INSERT INTO `tb_badge` (`kode`, `nama`, `deskripsi`, `ikon`, `warna`, `metrik`, `syarat`, `urutan`) VALUES
('member_baru',      'Anggota Baru',     'Selesaikan pinjaman pertama Anda.',            'fa-book-medical',    '#0e7490', 'total_pinjam', 1,   1),
('pembaca_tekun',    'Pembaca Tekun',    'Selesaikan 5 peminjaman buku.',                'fa-book-open',       '#059669', 'total_pinjam', 5,   2),
('kutu_buku',        'Kutu Buku',        'Selesaikan 10 buku yang Anda pinjam.',         'fa-book-open-reader','#b45309', 'total_pinjam', 10,  3),
('legenda_literasi', 'Legenda Literasi', 'Koleksi 25 buku selesai dibaca.',              'fa-crown',           '#7c3aed', 'total_pinjam', 25,  4),
('kritikus_baru',    'Kritikus Baru',    'Beri rating pertama untuk sebuah buku.',       'fa-star',            '#0ea5e9', 'total_review', 1,   5),
('kritikus_handal',  'Kritikus Handal',  'Tinggalkan 5 ulasan buku.',                    'fa-comment-dots',    '#ef4444', 'total_review', 5,   6),
('tepat_waktu',      'Tepat Waktu',      'Kembalikan 3 buku tanpa keterlambatan.',      'fa-clock',           '#2563eb', 'tepat_waktu',  3,   7),
('disiplin',         'Si Paling Disiplin','Kembalikan 10 buku tanpa denda.',             'fa-stopwatch',       '#16a34a', 'tepat_waktu', 10,   8)
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`), `warna` = VALUES(`warna`), `ikon` = VALUES(`ikon`);

-- =============================================================
-- 2. PENCAPAIAN PER ANGGOTA
-- =============================================================
CREATE TABLE IF NOT EXISTS `tb_badge_anggota` (
    `id_badge_anggota` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_anggota`       INT(11) UNSIGNED NOT NULL,
    `id_badge`         INT(11) UNSIGNED NOT NULL,
    `earned_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_badge_anggota`),
    UNIQUE KEY `uq_badge_anggota` (`id_anggota`,`id_badge`),
    KEY `idx_badge_anggota_anggota` (`id_anggota`),
    KEY `idx_badge_anggota_badge` (`id_badge`),
    CONSTRAINT `fk_badge_anggota_anggota`
        FOREIGN KEY (`id_anggota`) REFERENCES `tb_anggota` (`id_anggota`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_badge_anggota_badge`
        FOREIGN KEY (`id_badge`) REFERENCES `tb_badge` (`id_badge`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Akhir Migrasi: Badge
-- =============================================================