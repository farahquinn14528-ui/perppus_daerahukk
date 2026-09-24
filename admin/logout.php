<?php
/**
 * PERPUSTAKAAN DAERAH - Logout (Anggota & umum)
 * File: logout.php
 * Deskripsi: Menghapus seluruh session dan redirect ke login
 */

require_once __DIR__ . '/config/app.php';

// V5: Hapus remember-me token + cookie
try { clear_remember_cookie($koneksi); } catch (Throwable $e) { /* silent */ }

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Regenerasi + destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
    session_destroy();
}

// Redirect ke login
header('Location: ' . BASE_URL . 'login.php');
exit;
