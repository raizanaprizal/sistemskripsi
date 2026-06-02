<?php
// ============================================================
// database.php - Konfigurasi Koneksi Database (Supabase / PostgreSQL)
// Sistem Pemilihan Vendor PT Bio Farma (Persero)
// ============================================================

// ── Load .env jika ada (development lokal) ───────────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (!isset($_ENV[$key]) && !getenv($key)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }
}

// ── Konfigurasi Supabase ──────────────────────────────────────
$_db_host = getenv('DB_HOST') ?: 'localhost';
$_db_port = getenv('DB_PORT') ?: '5432';
$_db_name = getenv('DB_NAME') ?: 'postgres';
$_db_user = getenv('DB_USER') ?: 'postgres';
$_db_pass = getenv('DB_PASS') ?: '';

// ── BASE_URL: auto-detect dari HTTP_HOST jika tidak diset ─────
$_base = getenv('BASE_URL');
if (!$_base) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_base    = rtrim("{$scheme}://{$host}", '/');
}
define('BASE_URL', rtrim($_base, '/'));

// ── Koneksi PDO ke PostgreSQL ─────────────────────────────────
try {
    $dsn  = "pgsql:host={$_db_host};port={$_db_port};dbname={$_db_name}";
    $conn = new PDO($dsn, $_db_user, $_db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Tampilkan pesan ramah (jangan expose credentials di production)
    die("Koneksi database gagal. Periksa konfigurasi environment variables.<br><small>" . htmlspecialchars($e->getMessage()) . "</small>");
}

// ============================================================
// Helper Functions — Pengganti mysqli_* functions
// ============================================================

/**
 * Eksekusi query SQL dengan parameter (prepared statement).
 * @return PDOStatement
 */
function db_query(string $sql, array $params = []): PDOStatement
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Ambil satu baris sebagai array asosiatif (atau null jika tidak ada).
 */
function db_fetch(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

/**
 * Ambil semua baris sebagai array of associative arrays.
 */
function db_fetch_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ambil satu baris sebagai array numerik (untuk COUNT(*) dsb).
 */
function db_fetch_row(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch(PDO::FETCH_NUM);
    return $row !== false ? $row : null;
}

/**
 * Ambil nilai scalar tunggal (misal hasil COUNT(*)).
 */
function db_scalar(string $sql, array $params = [])
{
    $row = db_fetch_row($sql, $params);
    return $row ? $row[0] : null;
}

/**
 * Dapatkan ID terakhir yang di-insert (gunakan RETURNING untuk PostgreSQL).
 * Dipanggil setelah INSERT dengan RETURNING id_xxx.
 */
function db_insert_id(PDOStatement $stmt, string $column = null): int
{
    if ($column) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row[$column] ?? 0);
    }
    global $conn;
    return (int) $conn->lastInsertId();
}