<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Manajer');

$page_title  = 'Detail Penilaian';
$active_menu = 'detail';

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

$vendor_results = [];
$vendor_details = [];

if ($selected_jenis && $periode_id) {
    $vendor_res = db_fetch_all(
        "SELECT h.id_hasil, h.peringkat, h.nilai_preferensi, v.id_vendor, v.kode_vendor, v.nama_vendor
         FROM hasil_saw h
         JOIN vendor v ON v.id_vendor = h.id_vendor
         WHERE h.id_periode = ? AND h.id_jenis = ?
         ORDER BY h.peringkat ASC, h.nilai_preferensi DESC",
        [$periode_id, $selected_jenis]
    );

    foreach ($vendor_res as $row) {
        $vendor_results[$row['id_hasil']] = $row;
    }

    if ($vendor_results) {
        $hasil_ids  = array_keys($vendor_results);
        // PostgreSQL parameter binding for IN clause
        $placeholders = implode(',', array_fill(0, count($hasil_ids), '?'));
        
        $detail_res = db_fetch_all(
            "SELECT d.id_hasil, k.kode_kriteria, k.nama_kriteria,
                d.nilai_asli, d.nilai_normalisasi, d.bobot_kriteria, d.nilai_terbobot
             FROM detail_normalisasi d
             JOIN kriteria k ON k.id_kriteria = d.id_kriteria
             WHERE d.id_hasil IN ($placeholders)
             ORDER BY d.id_hasil, k.id_kriteria",
            $hasil_ids
        );

        foreach ($detail_res as $row) {
            $vendor_details[$row['id_hasil']][] = $row;
        }
    }
}

require_once 'header.php';
?>

<h1 class="page-title">Detail Penilaian</h1>
<p class="page-subtitle">Detail normalisasi SAW untuk vendor terpilih dan periode aktif.</p>

<div class="card">
  <div class="card-title">Periode Aktif</div>
  <div><strong>Tahun:</strong> <?= htmlspecialchars($periode['tahun'] ?? '-') ?></div>
</div>

<div class="card">
  <form method="GET" class="filter-row">
    <div class="form-group">
      <label class="form-label">Jenis Pekerjaan</label>
      <select name="jenis" class="form-control" onchange="this.form.submit()">
        <?php foreach ($jenis_list as $jenis): ?>
        <option value="<?= $jenis['id_jenis'] ?>" <?= $selected_jenis === (int) $jenis['id_jenis'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($jenis['kode_barang'] . ' — ' . $jenis['nama_barang']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if ($vendor_results): ?>
  <?php foreach ($vendor_results as $id_hasil => $vendor_info): ?>
  <div class="card">
    <div class="card-title">Ringkasan Hasil Penilaian</div>
    <div><strong>Kode Vendor:</strong> <?= htmlspecialchars($vendor_info['kode_vendor']) ?></div>
    <div><strong>Nama Vendor:</strong> <?= htmlspecialchars($vendor_info['nama_vendor']) ?></div>
    <div><strong>Peringkat:</strong> <?= htmlspecialchars($vendor_info['peringkat']) ?></div>
    <div><strong>Nilai Preferensi:</strong> <?= number_format((float) $vendor_info['nilai_preferensi'], 4, '.', '') ?></div>
  </div>

  <?php if (!empty($vendor_details[$id_hasil])): ?>
  <div class="card">
    <div class="card-title">Data Normalisasi</div>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Kriteria</th>
            <th>Nilai Asli</th>
            <th>Nilai Normalisasi</th>
            <th>Bobot Kriteria</th>
            <th>Nilai Terbobot</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vendor_details[$id_hasil] as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['kode_kriteria'] . ' — ' . $row['nama_kriteria']) ?></td>
            <td><?= number_format((float) $row['nilai_asli'], 2, '.', '') ?></td>
            <td><?= number_format((float) $row['nilai_normalisasi'], 6, '.', '') ?></td>
            <td><?= number_format((float) $row['bobot_kriteria'], 2, '.', '') ?></td>
            <td><?= number_format((float) $row['nilai_terbobot'], 6, '.', '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
  <div class="card">
    <p>Tidak ada detail normalisasi untuk vendor ini. Pastikan proses SAW sudah dijalankan.</p>
    <a href="<?= BASE_URL ?>/proses_saw.php" class="btn btn-primary">Ke Proses SAW</a>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>
<?php else: ?>
<div class="card">
  <p>Tidak ada hasil penilaian untuk jenis pekerjaan ini. Pastikan proses SAW sudah dijalankan.</p>
  <a href="<?= BASE_URL ?>/proses_saw.php" class="btn btn-primary">Ke Proses SAW</a>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
