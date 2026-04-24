<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();

if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $used = $db->query("SELECT COUNT(*) c FROM buku WHERE kategori_id=$id")->fetch_assoc()['c'];
    if ($used > 0) { setFlash('danger', "Kategori tidak bisa dihapus, masih digunakan $used buku."); }
    else { $db->query("DELETE FROM kategori WHERE id=$id"); setFlash('success', 'Kategori dihapus.'); }
    header('Location: kategori.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $nama = clean($_POST['nama_kategori'] ?? '');
    $desk = clean($_POST['deskripsi'] ?? '');
    if (empty($nama)) { setFlash('danger', 'Nama kategori wajib diisi.'); }
    elseif ($id > 0) {
        $stmt = $db->prepare("UPDATE kategori SET nama_kategori=?,deskripsi=? WHERE id=?");
        $stmt->bind_param('ssi', $nama,$desk,$id);
        $stmt->execute();
        setFlash('success', 'Kategori diperbarui.');
    } else {
        $stmt = $db->prepare("INSERT INTO kategori (nama_kategori,deskripsi) VALUES (?,?)");
        $stmt->bind_param('ss', $nama,$desk);
        $stmt->execute();
        setFlash('success', 'Kategori ditambahkan.');
    }
    header('Location: kategori.php'); exit;
}

$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_data = $db->query("SELECT * FROM kategori WHERE id=".(int)$_GET['edit'])->fetch_assoc();
}

$kategoris = $db->query("SELECT k.*, COUNT(b.id) as jumlah_buku FROM kategori k LEFT JOIN buku b ON k.id=b.kategori_id GROUP BY k.id ORDER BY k.nama_kategori");

$page_title = 'Kelola Kategori';
$base = '../';
include '../includes/header.php';
?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px">
<!-- List Kategori -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🏷️ Daftar Kategori</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>#</th><th>Nama Kategori</th><th>Deskripsi</th><th>Jumlah Buku</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php $no=1; while ($row = $kategoris->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                <td style="font-size:.82rem;color:var(--brown-pale)"><?= htmlspecialchars($row['deskripsi'] ?: '-') ?></td>
                <td><span class="badge badge-dipinjam"><?= $row['jumlah_buku'] ?></span></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-gold">✏️</a>
                    <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kategori ini?')">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Form Tambah/Edit -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= $edit_data ? '✏️ Edit Kategori' : '+ Tambah Kategori' ?></h3>
        <?php if ($edit_data): ?><a href="kategori.php" class="btn btn-sm btn-secondary">Batal</a><?php endif; ?>
    </div>
    <form method="POST">
        <?php if ($edit_data): ?><input type="hidden" name="id" value="<?= $edit_data['id'] ?>"><?php endif; ?>
        <div class="form-group">
            <label>Nama Kategori *</label>
            <input type="text" name="nama_kategori" required value="<?= htmlspecialchars($edit_data['nama_kategori'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi"><?= htmlspecialchars($edit_data['deskripsi'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">
            <?= $edit_data ? '💾 Simpan' : '+ Tambah' ?>
        </button>
    </form>
</div>
</div>

<?php include '../includes/footer.php'; ?>
