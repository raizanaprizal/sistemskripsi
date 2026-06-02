<?php
require_once 'database.php';
require_once 'session.php';
requireLogin();
 
$page_title  = 'Dashboard';
$active_menu = 'dashboard';
$role = getRole();
 
// Stats
$total_vendor   = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM vendor WHERE status_aktif=1"))[0];
$total_kriteria = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM kriteria"))[0];
$total_user     = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM users WHERE status_aktif=1"))[0];
$periode_aktif  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM periode WHERE status_aktif=1 LIMIT 1"));
$tahun_aktif    = $periode_aktif['tahun'] ?? '-';
$periode_id     = $periode_aktif['id_periode'] ?? 0;

// Kriteria untuk progress bar
$kriteria_all = mysqli_query($conn,"SELECT nama_kriteria, bobot FROM kriteria ORDER BY id_kriteria");

// Vendor yang sudah dinilai untuk staff
$evaluated_vendors = null;
if ($role === 'Staff' && $periode_id) {
    $evaluated_vendors = mysqli_query($conn, "SELECT v.id_vendor, v.kode_vendor, v.nama_vendor,
        COUNT(DISTINCT jb.id_jenis) AS jumlah_jenis,
        MAX(p.updated_at) AS terakhir_dinilai
        FROM penilaian p
        JOIN vendor v ON p.id_vendor=v.id_vendor
        JOIN jenis_barang jb ON p.id_jenis=jb.id_jenis
        WHERE p.id_periode=$periode_id AND v.status_aktif=1
        GROUP BY v.id_vendor ORDER BY v.nama_vendor LIMIT 10");
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
  <?php while($k = mysqli_fetch_assoc($kriteria_all)): ?>
  <div style="margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
      <span><?= htmlspecialchars($k['nama_kriteria']) ?></span>
      <span><?= ($k['bobot']*100) ?>%</span>
    </div>
    <div class="progress">
      <div class="progress-fill" style="width:<?= ($k['bobot']*100) ?>%"></div>
    </div>
  </div>
  <?php endwhile; ?>
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
  <?php if ($evaluated_vendors && mysqli_num_rows($evaluated_vendors) > 0): ?>
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
        <?php $no = 1; while ($row = mysqli_fetch_assoc($evaluated_vendors)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['kode_vendor']) ?></td>
          <td><?= htmlspecialchars($row['nama_vendor']) ?></td>
          <td><?= htmlspecialchars($row['jumlah_jenis']) ?> jenis</td>
          <td><?= htmlspecialchars($row['terakhir_dinilai'] ?? '-') ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p style="color:#666">Belum ada vendor yang dinilai untuk periode aktif.</p>
  <?php endif; ?>
</div>
<?php endif; ?>
 
<?php require_once 'footer.php'; ?>