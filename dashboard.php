<?php
require_once 'database.php';
require_once 'session.php';
requireLogin();

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
$role = getRole();

// Stats
$total_vendor   = db_scalar("SELECT COUNT(*) FROM vendor WHERE status_aktif = true");
$total_kriteria = db_scalar("SELECT COUNT(*) FROM kriteria");
$total_user     = db_scalar("SELECT COUNT(*) FROM users WHERE status_aktif = true");
$periode_aktif  = db_fetch("SELECT * FROM periode WHERE status_aktif = true LIMIT 1");
$tahun_aktif    = $periode_aktif['tahun'] ?? '-';
$periode_id     = $periode_aktif['id_periode'] ?? 0;

// Kriteria untuk progress bar
$kriteria_all = db_fetch_all("SELECT nama_kriteria, bobot FROM kriteria ORDER BY id_kriteria");

// Vendor yang sudah dinilai untuk staff
$evaluated_vendors = [];
if ($role === 'Staff' && $periode_id) {
    $evaluated_vendors = db_fetch_all(
        "SELECT v.id_vendor, v.kode_vendor, v.nama_vendor,
            COUNT(DISTINCT jb.id_jenis) AS jumlah_jenis,
            MAX(p.updated_at) AS terakhir_dinilai
         FROM penilaian p
         JOIN vendor v ON p.id_vendor = v.id_vendor
         JOIN jenis_barang jb ON p.id_jenis = jb.id_jenis
         WHERE p.id_periode = ? AND v.status_aktif = true
         GROUP BY v.id_vendor, v.kode_vendor, v.nama_vendor
         ORDER BY v.nama_vendor
         LIMIT 10",
        [$periode_id]
    );
}

require_once 'header.php';
?>

<h1 class="page-title">Dashboard</h1>
<p class="page-subtitle">Ringkasan data vendor, kriteria, dan periode aktif. Lihat statistik utama agar keputusan seleksi vendor lebih cepat.</p>

<div class="stats-row">
  <div class="stat-card">
    <div class="stat-num"><?= $total_vendor ?></div>
    <div class="stat-label">Total Vendor Aktif</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $total_kriteria ?></div>
    <div class="stat-label">Total Kriteria</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $tahun_aktif ?></div>
    <div class="stat-label">Periode Aktif</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $total_user ?></div>
    <div class="stat-label">Pengguna Sistem</div>
  </div>
</div>

<?php if ($role !== 'Staff'): ?>
<div class="card">
  <div class="card-title">Distribusi Bobot Kriteria Penilaian</div>
  <?php foreach ($kriteria_all as $k): ?>
  <div style="margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
      <span><?= htmlspecialchars($k['nama_kriteria']) ?></span>
      <span><?= ($k['bobot'] * 100) ?>%</span>
    </div>
    <div class="progress">
      <div class="progress-fill" style="width:<?= ($k['bobot'] * 100) ?>%"></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($role !== 'Staff'): ?>
<div class="card">
  <div class="card-title">Shortcut — Lihat Perangkingan Vendor</div>
  <a href="<?= BASE_URL ?>/perangkingan.php" class="btn btn-primary">
    Lihat Hasil Perangkingan &rarr;
  </a>
</div>
<?php endif; ?>

<?php if ($role === 'Staff'): ?>
<div class="card">
  <div class="card-title">Vendor yang Sudah Dinilai (Periode Aktif)</div>
  <?php if (!empty($evaluated_vendors)): ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No</th>
          <th>Kode Vendor</th>
          <th>Nama Vendor</th>
          <th>Jenis Pekerjaan</th>
          <th>Terakhir Dinilai</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($evaluated_vendors as $row): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['kode_vendor']) ?></td>
          <td><?= htmlspecialchars($row['nama_vendor']) ?></td>
          <td><?= htmlspecialchars($row['jumlah_jenis']) ?> jenis</td>
          <td><?= htmlspecialchars($row['terakhir_dinilai'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p style="color:#666">Belum ada vendor yang dinilai untuk periode aktif.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>