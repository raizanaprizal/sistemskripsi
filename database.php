<?php
// ============================================================
// config/database.php - Konfigurasi Koneksi Database
// Sistem Pemilihan Vendor PT Bio Farma (Persero)
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_vendor_biofarma');
define('BASE_URL', 'http://localhost/sistemskripsi');
 
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
 