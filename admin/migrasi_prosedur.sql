-- =============================================================
-- Migrasi: Stored Procedure & Function — PERPUSTAKAAN DAERAH
-- -------------------------------------------------------------
-- Konteks (Shared Database):
--   Aplikasi berjalan PHP Native + mysqli dengan transaction inline
--   (lihat proses_pinjam.php, pengembalian.php). Objek DB berikut
--   DISEDIAKAN SEBAGAI LAPISAN OPSIONAL yang SELARAS dengan schema
--   aktual (tb_anggota, tb_buku, tb_peminjaman) dan aturan bisnis
--   (maks 2 buku, denda Rp10.000/hari, status mengikuti alur V5).
--
--   ENFORCEMENT:
--     * ADITIF / backward-compatible — hanya membuat objek baru,
--       TIDAK mengubah tabel, kolom, trigger, atau data yang ada.
--     * Tidak dipanggil oleh kode aplikasi saat ini, sehingga tidak
--       mengubah behaviour PHP yang sudah benar.
--     * Aman dieksekusi berulang (DROP ... IF EXISTS lalu CREATE).
--
-- Daftar objek:
--   SP  : sp_pinjam_buku(p_anggota, p_buku, p_tgl_kembali)
--         sp_ajukan_pengembalian(p_peminjaman)
--         sp_approve_pengembalian(p_peminjaman, p_kondisi, p_catatan, p_oleh)
--   FN  : fn_hitung_denda(p_tgl_jatuh_tempo, p_tgl_kembali)
--         fn_total_peminjaman_aktif(p_anggota)
-- =============================================================

-- 1) ---------- FUNCTION: fn_hitung_denda ----------
-- Menghitung denda keterlambatan (Rp) = selisih hari × 10000.
-- Denda 0 jika dikembalikan sebelum/tepat jatuh tempo.
DROP FUNCTION IF EXISTS `fn_hitung_denda`;
DELIMITER $$
CREATE FUNCTION `fn_hitung_denda`(
    p_tgl_jatuh_tempo DATE,
    p_tgl_dikembalikan DATE
) RETURNS DECIMAL(10,2)
    DETERMINISTIC
    READS SQL DATA
BEGIN
    DECLARE v_selisih INT DEFAULT 0;
    DECLARE v_tgl DATE;
    SET v_tgl = COALESCE(p_tgl_dikembalikan, CURDATE());
    IF p_tgl_jatuh_tempo IS NULL THEN
        RETURN 0.00;
    END IF;
    SET v_selisih = DATEDIFF(v_tgl, p_tgl_jatuh_tempo);
    IF v_selisih <= 0 THEN
        RETURN 0.00;
    END IF;
    RETURN CAST(v_selisih * 10000 AS DECIMAL(10,2));
END$$
DELIMITER ;

-- 2) ---------- FUNCTION: fn_total_peminjaman_aktif ----------
-- Jumlah peminjaman AKTIF seorang anggota (dipinjam, terlambat,
-- menunggu pengembalian, ditolak) sesuai aturan hitung_buku_dipinjam().
DROP FUNCTION IF EXISTS `fn_total_peminjaman_aktif`;
DELIMITER $$
CREATE FUNCTION `fn_total_peminjaman_aktif`(
    p_id_anggota INT UNSIGNED
) RETURNS INT
    DETERMINISTIC
    READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT COUNT(*) INTO v_total
    FROM `tb_peminjaman`
    WHERE `id_anggota` = p_id_anggota
      AND `status` IN ('dipinjam','terlambat','menunggu_pengembalian','ditolak');
    RETURN v_total;
END$$
DELIMITER ;

-- 3) ---------- PROCEDURE: sp_pinjam_buku ----------
-- Meminjam buku dengan validasi: anggota aktif, stok tersedia,
-- belum meminjam buku yang sama, dan belum melebihi batas 2 buku.
-- Menggunakan transaction + row lock (FOR UPDATE) agar aman.
-- OUT: p_kode_peminjaman (kode yang dibuat) & p_id_peminjaman.
DROP PROCEDURE IF EXISTS `sp_pinjam_buku`;
DELIMITER $$
CREATE PROCEDURE `sp_pinjam_buku`(
    IN  p_id_anggota INT UNSIGNED,
    IN  p_id_buku    INT UNSIGNED,
    IN  p_tanggal_kembali DATE,
    IN  p_keterangan TEXT,
    OUT p_kode_peminjaman VARCHAR(30),
    OUT p_id_peminjaman  INT UNSIGNED,
    OUT p_berhasil        TINYINT
)
BEGIN
    DECLARE v_stok INT DEFAULT 0;
    DECLARE v_judul VARCHAR(255);
    DECLARE v_prefix VARCHAR(30);
    DECLARE v_seq INT DEFAULT 0;
    DECLARE v_status_anggota ENUM('aktif','nonaktif');
    DECLARE v_tanggal_pinjam DATE DEFAULT CURDATE();
    DECLARE v_kembali DATE;

    SET p_berhasil = 0;
    SET p_kode_peminjaman = NULL;
    SET p_id_peminjaman = NULL;

    -- Validasi anggota
    SELECT `status` INTO v_status_anggota
    FROM `tb_anggota` WHERE `id_anggota` = p_id_anggota;
    IF v_status_anggota IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Anggota tidak ditemukan.';
    END IF;
    IF v_status_anggota <> 'aktif' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Akun anggota tidak aktif.';
    END IF;

    -- Validasi stok (data awal, akan dicek ulang di transaksi)
    SELECT `stok_buku`, `judul_buku` INTO v_stok, v_judul
    FROM `tb_buku` WHERE `id_buku` = p_id_buku;
    IF v_stok IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Buku tidak ditemukan.';
    END IF;

    SET v_kembali = COALESCE(p_tanggal_kembali, DATE_ADD(CURDATE(), INTERVAL 14 DAY));

    START TRANSACTION;

    -- Kunci baris anggota (serialisasi permintaan paralel)
    SELECT `id_anggota` FROM `tb_anggota`
    WHERE `id_anggota` = p_id_anggota FOR UPDATE;

    -- Cek peminjaman ganda buku yang sama
    IF EXISTS (
        SELECT 1 FROM `tb_peminjaman`
        WHERE `id_anggota` = p_id_anggota AND `id_buku` = p_id_buku
          AND `status` IN ('dipinjam','terlambat','menunggu_pengembalian','ditolak')
    ) THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Anda sudah meminjam buku ini dan belum mengembalikannya.';
    END IF;

    -- Cek batas maksimal 2 buku
    IF fn_total_peminjaman_aktif(p_id_anggota) >= 2 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batas maksimal peminjaman (2 buku) tercapai.';
    END IF;

    -- Kurangi stok (hanya jika tersedia)
    UPDATE `tb_buku` SET `stok_buku` = `stok_buku` - 1
    WHERE `id_buku` = p_id_buku AND `stok_buku` > 0;
    IF ROW_COUNT() = 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok buku habis.';
    END IF;

    -- Generate kode: PMJ-YYYYMMDD-NNN (mirip generate_kode_peminjaman PHP)
    SET v_prefix = CONCAT('PMJ-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-');
    SELECT (COUNT(*) + 1) INTO v_seq
    FROM `tb_peminjaman` WHERE `kode_peminjaman` LIKE CONCAT(v_prefix, '%');
    SET p_kode_peminjaman = CONCAT(v_prefix, LPAD(v_seq, 3, '0'));

    INSERT INTO `tb_peminjaman`
        (`kode_peminjaman`, `id_anggota`, `id_buku`, `id_user`,
         `tanggal_pinjam`, `tanggal_kembali`, `status`, `denda`, `keterangan`)
    VALUES
        (p_kode_peminjaman, p_id_anggota, p_id_buku, NULL,
         v_tanggal_pinjam, v_kembali, 'dipinjam', 0, COALESCE(p_keterangan, NULL));

    SET p_id_peminjaman = LAST_INSERT_ID();
    SET p_berhasil = 1;

    COMMIT;
END$$
DELIMITER ;

-- 4) ---------- PROCEDURE: sp_ajukan_pengembalian ----------
-- Mengubah status 'dipinjam'/'terlambat'/'ditolak' → 'menunggu_pengembalian'
-- dan mencatat tanggal diajukan. Hanya peminjaman milik anggota tsb.
DROP PROCEDURE IF EXISTS `sp_ajukan_pengembalian`;
DELIMITER $$
CREATE PROCEDURE `sp_ajukan_pengembalian`(
    IN p_id_peminjaman INT UNSIGNED,
    IN p_id_anggota    INT UNSIGNED,
    IN p_catatan       TEXT,
    OUT p_berhasil     TINYINT
)
BEGIN
    DECLARE v_status VARCHAR(30);
    SET p_berhasil = 0;

    SELECT `status` INTO v_status
    FROM `tb_peminjaman`
    WHERE `id_peminjaman` = p_id_peminjaman AND `id_anggota` = p_id_anggota
    LIMIT 1;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data peminjaman tidak ditemukan.';
    END IF;

    IF v_status NOT IN ('dipinjam','terlambat','ditolak') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Status peminjaman tidak dapat diajukan.';
    END IF;

    UPDATE `tb_peminjaman`
    SET `status` = 'menunggu_pengembalian',
        `tanggal_diajukan` = NOW(),
        `catatan_pengembalian` = COALESCE(p_catatan, `catatan_pengembalian`)
    WHERE `id_peminjaman` = p_id_peminjaman AND `id_anggota` = p_id_anggota;

    SET p_berhasil = 1;
END$$
DELIMITER ;

-- 5) ---------- PROCEDURE: sp_approve_pengembalian ----------
-- Menyetujui permintaan pengembalian:
--   * menghitung denda via fn_hitung_denda
--   * status → 'dikembalikan', tanggal_dikembalikan = CURDATE()
--   * stok buku bertambah 1
-- Meniru alur approval Admin (tidak mengubah logika Admin; objek
-- ini murni opsional dan identik hasilnya dengan query admin).
DROP PROCEDURE IF EXISTS `sp_approve_pengembalian`;
DELIMITER $$
CREATE PROCEDURE `sp_approve_pengembalian`(
    IN p_id_peminjaman INT UNSIGNED,
    IN p_kondisi       VARCHAR(30),
    IN p_catatan       TEXT,
    IN p_diproses_oleh INT UNSIGNED,
    OUT p_denda        DECIMAL(10,2),
    OUT p_berhasil     TINYINT
)
BEGIN
    DECLARE v_id_buku INT UNSIGNED;
    DECLARE v_tgl_jatuh DATE;
    DECLARE v_status VARCHAR(30);
    SET p_berhasil = 0;
    SET p_denda = 0;

    SELECT `id_buku`, `tanggal_kembali`, `status` INTO v_id_buku, v_tgl_jatuh, v_status
    FROM `tb_peminjaman` WHERE `id_peminjaman` = p_id_peminjaman
    LIMIT 1;

    IF v_id_buku IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data peminjaman tidak ditemukan.';
    END IF;

    IF v_status <> 'menunggu_pengembalian' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Peminjaman tidak dalam status menunggu pengembalian.';
    END IF;

    SET p_denda = fn_hitung_denda(v_tgl_jatuh, CURDATE());

    START TRANSACTION;

    UPDATE `tb_peminjaman`
    SET `status` = 'dikembalikan',
        `tanggal_dikembalikan` = CURDATE(),
        `tanggal_disetujui` = NOW(),
        `kondisi_saat_kembali` = COALESCE(p_kondisi, `kondisi_saat_kembali`),
        `catatan_pengembalian` = COALESCE(p_catatan, `catatan_pengembalian`),
        `denda` = p_denda,
        `status_denda` = IF(p_denda > 0, 'belum_dibayar', 'lunas'),
        `diproses_oleh` = p_diproses_oleh
    WHERE `id_peminjaman` = p_id_peminjaman;

    UPDATE `tb_buku` SET `stok_buku` = `stok_buku` + 1 WHERE `id_buku` = v_id_buku;

    SET p_berhasil = 1;

    COMMIT;
END$$
DELIMITER ;