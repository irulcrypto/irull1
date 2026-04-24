<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();

/* =========================
   UPLOAD COVER
========================= */
function uploadCover($file) {
    if ($file['error'] === 4) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed)) return false;

    $namaBaru = uniqid('cover_') . '.' . $ext;
    $folder = __DIR__ . '/../uploads/cover/';

    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $tujuan = $folder . $namaBaru;

    return move_uploaded_file($file['tmp_name'], $tujuan) ? $namaBaru : false;
}

/* =========================
   DELETE
========================= */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $cek = $db->query("
        SELECT COUNT(*) c 
        FROM peminjaman 
        WHERE buku_id=$id AND status IN ('menunggu','dipinjam')
    ")->fetch_assoc()['c'];

    if ($cek > 0) {
        setFlash('danger','Buku masih dipinjam');
    } else {
        $db->query("DELETE FROM buku WHERE id=$id");
        setFlash('success','Buku dihapus');
    }

    header('Location: buku.php'); exit;
}

/* =========================
   GET EDIT DATA
========================= */
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = $db->query("SELECT * FROM buku WHERE id=$id")->fetch_assoc();
}

/* =========================
   ADD / UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);

    $isbn = clean($_POST['isbn'] ?? '');
    $judul = clean($_POST['judul'] ?? '');
    $pengarang = clean($_POST['pengarang'] ?? '');
    $kategori = (int)($_POST['kategori_id'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 1);

    /* =========================
       VALIDASI WAJIB (FIX FK ERROR)
    ========================= */
    if ($judul == '' || $pengarang == '') {
        setFlash('danger','Judul & pengarang wajib diisi');
        header('Location: buku.php'); exit;
    }

    if ($kategori <= 0) {
        setFlash('danger','Kategori wajib dipilih');
        header('Location: buku.php'); exit;
    }

    $cek_kategori = $db->query("SELECT id FROM kategori WHERE id=$kategori");
    if ($cek_kategori->num_rows == 0) {
        setFlash('danger','Kategori tidak valid');
        header('Location: buku.php'); exit;
    }

    $cover = uploadCover($_FILES['cover']);

    /* =========================
       EDIT
    ========================= */
    if ($id > 0) {

        $old = $db->query("SELECT stok,stok_tersedia FROM buku WHERE id=$id")->fetch_assoc();
        $stok_tersedia = max(0, $old['stok_tersedia'] + ($stok - $old['stok']));

        if ($cover) {
            $stmt = $db->prepare("
                UPDATE buku 
                SET isbn=?,judul=?,pengarang=?,kategori_id=?,stok=?,stok_tersedia=?,cover=?
                WHERE id=?
            ");
            $stmt->bind_param('sssiiisi',
                $isbn,$judul,$pengarang,$kategori,$stok,$stok_tersedia,$cover,$id
            );
        } else {
            $stmt = $db->prepare("
                UPDATE buku 
                SET isbn=?,judul=?,pengarang=?,kategori_id=?,stok=?,stok_tersedia=?
                WHERE id=?
            ");
            $stmt->bind_param('sssiiii',
                $isbn,$judul,$pengarang,$kategori,$stok,$stok_tersedia,$id
            );
        }

        $stmt->execute();
        setFlash('success','Buku diupdate');

    } else {

        if ($cover === false) {
            setFlash('danger','Cover tidak valid');
            header('Location: buku.php'); exit;
        }

        $stmt = $db->prepare("
            INSERT INTO buku 
            (isbn,judul,pengarang,kategori_id,stok,stok_tersedia,cover)
            VALUES (?,?,?,?,?,?,?)
        ");

        $stmt->bind_param('sssiiis',
            $isbn,$judul,$pengarang,$kategori,$stok,$stok,$cover
        );

        $stmt->execute();
        setFlash('success','Buku ditambah');
    }

    header('Location: buku.php'); exit;
}

/* =========================
   SEARCH + PAGINATION
========================= */
$search = clean($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page-1)*$limit;

$where = "WHERE 1=1";

if ($search) {
    $where .= " AND (judul LIKE '%$search%' OR pengarang LIKE '%$search%' OR isbn LIKE '%$search%')";
}

$total = $db->query("SELECT COUNT(*) c FROM buku $where")->fetch_assoc()['c'];
$pages = ceil($total/$limit);

$books = $db->query("
    SELECT * FROM buku 
    $where 
    ORDER BY id DESC 
    LIMIT $limit OFFSET $offset
");

/* =========================
   KATEGORI LIST
========================= */
$kategori_list = $db->query("SELECT * FROM kategori");

include '../includes/header.php';
?>

<!-- SEARCH -->
<div class="card" style="margin-bottom:15px">
<form method="GET" style="display:flex;gap:10px">

<input type="text" name="q"
value="<?= htmlspecialchars($search) ?>"
placeholder="Cari buku..."
style="flex:1;padding:10px">

<button class="btn btn-primary">Cari</button>
<a href="buku.php" class="btn btn-secondary">Reset</a>

</form>
</div>

<!-- TABLE -->
<div class="card">
<table>
<thead>
<tr>
<th>#</th>
<th>Cover</th>
<th>ISBN</th>
<th>Judul</th>
<th>Pengarang</th>
<th>Stok</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>
<?php $no=$offset+1; while($b=$books->fetch_assoc()): ?>
<tr>

<td><?= $no++ ?></td>

<td>
<?php if($b['cover']): ?>
<img src="../uploads/cover/<?= $b['cover'] ?>" style="width:40px;height:55px;object-fit:cover">
<?php else: ?>
<div style="width:40px;height:55px;background:#ddd"></div>
<?php endif; ?>
</td>

<td><?= $b['isbn'] ?></td>
<td><?= $b['judul'] ?></td>
<td><?= $b['pengarang'] ?></td>
<td><?= $b['stok'] ?></td>

<td>
<a href="?edit=<?= $b['id'] ?>">✏️</a>
<a href="?hapus=<?= $b['id'] ?>" onclick="return confirm('Hapus?')">🗑️</a>
</td>

</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- PAGINATION -->
<?php if($pages>1): ?>
<div class="pagination">
<?php for($i=1;$i<=$pages;$i++): ?>
<a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"
class="<?= $i==$page?'active':'' ?>">
<?= $i ?>
</a>
<?php endfor; ?>
</div>
<?php endif; ?>

</div>

<!-- FORM -->
<div class="card" style="margin-top:20px">

<h3><?= $edit_data ? 'Edit Buku' : 'Tambah Buku' ?></h3>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">

<input type="text" name="judul" placeholder="Judul"
value="<?= $edit_data['judul'] ?? '' ?>" required>

<input type="text" name="pengarang" placeholder="Pengarang"
value="<?= $edit_data['pengarang'] ?? '' ?>" required>

<input type="text" name="isbn"
value="<?= $edit_data['isbn'] ?? '' ?>">

<input type="number" name="stok"
value="<?= $edit_data['stok'] ?? 1 ?>">

<!-- KATEGORI FIX -->
<select name="kategori_id" required>
<option value="">-- Pilih Kategori --</option>
<?php while($k=$kategori_list->fetch_assoc()): ?>
<option value="<?= $k['id'] ?>"
<?= ($edit_data['kategori_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>>
<?= $k['nama_kategori'] ?>
</option>
<?php endwhile; ?>
</select>

<input type="file" name="cover">

<button type="submit">
<?= $edit_data ? 'Update' : 'Tambah' ?>
</button>

</form>

</div>

<?php include '../includes/footer.php'; ?>