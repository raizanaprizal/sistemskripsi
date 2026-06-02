<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Staff');

$page_title  = 'Proses SAW';
$active_menu = 'proses_saw';

$periode        = db_fetch("SELECT * FROM periode WHERE status_aktif = true LIMIT 1");
$periode_id     = $periode['id_periode'] ?? 0;
$jenis_list     = db_fetch_all("SELECT * FROM jenis_barang WHERE status_aktif = true ORDER BY nama_barang");
$selected_jenis = (int) ($_GET['jenis'] ?? $_POST['id_jenis'] ?? ($jenis_list[0]['id_jenis'] ?? 0));
$errors         = [];
$success        = '';
$results        = [];
$warning        = '';

function formatScore(float $score): string
{
    return number_format($score, 4, '.', '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hitung') {
    if (!$periode_id) {
        $errors[] = 'Tidak ada periode aktif. Silakan atur periode terlebih dahulu.';
    }
    if (!$selected_jenis) {
        $errors[] = 'Pilih jenis pekerjaan terlebih dahulu.';
    }

    if (empty($errors)) {
        $kriteria_list = db_fetch_all("SELECT * FROM kriteria ORDER BY id_kriteria");
        $penilaian_res = db_fetch_all(
            "SELECT p.id_vendor, p.id_kriteria, p.nilai_kriteria,
                v.nama_vendor, k.bobot, k.nama_kriteria
             FROM penilaian p
             JOIN vendor v ON v.id_vendor = p.id_vendor
             JOIN kriteria k ON k.id_kriteria = p.id_kriteria
             WHERE p.id_periode = ? AND p.id_jenis = ? AND v.status_aktif = true
             ORDER BY p.id_vendor, p.id_kriteria",
            [$periode_id, $selected_jenis]
        );

        $data          = [];
        $vendor_names  = [];
        $criterion_max = [];

        foreach ($penilaian_res as $row) {
            $vid   = $row['id_vendor'];
            $kid   = $row['id_kriteria'];
            $nilai = (float) $row['nilai_kriteria'];
            $data[$vid][$kid] = $nilai;
            $vendor_names[$vid] = $row['nama_vendor'];
            $criterion_max[$kid] = max($criterion_max[$kid] ?? 0, $nilai);
        }

        if (empty($data)) {
            $errors[] = 'Belum ada data penilaian untuk periode dan jenis pekerjaan terpilih.';
        }

        if (empty($errors)) {
            $complete_vendors = [];
            $kriteria_count   = count($kriteria_list);
            foreach ($data as $vid => $values) {
                if (count($values) === $kriteria_count) {
                    $complete_vendors[] = $vid;
                }
            }

            if (empty($complete_vendors)) {
                $errors[] = 'Belum ada vendor dengan penilaian lengkap untuk semua kriteria.';
            }

            if (empty($errors)) {
                $rank_data = [];
                foreach ($complete_vendors as $vid) {
                    $total = 0.0;
                    foreach ($kriteria_list as $kriteria) {
                        $kid        = $kriteria['id_kriteria'];
                        $nilai      = $data[$vid][$kid];
                        $max        = $criterion_max[$kid] ?: 1;
                        $normalized = $nilai / $max;
                        $total      += $normalized * (float) $kriteria['bobot'];
                    }
                    $rank_data[$vid] = [
                        'nama_vendor'      => $vendor_names[$vid],
                        'nilai_preferensi' => $total,
                    ];
                }

                arsort($rank_data);
                
                // PostgreSQL: DELETE FROM ... WHERE ... IN (SELECT ...)
                db_query(
                    "DELETE FROM detail_normalisasi WHERE id_hasil IN (
                        SELECT id_hasil FROM hasil_saw WHERE id_periode = ? AND id_jenis = ?
                    )",
                    [$periode_id, $selected_jenis]
                );
                db_query(
                    "DELETE FROM hasil_saw WHERE id_periode = ? AND id_jenis = ?",
                    [$periode_id, $selected_jenis]
                );

                $rank = 1;
                foreach ($rank_data as $vid => $row) {
                    $nilai_preferensi = number_format($row['nilai_preferensi'], 6, '.', '');
                    // Gunakan RETURNING id_hasil
                    $stmt = db_query(
                        "INSERT INTO hasil_saw (id_vendor, id_jenis, id_periode, nilai_preferensi, peringkat)
                         VALUES (?, ?, ?, ?, ?) RETURNING id_hasil",
                        [$vid, $selected_jenis, $periode_id, $nilai_preferensi, $rank]
                    );
                    $id_hasil = db_insert_id($stmt, 'id_hasil');

                    foreach ($kriteria_list as $kriteria) {
                        $kid        = $kriteria['id_kriteria'];
                        $nilai      = $data[$vid][$kid];
                        $max        = $criterion_max[$kid] ?: 1;
                        $normalized = $nilai / $max;
                        $terbobot   = $normalized * (float) $kriteria['bobot'];
                        db_query(
                            "INSERT INTO detail_normalisasi
                             (id_hasil, id_kriteria, nilai_asli, nilai_normalisasi, bobot_kriteria, nilai_terbobot)
                             VALUES (?, ?, ?, ?, ?, ?)",
                            [$id_hasil, $kid, $nilai, $normalized, $kriteria['bobot'], $terbobot]
                        );
                    }

                    $results[] = [
                        'peringkat'        => $rank,
                        'nama_vendor'      => $row['nama_vendor'],
                        'nilai_preferensi' => $nilai_preferensi,
                    ];
                    $rank++;
                }

                if (empty($errors)) {
                    $success = 'Perhitungan SAW selesai dan hasil sudah disimpan.';
                }
            }
        }
    }
}

if (empty($results) && $selected_jenis && $periode_id) {
    $results = db_fetch_all(
        "SELECT h.peringkat, h.nilai_preferensi, v.nama_vendor
         FROM hasil_saw h
         JOIN vendor v ON v.id_vendor = h.id_vendor
         WHERE h.id_periode = ? AND h.id_jenis = ?
         ORDER BY h.peringkat ASC, h.nilai_preferensi DESC",
        [$periode_id, $selected_jenis]
    );
}

require_once 'header.php';
?>

<h1 class="page-title">Proses SAW</h1>
<p class="page-subtitle">Hitung ranking vendor berdasarkan metode SAW untuk periode dan jenis pekerjaan yang dipilih.</p>

<div class="card">
  <div class="card-title">Periode Aktif</div>
  <div><strong>Tahun:</strong> <?= htmlspecialchars($periode['tahun'] ?? '-') ?></div>
</div>

<?php if ($errors): ?>
<div class="card" style="background:#fff3f2;border:1px solid #f1c0b7;color:#8c2318">
  <div style="font-weight:700;margin-bottom:8px">Terjadi kesalahan:</div>
  <ul style="margin-left:18px;line-height:1.6">
    <?php foreach ($errors as $error): ?>
      <li><?= htmlspecialchars($error) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="card" style="background:#eaf3de;border:1px solid #27500a;color:#27500a">
  <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

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

<div class="card">
  <form method="POST">
    <input type="hidden" name="id_jenis" value="<?= $selected_jenis ?>">
    <input type="hidden" name="aksi" value="hitung">
    <button type="submit" class="btn btn-success">Hitung SAW</button>
  </form>
</div>

<?php if ($results): ?>
<div class="card">
  <div class="card-title">Hasil Ranking SAW</div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Peringkat</th>
          <th>Vendor</th>
          <th>Nilai Preferensi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['peringkat']) ?></td>
          <td><?= htmlspecialchars($row['nama_vendor']) ?></td>
          <td><?= formatScore((float) $row['nilai_preferensi']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="card">
  <p>Tidak ada hasil SAW untuk parameter yang dipilih. Silakan tambahkan penilaian lengkap dan tekan tombol "Hitung SAW".</p>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
