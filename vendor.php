<?php
require_once 'database.php';
require_once 'session.php';
requireRole('Administrator');
 
$page_title  = 'Data Vendor';
$active_menu = 'vendor';
 
// TAMBAH
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi']) && $_POST['aksi']==='tambah') {
    $kode   = clean($conn, $_POST['kode_vendor']);
    $nama   = clean($conn, $_POST['nama_vendor']);
    $ket    = clean($conn, $_POST['keterangan']);
    $alamat = clean($conn, $_POST['alamat']);
    $telp   = clean($conn, $_POST['no_telepon']);
    $email  = clean($conn, $_POST['email']);
 
    $cek = mysqli_fetch_row(mysqli_query($conn,"SELECT id_vendor FROM vendor WHERE kode_vendor='$kode'"));
    if ($cek) {
        setAlert('danger','Kode vendor sudah digunakan. Gunakan kode yang berbeda.');
    } else {
        mysqli_query($conn,"INSERT INTO vendor (kode_vendor,nama_vendor,keterangan,alamat,no_telepon,email)
            VALUES ('$kode','$nama','$ket','$alamat','$telp','$email')");
        setAlert('success','Data vendor berhasil disimpan.');
    }
    redirect('vendor.php');
}
 
// UBAH
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi']) && $_POST['aksi']==='ubah') {
    $id     = (int)$_POST['id_vendor'];
    $nama   = clean($conn, $_POST['nama_vendor']);
    $ket    = clean($conn, $_POST['keterangan']);
    $alamat = clean($conn, $_POST['alamat']);
    $telp   = clean($conn, $_POST['no_telepon']);
    $email  = clean($conn, $_POST['email']);
    mysqli_query($conn,"UPDATE vendor SET nama_vendor='$nama',keterangan='$ket',
        alamat='$alamat',no_telepon='$telp',email='$email'
        WHERE id_vendor=$id");
    setAlert('success','Data vendor berhasil diperbarui.');
    redirect('vendor.php');
}
 
// HAPUS
if (isset($_GET['hapus'])) {
    $id  = (int)$_GET['hapus'];
    $cek = mysqli_fetch_row(mysqli_query($conn,"SELECT id_penilaian FROM penilaian WHERE id_vendor=$id LIMIT 1"));
    if ($cek) {
        setAlert('danger','Vendor tidak dapat dihapus karena masih memiliki data penilaian terkait.');
    } else {
        mysqli_query($conn,"DELETE FROM vendor WHERE id_vendor=$id");
        setAlert('success','Data vendor berhasil dihapus.');
    }
    redirect('vendor.php');
}
 
// GET EDIT
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM vendor WHERE id_vendor=$id"));
}
 
// SEARCH & FILTER
$search = clean($conn, $_GET['search'] ?? '');
$filter = clean($conn, $_GET['filter'] ?? '');
$where  = "WHERE 1=1";
if ($search) $where .= " AND (nama_vendor LIKE '%$search%' OR kode_vendor LIKE '%$search%')";
if ($filter) $where .= " AND keterangan='$filter'";
$vendors = mysqli_query($conn,"SELECT * FROM vendor $where ORDER BY kode_vendor");
 
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
    <option value="Lokal" <?= $filter==='Lokal'?'selected':'' ?>>Lokal</option>
    <option value="Impor" <?= $filter==='Impor'?'selected':'' ?>>Impor</option>
  </select>
  <button type="submit" class="btn btn-secondary">Cari</button>
  <?php if ($search||$filter): ?>
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
  <?php $no=1; while($v = mysqli_fetch_assoc($vendors)): ?>
  <tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($v['kode_vendor']) ?></td>
    <td><?= htmlspecialchars($v['nama_vendor']) ?></td>
    <td><span class="badge <?= $v['keterangan']==='Lokal'?'badge-primary':'badge-secondary' ?>">
      <?= $v['keterangan'] ?></span></td>
    <td>
      <a href="vendor.php?edit=<?= $v['id_vendor'] ?>" class="btn btn-secondary btn-sm">Ubah</a>
      <a href="vendor.php?hapus=<?= $v['id_vendor'] ?>" class="btn btn-danger btn-sm"
         onclick="return confirmDelete()">Hapus</a>
    </td>
  </tr>
  <?php endwhile; ?>
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
            <option value="Lokal" <?= $edit_data['keterangan']==='Lokal'?'selected':'' ?>>Lokal</option>
            <option value="Impor" <?= $edit_data['keterangan']==='Impor'?'selected':'' ?>>Impor</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Vendor</label>
        <input type="text" name="nama_vendor" class="form-control" value="<?= htmlspecialchars($edit_data['nama_vendor']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($edit_data['alamat']) ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">No. Telepon</label>
          <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($edit_data['no_telepon']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_data['email']) ?>">
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
 
<?php require_once '../../includes/footer.php'; ?>