# DAFTAR FUNGSI FILE — Sistem Informasi Perpustakaan Daerah V5

Dokumen ini merangkum fungsi setiap file dalam proyek beserta penjelasan singkat, dikelompokkan per folder. Berguna sebagai panduan menjelaskan struktur proyek saat sidang/presentasi.

**Pola arsitektur proyek (sekilas):**
- **MVC-like + Repository pattern** — halaman (View/Controller) mengambil data lewat repository di `app/`, bukan query langsung.
- **Konfigurasi terpusat** — `config/database.php` (DB) dan `config/app.php` (konstanta, security, helper).
- **Keamanan berlapis** — CSRF token, rate limiting, prepared statement, session hardening, IDOR prevention.

---

## Apa itu CRUD dan bagaimana diterapkan di proyek ini

**CRUD** adalah empat operasi dasar pada data (tabel database):
- **C — Create**  : menambah data baru (INSERT INTO)
- **R — Read**    : membaca/menampilkan data (SELECT)
- **U — Update**  : mengubah data yang sudah ada (UPDATE)
- **D — Delete**  : menghapus data (DELETE FROM)

Di proyek ini CRUD dibagi menjadi dua lapisan:
1. **Lapisan data (repository `app/`)** — di sinilah query SELECT/INSERT/UPDATE/DELETE ditulis. Halaman hanya memanggil fungsinya, jadi SQL tidak berulang.
2. **Lapisan halaman (root)** — halaman PHP menangkap input dari form lalu memanggil repository, dan menampilkan hasilnya kepada pengguna.

> Catatan penting untuk penguji: situs ini adalah **website publik untuk anggota**, sehingga mayoritas operasi adalah **Read** (halaman katalog, detail, riwayat, informasi, dsb. hanya membaca). Operasi **Create/Update/Delete** hanya muncul di tempat yang memang wajib (registrasi, peminjaman, pengembalian, profil, rating, notifikasi, login) dan **tidak ada halaman yang menghapus data penting (DELETE) pada situs publik** — penghapusan hanya terjadi pada data teknis seperti token sesi login.

### Peta CRUD per file

| File | C | R | U | D | Penjelasan CRUD |
|------|---|---|---|---|-----------------|
| `register.php` | C | | | | **Create** anggota baru (`INSERT INTO tb_anggota`) saat pendaftaran. |
| `proses_pinjam.php` | C | | U | | **Create** data peminjaman baru (`INSERT INTO tb_peminjaman`) + **Update** stok buku dikurangi (`UPDATE tb_buku SET stok_buku = stok_buku - 1`). |
| `pengembalian.php` | | R | U | | **Read** data peminjaman aktif lalu **Update** status pengembalian (`UPDATE tb_peminjaman SET status='menunggu_pengembalian'`). |
| `akun.php` | | R | U | | **Read** data akun session + **Update** profil anggota (nama, email, telepon, alamat, password) — `UPDATE tb_anggota`. |
| `app/ratings.php` | C | R | U | | Repository rating: **Create/Update idempoten** (INSERT ... ON DUPLICATE KEY UPDATE) + **Read** ringkasan & daftar rating. |
| `app/notifications.php` | C | R | U | | Repository notifikasi: **Create** notifikasi baru, **Read** daftar & jumlah belum dibaca, **Update** status dibaca (`UPDATE tb_notifikasi SET dibaca_at`). |
| `api/notifikasi.php` | | R | U | | Endpoint AJAX: **Read** daftar notifikasi (`list`), **Update** status dibaca (`mark_read`, `mark_all_read`). |
| `config/app.php` | C | R | U | D | Session & remember-me: **Create/Update/Delete** token sesi login (`tb_sesi_login`) + **Update** status pinjaman otomatis menjadi terlambat. Ini satu-satunya DELETE di situs publik, dan hanya data teknis sesi. |
| Semua halaman lainnya (`index`, `landing`, `katalog_buku`, `cari`, `detail_buku`, `badge`, `riwayat_peminjaman`, `informasi`, `help`, `generate_pdf`) | | R | | | Murni **Read** — hanya menampilkan data lewat repository, tidak pernah mengubah database. |

---

## Root (halaman utama)

| File | Fungsi & Penjelasan |
|------|---------------------|
| `index.php` | **Entry point / router utama.** Mendeteksi halaman aktif otomatis dan menampilkan home. Dilihat penguji sebagai "titik masuk" aplikasi — semua akses dimulai dari sini, memuat konfigurasi, session, lalu memutuskan tampilkan `landing.php` atau konten lain. |
| `landing.php` | **Beranda (landing page).** Hero section, pencarian cepat, statistik, banner CMS, dan daftar buku populer & terbaru. Menarik data dari `app/catalog.php` dan `app/content.php`. |
| `login.php` | **Form login anggota & admin.** Verifikasi via email + password Bcrypt, dilindungi rate-limiting (5 percobaan/15 menit) dan CSRF token. Memakai helper `csrf_field()`, `check_login_rate_limit()`, `try_remember_login()` dari `config/app.php`. |
| `register.php` | **Pendaftaran anggota publik.** Validasi input, password di-hash Bcrypt, disimpan ke `tb_anggota`; nomor anggota dibuat otomatis (`generate_nomor_anggota`). |
| `logout.php` | **Logout.** Menghapus session dan cookie remember-me, lalu redirect ke login. |
| `akun.php` | **Dashboard anggota.** Kuota peminjaman, buku aktif, badge, data akun, notifikasi — gambaran ringkas aktivitas pengguna. |
| `badge.php` | **Halaman badge/pencapaian.** Menampilkan badge yang sudah diraih & yang masih terkunci (dari `app/badges.php`). |
| `cari.php` | **Pencarian buku.** Query `buku_cari()` di `app/catalog.php`; hasil tampil dalam bentuk kartu buku. |
| `katalog_buku.php` | **Katalog semua buku** dengan filter kategori & sorting. Memakai `buku_katalog()` dan `kategori_list()`. |
| `detail_buku.php` | **Detail lengkap buku** — cover, sinopsis, data penerbit/rak, rata-rata rating, komentar, dan form pinjam. Data dari `buku_detail()`, `buku_terkait()`, `rating_ringkasan()`. |
| `pinjam.php` | **Konfirmasi peminjaman.** Menampilkan form sebelum data disimpan; memeriksa kuota dan status pinjaman aktif. |
| `proses_pinjam.php` | **Proses simpan peminjaman** ke database (POST). Menerapkan aturan bisnis: maks. `MAX_BORROWED_BOOKS`, kode peminjaman otomatis, notifikasi dibuat. |
| `pengembalian.php` | **Pengajuan pengembalian buku.** Form untuk mengembalikan buku yang dipinjam, status menjadi menunggu approval admin. |
| `riwayat_peminjaman.php` | **Riwayat peminjaman anggota** ditampilkan sebagai timeline/premium cards — status lama dan baru. |
| `help.php` | **Pusat bantuan / panduan** layanan perpustakaan untuk pengguna. |
| `informasi.php` | **Berita & pengumuman CMS** — daftar aktif plus tampilan detail per item (dari `app/content.php`). |
| `generate_pdf.php` | **Generator PDF dokumentasi (18 chapter).** Hanya berisi logika render; template HTML dipisah ke `partials/pdf_dokumentasi.php`. Output: `DOKUMENTASI_PERPUSTAKAAN_DAERAH_VERSI1.pdf`. |

---

## `api/` (endpoint JSON)

| File | Fungsi & Penjelasan |
|------|---------------------|
| `notifikasi.php` | **Endpoint AJAX notifikasi (JSON).** Aksi: `list` (daftar + unread count), `mark_read`, `mark_all_read`. Auth wajib login; aksi mutasi butuh CSRF di header `X-CSRF-Token`; IDOR dicegah dengan id anggota dari session. |

---

## `app/` (repository — sumber data terpusat)

| File | Fungsi & Penjelasan |
|------|---------------------|
| `anggota.php` | **Repository data anggota (`tb_anggota`).** `anggota_by_id()` untuk detail profil, `anggota_email_dipakai()` untuk cek email saat registrasi agar tidak duplikat. |
| `badges.php` | **Repository badge (`tb_badge`, `tb_badge_anggota`).** Badge diraih **otomatis** dari aktivitas nyata: total pinjaman, jumlah review, pengembalian tepat waktu. Fungsi utama: `badge_sync()`, `badge_anggota()`, `badge_koleksi_pribadi()`. |
| `catalog.php` | **Repository buku & katalog (`tb_buku`, `tb_kategori`, `tb_penerbit`, `tb_rak`).** Semua query buku terpusat di sini: `buku_detail()`, `buku_cari()`, `buku_katalog()`, `buku_populer()`, `buku_terbaru()`, `buku_rekomendasi()`. Menghindari duplikasi SQL antar halaman. |
| `content.php` | **Repository konten CMS.** Mengambil data `tb_pengaturan`, `tb_banner`, `tb_berita`, `tb_pengumuman`, `tb_faq`, `tb_jam_buka`. Fungsi: `cms_pengaturan()`, `cms_banner_aktif()`, `cms_berita()`, `cms_faq()`, dll. |
| `loans.php` | **Repository peminjaman & pengembalian + peta status.** Fungsi: `loan_status_map()` (label/class), `loan_anggota_aktif()`, `loan_riwayat()`, `loan_statistik()`, `loan_terbaru()`. Aturan bisnis denda dipakai bersama `config/app.php`. |
| `notifications.php` | **Repository notifikasi (`tb_notifikasi`).** Event: peminjaman, pengajuan/persetujuan/penolakan pengembalian, registrasi, jatuh tempo. Fungsi: `notif_tambah()`, `notif_list()`, `notif_unread_count()`, `notif_mark_read()`, `notif_sync_events()`. |
| `ratings.php` | **Repository rating & komentar buku (`tb_rating`).** `rating_simpan()` (save/update), `rating_ringkasan()` (rata-rata + distribusi bintang), `rating_list()`, plus helper tampilan bintang `rating_stars_display()`. |

---

## `config/` (konfigurasi)

| File | Fungsi & Penjelasan |
|------|---------------------|
| `app.php` | **Konfigurasi aplikasi & helper umum** (~25+ fungsi). Berisi konstanta (nama aplikasi, `MAX_BORROWED_BOOKS`, `DENDA_PER_HARI`), session, security (CSRF rotate, rate-limit, session regen), auth (`is_logged_in`, `require_login`), helper format (`tgl_id`, `format_rupiah`), dan `buku_cover()`. |
| `database.php` | **Single source of truth konfigurasi database.** Variabel `$DB_ENV, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT` dan koneksi mysqli `$koneksi` yang dipakai semua halaman. Satu-satunya tempat yang perlu diubah bila pindah server. |

---

## `partials/` (komponen UI reusable)

| File | Fungsi & Penjelasan |
|------|---------------------|
| `header.php` | Header & navigasi publik (logo, menu, dark mode toggle, tombol login). Diletakkan terpisah agar konsisten di semua halaman. |
| `footer.php` | Footer enterprise (kontak, tautan, informasi). |
| `drawer.php` | Drawer / menu kiri (hamburger) untuk pengguna yang login. |
| `book_card.php` | Kartu buku reusable — dipakai katalog, hasil cari, rekomendasi (memakai `buku_cover()`). |
| `landing_book_card.php` | Variasi kartu buku khusus landing page (tag populer/baru, animasi reveal). |
| `library_video.php` | Section video pengenalan perpustakaan di landing page. |
| `notifications.php` | Komponen lonceng notifikasi + dropdown panel (dipakai di header/akun). |
| `pdf_dokumentasi.php` | Template HTML 18-chapter PDF. Satu-satunya fungsi: `render_dokumentasi_html($koneksi, $dbConnected)`. Dipisah dari `generate_pdf.php` agar file logika tetap pendek. |

---

## `assets/` (CSS & JavaScript)

| Path | Fungsi & Penjelasan |
|------|---------------------|
| `assets/css/design-tokens.css` | Variabel desain: warna, spacing, radius — dipakai seluruh stylesheet lain. |
| `assets/css/style.css`, `v5.css`, `enterprise.css` | Stylesheet utama (base + gaya V5 + gaya enterprise). |
| `assets/css/themes/dark.css` | Tema dark mode. |
| `assets/css/pages/` | CSS spesifik per halaman (mis. `landing.css`). |
| `assets/css/components/` | CSS komponen: buttons, cards, forms, modals, tables, toasts, notifications, skeleton, drawer. |
| `assets/css/layout/` | CSS layout: navbar, footer. |
| `assets/js/app.js` | JavaScript utama (inisialisasi, helper umum). |
| `assets/js/pages/` | JS per halaman: `landing.js`, `library-video.js`. |
| `assets/js/ui/` | JS komponen UI: drawer, notifications, theme manager (dark/light), toast. |

---

## Lainnya

| File | Fungsi & Penjelasan |
|------|---------------------|
| `.htaccess` | Konfigurasi Apache: rewrite engine untuk routing, security headers, batas upload, dll. |
| `database.sql` | Skema database lengkap (keseluruhan tabel inti). |
| `migrasi_*.sql` | Script migrasi untuk tabel baru berikutnya (mis. `tb_badge`, `tb_rating`, `tb_notifikasi`, `tb_pengaturan`). Dipakai saat upgrade dari versi lama. |
| `composer.json` / `composer.lock` | Dependency manager PHP. Satu dependency utama: `dompdf/dompdf` (untuk generate PDF). |
| `README.md` | Dokumentasi proyek: fitur, tech stack, keamanan, cara menjalankan. |
| `docs/` | Dokumentasi tambahan (desain DB, panduan, dll). |
| `PERENCANAAN_PROYEK_PERPUSTAKAAN_DAERAH_V5.md` | Perencanaan proyek. |
| `DOKUMENTASI_PERPUSTAKAAN_DAERAH_VERSI1.pdf` | PDF hasil generate dari `generate_pdf.php` (18 chapter). |
| `DOKUMENTASI_DATABASE_PERPUSTAKAAN_DAERAH_V5.pdf` | Dokumentasi database dalam bentuk PDF. |
| `DAFTAR_FUNGSI_FILE.md` | Dokumen ini — daftar fungsi setiap file. |