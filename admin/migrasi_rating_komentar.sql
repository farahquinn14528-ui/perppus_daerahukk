-- =============================================================
-- Migrasi: Sistem Rating & Komentar Buku (halaman publik / USER)
-- -------------------------------------------------------------
-- Menambahkan tabel `tb_rating` untuk menyimpan rating (1-5 bintang)
-- dan komentar anggota terhadap sebuah buku.
--
-- ENFORCEMENT RULES (Shared Database):
--   * ADITIF / backward-compatible: ini tabel BARU.
--   * TIDAK mengubah, menghapus, atau mengganti nama tabel/kolom
--     yang sudah ada → tidak mengganggu Admin / tb_peminjaman, dst.
--   * Tidak ada konflik dengan query Admin karena tabel ini murni baru
--     dan hanya dibaca/ditulis oleh modul USER.
--
-- Aturan bisnis rating & komentar:
--   - rating wajib 1–5 (diperiksa sisi aplikasi, kolom menyimpan 1-5).
--   - satu anggota memberi satu rating + satu komentar per buku
--     (UNIQUE (id_buku, id_anggota) → idempoten / update).
--   - komentar opsional (boleh kosong).
--   - status untuk moderasi ringan (tampil / disembunyikan).
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `tb_rating` (
    `id_rating`  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_buku`    INT(11) UNSIGNED NOT NULL,
    `id_anggota` INT(11) UNSIGNED NOT NULL,
    `rating`     TINYINT(1)       NOT NULL DEFAULT 5 COMMENT 'Skor 1-5',
    `komentar`   TEXT             DEFAULT NULL,
    `status`     ENUM('tampil','disembunyikan') NOT NULL DEFAULT 'tampil',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_rating`),
    UNIQUE KEY `uq_rating_buku_anggota` (`id_buku`,`id_anggota`),
    KEY `idx_rating_buku` (`id_buku`,`status`),
    KEY `idx_rating_anggota` (`id_anggota`),
    CONSTRAINT `fk_rating_buku` FOREIGN KEY (`id_buku`)
        REFERENCES `tb_buku` (`id_buku`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rating_anggota` FOREIGN KEY (`id_anggota`)
        REFERENCES `tb_anggota` (`id_anggota`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;