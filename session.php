<?php
// ============================================================
// session.php - Manajemen Session & Auth Helper
// Menggunakan Database Session Handler agar kompatibel dengan
// Vercel serverless (tidak ada filesystem persisten).
// ============================================================

/**
 * DbSessionHandler — menyimpan session di tabel 'sessions' di Supabase.
 */
class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        try {
            $row = db_fetch("SELECT data FROM sessions WHERE id = ?", [$id]);
            return $row ? ($row['data'] ?? '') : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            db_query(
                "INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, NOW())
                 ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = NOW()",
                [$id, $data]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            db_query("DELETE FROM sessions WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = db_query(
                "DELETE FROM sessions WHERE last_activity < NOW() - INTERVAL '" . intval($max_lifetime) . " seconds'"
            );
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }
}

// Daftarkan DB session handler sebelum session_start()
$handler = new DbSessionHandler();
session_set_save_handler($handler, true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Auth Helper Functions
// ============================================================

function isLoggedIn(): bool
{
    return isset($_SESSION['id_user']) && !empty($_SESSION['id_user']);
}

function getRole(): string
{
    return $_SESSION['role'] ?? '';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}

function requireRole($roles): void
{
    requireLogin();
    if (!in_array(getRole(), (array) $roles)) {
        header("Location: " . BASE_URL . "/unauthorized.php");
        exit;
    }
}

function redirect(string $url): void
{
    // Jika URL sudah absolut, redirect langsung; jika tidak, tambahkan BASE_URL
    if (str_starts_with($url, 'http')) {
        header("Location: {$url}");
    } else {
        header("Location: " . BASE_URL . "/{$url}");
    }
    exit;
}

function setAlert(string $type, string $msg): void
{
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_msg']  = $msg;
}

function showAlert(): void
{
    if (!empty($_SESSION['alert_msg'])) {
        $type   = $_SESSION['alert_type'];
        $msg    = htmlspecialchars($_SESSION['alert_msg']);
        unset($_SESSION['alert_type'], $_SESSION['alert_msg']);
        $styles = [
            'success' => ['#EAF3DE', '#3B6D11', '#27500A'],
            'danger'  => ['#FAECE7', '#993C1D', '#993C1D'],
            'warning' => ['#FAEEDA', '#BA7517', '#633806'],
        ];
        [$bg, $border, $text] = $styles[$type] ?? ['#E6F1FB', '#185FA5', '#0C447C'];
        echo "<div style='background:{$bg};border-left:4px solid {$border};color:{$text};
              padding:10px 16px;margin-bottom:16px;border-radius:4px;font-size:13px'>{$msg}</div>";
    }
}

/**
 * Sanitasi input — dengan PDO prepared statements,
 * escaping tidak lagi diperlukan. Fungsi ini hanya trim.
 */
function clean($conn, string $val): string
{
    return trim($val);
}
