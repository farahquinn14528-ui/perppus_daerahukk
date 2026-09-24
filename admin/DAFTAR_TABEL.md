# DAFTAR TABEL — Database Sistem Informasi Perpustakaan Daerah V5

Dokumen ini menjelaskan fungsi **setiap tabel** dalam database `perpustakaan_daerah`, dikelompokkan menurut fungsinya. Berguna sebagai panduan saat sidang/presentasi.

**Total tabel: 20** — 16 tabel inti di `database.sql` + 4 tabel fitur tambahan dari file `migrasi_*.sql`.

---

## A. Tabel Data Utama

| Tabel | Fungsi |
|-------|--------|
| `tb_anggota` | **Data anggota perpustakaan.** Menyimpan identitas, email, password (Bcrypt), role (anggota/admin), kelas/jurusan, kontak, dan status aktif/nonaktif. Dibuat saat registrasi. |
| `tb_buku` | **Data koleksi buku.** Judul, ISBN, pengarang, penerbit, kategori, rak, tahun terbit, jumlah & stok, kondisi, foto, sinopsis, serta penanda populer/baru. `stok_buku` berkurang saat dipinjam. |
| `tb_user` | **Data petugas & admin pengelola.** Login panel admin, dibedakan dengan level (`admin`/`petugas`). |
| `tb_kategori` | **Kategori buku** (mis. Fiksi, Teknologi, Agama). Buku berelasi ke tabel ini. |
| `tb_penerbit` | **Data penerbit buku** (nama, alamat, telepon, email). |
| `tb_rak` | **Lokasi rak fisik buku** di perpustakaan (nama rak & lokasi). |
| `tb_peminjaman` | **Data peminjaman & pengembalian buku.** Mencatat siapa meminjam buku apa, tanggal pinjam/kembali, status (`dipinjam`, `terlambat`, `menunggu_pengembalian`, `dikembalikan`, `ditolak`), denda, approval pengembalian (siapa proses, kapan, kondisi buku). **Tabel terpenting untuk fitur peminjaman.** |

---

## B. Tabel Autentikasi & Keamanan

| Tabel | Fungsi |
|-------|--------|
| `tb_sesi_login` | **Login persistent ("remember me").** Menyimpan token hash sesi login anggota agar bisa login otomatis saat kembali. Token diperbarui & dihapus pada logout. |
| `tb_permission` | **Matriks hak akses (role management).** Menentukan modul apa yang boleh diakses role admin/petugas/anggota dan level aksesnya (baca/tulis/semua). |

---

## C. Tabel Konten & CMS (dikelola admin)

| Tabel | Fungsi |
|-------|--------|
| `tb_banner` | **Banner/carousel di halaman beranda** (judul, subjudul, gambar, tombol). |
| `tb_berita` | **Berita** yang ditampilkan di halaman Informasi. |
| `tb_pengumuman` | **Pengumuman** dengan tipe info/peringatan/penting. |
| `tb_layanan` | **Daftar layanan perpustakaan** (judul, deskripsi, ikon). |
| `tb_faq` | **Pertanyaan & jawaban** yang sering ditanyakan (halaman Bantuan). |
| `tb_jam_buka` | **Jam buka perpustakaan** (hari, jam buka/tutup) — tampil di footer. |
| `tb_pengaturan` | **Pengaturan umum (key–value).** Menyimpan nama aplikasi, alamat, telepon, email, logo — dipakai di seluruh situs (header/footer/kontak). |

---

## D. Tabel Fitur Tambahan (dari migrasi)

| Tabel | Fungsi |
|-------|--------|
| `tb_badge` | **Definisi badge/pencapaian** (nama, ikon, warna, metrik, syarat). Metrik: `total_pinjam`, `total_review`, `tepat_waktu`. |
| `tb_badge_anggota` | **Badge yang sudah diraih anggota.** Menghubungkan anggota dengan badge (banyak-ke-banyak). Diraih otomatis berdasarkan aktivitas nyata. |
| `tb_rating` | **Rating & komentar buku.** Anggota memberi skor 1–5 dan komentar per buku. Satu anggota boleh satu rating per buku (update bila ada). |
| `tb_notifikasi` | **Notifikasi sistem** untuk anggota & petugas. Mencatat event (peminjaman, pengembalian, registrasi, dll), target penerima, dan status dibaca/belum dibaca. |

---

## Relasi antar tabel (ringkas)

```
tb_user ─┐
         ├── tb_peminjaman ──┬── tb_buku ──┬── tb_kategori
tb_anggota ─┘                │            ├── tb_penerbit
                             │            └── tb_rak
   ├── tb_badge_anggota ── tb_badge
   ├── tb_rating ── tb_buku
   ├── tb_sesi_login
   └── tb_notifikasi
```

Keterangan:
- `tb_peminjaman` = jembatan antara **anggota** (siapa) dan **buku** (apa), dengan **tb_user** (petugas yang memproses).
- `tb_buku` berelasi ke **kategori**, **penerbit**, dan **rak**.
- `tb_badge_anggota` & `tb_rating` menghubungkan **anggota** dengan **badge** dan **buku**.
- Tabel CMS (`tb_banner` s.d. `tb_pengaturan`) berdiri sendiri — konten tampilan saja, tidak berelasi ke data transaksi.

---

## Asal file pembuatan tabel

| Tabel | Dibuat di |
|-------|-----------|
| `tb_user`, `tb_kategori`, `tb_penerbit`, `tb_rak`, `tb_buku`, `tb_anggota`, `tb_peminjaman`, `tb_sesi_login`, `tb_permission` | `database.sql` |
| `tb_banner`, `tb_berita`, `tb_pengumuman`, `tb_layanan`, `tb_faq`, `tb_jam_buka`, `tb_pengaturan` | `database.sql` (atau `migrasi_v5.sql` untuk upgrade) |
| `tb_badge`, `tb_badge_anggota` | `migrasi_badge_informasi.sql` |
| `tb_rating` | `migrasi_rating_komentar.sql` |
| `tb_notifikasi` | `database.sql` (+ kolom `id_target_staff` di `migrasi_notifikasi.sql`) |