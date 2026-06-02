<?php
require_once 'database.php';
require_once 'session.php';
requireRole(['Administrator', 'Staff']);

$page_title  = 'Periode';
$active_menu = 'periode';

$search = trim($_GET['search'] ?? '');
$params = [];
$where  = "WHERE 1=1";
if ($search) {
    $where   .= " AND (tahun ILIKE ? OR semester ILIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$periode_all = db_fetch_all("SELECT * FROM periode $where ORDER BY tahun DESC, semester ASC", $params);
$edit_data   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi       = $_POST['aksi'];
    $tahun      = trim($_POST['tahun'] ?? '');
    $semester   = trim($_POST['semester'] ?? '');
    $aktif      = isset($_POST['status_aktif']) ? true : false;
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($aksi === 'tambah') {
        $exists = db_fetch(
            "SELECT id_periode FROM periode WHERE tahun = ? AND semester = ?",
            [$tahun, $semester]
        );
        if ($exists) {
            setAlert('danger', 'Periode sudah ada. Pilih tahun/semester lain.');
        } else {
            if ($aktif) {
                db_query("UPDATE periode SET status_aktif = false WHERE status_aktif = true");
            }
            db_query(
                "INSERT INTO periode (tahun, semester, keterangan, status_aktif) VALUES (?, ?, ?, ?)",
                [$tahun, $semester, $keterangan, $aktif ? 'true' : 'false']
            );
            setAlert('success', 'Periode berhasil ditambahkan.');
        }
        redirect('periode.php');
    }

    if ($aksi === 'ubah') {
        $id = (int) ($_POST['id_periode'] ?? 0);
        if ($aktif) {
            db_query("UPDATE periode SET status_aktif = false WHERE status_aktif = true");
        }
        db_query(
            "UPDATE periode SET tahun = ?, semester = ?, keterangan = ?, status_aktif = ?
             WHERE id_periode = ?",
            [$tahun, $semester, $keterangan, $aktif ? 'true' : 'false', $id]
        );
        setAlert('success', 'Periode berhasil diperbarui.');
        redirect('periode.php');
    }
}

if (isset($_GET['hapus'])) {
    $id  = (int) $_GET['hapus'];
    $cek = db_fetch("SELECT id_penilaian FROM penilaian WHERE id_periode = ? LIMIT 1", [$id]);
    if ($cek) {
        setAlert('danger', 'Periode tidak dapat dihapus karena sudah dipakai dalam penilaian.');
    } else {
        db_query("DELETE FROM periode WHERE id_periode = ?", [$id]);
        setAlert('success', 'Periode berhasil dihapus.');
    }
    redirect('periode.php');
}

if (isset($_GET['edit'])) {
    $id        = (int) $_GET['edit'];
    $edit_data = db_fetch("SELECT * FROM periode WHERE id_periode = ?", [$id]);
}

require_once 'header.php';
?>

<h1 class="page-title">Periode</h1>
<p class="page-subtitle">Kelola periode penilaian untuk input penilaian dan proses SAW.</p>

<div class="card" style="margin-bottom:18px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
    <button onclick="document.getElementById('modal-tambah').style.display='flex'" class="btn btn-primary">+ Tambah Periode</button>
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label class="form-label">Cari</label>
        <input type="text" name="search" class="form-control" placeholder="Tahun atau semester" value="<?= htmlspecialchars($search) ?>">
      </div>
      <button type="submit" class="btn btn-secondary">Cari</button>
      <?php if ($search): ?>
      <a href="periode.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No</th><th>Tahun</th><th>Semester</th><th>Keterangan</th><th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($periode_all as $row): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['tahun']) ?></td>
          <td><?= htmlspecialchars($row['semester']) ?></td>
          <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
          <td><span class="badge <?= $row['status_aktif'] ? 'badge-success' : 'badge-secondary' ?>">
            <?= $row['status_aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
          <td>
            <a href="periode.php?edit=<?= $row['id_periode'] ?>" class="btn btn-secondary btn-sm">Ubah</a>
            <a href="periode.php?hapus=<?= $row['id_periode'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Hapus</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="modal-tambah" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:8px;padding:24px;width:500px;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:16px;font-size:18px">Tambah Periode</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Tahun</label>
          <input type="text" name="tahun" class="form-control" placeholder="2026" required>
        </div>
        <div class="form-group">
          <label class="form-label">Semester</label>
          <select name="semester" class="form-control" required>
            <option value="Ganjil">Ganjil</option>
            <option value="Genap">Genap</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label"><input type="checkbox" name="status_aktif" checked> Jadikan Periode Aktif</label>
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
  <div style="background:#fff;border-radius:8px;padding:24px;width:500px;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:16px;font-size:18px">Ubah Periode</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="ubah">
      <input type="hidden" name="id_periode" value="<?= $edit_data['id_periode'] ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Tahun</label>
          <input type="text" name="tahun" class="form-control" value="<?= htmlspecialchars($edit_data['tahun']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Semester</label>
          <select name="semester" class="form-control" required>
            <option value="Ganjil" <?= $edit_data['semester'] === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
            <option value="Genap"  <?= $edit_data['semester'] === 'Genap'  ? 'selected' : '' ?>>Genap</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['keterangan']) ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">
          <input type="checkbox" name="status_aktif" <?= $edit_data['status_aktif'] ? 'checked' : '' ?>>
          Jadikan Periode Aktif
        </label>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px">
        <button type="button" onclick="window.location='periode.php'" class="btn btn-secondary">Batal</button>
        <button type="submit" class="btn btn-primary">Perbarui</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
