<?php
// ============================================================
// config/session.php - Manajemen Session & Auth Helper
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
 
function isLoggedIn() {
    return isset($_SESSION['id_user']) && !empty($_SESSION['id_user']);
}
function getRole() {
    return $_SESSION['role'] ?? '';
}
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}
function requireRole($roles) {
    requireLogin();
    if (!in_array(getRole(), (array)$roles)) {
        header("Location: " . BASE_URL . "/unauthorized.php");
        exit;
    }
}
function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit;
}
function setAlert($type, $msg) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_msg']  = $msg;
}
function showAlert() {
    if (!empty($_SESSION['alert_msg'])) {
        $type   = $_SESSION['alert_type'];
        $msg    = htmlspecialchars($_SESSION['alert_msg']);
        unset($_SESSION['alert_type'], $_SESSION['alert_msg']);
        $styles = [
            'success' => ['#EAF3DE','#3B6D11','#27500A'],
            'danger'  => ['#FAECE7','#993C1D','#993C1D'],
            'warning' => ['#FAEEDA','#BA7517','#633806'],
        ];
        [$bg,$border,$text] = $styles[$type] ?? ['#E6F1FB','#185FA5','#0C447C'];
        echo "<div style='background:{$bg};border-left:4px solid {$border};color:{$text};
              padding:10px 16px;margin-bottom:16px;border-radius:4px;font-size:13px'>{$msg}</div>";
    }
}
function clean($conn, $val) {
    return mysqli_real_escape_string($conn, trim($val));
}

