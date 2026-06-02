<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Administrator');

$page_title  = 'Data Kriteria';
$active_menu = 'kriteria';

$search = trim($_GET['search'] ?? '');
$params = [];
$where  = "WHERE 1=1";
if ($search) {
    $where   .= " AND (nama_kriteria ILIKE ? OR kode_kriteria ILIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$kriteria_all = db_fetch_all("SELECT * FROM kriteria $where ORDER BY id_kriteria", $params);
$edit_data    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi       = $_POST['aksi'];
    $kode       = trim($_POST['kode_kriteria'] ?? '');
    $nama       = trim($_POST['nama_kriteria'] ?? '');
    $bobot      = (float) ($_POST['bobot'] ?? 0);
    $jenis      = ($_POST['jenis_kriteria'] ?? '') === 'cost' ? 'cost' : 'benefit';
    $nilai_min  = is_numeric($_POST['nilai_min'] ?? null) ? number_format((float) $_POST['nilai_min'], 2, '.', '') : 0;
    $nilai_max  = is_numeric($_POST['nilai_max'] ?? null) ? number_format((float) $_POST['nilai_max'], 2, '.', '') : 100;
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($aksi === 'tambah') {
        $exists = db_fetch("SELECT id_kriteria FROM kriteria WHERE kode_kriteria = ?", [$kode]);
        if ($exists) {
            setAlert('danger', 'Kode kriteria sudah digunakan. Gunakan kode lain.');
        } else {
            db_query(
                "INSERT INTO kriteria (kode_kriteria, nama_kriteria, bobot, jenis_kriteria, nilai_min, nilai_max, keterangan)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$kode, $nama, $bobot, $jenis, $nilai_min, $nilai_max, $keterangan]
            );
            setAlert('success', 'Data kriteria berhasil disimpan.');
        }
        redirect('kriteria.php');
    }

    if ($aksi === 'ubah') {
        $id = (int) ($_POST['id_kriteria'] ?? 0);
        db_query(
            "UPDATE kriteria SET nama_kriteria = ?, bobot = ?, jenis_kriteria = ?,
             nilai_min = ?, nilai_max = ?, keterangan = ? WHERE id_kriteria = ?",
            [$nama, $bobot, $jenis, $nilai_min, $nilai_max, $keterangan, $id]
        );
        setAlert('success', 'Data kriteria berhasil diperbarui.');
        redirect('kriteria.php');
    }
}

if (isset($_GET['hapus'])) {
    $id  = (int) $_GET['hapus'];
    $cek = db_fetch("SELECT id_penilaian FROM penilaian WHERE id_kriteria = ? LIMIT 1", [$id]);
    if ($cek) {
        setAlert('danger', 'Kriteria tidak bisa dihapus karena sudah digunakan di penilaian.');
    } else {
        db_query("DELETE FROM kriteria WHERE id_kriteria = ?", [$id]);
        setAlert('success', 'Data kriteria berhasil dihapus.');
    }
    redirect('kriteria.php');
}

if (isset($_GET['edit'])) {
    $id        = (int) $_GET['edit'];
    $edit_data = db_fetch("SELECT * FROM kriteria WHERE id_kriteria = ?", [$id]);
}

require_once 'header.php';
?>

<h1 class="page-title">Data Kriteria</h1>
<p class="page-subtitle">Kelola kriteria penilaian vendor: tambah, ubah, atau hapus data.</p>

<div class="card" style="margin-bottom:18px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
    <div>
      <button onclick="document.getElementById('modal-tambah').style.display='flex'" class="btn btn-primary">+ Tambah Kriteria</button>
    </div>
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label class="form-label">Cari</label>
        <input type="text" name="search" class="form-control" placeholder="Nama atau kode" value="<?= htmlspecialchars($search) ?>">
      </div>
      <button type="submit" class="btn btn-secondary">Cari</button>
      <?php if ($search): ?>
      <a href="kriteria.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No</th><th>Kode</th><th>Nama Kriteria</th><th>Bobot</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($kriteria_all as $k): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($k['kode_kriteria']) ?></td>
          <td><?= htmlspecialchars($k['nama_kriteria']) ?></td>
          <td><?= number_format((float) $k['bobot'] * 100, 0) ?>%</td>
          <td>
            <a href="kriteria.php?edit=<?= $k['id_kriteria'] ?>" class="btn btn-secondary btn-sm">Ubah</a>
            <a href="kriteria.php?hapus=<?= $k['id_kriteria'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Hapus</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH -->
<div id="modal-tambah" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:8px;padding:24px;width:540px;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:16px;font-size:18px">Tambah Kriteria</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Kriteria</label>
          <input type="text" name="kode_kriteria" class="form-control" placeholder="CRT-01" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Kriteria</label>
          <input type="text" name="nama_kriteria" class="form-control" placeholder="Nama kriteria" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Bobot</label>
          <input type="number" step="0.01" min="0" max="1" name="bobot" class="form-control" placeholder="0.20" required>
        </div>
      </div>
      <input type="hidden" name="jenis_kriteria" value="benefit">
      <input type="hidden" name="nilai_min" value="0.00">
      <input type="hidden" name="nilai_max" value="100.00">
      <div class="form-group">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px">
        <button type="button" onclick="document.getElementById('modal-tambah').style.display='none'" class="btn btn-secondary">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php if ($edit_data): ?>
<div id="modal-ubah" style="display:flex;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:8px;padding:24px;width:540px;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:16px;font-size:18px">Ubah Kriteria</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="ubah">
      <input type="hidden" name="id_kriteria" value="<?= $edit_data['id_kriteria'] ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Kriteria</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($edit_data['kode_kriteria']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Kriteria</label>
          <input type="text" name="nama_kriteria" class="form-control" value="<?= htmlspecialchars($edit_data['nama_kriteria']) ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Bobot</label>
          <input type="number" step="0.01" min="0" max="1" name="bobot" class="form-control" value="<?= htmlspecialchars($edit_data['bobot']) ?>" required>
        </div>
      </div>
      <input type="hidden" name="jenis_kriteria" value="<?= htmlspecialchars($edit_data['jenis_kriteria']) ?>">
      <input type="hidden" name="nilai_min" value="<?= htmlspecialchars($edit_data['nilai_min']) ?>">
      <input type="hidden" name="nilai_max" value="<?= htmlspecialchars($edit_data['nilai_max']) ?>">
      <div class="form-group">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($edit_data['keterangan']) ?></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px">
        <button type="button" onclick="window.location='kriteria.php'" class="btn btn-secondary">Batal</button>
        <button type="submit" class="btn btn-primary">Perbarui</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
