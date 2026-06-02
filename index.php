<?php
require_once 'database.php';
require_once 'session.php';
 
if (isLoggedIn()) {
    redirect('dashboard.php');
}
 
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $sql = "SELECT * FROM users WHERE username = '$username' AND status_aktif = 1";
        $res = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($res);
 
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id_user']      = $user['id_user'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'];
            redirect('dashboard.php');
        } else {
            $error = 'Username atau password salah. Silakan periksa kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — SPV Bio Farma</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">
      <div style="font-size:36px">&#10022;</div>
      <h2>Sistem Pemilihan Vendor</h2>
      <p>PT Bio Farma (Persero)</p>
    </div>
 
    <?php if ($error): ?>
    <div style="background:#FAECE7;border-left:4px solid #993C1D;color:#993C1D;
                padding:9px 14px;border-radius:4px;font-size:12px;margin-bottom:14px">
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
 
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control"
               placeholder="Masukkan username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:9px">
        Masuk
      </button>
    </form>
 
    <p style="text-align:center;font-size:11px;color:#bbb;margin-top:14px">
      Hak akses disesuaikan dengan role pengguna
    </p>
  </div>
</div>
</body>
</html>