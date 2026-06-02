<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Administrator');

$page_title  = 'Kelola Pengguna';
$active_menu = 'pengguna';
require_once 'header.php';
?>

<h1 class="page-title">Kelola Pengguna</h1>
<p class="page-subtitle">Fitur ini belum tersedia. Halaman sementara menunggu pengembangan.</p>

<div class="card">
  <p>Maaf, halaman manajemen pengguna belum lengkap di versi ini.</p>
  <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>

<?php require_once 'footer.php'; ?>
