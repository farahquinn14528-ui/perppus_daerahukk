PERPUSTAKAAN-DAERAH-V5/
│
├── app/
│   ├── Core/
│   │   ├── Application.php
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Session.php
│   │   ├── Auth.php
│   │   ├── CSRF.php
│   │   ├── Validator.php
│   │   ├── Database.php
│   │   ├── Cache.php
│   │   └── Logger.php
│   │
│   ├── Config/
│   │   ├── app.php
│   │   ├── database.php
│   │   ├── security.php
│   │   └── permissions.php
│   │
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── CatalogController.php
│   │   ├── BookController.php
│   │   ├── LoanController.php
│   │   ├── ReturnController.php
│   │   ├── MemberController.php
│   │   ├── NotificationController.php
│   │   ├── RatingController.php
│   │   ├── BadgeController.php
│   │   ├── ContentController.php
│   │   └── ProfileController.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Member.php
│   │   ├── Book.php
│   │   ├── Category.php
│   │   ├── Author.php
│   │   ├── Publisher.php
│   │   ├── Loan.php
│   │   ├── ReturnTransaction.php
│   │   ├── Rating.php
│   │   ├── Notification.php
│   │   ├── Badge.php
│   │   └── Content.php
│   │
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── BookService.php
│   │   ├── LoanService.php
│   │   ├── ReturnService.php
│   │   ├── FineService.php
│   │   ├── NotificationService.php
│   │   ├── RatingService.php
│   │   ├── BadgeService.php
│   │   └── ContentService.php
│   │
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── BookRepository.php
│   │   ├── LoanRepository.php
│   │   ├── MemberRepository.php
│   │   └── NotificationRepository.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── GuestMiddleware.php
│   │   ├── AdminMiddleware.php
│   │   ├── CSRFMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── SecurityMiddleware.php
│   │
│   ├── Helpers/
│   │   ├── auth.php
│   │   ├── url.php
│   │   ├── format.php
│   │   ├── validation.php
│   │   └── security.php
│   │
│   └── Exceptions/
│       ├── AuthException.php
│       ├── DatabaseException.php
│       ├── ValidationException.php
│       └── LoanException.php
│
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── auth.php
│   └── admin.php
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   ├── core/
│   │   │   ├── components/
│   │   │   ├── layouts/
│   │   │   ├── pages/
│   │   │   ├── themes/
│   │   │   └── utilities/
│   │   │
│   │   ├── js/
│   │   │   ├── core/
│   │   │   ├── components/
│   │   │   ├── pages/
│   │   │   ├── services/
│   │   │   └── utils/
│   │   │
│   │   ├── images/
│   │   │   ├── books/
│   │   │   ├── banners/
│   │   │   ├── logos/
│   │   │   ├── avatars/
│   │   │   └── icons/
│   │   │
│   │   └── fonts/
│   │
│   └── uploads/
│       ├── books/
│       ├── avatars/
│       ├── banners/
│       └── documents/
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── main.php
│   │   │   ├── auth.php
│   │   │   ├── dashboard.php
│   │   │   └── admin.php
│   │   │
│   │   ├── components/
│   │   │   ├── navbar.php
│   │   │   ├── sidebar.php
│   │   │   ├── drawer.php
│   │   │   ├── book-card.php
│   │   │   ├── notification.php
│   │   │   ├── modal.php
│   │   │   ├── toast.php
│   │   │   └── pagination.php
│   │   │
│   │   ├── landing/
│   │   │   ├── index.php
│   │   │   ├── hero.php
│   │   │   ├── featured-books.php
│   │   │   ├── statistics.php
│   │   │   └── library-video.php
│   │   │
│   │   ├── catalog/
│   │   │   ├── index.php
│   │   │   ├── detail.php
│   │   │   ├── search.php
│   │   │   └── filters.php
│   │   │
│   │   ├── loans/
│   │   │   ├── index.php
│   │   │   ├── borrow.php
│   │   │   ├── history.php
│   │   │   └── detail.php
│   │   │
│   │   ├── returns/
│   │   │   ├── index.php
│   │   │   └── history.php
│   │   │
│   │   ├── profile/
│   │   │   ├── index.php
│   │   │   ├── edit.php
│   │   │   └── badges.php
│   │   │
│   │   ├── notifications/
│   │   │   └── index.php
│   │   │
│   │   ├── ratings/
│   │   │   └── index.php
│   │   │
│   │   ├── help/
│   │   │   └── index.php
│   │   │
│   │   └── errors/
│   │       ├── 404.php
│   │       ├── 403.php
│   │       └── 500.php
│   │
│   └── lang/
│       ├── id/
│       └── jv/
│
├── api/
│   ├── v1/
│   │   ├── auth/
│   │   ├── books/
│   │   ├── loans/
│   │   ├── members/
│   │   ├── notifications/
│   │   ├── ratings/
│   │   └── content/
│   │
│   └── v2/
│       └── ...
│
├── database/
│   ├── schema/
│   │   ├── users.sql
│   │   ├── members.sql
│   │   ├── books.sql
│   │   ├── loans.sql
│   │   ├── returns.sql
│   │   ├── ratings.sql
│   │   ├── notifications.sql
│   │   └── badges.sql
│   │
│   ├── migrations/
│   │   ├── 001_initial.sql
│   │   ├── 002_badges.sql
│   │   ├── 003_notifications.sql
│   │   ├── 004_ratings.sql
│   │   └── 005_v5.sql
│   │
│   ├── seeds/
│   │   ├── books.sql
│   │   ├── users.sql
│   │   └── demo.sql
│   │
│   ├── procedures/
│   ├── triggers/
│   └── database.sql
│
├── storage/
│   ├── cache/
│   ├── logs/
│   ├── sessions/
│   └── temp/
│
├── tests/
│   ├── Unit/
│   │   ├── AuthTest.php
│   │   ├── BookTest.php
│   │   ├── LoanTest.php
│   │   └── FineTest.php
│   │
│   ├── Integration/
│   │   ├── DatabaseTest.php
│   │   └── LoanFlowTest.php
│   │
│   └── Security/
│       ├── CSRFAttackTest.php
│       ├── SQLInjectionTest.php
│       └── RateLimitTest.php
│
├── docs/
│   ├── architecture/
│   ├── database/
│   ├── api/
│   ├── security/
│   └── deployment/
│
├── scripts/
│   ├── migrate.php
│   ├── seed.php
│   ├── backup.php
│   ├── clear-cache.php
│   └── health-check.php
│
├── config/
│   ├── .env.example
│   └── app.php
│
├── .env
├── .env.example
├── .gitignore
├── .htaccess
├── composer.json
├── phpunit.xml
├── README.md
└── CHANGELOG.md


                    ┌─────────────────────┐
                    │      BROWSER        │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │       ROUTES        │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │    MIDDLEWARE       │
                    │ Auth / CSRF / Rate  │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │    CONTROLLER       │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │      SERVICE        │
                    │ Business Logic      │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │    REPOSITORY       │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │   MYSQL/MARIADB     │
                    └─────────────────────┘

                               ▲
                               │
                    ┌──────────┴──────────┐
                    │       MODEL         │
                    └─────────────────────┘








Dashboard
├── Beranda
├── Katalog Buku
│   ├── Semua Buku
│   ├── Kategori
│   ├── Pencarian
│   └── Detail Buku
│
├── Peminjaman
│   ├── Pinjam Buku
│   ├── Peminjaman Aktif
│   └── Riwayat Peminjaman
│
├── Pengembalian
│   └── Riwayat Pengembalian
│
├── Notifikasi
├── Rating & Komentar
├── Badge / Achievement
├── Informasi
├── Bantuan
└── Profil


landing.php
login.php
register.php
katalog_buku.php
detail_buku.php
pinjam.php
proses_pinjam.php
pengembalian.php
riwayat_peminjaman.php
akun.php
badge.php
informasi.php
help.php



                    
