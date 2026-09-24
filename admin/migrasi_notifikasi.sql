-- =============================================================
-- Migrasi: Perbaikan & Implementasi Sistem Notifikasi (tb_notifikasi)
-- -------------------------------------------------------------
-- Tabel tb_notifikasi SUDAH ada namun memiliki cacat skema:
--   Dua FK pada kolom yang sama `id_target_user` memaksa setiap
--   target harus ada di tb_anggota DAN tb_user sekaligus.
--   Akibatnya notifikasi untuk anggota (id_anggota) yang belum
--   terdaftar sebagai user (id_user) gagal disisipkan (FK error).
--
-- Perbaikan:
--   1) Lepas FK fk_notif_user dari `id_target_user`.
--   2) `id_target_user` kini merujuk ke anggota tb_anggota(id_anggota)
--      (dipakai untuk target_tipe='anggota').
--   3) Tambah kolom baru `id_target_staff` -> tb_user(id_user)
--      (untuk target_tipe='admin'/'petugas').
--
-- Kompatibel, aditif & idempotent:
--   * TIDAK mengubah kolom/key yang sudah ada (aman dijalankan ulang).
--   * Menggunakan klausa IF EXISTS / IF NOT EXISTS (MariaDB 10.x).
--   * Berjalan baik pada database lama (FK ganda) maupun database
--     yang sudah benar (create ulang FK identik).
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Lepas FK lama (aman / idempotent; dihapus hanya jika masih ada)
ALTER TABLE `tb_notifikasi` DROP FOREIGN KEY IF EXISTS `fk_notif_user`;
ALTER TABLE `tb_notifikasi` DROP FOREIGN KEY IF EXISTS `fk_notif_anggota`;
ALTER TABLE `tb_notifikasi` DROP FOREIGN KEY IF EXISTS `fk_notif_staff`;

-- 2) Tambah kolom target staf (admin/petugas) jika belum ada
ALTER TABLE `tb_notifikasi`
    ADD COLUMN IF NOT EXISTS `id_target_staff` INT(11) UNSIGNED DEFAULT NULL AFTER `id_target_user`;

-- 3) Pasang FK final:
--    id_target_user  -> tb_anggota(id_anggota)   (target_tipe='anggota')
--    id_target_staff -> tb_user(id_user)         (target_tipe='admin'/'petugas')
ALTER TABLE `tb_notifikasi`
    ADD CONSTRAINT `fk_notif_anggota`
        FOREIGN KEY (`id_target_user`) REFERENCES `tb_anggota` (`id_anggota`)
        ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `tb_notifikasi`
    ADD CONSTRAINT `fk_notif_staff`
        FOREIGN KEY (`id_target_staff`) REFERENCES `tb_user` (`id_user`)
        ON UPDATE CASCADE ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;