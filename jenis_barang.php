<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Administrator');

$page_title  = 'Jenis Barang';
$active_menu = 'jenis_barang';

$search = trim($_GET['search'] ?? '');
$params = [];
$where  = "WHERE 1=1";
if ($search) {
    $where   .= " AND (nama_barang ILIKE ? OR kode_barang ILIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
$barang_all = db_fetch_all("SELECT * FROM jenis_barang $where ORDER BY id_jenis", $params);
$edit_data  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi       = $_POST['aksi'];
    $kode       = trim($_POST['kode_barang'] ?? '');
    $nama       = trim($_POST['nama_barang'] ?? '');
    $kategori   = trim($_POST['kategori_jenis'] ?? '');
    $satuan     = trim($_POST['satuan'] ?? '');
    $status     = (int) ($_POST['status_aktif'] ?? 1);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($aksi === 'tambah') {
        $exists = db_fetch("SELECT id_jenis FROM jenis_barang WHERE kode_barang = ? LIMIT 1", [$kode]);
        if ($exists) {
            setAlert('danger', 'Kode barang sudah digunakan. Gunakan kode lain.');
        } else {
            db_query(
                "INSERT INTO jenis_barang (kode_barang, nama_barang, kategori_jenis, satuan, keterangan, status_aktif)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$kode, $nama, $kategori, $satuan, $keterangan, $status ? 'true' : 'false']
            );
            setAlert('success', 'Data jenis barang berhasil disimpan.');
        }
        redirect('jenis_barang.php');
    }

    if ($aksi === 'ubah') {
        $id = (int) ($_POST['id_jenis'] ?? 0);
        db_query(
            "UPDATE jenis_barang SET nama_barang = ?, kategori_jenis = ?,
             satuan = ?, keterangan = ?, status_aktif = ?
             WHERE id_jenis = ?",
            [$nama, $kategori, $satuan, $keterangan, $status ? 'true' : 'false', $id]
        );
        setAlert('success', 'Data jenis barang berhasil diperbarui.');
        redirect('jenis_barang.php');
    }
}

if (isset($_GET['hapus'])) {
    $id  = (int) $_GET['hapus'];
    $cek = db_fetch("SELECT id_penilaian FROM penilaian WHERE id_jenis = ? LIMIT 1", [$id]);
    if ($cek) {
        setAlert('danger', 'Jenis barang tidak dapat dihapus karena sudah digunakan di penilaian.');
    } else {
        db_query("DELETE FROM jenis_barang WHERE id_jenis = ?", [$id]);
        setAlert('success', 'Data jenis barang berhasil dihapus.');
    }
    redirect('jenis_barang.php');
}

if (isset($_GET['edit'])) {
    $id        = (int) $_GET['edit'];
    $edit_data = db_fetch("SELECT * FROM jenis_barang WHERE id_jenis = ?", [$id]);
}

require_once 'header.php';
?>

<h1 class="page-title">Jenis Barang</h1>
<p class="page-subtitle">Kelola jenis pekerjaan / barang untuk penilaian vendor.</p>

<div class="card" style="margin-bottom:18px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
    <button onclick="document.getElementById('modal-tambah').style.display='flex'" class="btn btn-primary">+ Tambah Jenis Barang</button>
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label class="form-label">Cari</label>
        <input type="text" name="search" class="form-control" placeholder="Nama atau kode" value="<?= htmlspecialchars($search) ?>">
      </div>
      <button type="submit" class="btn btn-secondary">Cari</button>
      <?php if ($search): ?>
      <a href="jenis_barang.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>No</th><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($barang_all as $b): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($b['kode_barang']) ?></td>
          <td><?= htmlspecialchars($b['nama_barang']) ?></td>
          <td><?= htmlspecialchars($b['kategori_jenis'] ?? '-') ?></td>
          <td>
            <a href="jenis_barang.php?edit=<?= $b['id_jenis'] ?>" class="btn btn-secondary btn-sm">Ubah</a>
            <a href="jenis_barang.php?hapus=<?= $b['id_jenis'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Hapus</a>
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
    <h3 style="margin-bottom:16px;font-size:18px">Tambah Jenis Barang</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Barang</label>
          <input type="text" name="kode_barang" class="form-control" placeholder="F1001" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Barang</label>
          <input type="text" name="nama_barang" class="form-control" placeholder="Nama jenis barang" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <input type="text" name="kategori_jenis" class="form-control" placeholder="Kategori" required>
        </div>
      </div>
      <input type="hidden" name="satuan" value="Pengadaan Barang">
      <input type="hidden" name="status_aktif" value="1">
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

<!-- MODAL UBAH -->
<?php if ($edit_data): ?>
<div id="modal-ubah" style="display:flex;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:8px;padding:24px;width:540px;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:16px;font-size:18px">Ubah Jenis Barang</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="ubah">
      <input type="hidden" name="id_jenis" value="<?= $edit_data['id_jenis'] ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Barang</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($edit_data['kode_barang']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Barang</label>
          <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($edit_data['nama_barang']) ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <input type="text" name="kategori_jenis" class="form-control" value="<?= htmlspecialchars($edit_data['kategori_jenis']) ?>" required>
        </div>
      </div>
      <input type="hidden" name="satuan" value="<?= htmlspecialchars($edit_data['satuan']) ?>">
      <input type="hidden" name="status_aktif" value="<?= $edit_data['status_aktif'] ? '1' : '0' ?>">
      <div class="form-group">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($edit_data['keterangan']) ?></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px">
        <button type="button" onclick="window.location='jenis_barang.php'" class="btn btn-secondary">Batal</button>
        <button type="submit" class="btn btn-primary">Perbarui</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
