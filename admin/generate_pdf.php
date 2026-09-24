<?php
/**
 * Generator Dokumen PDF - Sistem Informasi Perpustakaan Daerah V5
 * 
 * Deskripsi: Generator dokumen PDF dokumentasi (18 chapter) dari source code.
 * Men-generate dokumen PDF A4 dengan 18 chapter dari source code.
 * Jalankan dari browser: http://localhost/perpustakaan-daerah%20V5/generate_pdf.php
 * 
 * Requirement: composer require dompdf/dompdf:^3.1
 */

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/partials/pdf_dokumentasi.php';

// ponytail: dokumen berat (18 chapter + gambar base64) butuh memori di atas default 128M
ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');

use Dompdf\Dompdf;
use Dompdf\Options;

// ============================================================
// KONFIGURASI
// ============================================================
define('PDF_TITLE', 'DOKUMENTASI SISTEM INFORMASI PERPUSTAKAAN DAERAH VERSI 1');
define('PDF_AUTHOR', 'Tim Pengembang Sistem Perpustakaan Daerah');
define('PDF_DATE', date('d F Y'));
define('PDF_VERSION', '1.0');

// ============================================================
// PATH BANTUAN UNTUK GAMBAR
// ============================================================
define('ASSETS_IMG', __DIR__ . '/assets/img');
define('ASSETS_LOGO', ASSETS_IMG . '/logo/logo.svg');
define('ASSETS_FAVICON', ASSETS_IMG . '/logo/favicon.svg');
define('ASSETS_BANNER', ASSETS_IMG . '/banner/banner.svg');
define('ASSETS_POSTER', ASSETS_IMG . '/video/poster.svg');
define('DIR_BUKU', ASSETS_IMG . '/buku');

function buku_cover(int $num): string {
    return img_data_uri(DIR_BUKU . '/bk' . $num . '.jpg');
}

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'serif');
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);

// ============================================================
// FUNGSI HELPER
// ============================================================
function htmlEscape(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function e(string $text): string {
    return htmlEscape($text);
}

/**
 * Embed an image file as a base64 data URI for dompdf.
 * Returns empty string if file not found / not readable.
 */
function img_data_uri(string $path): string {
    if (!file_exists($path) || !is_readable($path)) {
        return '';
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match($ext) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        default => 'application/octet-stream',
    };
    $data = file_get_contents($path);
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

/**
 * Embed a local image with fallback caption if missing.
 */
function img_tag(string $path, string $alt, string $style = ''): string {
    $dataUri = img_data_uri($path);
    if ($dataUri === '') {
        return '<div class="note">Gambar tidak ditemukan: ' . e($alt) . ' (' . e(basename($path)) . ')</div>';
    }
    $styleAttr = $style !== '' ? ' style="' . $style . '"' : '';
    return '<img src="' . $dataUri . '" alt="' . e($alt) . '"' . $styleAttr . '>';
}

// ============================================================
// FUNGSI-FUNGSI DATABASE (read-only untuk konten)
// ============================================================
function db_fetch_all($koneksi, string $sql, array $params = []): array {
    if (!$koneksi) return [];
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) return [];
    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $val) {
            if (is_int($val)) { $types .= 'i'; $values[] = $val; }
            elseif (is_float($val)) { $types .= 'd'; $values[] = $val; }
            else { $types .= 's'; $values[] = $val; }
        }
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

function db_count($koneksi, string $table): int {
    if (!$koneksi) return 0;
    $result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM {$table}");
    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

// ============================================================
// KONEKSI DATABASE (opsional - untuk data dinamis)
// ============================================================
$koneksi = @mysqli_connect('localhost', 'root', '', 'perpustakaan_daerah');
$dbConnected = $koneksi && !mysqli_connect_error();

// ============================================================
// WAKTU GENERASI
// ============================================================
$startTime = microtime(true);

// ============================================================
// BANGUN HTML (template 18 chapter ada di partials/pdf_dokumentasi.php)
// ============================================================
$html = render_dokumentasi_html($koneksi, $dbConnected);

// ============================================================
// RENDER PDF
// ============================================================
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Set metadata via the adapter (dompdf 3.x)
try {
    $canvas = $dompdf->getCanvas();
    if (method_exists($canvas, 'set_info')) {
        $canvas->set_info('Author', PDF_AUTHOR);
        $canvas->set_info('Title', PDF_TITLE);
        $canvas->set_info('Subject', 'Dokumentasi Sistem Informasi Perpustakaan Daerah V5');
        $canvas->set_info('Creator', 'Perpustakaan Daerah V5 Documentation Generator');
    } elseif (method_exists($canvas, 'getpdfinfo')) {
        $canvas->getpdfinfo()->setAuthor(PDF_AUTHOR);
        $canvas->getpdfinfo()->setTitle(PDF_TITLE);
        $canvas->getpdfinfo()->setSubject('Dokumentasi Sistem Informasi Perpustakaan Daerah V5');
        $canvas->getpdfinfo()->setCreator('Perpustakaan Daerah V5 Documentation Generator');
    }
} catch (\Throwable $e) {
    // Metadata optional - ignore errors
}

// Output ke browser
$filename = 'DOKUMENTASI_PERPUSTAKAAN_DAERAH_VERSI1.pdf';
$dompdf->stream($filename, ['Attachment' => false]);

// Juga simpan ke file
$outputPath = __DIR__ . '/' . $filename;
file_put_contents($outputPath, $dompdf->output());

// Close DB connection
if ($dbConnected) {
    mysqli_close($koneksi);
}