<?php
// includes/header.php - Shared header + sidebar
$role       = getRole();
$page_title = $page_title ?? 'Sistem Pemilihan Vendor';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — SPV Bio Farma</title>
<!-- Gunakan path relatif untuk static assets di Vercel -->
<link rel="stylesheet" href="/style.css">
</head>
<body>

<!-- TOP NAV -->
<div class="topnav">
  <div class="topnav-left">
    <span class="topnav-title">Sistem Pemilihan Vendor — PT Bio Farma (Persero)</span>
  </div>
  <div class="topnav-right">
    <span><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? '') ?></span>
    <span class="role-badge role-<?= strtolower($role) ?>"><?= htmlspecialchars($role) ?></span>
  </div>
</div>

<div class="layout-wrap">
<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sb-brand">
    SPV Bio Farma
    <small>Sistem Pemilihan Vendor</small>
  </div>

  <div class="sb-section">Menu Utama</div>
  <a href="<?= BASE_URL ?>/dashboard.php" class="sb-link <?= ($active_menu === 'dashboard') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Dashboard
  </a>

  <?php if ($role === 'Administrator'): ?>
  <a href="<?= BASE_URL ?>/pengguna.php" class="sb-link <?= ($active_menu === 'pengguna') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Kelola Pengguna
  </a>
  <div class="sb-section">Data Master</div>
  <a href="<?= BASE_URL ?>/vendor.php" class="sb-link <?= ($active_menu === 'vendor') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Data Vendor
  </a>
  <a href="<?= BASE_URL ?>/kriteria.php" class="sb-link <?= ($active_menu === 'kriteria') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Data Kriteria
  </a>
  <a href="<?= BASE_URL ?>/jenis_barang.php" class="sb-link <?= ($active_menu === 'jenis_barang') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Jenis Barang
  </a>
  <?php endif; ?>

  <?php if ($role === 'Staff'): ?>
  <div class="sb-section">Penilaian</div>
  <a href="<?= BASE_URL ?>/periode.php" class="sb-link <?= ($active_menu === 'periode') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Periode
  </a>
  <a href="<?= BASE_URL ?>/input_penilaian.php" class="sb-link <?= ($active_menu === 'input_penilaian') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Input Penilaian
  </a>
  <a href="<?= BASE_URL ?>/proses_saw.php" class="sb-link <?= ($active_menu === 'proses_saw') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Proses SAW
  </a>
  <?php endif; ?>

  <?php if ($role === 'Manajer'): ?>
  <div class="sb-section">Hasil SAW</div>
  <a href="<?= BASE_URL ?>/perangkingan.php" class="sb-link <?= ($active_menu === 'perangkingan') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Perangkingan Vendor
  </a>
  <a href="<?= BASE_URL ?>/detail_penilaian.php" class="sb-link <?= ($active_menu === 'detail') ? 'active' : '' ?>">
    <span class="sb-icon">&#9632;</span> Detail Penilaian
  </a>
  <?php endif; ?>

  <div class="sb-section">Akun</div>
  <a href="<?= BASE_URL ?>/logout.php" class="sb-link">
    <span class="sb-icon">&#9632;</span> Keluar
  </a>
</div>

<!-- CONTENT AREA -->
<div class="content-area">
<?php showAlert(); ?>