<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Manajer');

$page_title  = 'Perangkingan Vendor';
$active_menu = 'perangkingan';

$periode              = db_fetch("SELECT * FROM periode WHERE status_aktif = true LIMIT 1");
$periode_id           = $periode['id_periode'] ?? 0;
$jenis_list           = db_fetch_all("SELECT * FROM jenis_barang WHERE status_aktif = true ORDER BY nama_barang");
$selected_jenis       = (int) ($_GET['jenis'] ?? ($jenis_list[0]['id_jenis'] ?? 0));
$selected_jenis_label = '';

foreach ($jenis_list as $jenis) {
    if ($selected_jenis === (int) $jenis['id_jenis']) {
        $selected_jenis_label = $jenis['kode_barang'] . ' — ' . $jenis['nama_barang'];
        break;
    }
}

function formatPreferenceScore(float $score): string
{
    return number_format($score, 4, '.', '');
}

$results = [];
if ($selected_jenis && $periode_id) {
    $results = db_fetch_all(
        "SELECT h.peringkat, h.nilai_preferensi, v.kode_vendor, v.nama_vendor
         FROM hasil_saw h
         JOIN vendor v ON v.id_vendor = h.id_vendor
         WHERE h.id_periode = ? AND h.id_jenis = ?
         ORDER BY h.peringkat ASC, h.nilai_preferensi DESC",
        [$periode_id, $selected_jenis]
    );
}

require_once 'header.php';
?>

<h1 class="page-title">Perangkingan Vendor</h1>
<p class="page-subtitle">Lihat hasil ranking dari proses SAW untuk periode dan jenis pekerjaan saat ini.</p>

<div class="card">
  <div class="card-title">Periode Aktif</div>
  <div><strong>Tahun:</strong> <?= htmlspecialchars($periode['tahun'] ?? '-') ?></div>
  <div><strong>Jenis Pekerjaan:</strong> <?= htmlspecialchars($selected_jenis_label ?: '-') ?></div>
</div>

<div class="card">
  <form method="GET" class="filter-row">
    <div class="form-group" style="flex:1">
      <label class="form-label">Jenis Pekerjaan</label>
      <select name="jenis" class="form-control" required>
        <?php foreach ($jenis_list as $jenis): ?>
        <option value="<?= $jenis['id_jenis'] ?>" <?= $selected_jenis === (int) $jenis['id_jenis'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($jenis['kode_barang'] . ' — ' . $jenis['nama_barang']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="align-self:flex-end">
      <button type="submit" class="btn btn-primary">Tampilkan</button>
    </div>
  </form>
</div>

<?php if ($results): ?>
<div class="card">
  <div class="card-title">Hasil Perangkingan</div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Peringkat</th>
          <th>Kode Vendor</th>
          <th>Nama Vendor</th>
          <th>Nilai Preferensi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['peringkat']) ?></td>
          <td><?= htmlspecialchars($row['kode_vendor']) ?></td>
          <td><?= htmlspecialchars($row['nama_vendor']) ?></td>
          <td><?= formatPreferenceScore((float) $row['nilai_preferensi']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="card">
  <p>Belum ada hasil ranking SAW untuk periode dan jenis pekerjaan ini. Silakan jalankan proses SAW terlebih dahulu.</p>
  <a href="<?= BASE_URL ?>/proses_saw.php" class="btn btn-success">Ke Proses SAW</a>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
