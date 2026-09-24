# Perpustakaan Daerah UKK

Sistem informasi perpustakaan daerah berbasis PHP dan MySQL untuk mengelola katalog buku, peminjaman, pengembalian, anggota, notifikasi, dan administrasi perpustakaan secara terstruktur.

Project utama berada di folder `admin/` dan dirancang untuk kebutuhan operasional perpustakaan daerah dengan fitur CRUD, laporan sederhana, serta antarmuka web yang mudah digunakan.

## Fitur utama

- Katalog buku dan pencarian buku
- Peminjaman dan pengembalian buku
- Manajemen anggota / akun pengguna
- Dashboard admin dan statistik umum
- Sistem notifikasi dan badge/achievement
- Rating dan komentar buku
- Pengelolaan informasi, bantuan, dan profil
- Dokumentasi proyek dan database yang tersedia di dalam repo

## Teknologi yang digunakan

- PHP 8+
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript / jQuery
- Bootstrap
- Apache / XAMPP

## Struktur repository

```text
perppus_daerahukk/
├── admin/                              # Aplikasi utama (PHP)
│   ├── .htaccess
│   ├── README.md
│   ├── akun.php
│   ├── badge.php
│   ├── database.sql
│   ├── detail_buku.php
│   ├── help.php
│   ├── index.php
│   ├── informasi.php
│   ├── katalog_buku.php
│   ├── landing.php
│   ├── login.php
│   ├── pengembalian.php
│   ├── pinjam.php
│   ├── proses_pinjam.php
│   ├── register.php
│   ├── riwayat_peminjaman.php
│   ├── ...
│   └── migrasi_*.sql
├── DOKUMENTASI_PERPUSTAKAAN_DAERAH_VERSI1.pdf
├── Screenshot 2026-08-28 085937.png
├── README.md
└── .gitignore
```

## Persyaratan

Sebelum menjalankan proyek, pastikan lingkungan berikut sudah tersedia:

- PHP 8.0 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.3+
- Apache / XAMPP
- Browser modern (Chrome, Edge, Firefox)

## Cara menjalankan

### 1. Clone atau unduh repository

```bash
git clone https://github.com/farahquinn14528-ui/perppus_daerahukk.git
```

### 2. Siapkan server lokal

Buka XAMPP Control Panel, lalu aktifkan:

- Apache
- MySQL

### 3. Buat database

Buka phpMyAdmin, lalu buat database baru dengan nama misalnya:

```sql
perpustakaan_daerah
```

Kemudian import file SQL pada folder `admin/database.sql`.

### 4. Jalankan aplikasi

Letakkan folder `admin/` ke direktori web server, misalnya:

```text
C:\xampp\htdocs\perppus_daerahukk\
```

Lalu buka browser ke:

```text
http://localhost/perppus_daerahukk/
```

Jika aplikasi berjalan dari folder `admin/` secara langsung, biasanya URL menjadi:

```text
http://localhost/perppus_daerahukk/admin/
```

## Login default

Berdasarkan dokumentasi proyek, akun default yang umum dipakai adalah:

| Role | Username / Email | Password |
|------|------------------|----------|
| Admin | admin | admin123 |
| Petugas | petugas | admin123 |
| Anggota | ahmad@email.com | admin123 |

Catatan: kredensial dapat berubah sesuai konfigurasi database yang Anda import.

## Fitur utama yang relevan dengan repo

### Untuk anggota

- Registrasi dan login
- Melihat katalog buku
- Mencari buku
- Peminjaman buku
- Riwayat peminjaman
- Rating dan komentar
- Badge pencapaian

### Untuk admin / petugas

- Dashboard statistik
- Manajemen data buku dan anggota
- Proses peminjaman dan pengembalian
- Pengelolaan informasi, banner, dan bantuan
- Akses admin panel

## Dokumentasi tambahan

Repo ini juga dilengkapi dengan dokumen pendukung seperti:

- `admin/DOKUMENTASI_PERPUSTAKAAN_DAERAH_VERSI1.pdf`
- `admin/DOKUMENTASI_DATABASE_PERPUSTAKAAN_DAERAH_VERSI1.pdf`
- `admin/DAFTAR_TABEL.md`
- `admin/DAFTAR_FUNGSI_FILE.md`
- `admin/PERENCANAAN_PROYEK_PERPUSTAKAAN_DAERAH_V5.md`

Dokumen-dokumen tersebut dapat membantu memahami struktur sistem, skema database, dan alur pengembangan aplikasi.

## Kontribusi

Proyek ini masih bersifat local project / project assignment. Jika Anda ingin mengembangkan lebih lanjut:

1. Fork repository ini
2. Buat branch baru
3. Lakukan perubahan
4. Commit dan push
5. Buat pull request

## Lisensi

Hak cipta © 2026. Semua hak dilindungi undang-undang.

Aplikasi ini dibuat untuk kebutuhan sistem informasi perpustakaan daerah dan dapat dimodifikasi sesuai kebutuhan proyek Anda.
