<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();

/* =========================
   FUNCTION UPLOAD FOTO
========================= */
function uploadFoto($file) {
    if ($file['error'] === 4) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed)) return false;

    $namaBaru = uniqid() . '.' . $ext;
    $tujuan = __DIR__ . '/../uploads/profile/' . $namaBaru;

    if (move_uploaded_file($file['tmp_name'], $tujuan)) {
        return $namaBaru;
    }

    return false;
}

/* =========================
   TOGGLE STATUS
========================= */
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->query("UPDATE users SET status = IF(status='aktif','nonaktif','aktif') WHERE id=$id AND role='user'");
    setFlash('success', 'Status anggota berhasil diperbarui.');
    header('Location: anggota.php'); exit;
}

/* =========================
   RESET PASSWORD
========================= */
if (isset($_GET['reset_pw']) && is_numeric($_GET['reset_pw'])) {
    $id = (int)$_GET['reset_pw'];
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password='$hash' WHERE id=$id");
    setFlash('info', 'Password direset menjadi: password123');
    header('Location: anggota.php'); exit;
}

/* =========================
   GET EDIT DATA
========================= */
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id=? AND role='user'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
}

/* =========================
   POST HANDLER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama  = clean($_POST['nama'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $no_hp = clean($_POST['no_hp'] ?? '');
    $alamat = clean($_POST['alamat'] ?? '');

    /* =====================
       UPDATE
    ===================== */
    if (isset($_POST['update_id'])) {
        $id = (int)$_POST['update_id'];

        $foto = uploadFoto($_FILES['foto']);
        if ($foto === false) {
            setFlash('danger', 'Format gambar tidak valid.');
            header('Location: anggota.php'); exit;
        }

        if ($foto) {
            $stmt = $db->prepare("UPDATE users SET nama=?, email=?, no_hp=?, alamat=?, foto=? WHERE id=? AND role='user'");
            $stmt->bind_param('sssssi', $nama,$email,$no_hp,$alamat,$foto,$id);
        } else {
            $stmt = $db->prepare("UPDATE users SET nama=?, email=?, no_hp=?, alamat=? WHERE id=? AND role='user'");
            $stmt->bind_param('ssssi', $nama,$email,$no_hp,$alamat,$id);
        }

        $stmt->execute();
        setFlash('success', 'Data anggota berhasil diperbarui.');
        header('Location: anggota.php'); exit;
    }

    /* =====================
       TAMBAH
    ===================== */
    $check = $db->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param('s', $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        setFlash('danger', 'Email sudah terdaftar.');
    } else {
        $hash = password_hash('password123', PASSWORD_DEFAULT);

        $foto = uploadFoto($_FILES['foto']);
        if ($foto === false) {
            setFlash('danger', 'Format gambar tidak valid.');
            header('Location: anggota.php'); exit;
        }

        $stmt = $db->prepare("INSERT INTO users (nama,email,password,no_hp,alamat,foto,role) VALUES (?,?,?,?,?,?,'user')");
        $stmt->bind_param('ssssss', $nama,$email,$hash,$no_hp,$alamat,$foto);
        $stmt->execute();

        setFlash('success', "Anggota $nama berhasil ditambahkan.");
    }

    header('Location: anggota.php'); exit;
}

/* =========================
   PAGINATION (INI YANG TAMBAH)
========================= */
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

/* total data */
$total = $db->query("SELECT COUNT(*) as total FROM users WHERE role='user'")
            ->fetch_assoc()['total'];

$total_pages = ceil($total / $per_page);

/* data anggota */
$members = $db->query("
    SELECT * FROM users 
    WHERE role='user' 
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");

include '../includes/header.php';
?>

<div class="card">
<table>
<thead>
<tr>
<th>#</th>
<th>Nama & Email</th>
<th>No HP</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php $no = $offset + 1; while($row=$members->fetch_assoc()): ?>
<tr>

<td><?= $no++ ?></td>

<td>
<div style="display:flex;align-items:center;gap:10px">

<div style="width:34px;height:34px;background:var(--gold);border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--brown);overflow:hidden">

<?php if (!empty($row['foto'])): ?>
    <img src="../uploads/profile/<?= $row['foto'] ?>" style="width:100%;height:100%;object-fit:cover">
<?php else: ?>
    <?= strtoupper(substr($row['nama'],0,1)) ?>
<?php endif; ?>

</div>

<div>
<div><?= htmlspecialchars($row['nama']) ?></div>
<div style="font-size:.8rem"><?= htmlspecialchars($row['email']) ?></div>
</div>

</div>
</td>

<td><?= htmlspecialchars($row['no_hp']) ?></td>
<td><?= htmlspecialchars($row['status']) ?></td>

<td>
<a href="?edit=<?= $row['id'] ?>">Edit</a>
<a href="?toggle=<?= $row['id'] ?>">Toggle</a>
<a href="?reset_pw=<?= $row['id'] ?>">Reset</a>
</td>

</tr>
<?php endwhile; ?>

</tbody>
</table>

<!-- =========================
   PAGINATION UI
========================= -->
<?php if ($total_pages > 1): ?>
<div style="margin-top:15px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">

<?php if ($page > 1): ?>
    <a class="btn btn-secondary" href="?page=<?= $page-1 ?>">⬅ Prev</a>
<?php endif; ?>

<?php for ($i=1; $i<=$total_pages; $i++): ?>
    <a class="btn <?= $i==$page?'btn-primary':'btn-secondary' ?>" href="?page=<?= $i ?>">
        <?= $i ?>
    </a>
<?php endfor; ?>

<?php if ($page < $total_pages): ?>
    <a class="btn btn-secondary" href="?page=<?= $page+1 ?>">Next ➡</a>
<?php endif; ?>

</div>
<?php endif; ?>

</div>

<!-- =========================
   MODAL
========================= -->
<div id="modal-anggota" style="display:none;">
<form method="POST" enctype="multipart/form-data">

<?php if ($edit_data): ?>
<input type="hidden" name="update_id" value="<?= $edit_data['id'] ?>">
<?php endif; ?>

<input type="text" name="nama" value="<?= $edit_data['nama'] ?? '' ?>" required>
<input type="email" name="email" value="<?= $edit_data['email'] ?? '' ?>" required>
<input type="text" name="no_hp" value="<?= $edit_data['no_hp'] ?? '' ?>">
<textarea name="alamat"><?= $edit_data['alamat'] ?? '' ?></textarea>

<input type="file" name="foto">

<?php if (!empty($edit_data['foto'])): ?>
<img src="../uploads/profile/<?= $edit_data['foto'] ?>" width="60" style="border-radius:50%">
<?php endif; ?>

<button type="submit"><?= $edit_data ? 'Update' : 'Tambah' ?></button>

</form>
</div>

<?php if ($edit_data): ?>
<script>
document.getElementById('modal-anggota').style.display='block';
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>