<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Staff');

$page_title  = 'Input Penilaian Vendor';
$active_menu = 'input_penilaian';

$periode = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM periode WHERE status_aktif=1 LIMIT 1"));
$periode_id = $periode['id_periode'] ?? 0;
$vendors = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM vendor WHERE status_aktif=1 ORDER BY nama_vendor"), MYSQLI_ASSOC);
$jenis_list = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM jenis_barang WHERE status_aktif=1 ORDER BY nama_barang"), MYSQLI_ASSOC);
$kriteria_list = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM kriteria ORDER BY id_kriteria"), MYSQLI_ASSOC);

$selected_vendor = (int) ($_GET['vendor'] ?? $_POST['id_vendor'] ?? 0);
$selected_jenis  = (int) ($_GET['jenis'] ?? $_POST['id_jenis'] ?? 0);
$nilai_values = [];
$errors = [];
$evaluated_vendors = [];

if ($periode_id && $selected_jenis) {
    $evaluated_vendors = mysqli_query($conn, "SELECT v.id_vendor, v.kode_vendor, v.nama_vendor,
        COUNT(DISTINCT p.id_kriteria) AS jumlah_kriteria,
        MAX(p.updated_at) AS terakhir_dinilai
        FROM penilaian p
        JOIN vendor v ON p.id_vendor=v.id_vendor
        WHERE p.id_periode=$periode_id AND p.id_jenis=$selected_jenis AND v.status_aktif=1
        GROUP BY v.id_vendor ORDER BY v.nama_vendor");
}

function gradeFromScore(float $score): string {
    if ($score >= 85) return 'A';
    if ($score >= 70) return 'B';
    if ($score >= 55) return 'C';
    if ($score >= 40) return 'D';
    return 'E';
}

if ($selected_vendor && $selected_jenis && $periode_id) {
    $res = mysqli_query($conn, "SELECT id_kriteria, nilai_kriteria FROM penilaian
        WHERE id_vendor=$selected_vendor AND id_jenis=$selected_jenis AND id_periode=$periode_id");
    while ($row = mysqli_fetch_assoc($res)) {
        $nilai_values[$row['id_kriteria']] = $row['nilai_kriteria'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'simpan') {
    if (!$periode_id) {
        $errors[] = 'Periode aktif tidak ditemukan. Silakan atur periode terlebih dahulu.';
    }
    if (!$selected_vendor) {
        $errors[] = 'Pilih vendor terlebih dahulu.';
    }
    if (!$selected_jenis) {
        $errors[] = 'Pilih jenis pekerjaan terlebih dahulu.';
    }

    $submitted_values = $_POST['nilai'] ?? [];
    $rows = [];
    foreach ($kriteria_list as $kriteria) {
        $k_id = $kriteria['id_kriteria'];
        $raw = $submitted_values[$k_id] ?? '';
        $nilai = is_numeric($raw) ? (float) $raw : null;
        if ($nilai === null || $raw === '') {
            $errors[] = "Nilai untuk kriteria '{$kriteria['nama_kriteria']}' harus diisi.";
        } elseif ($nilai < 0 || $nilai > 100) {
            $errors[] = "Nilai untuk kriteria '{$kriteria['nama_kriteria']}' harus antara 0 dan 100.";
        } else {
            $rows[] = [
                'id_kriteria' => $k_id,
                'nilai'       => number_format($nilai, 2, '.', ''),
            ];
            $nilai_values[$k_id] = $nilai;
        }
    }

    if (empty($errors) && !empty($rows)) {
        $values = [];
        foreach ($rows as $row) {
            $grade = gradeFromScore((float) $row['nilai']);
            $values[] = "($selected_vendor,{$row['id_kriteria']},$periode_id,$selected_jenis,{$_SESSION['id_user']},{$row['nilai']},{$row['nilai']},'$grade')";
        }

        $sql = "INSERT INTO penilaian (id_vendor,id_kriteria,id_periode,id_jenis,id_user,nilai_kriteria,total_nilai,grade) VALUES " . implode(',', $values) .
               " ON DUPLICATE KEY UPDATE nilai_kriteria=VALUES(nilai_kriteria), total_nilai=VALUES(total_nilai), grade=VALUES(grade), updated_at=NOW()";
        mysqli_query($conn, $sql);
        if (mysqli_error($conn)) {
            $errors[] = 'Gagal menyimpan penilaian: ' . mysqli_error($conn);
        } else {
            setAlert('success', 'Penilaian vendor berhasil disimpan.');
            redirect("input_penilaian.php?vendor=$selected_vendor&jenis=$selected_jenis");
        }
    }
}

require_once 'header.php';
?>

<h1 class="page-title">Input Penilaian Vendor</h1>
<p class="page-subtitle">Masukkan nilai tiap kriteria untuk vendor dan jenis pekerjaan yang dipilih.</p>

<div class="card">
  <div class="card-title">Detail Periode</div>
  <div style="display:flex;flex-wrap:wrap;gap:16px">
    <div><strong>Periode Aktif:</strong> <?= htmlspecialchars($periode['tahun'] ?? '-') ?></div>
  </div>
</div>

<?php if ($errors): ?>
<div class="card" style="background:#fff3f2;border:1px solid #f1c0b7;color:#8c2318">
  <div style="font-weight:700;margin-bottom:8px">Harap perbaiki:</div>
  <ul style="margin-left:18px;line-height:1.6">
    <?php foreach ($errors as $error): ?>
      <li><?= htmlspecialchars($error) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="GET" class="filter-row" style="margin-bottom:18px">
  <div class="form-group">
    <label class="form-label">Vendor</label>
    <select name="vendor" class="form-control" required>
      <option value="">Pilih vendor</option>
      <?php foreach ($vendors as $vendor): ?>
      <option value="<?= $vendor['id_vendor'] ?>" <?= $selected_vendor === (int) $vendor['id_vendor'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($vendor['kode_vendor'] . ' — ' . $vendor['nama_vendor']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label">Jenis Pekerjaan</label>
    <select name="jenis" class="form-control" required>
      <option value="">Pilih jenis pekerjaan</option>
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

<?php if ($selected_jenis && $periode_id): ?>
<div class="card" style="margin-bottom:18px">
  <div class="card-title">Vendor yang Sudah Dinilai</div>
  <?php if (mysqli_num_rows($evaluated_vendors) > 0): ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No</th>
          <th>Kode Vendor</th>
          <th>Nama Vendor</th>
          <th>Jumlah Kriteria</th>
          <th>Terakhir Dinilai</th>
        </tr>
      </thead>
      <tbody>
        <?php $no=1; while ($row = mysqli_fetch_assoc($evaluated_vendors)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['kode_vendor']) ?></td>
          <td><?= htmlspecialchars($row['nama_vendor']) ?></td>
          <td><?= htmlspecialchars($row['jumlah_kriteria']) ?></td>
          <td><?= htmlspecialchars($row['terakhir_dinilai'] ?? '-') ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p>Belum ada vendor yang dinilai untuk periode dan jenis pekerjaan ini.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($selected_vendor && $selected_jenis): ?>
<div class="card">
  <div class="card-title">Form Penilaian</div>
  <form method="POST" action="input_penilaian.php?vendor=<?= $selected_vendor ?>&jenis=<?= $selected_jenis ?>">
    <input type="hidden" name="id_vendor" value="<?= $selected_vendor ?>">
    <input type="hidden" name="id_jenis" value="<?= $selected_jenis ?>">
    <input type="hidden" name="aksi" value="simpan">

    <div style="display:grid;gap:14px">
      <?php $i = 1; foreach ($kriteria_list as $kriteria): ?>
      <div class="form-group">
        <label class="form-label"><?= htmlspecialchars($kriteria['nama_kriteria']) ?> <span style="color:#999;font-size:11px">(Bobot <?= ($kriteria['bobot'] * 100) ?>%)</span></label>
        <input id="nilai_<?= $i ?>" type="number" step="0.01" min="0" max="100" name="nilai[<?= $kriteria['id_kriteria'] ?>]"
               class="form-control" placeholder="Masukkan nilai 0-100"
               data-bobot="<?= htmlspecialchars($kriteria['bobot']) ?>"
               value="<?= htmlspecialchars($nilai_values[$kriteria['id_kriteria']] ?? '') ?>" required>
      </div>
      <?php $i++; endforeach; ?>
    </div>

    <div class="formula-box">
      Pratinjau nilai total: <strong id="total_nilai">0.00</strong> — Grade: <strong id="grade_hasil">E</strong>
      <div style="margin-top:8px;color:#556a88;font-size:13px">Grade dihitung secara otomatis berdasarkan nilai kriteria.</div>
    </div>

    <button type="submit" class="btn btn-success">Simpan Penilaian</button>
  </form>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
