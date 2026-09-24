# Sistem Informasi Perpustakaan Daerah (Versi 1)

Sistem informasi manajemen perpustakaan daerah berbasis PHP Native + MySQL/MariaDB. Dibangun tanpa framework dengan pendekatan MVC-like, repository pattern, dan keamanan berlapis. **Versi 1.0**

## Fitur Utama

### Anggota
- Registrasi & Login dengan Bcrypt hashing
- Katalog buku dengan pencarian & filter kategori
- Peminjaman buku (maks. 2 buku)
- Pengembalian buku dengan sistem approval
- Rating & komentar buku
- Sistem badge pencapaian (Anggota Baru, Pembaca Tekun, Tepat Waktu)
- Notifikasi real-time (AJAX polling 30 detik)
- Dark mode toggle

### Admin & Petugas
- Dashboard statistik
- CRUD buku, kategori, penerbit, rak
- Approval pengembalian dengan kalkulasi denda
- CMS: berita, pengumuman, banner, layanan, FAQ, jam buka
- Manajemen anggota & user sistem
- Pengaturan sistem

### Keamanan
- Bcrypt password hashing
- CSRF token rotation (30 menit)
- Prepared statements (mysqli + PDO)
- Rate limiting (5 percobaan / 15 menit)
- Session hardening (httponly, samesite)
- HTTP security headers (.htaccess)
- IDOR prevention (session-based queries)
- File upload validation

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Bootstrap 5.3.2 |
| Icons | Font Awesome 6.5.1, Bootstrap Icons |
| JavaScript | jQuery 3.7.1, Vanilla JS ES6+ |
| Alerts | SweetAlert2 11 |
| Tables | DataTables 1.13.7 |
| Fonts | Inter, Poppins (Google Fonts) |
| Backend | PHP 8.0+ (strict_types) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| PDF | dompdf/dompdf ^3.1 |
| Server | Apache 2.4+ (XAMPP) |

## Requirements

- PHP 8.0 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.3+
- Apache 2.4+ dengan mod_rewrite
- XAMPP (local development)

## Instalasi

### 1. Clone/Copy Project

```
Copy folder ke: C:\xampp\htdocs\perpustakaan-daerah V5\
```

### 2. Start Services

Buka XAMPP Control Panel, start **Apache** dan **MySQL**.

### 3. Buat Database

Buka phpMyAdmin (`http://localhost/phpmyadmin`):

1. Klik **New** di sidebar
2. Nama database: `perpustakaan_daerah`
3. Collation: `utf8mb4_unicode_ci`
4. Klik **Create**

### 4. Import Database

1. P database `perpustakaan_daerah`
2. Tab **Import**
3. Pilih file: `database.sql`
4. Klik **Go**

### 5. Jalankan Aplikasi

Buka browser:

```
http://localhost/perpustakaan-daerah%20V5/
```

### 6. Login

| Role | Email | Password |
|------|-------|----------|
| Admin | admin | admin123 |
| Petugas | petugas | admin123 |
| Anggota | ahmad@email.com | admin123 |

## Struktur Projek

```
perpustakaan-daerah V5/
├── config/           # Konfigurasi (DB, app)
├── app/              # Repository layer
├── api/              # JSON API endpoints
├── partials/         # Reusable UI components
├── assets/           # CSS, JS, images
├── uploads/          # User uploads (covers, profiles)
├── docs/             # Dokumentasi teknis
├── *.php             # Halaman utama
└── *.sql             # Database scripts
```

## Database

20 tabel dengan relasi FOREIGN KEY, stored procedures, dan triggers:

| Table | Deskripsi |
|-------|-----------|
| tb_user | User sistem (admin/petugas) |
| tb_anggota | Data anggota |
| tb_buku | Katalog buku |
| tb_kategori | Kategori buku |
| tb_penerbit | Data penerbit |
| tb_rak | Lokasi rak |
| tb_peminjaman | Transaksi peminjaman |
| tb_sesi_login | Sesi login persisten |
| tb_rating | Rating & komentar |
| tb_badge | Definisi badge |
| tb_badge_anggota | Badge teraih |
| tb_notifikasi | Notifikasi |
| tb_permission | Hak akses role |
| tb_banner | Banner CMS |
| tb_berita | Berita |
| tb_pengumuman | Pengumuman |
| tb_layanan | Layanan |
| tb_faq | FAQ |
| tb_jam_buka | Jam operasional |
| tb_pengaturan | Pengaturan sistem |

## Deployment

### Local (XAMPP)
```
http://localhost/perpustakaan-daerah%20V5/
```

### Produksi (InfinityFree)
1. Buat akun InfinityFree
2. Upload files ke `htdocs/`
3. Import database via phpMyAdmin
4. Edit `config/database.php` (kredensial hosting)
5. Edit `config/app.php` (APP_URL produksi)

Lihat `docs/12_deployment.md` untuk panduan lengkap.

## Dokumentasi

| Dokumen | Deskripsi |
|---------|-----------|
| [ERD](docs/01_erd.md) | Entity Relationship Diagram |
| [Use Case](docs/02_use_case_diagram.md) | Diagram Use Case |
| [User Flow](docs/03_user_flow.md) | Alur pengguna |
| [Admin Flow](docs/04_admin_flow.md) | Alur administrator |
| [Flowchart](docs/05_flowchart.md) | Diagram alir |
| [Algoritma](docs/06_algoritma.md) | Pseudocode algoritma |
| [Mockup UI](docs/07_mockup_ui.md) | Wireframe UI |
| [Sequence Diagram](docs/08_sequence_diagram.md) | Interaksi sistem |
| [Arsitektur](docs/09_architecture_diagram.md) | Arsitektur sistem |
| [Keamanan](docs/10_keamanan_sistem.md) | Dokumentasi keamanan |
| [Pengujian](docs/11_pengujian_sistem.md) | Test cases |
| [Deployment](docs/12_deployment.md) | Panduan deployment |

### Generate PDF

Jalankan dari browser untuk menghasilkan dokumentasi PDF:

```
http://localhost/perpustakaan-daerah%20V5/generate_pdf.php
```

Output: `DOKUMENTASI_PERPUSTAKAAN_DAERAH_VERSI1.pdf` (A4, 18 chapter)

## Changelog

### V1.0 (2 September 2026)
- Redesign total Bootstrap 5.3.2
- Dark Mode (CSS Custom Properties)
- Notifikasi Real-Time (AJAX polling)
- Sistem Rating & Komentar
- Sistem Badge Pencapaian
- CMS Lengkap (Berita, FAQ, Banner, dll)
- 20 Database Tables
- Prepared Statements
- Rate Limiting & CSRF Protection
- Mobile-First Responsive Design
- Design Token System
- Enterprise Theme

## License

© 2026 Perpustakaan Daerah. All rights reserved.
