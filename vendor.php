<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Administrator');

$page_title  = 'Data Vendor';
$active_menu = 'vendor';

// TAMBAH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $kode   = trim($_POST['kode_vendor'] ?? '');
    $nama   = trim($_POST['nama_vendor'] ?? '');
    $ket    = trim($_POST['keterangan'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telp   = trim($_POST['no_telepon'] ?? '');
    $email  = trim($_POST['email'] ?? '');

    $cek = db_fetch("SELECT id_vendor FROM vendor WHERE kode_vendor = ?", [$kode]);
    if ($cek) {
        setAlert('danger', 'Kode vendor sudah digunakan. Gunakan kode yang berbeda.');
    } else {
        db_query(
            "INSERT INTO vendor (kode_vendor, nama_vendor, keterangan, alamat, no_telepon, email)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$kode, $nama, $ket, $alamat, $telp, $email]
        );
        setAlert('success', 'Data vendor berhasil disimpan.');
    }
    redirect('vendor.php');
}

// UBAH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'ubah') {
    $id     = (int) $_POST['id_vendor'];
    $nama   = trim($_POST['nama_vendor'] ?? '');
    $ket    = trim($_POST['keterangan'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telp   = trim($_POST['no_telepon'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    db_query(
        "UPDATE vendor SET nama_vendor = ?, keterangan = ?, alamat = ?, no_telepon = ?, email = ?
         WHERE id_vendor = ?",
        [$nama, $ket, $alamat, $telp, $email, $id]
    );
    setAlert('success', 'Data vendor berhasil diperbarui.');
    redirect('vendor.php');
}

// HAPUS
if (isset($_GET['hapus'])) {
    $id  = (int) $_GET['hapus'];
    $cek = db_fetch("SELECT id_penilaian FROM penilaian WHERE id_vendor = ? LIMIT 1", [$id]);
    if ($cek) {
        setAlert('danger', 'Vendor tidak dapat dihapus karena masih memiliki data penilaian terkait.');
    } else {
        db_query("DELETE FROM vendor WHERE id_vendor = ?", [$id]);
        setAlert('success', 'Data vendor berhasil dihapus.');
    }
    redirect('vendor.php');
}

// GET EDIT
$edit_data = null;
if (isset($_GET['edit'])) {
    $id        = (int) $_GET['edit'];
    $edit_data = db_fetch("SELECT * FROM vendor WHERE id_vendor = ?", [$id]);
}

// SEARCH & FILTER
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');

$params = [];
$where  = "WHERE 1=1";
if ($search) {
    $where   .= " AND (nama_vendor ILIKE ? OR kode_vendor ILIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($filter) {
    $where   .= " AND keterangan = ?";
    $params[] = $filter;
}
$vendors = db_fetch_all("SELECT * FROM vendor $where ORDER BY kode_vendor", $params);

require_once 'header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <h1 class="page-title" style="margin:0">Data Master Vendor</h1>
  <button onclick="document.getElementById('modal-tambah').style.display='flex'"
          class="btn btn-primary">+ Tambah Vendor</button>
</div>

<!-- SEARCH -->
<form method="GET" class="search-bar" style="margin-bottom:14px">
  <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode vendor..."
         value="<?= htmlspecialchars($search) ?>">
  <select name="filter" class="form-control" style="width:160px">
    <option value="">Semua keterangan</option>
    <option value="Lokal" <?= $filter === 'Lokal' ? 'selected' : '' ?>>Lokal</option>
    <option value="Impor" <?= $filter === 'Impor' ? 'selected' : '' ?>>Impor</option>
  </select>
  <button type="submit" class="btn btn-secondary">Cari</button>
  <?php if ($search || $filter): ?>
  <a href="vendor.php" class="btn btn-secondary">Reset</a>
  <?php endif; ?>
</form>

<!-- TABEL -->
<div class="card" style="padding:0;overflow:hidden">
<div class="tbl-wrap">
<table class="tbl">
  <thead>
    <tr>
      <th>No</th><th>Kode</th><th>Nama Vendor</th>
      <th>Keterangan</th><th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php $no = 1; foreach ($vendors as $v): ?>
  <tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($v['kode_vendor']) ?></td>
    <td><?= htmlspecialchars($v['nama_vendor']) ?></td>
    <td><span class="badge <?= $v['keterangan'] === 'Lokal' ? 'badge-primary' : 'badge-secondary' ?>">
      <?= $v['keterangan'] ?></span></td>
    <td>
      <a href="vendor.php?edit=<?= $v['id_vendor'] ?>" class="btn btn-secondary btn-sm">Ubah</a>
      <a href="vendor.php?hapus=<?= $v['id_vendor'] ?>" class="btn btn-danger btn-sm"
         onclick="return confirmDelete()">Hapus</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

<!-- MODAL TAMBAH -->
<div id="modal-tambah" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
     background:rgba(0,0,0,.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:8px;padding:24px;width:500px;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:16px;font-size:15px">Tambah Vendor</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Vendor <span style="color:red">*</span></label>
          <input type="text" name="kode_vendor" class="form-control" placeholder="VDR-001" required>
        </div>
        <div class="form-group">
          <label class="form-label">Keterangan <span style="color:red">*</span></label>
          <select name="keterangan" class="form-control" required>
            <option value="Lokal">Lokal</option>
            <option value="Impor">Impor</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Vendor <span style="color:red">*</span></label>
        <input type="text" name="nama_vendor" class="form-control" placeholder="Nama resmi vendor" required>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap vendor"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">No. Telepon</label>
          <input type="text" name="no_telepon" class="form-control" placeholder="08xx...">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="vendor@email.com">
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
        <button type="button" onclick="document.getElementById('modal-tambah').style.display='none'"
                class="btn btn-secondary">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL UBAH -->
<?php if ($edit_data): ?>
<div id="modal-ubah" style="display:flex;position:fixed;top:0;left:0;width:100%;height:100%;
     background:rgba(0,0,0,.4);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:8px;padding:24px;width:500px;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:16px;font-size:15px">Ubah Vendor</h3>
    <form method="POST">
      <input type="hidden" name="aksi" value="ubah">
      <input type="hidden" name="id_vendor" value="<?= $edit_data['id_vendor'] ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Vendor</label>
          <input type="text" class="form-control" value="<?= $edit_data['kode_vendor'] ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Keterangan</label>
          <select name="keterangan" class="form-control">
            <option value="Lokal" <?= $edit_data['keterangan'] === 'Lokal' ? 'selected' : '' ?>>Lokal</option>
            <option value="Impor" <?= $edit_data['keterangan'] === 'Impor' ? 'selected' : '' ?>>Impor</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Vendor</label>
        <input type="text" name="nama_vendor" class="form-control"
               value="<?= htmlspecialchars($edit_data['nama_vendor']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($edit_data['alamat']) ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">No. Telepon</label>
          <input type="text" name="no_telepon" class="form-control"
                 value="<?= htmlspecialchars($edit_data['no_telepon']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($edit_data['email']) ?>">
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
        <a href="vendor.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>