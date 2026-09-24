<?php
/**
 * PERPUSTAKAAN DAERAH - Entry Point
 * File: index.php
 * Deskripsi: Router utama yang menampilkan halaman home
 */

require_once __DIR__ . '/config/app.php';

// Include header
require_once __DIR__ . '/partials/header.php';

// Include landing page content
require_once __DIR__ . '/landing.php';

// Include footer
require_once __DIR__ . '/partials/footer.php';
