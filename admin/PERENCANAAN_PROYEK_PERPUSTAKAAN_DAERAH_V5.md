# PERENCANAAN PROYEK SISTEM INFORMASI PERPUSTAKAAN DAERAH V5

**Tanggal:** 2 September 2026
**Versi:** 5.0 (PRO MAX ENTERPRISE REDESIGN)
**Status:** Aktif dalam Pengembangan

---

## 1. RINGKASAN EKSEKUTIF

### 1.1 Latar Belakang
Sistem Informasi Perpustakaan Daerah V5 merupakan aplikasi web berbasis PHP Native yang dirancang untuk mengelola operasional perpustakaan daerah secara digital. Sistem ini menyediakan fitur lengkap mulai dari katalog buku, peminjaman, pengembalian, hingga manajemen konten (CMS).

### 1.2 Tujuan Proyek
- **Modernisasi** sistem perpustakaan konvensional menjadi digital
- **Efisiensi** proses peminjaman dan pengembalian buku
- **Akuntabilitas** melalui sistem denda dan badge pencapaian
- **Aksesibilitas** informasi perpustakaan melalui web responsif
- **Manajemen konten** dinamis melalui modul CMS

### 1.3 Ruang Lingkup
- Sistem peminjaman & pengembalian buku dengan approval workflow
- Manajemen anggota dengan sistem badge pencapaian
- CMS untuk banner, berita, pengumuman, layanan, FAQ
- Sistem notifikasi real-time
- Rating & komentar buku
- Laporan dan statistik

---

## 2. SPESIFIKASI TEKNOLOGI

### 2.1 Backend
| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| Bahasa | PHP | 8.0+ |
| Database | MySQL/MariaDB | 5.7+ / 10.3+ |
| Server | XAMPP/Laragon/WAMP | Apache + PHP + MySQL |
| Koneksi DB | mysqli + PDO | - |

### 2.2 Frontend
| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| CSS Framework | Bootstrap | 5.3.2 |
| Icon Library | Font Awesome | 6.5.1 |
| Icon Set | Bootstrap Icons | - |
| Font | Google Fonts (Poppins) | - |
| Alert | SweetAlert2 | 11 |
| Table | DataTables | 1.13.7 |
| DOM Manipulation | jQuery | 3.7.1 |

### 2.3 Keamanan
- CSRF Token (rotasi tiap 30 menit)
- Password Bcrypt (password_hash/password_verify)
- Prepared Statements (mysqli & PDO)
- XSS Protection (htmlspecialchars/helper e())
- Session Hardening (httponly, samesite=Lax, secure)
- Rate Limiting (5 attempt/15 menit lockout)
- Security Headers (X-Content-Type-Options, X-Frame-Options, dll)

---

## 3. ARSITEKTUR SISTEM

### 3.1 High-Level Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                      CLIENT (Browser)                       │
├─────────────────────────────────────────────────────────────┤
│  Bootstrap 5.3 │ jQuery │ SweetAlert2 │ DataTables │ FA 6  │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP/HTTPS
┌───────────────────────────▼─────────────────────────────────┐
│                    APACHE SERVER (XAMPP)                     │
├─────────────────────────────────────────────────────────────┤
│                     PHP 8.0+ NATIVE                         │
├─────────────┬─────────────┬─────────────┬───────────────────┤
│   config/   │    app/     │   api/      │   partials/       │
│  (app.php)  │  (repos)    │ (AJAX)      │  (components)     │
│  (database) │             │             │                   │
└─────────────┴──────┬──────┴─────────────┴───────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                    MySQL/MariaDB                            │
├─────────────────────────────────────────────────────────────┤
│  20 Tables │ Stored Procedures │ Functions │ Triggers       │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Layer Architecture
```
┌─────────────────────────────────────────┐
│           Presentation Layer            │
│  (PHP Pages + Bootstrap + JavaScript)   │
├─────────────────────────────────────────┤
│           Application Layer             │
│  (config/app.php - Business Rules)      │
├─────────────────────────────────────────┤
│           Repository Layer              │
│  (app/*.php - Data Access Objects)      │
├─────────────────────────────────────────┤
│           Database Layer                │
│  (MySQL/MariaDB + Stored Procedures)    │
└─────────────────────────────────────────┘
```

---

## 4. STRUKTUR DATABASE

### 4.1 Entity Relationship Diagram (ERD)

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   tb_user    │     │  tb_anggota  │     │   tb_buku    │
├──────────────┤     ├──────────────┤     ├──────────────┤
│ id_user (PK) │     │ id_anggota(PK│     │ id_buku (PK) │
│ nama_user    │     │ nomor_anggota│     │ isbn         │
│ username     │     │ nama_lengkap │     │ judul_buku   │
│ password     │     │ email        │     │ id_kategori  │──┐
│ level        │     │ password     │     │ id_penerbit  │──┤
│ foto         │     │ role         │     │ id_rak       │──┤
│ status       │     │ jenis_kelamin│     │ pengarang    │  │
│ created_at   │     │ kelas        │     │ tahun_terbit │  │
│ updated_at   │     │ jurusan      │     │ bahasa       │  │
└──────┬───────┘     │ alamat       │     │ jumlah_buku  │  │
       │             │ telepon      │     │ stok_buku    │  │
       │             │ foto         │     │ kondisi      │  │
       │             │ status       │     │ foto         │  │
       │             │ tanggal_daftar│    │ sinopsis     │  │
       │             └──────┬───────┘     │ populer      │  │
       │                    │             │ baru         │  │
       │                    │             └──────┬───────┘  │
       │                    │                    │          │
       │             ┌──────▼───────┐     ┌──────▼───────┐  │
       │             │tb_peminjaman │     │  tb_kategori │  │
       │             ├──────────────┤     ├──────────────┤  │
       │             │id_peminjaman │     │id_kategori(PK│◄─┘
       │             │kode_peminjaman│    │nama_kategori │
       │             │id_anggota(FK)│    │keterangan    │
       │             │id_buku (FK)  │    └──────────────┘
       │             │id_user (FK)  │
       │             │tanggal_pinjam│    ┌──────────────┐
       │             │tanggal_kembali│   │  tb_penerbit │
       │             │status        │    ├──────────────┤
       │             │denda         │    │id_penerbit(PK│
       │             │status_denda  │    │nama_penerbit │
       │             │diproses_oleh │    │alamat        │
       │             └──────────────┘    │telepon       │
       │                                 │email         │
       │             ┌──────────────┐    └──────────────┘
       │             │  tb_sesi_login│
       │             ├──────────────┤    ┌──────────────┐
       │             │id_sesi (PK)  │    │    tb_rak    │
       │             │id_anggota(FK)│    ├──────────────┤
       │             │token_hash    │    │id_rak (PK)   │
       │             │expires_at    │    │nama_rak      │
       │             └──────────────┘    │lokasi        │
       │                                 │keterangan    │
       │             ┌──────────────┐    └──────────────┘
       │             │tb_permission │
       │             ├──────────────┤    ┌──────────────┐
       │             │id_permission │    │  tb_rating   │
       │             │role          │    ├──────────────┤
       │             │modul         │    │id_rating (PK)│
       │             │akses         │    │id_buku (FK)  │
       │             └──────────────┘    │id_anggota(FK)│
       │                                 │rating        │
       │             ┌──────────────┐    │komentar      │
       │             │tb_notifikasi │    │status        │
       │             ├──────────────┤    └──────────────┘
       │             │id_notifikasi │
       │             │id_target_user│    ┌──────────────┐
       │             │id_target_staff│   │  tb_badge    │
       │             │target_tipe   │    ├──────────────┤
       │             │tipe          │    │id_badge (PK) │
       │             │judul         │    │kode          │
       │             │pesan         │    │nama          │
       │             │link          │    │deskripsi     │
       │             └──────────────┘    │ikon          │
       │                                 │metrik        │
       │             ┌──────────────┐    │syarat        │
       │             │tb_badge_anggota│   └──────┬───────┘
       │             ├──────────────┤           │
       │             │id_badge_anggota│  ┌──────▼───────┐
       │             │id_anggota(FK) │  │tb_badge_anggota│
       │             │id_badge (FK)  │  │id_badge_anggota│
       │             │earned_at      │  │id_anggota(FK) │
       │             └──────────────┘  │id_badge (FK)  │
       │                               │earned_at      │
       │             ┌──────────────┐  └───────────────┘
       │             │   CMS Tables │
       │             ├──────────────┤
       │             │tb_banner     │
       │             │tb_berita     │
       │             │tb_pengumuman │
       │             │tb_layanan    │
       │             │tb_faq        │
       │             │tb_jam_buka   │
       │             │tb_pengaturan │
       │             └──────────────┘
```

### 4.2 Daftar Tabel (20 Tabel)

| No | Tabel | Deskripsi | Record Default |
|----|-------|-----------|----------------|
| 1 | `tb_user` | Akun admin & petugas | 2 |
| 2 | `tb_anggota` | Data anggota perpustakaan | 8 |
| 3 | `tb_kategori` | Kategori buku | 8 |
| 4 | `tb_penerbit` | Data penerbit | 7 |
| 5 | `tb_rak` | Lokasi rak buku | 5 |
| 6 | `tb_buku` | Data buku | 11 |
| 7 | `tb_peminjaman` | Transaksi peminjaman | 7 |
| 8 | `tb_sesi_login` | Token login persisten | 0 |
| 9 | `tb_banner` | Hero banner homepage | 0 |
| 10 | `tb_berita` | Berita perpustakaan | 0 |
| 11 | `tb_pengumuman` | Pengumuman | 1 |
| 12 | `tb_layanan` | Layanan perpustakaan | 6 |
| 13 | `tb_faq` | FAQ | 4 |
| 14 | `tb_jam_buka` | Jam operasional | 7 |
| 15 | `tb_pengaturan` | Pengaturan umum | 7 |
| 16 | `tb_permission` | Matriks izin role | 24 |
| 17 | `tb_notifikasi` | Notifikasi pengguna | 0 |
| 18 | `tb_rating` | Rating & komentar buku | 0 |
| 19 | `tb_badge` | Master badge pencapaian | 8 |
| 20 | `tb_badge_anggota` | Badge anggota | 0 |

### 4.3 Business Rules

| Parameter | Nilai Default | Keterangan |
|-----------|---------------|------------|
| `LOAN_PERIOD_DAYS` | 14 | Masa peminjaman (hari) |
| `MAX_BORROWED_BOOKS` | 2 | Maks buku per anggota |
| `DENDA_PER_HARI` | 10,000 | Denda per hari keterlambatan (Rp) |
| `REMEMBER_TTL` | 30 | Login persisten (hari) |
| `LOGIN_MAX_ATTEMPTS` | 5 | Batas percobaan login |
| `LOGIN_LOCKOUT_MINUTES` | 15 | Lockout setelah gagal (menit) |

### 4.4 Status Peminjaman
```
dipinjam → terlambat → menunggu_pengembalian → dikembalikan
                    ↘ menunggu_pengembalian → ditolak
```

---

## 5. MODUL SISTEM

### 5.1 Modul Autentikasi
- **Registrasi** (`register.php`) - Pendaftaran anggota baru
- **Login** (`login.php`) - Autentikasi pengguna
- **Logout** (`logout.php`) - Terminasi sesi
- **Persistent Login** - "Ingat saya" via cookie + token di `tb_sesi_login`

### 5.2 Modul Katalog Buku
- **Katalog** (`katalog_buku.php`) - Daftar buku dengan filter & pagination
- **Detail Buku** (`detail_buku.php`) - Informasi lengkap buku + rating/komentar
- **Pencarian** (`cari.php`) - Pencarian buku (Ctrl+K)

### 5.3 Modul Peminjaman
- **Form Pinjam** (`pinjam.php`) - Form peminjaman buku
- **Proses Pinjam** (`proses_pinjam.php`) - Validasi & eksekusi peminjaman
- **Riwayat** (`riwayat_peminjaman.php`) - Daftar peminjaman anggota

### 5.4 Modul Pengembalian
- **Pengajuan** (`pengembalian.php`) - Ajukan pengembalian buku
- **Approval** - Persetujuan/penolakan oleh admin/petugas

### 5.5 Modul Anggota
- **Dashboard** (`akun.php`) - Profil & statistik anggota
- **Badge** (`badge.php`) - Pencapaian anggota

### 5.6 Modul CMS
- **Banner** - Carousel homepage
- **Berita** - Informasi berita perpustakaan
- **Pengumuman** - Pengumuman penting
- **Layanan** - Daftar layanan
- **FAQ** - Pertanyaan umum
- **Jam Buka** - Jam operasional
- **Pengaturan** - Kontak & sosial media

### 5.7 Modul Notifikasi
- **Real-time Notification** (`api/notifikasi.php`) - Endpoint AJAX
- **Tipe Notifikasi**: borrowing, return_request, return_approved, return_rejected, dll.

### 5.8 Modul Rating & Komentar
- **Rating Buku** - Penilaian 1-5 bintang
- **Komentar** - Ulasan buku oleh anggota

---

## 6. ALUR SISTEM

### 6.1 Alur Peminjaman Buku
```
┌─────────────┐
│  Anggota    │
│  Login      │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Cari &     │
│  Pilih Buku │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Klik Pinjam│
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│           VALIDASI SERVER                │
│  1. Anggota aktif?                      │
│  2. Stok tersedia?                      │
│  3. Belum pinjam buku sama?             │
│  4. Belum melebihi batas 2 buku?        │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴───────┐
       │               │
       ▼               ▼
┌─────────────┐  ┌─────────────┐
│  BERHASIL   │  │  GAGAL      │
│  stok -1    │  │  Tampilkan  │
│  status:    │  │  error      │
│  dipinjam   │  └─────────────┘
└─────────────┘
```

### 6.2 Alur Pengembalian Buku
```
┌─────────────┐
│  Anggota    │
│  Ajukan    │
│  Return     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Status:    │
│  menunggu_  │
│  pengembalian│
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│           ADMIN/PETUGAS                 │
│           Review                        │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴───────┐
       │               │
       ▼               ▼
┌─────────────┐  ┌─────────────┐
│  DISETUJUI  │  │  DITOLAK    │
│  Hitung     │  │  Kembali ke │
│  denda      │  │  dipinjam/  │
│  stok +1    │  │  terlambat  │
│  status:    │  └─────────────┘
│  dikembalikan│
└─────────────┘
```

### 6.3 Alur Login
```
┌─────────────┐
│  Input      │
│  Email +    │
│  Password   │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│           VALIDASI                      │
│  1. Rate limit (5/15 menit)?           │
│  2. Email terdaftar?                    │
│  3. Password valid?                     │
│  4. Status aktif?                       │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴───────┐
       │               │
       ▼               ▼
┌─────────────┐  ┌─────────────┐
│  BERHASIL   │  │  GAGAL      │
│  Create     │  │  Increment  │
│  session    │  │  attempt    │
│  Regen ID   │  │  Tampilkan  │
│             │  │  error      │
└─────────────┘  └─────────────┘
```

---

## 7. RENCANA PENGEMBANGAN

### 7.1 Fase 1: Fondasi (Selesai)
- [x] Struktur database 20 tabel
- [x] Koneksi database (mysqli + PDO)
- [x] Autentikasi (login, register, logout)
- [x] Session management & security

### 7.2 Fase 2: Modul Inti (Selesai)
- [x] Katalog buku dengan filter
- [x] Detail buku + rating/komentar
- [x] Peminjaman buku
- [x] Pengembalian dengan approval workflow
- [x] Dashboard anggota

### 7.3 Fase 3: Fitur Tambahan (Selesai)
- [x] CMS (banner, berita, pengumuman, layanan, FAQ)
- [x] Sistem notifikasi real-time
- [x] Badge pencapaian anggota
- [x] Login persisten (remember me)

### 7.4 Fase 4: Peningkatan (Rencana)
- [ ] Admin panel UI (frontend)
- [ ] Laporan cetak (PDF export)
- [ ] Barcode scanner untuk buku
- [ ] Integrasi e-book reader
- [ ] Mobile app (React Native/Flutter)
- [ ] Sistem reservasi buku
- [ ] Multi-cabang perpustakaan

### 7.5 Fase 5: Optimasi (Rencana)
- [ ] Caching (Redis/Memcached)
- [ ] Search engine (Elasticsearch)
- [ ] CDN untuk aset statis
- [ ] Load balancing
- [ ] Monitoring & logging
- [ ] Automated testing (PHPUnit)

---

## 8. TATA KELOLA DATA

### 8.1 Seed Data Default

#### User Admin & Petugas
| Username | Password | Level | Status |
|----------|----------|-------|--------|
| admin | admin123 | admin | aktif |
| petugas | admin123 | petugas | aktif |

#### Anggota Demo
| Email | Password | Nama | Kelas |
|-------|----------|------|-------|
| ahmad@email.com | admin123 | Ahmad Rizky Pratama | XII RPL |
| siti@email.com | admin123 | Siti Nurhaliza | XI TKJ |
| budi@email.com | admin123 | Budi Santoso | XII RPL |

#### Kategori Buku
1. Fiksi
2. Non-Fiksi
3. Pendidikan
4. Sains & Teknologi
5. Sejarah
6. Agama
7. Bahasa
8. Seni & Olahraga

#### Penerbit
1. Erlangga
2. Gramedia Pustaka Utama
3. Yudistira
4. Tiga Serangkai
5. Intan Pariwara
6. Mizan Pustaka
7. Bentang Pustaka

#### Rak Buku
| Rak | Lokasi | Keterangan |
|-----|--------|------------|
| Rak A | Lantai 1 - Sayap Kiri | Fiksi & Non-Fiksi |
| Rak B | Lantai 1 - Sayap Kanan | Pendidikan |
| Rak C | Lantai 2 - Sayap Kiri | Sains & Teknologi |
| Rak D | Lantai 2 - Sayap Kanan | Sejarah & Agama |
| Rak E | Lantai 3 | Referensi & Ensiklopedia |

### 8.2 Permission Matrix

| Role | Dashboard | Buku | Kategori | Penerbit | Rak | Anggota | Peminjaman | Pengembalian | Laporan | CMS | Pengaturan | User | Role |
|------|-----------|------|----------|----------|-----|---------|------------|--------------|---------|-----|------------|------|------|
| Admin | Semua | Semua | Semua | Semua | Semua | Semua | Semua | Semua | Semua | Semua | Semua | Semua | Semua |
| Petugas | Baca | Semua | Semua | Semua | Semua | Semua | Semua | Semua | Baca | Semua | Baca | - | - |
| Anggota | Baca | Baca | - | - | - | - | Baca | Baca | - | - | - | - | - |

---

## 9. DOKUMENTASI TEKNIS

### 9.1 Struktur Folder
```
perpustakaan-daerah V5/
├── config/
│   ├── app.php                   # Helper, konstanta, security, auth, CSRF, rate-limit
│   └── database.php              # Koneksi mysqli & PDO (utf8mb4)
├── app/                          # Lapisan repository / logika
│   ├── anggota.php               # Repositori anggota
│   ├── badges.php                # Repositori badge
│   ├── catalog.php               # Repositori katalog buku
│   ├── content.php               # Repositori konten CMS
│   ├── loans.php                 # Repositori peminjaman/pengembalian
│   ├── notifications.php         # Repositori notifikasi
│   └── ratings.php               # Repositori rating & komentar
├── api/
│   └── notifikasi.php            # AJAX endpoint notifikasi (JSON)
├── partials/                     # Komponen partial halaman publik
│   ├── header.php  footer.php  drawer.php  book_card.php
│   ├── landing_book_card.php  notifications.php  library_video.php
├── assets/
│   ├── css/                      # style.css + css/components, layout, pages, themes
│   ├── js/                       # app.js + js/pages, js/ui
│   └── img/                      # banner/, buku/, logo/, video/
├── uploads/
│   ├── buku/                     # Cover buku upload (dilindungi .htaccess)
│   └── user/                     # Foto anggota upload (dilindungi .htaccess)
├── index.php                     # Entry point
├── landing.php                   # Beranda publik (CMS-driven)
├── katalog_buku.php              # Katalog buku + filter + pagination
├── detail_buku.php               # Detail buku + rating/komentar
├── cari.php                      # Pencarian buku (Ctrl+K)
├── pinjam.php / proses_pinjam.php# Form & proses peminjaman
├── pengembalian.php              # Pengajuan pengembalian oleh anggota
├── akun.php                      # Dashboard / profil anggota
├── badge.php                     # Halaman badge anggota
├── informasi.php                 # Berita & pengumuman (CMS)
├── help.php                      # Pusat bantuan / FAQ
├── login.php  register.php  logout.php   # Autentikasi anggota
├── database.sql                  # Schema lengkap + seed
├── migrasi_v5.sql                # Migrasi V5
├── migrasi_rating_komentar.sql   # Migrasi tabel rating
├── migrasi_badge_informasi.sql   # Migrasi tabel badge
├── migrasi_notifikasi.sql        # Migrasi tabel notifikasi
├── migrasi_prosedur.sql          # Stored procedure & function
├── .htaccess                     # Konfigurasi Apache + security headers
├── README.md                     # Dokumentasi project
└── DOKUMENTASI_*.pdf             # Dokumentasi PDF
```

### 9.2 Stored Procedures & Functions

#### fn_hitung_denda(p_tgl_jatuh_tempo, p_tgl_dikembalikan)
Menghitung denda keterlambatan = selisih hari × Rp10,000

#### fn_total_peminjaman_aktif(p_id_anggota)
Menghitung jumlah peminjaman aktif anggota

#### sp_pinjam_buku(p_id_anggota, p_id_buku, p_tanggal_kembali, p_keterangan)
Proses peminjaman buku dengan validasi lengkap

#### sp_ajukan_pengembalian(p_id_peminjaman, p_id_anggota, p_catatan)
Ajukan pengembalian buku

#### sp_approve_pengembalian(p_id_peminjaman, p_kondisi, p_catatan, p_diproses_oleh)
Setujui pengembalian & hitung denda

---

## 10. DEPLOYMENT

### 10.1 Persiapan Server
1. Install XAMPP/Laragon/WAMP
2. Start Apache & MySQL
3. Copy project ke `htdocs/`

### 10.2 Instalasi Database
1. Buka phpMyAdmin
2. Buat database `perpustakaan_daerah` (utf8mb4_unicode_ci)
3. Import `database.sql`
4. Jalankan migrasi (jika upgrade):
   - `migrasi_v5.sql`
   - `migrasi_notifikasi.sql`
   - `migrasi_rating_komentar.sql`
   - `migrasi_badge_informasi.sql`
   - `migrasi_prosedur.sql`

### 10.3 Konfigurasi
1. Edit `config/database.php` (DB_ENV, kredensial)
2. Edit `config/app.php` (APP_URL, BASE_URL)
3. Pastikan folder `uploads/` writable

### 10.4 Akses Aplikasi
- **URL**: `http://localhost/perpustakaan-daerah%20V5/`
- **Login**: gunakan akun demo di atas

---

## 11. REKOMENDASI PENGEMBANGAN LANJUTAN

### 11.1 Prioritas Tinggi
1. **Admin Panel UI** - Buat halaman admin untuk CRUD semua data
2. **Laporan PDF** - Export laporan peminjaman, denda, statistik
3. **Barcode System** - Generate & scan barcode untuk buku

### 11.2 Prioritas Menengah
1. **Mobile App** - React Native/Flutter untuk akses mobile
2. **Reservasi Buku** - Sistem antrian peminjaman
3. **E-book Integration** - Reader untuk e-book

### 11.3 Prioritas Rendah
1. **Multi-cabang** - Support perpustakaan cabang
2. **AI Recommendation** - Rekomendasi buku berbasis AI
3. **Analytics Dashboard** - Dashboard analitik lanjutan

---

## 12. PENUTUP

Dokumen perencanaan ini memberikan gambaran komprehensif tentang Sistem Informasi Perpustakaan Daerah V5. Dengan arsitektur yang solid, fitur yang lengkap, dan keamanan yang terjamin, sistem ini siap menjadi fondasi perpustakaan digital yang modern dan efisien.

**Dokumen ini akan diperbarui seiring perkembangan proyek.**

---

**Terakhir Diperbarui:** 2 September 2026
**Dokumen Versi:** 1.0
