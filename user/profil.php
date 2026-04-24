<?php
require_once '../includes/auth.php';
requireUser();
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$user = getCurrentUser();

/* =========================
   FUNCTION UPLOAD FOTO
========================= */
function uploadFoto($file) {

    if (!isset($file) || $file['error'] === 4) return null;
    if ($file['error'] !== 0) return false;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) return false;

    $nama = uniqid('profile_', true) . '.' . $allowed[$mime];

    $folder = __DIR__ . '/../uploads/profile/';
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $folder . $nama)) {
        return false;
    }

    return $nama;
}

/* =========================
   UPDATE PROFIL (DITAMBAH FOTO)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profil') {

    $nama   = clean($_POST['nama'] ?? '');
    $no_hp  = clean($_POST['no_hp'] ?? '');
    $alamat = clean($_POST['alamat'] ?? '');

    $foto = uploadFoto($_FILES['foto']);

    if ($foto === false) {
        setFlash('danger', 'Format foto tidak valid.');
        header('Location: profil.php'); exit;
    }

    if ($foto) {
        $stmt = $db->prepare("UPDATE users SET nama=?, no_hp=?, alamat=?, foto=? WHERE id=?");
        $stmt->bind_param('ssssi', $nama, $no_hp, $alamat, $foto, $uid);
    } else {
        $stmt = $db->prepare("UPDATE users SET nama=?, no_hp=?, alamat=? WHERE id=?");
        $stmt->bind_param('sssi', $nama, $no_hp, $alamat, $uid);
    }

    $stmt->execute();

    $_SESSION['nama'] = $nama;
    setFlash('success', 'Profil berhasil diperbarui.');
    header('Location: profil.php'); exit;
}

/* =========================
   GANTI PASSWORD (TETAP)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ganti_password') {

    $lama      = $_POST['password_lama'] ?? '';
    $baru      = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (!password_verify($lama, $user['password'])) {
        setFlash('danger', 'Password lama tidak sesuai.');
    } elseif (strlen($baru) < 6) {
        setFlash('danger', 'Password baru minimal 6 karakter.');
    } elseif ($baru !== $konfirmasi) {
        setFlash('danger', 'Konfirmasi password tidak cocok.');
    } else {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password='$hash' WHERE id=$uid");
        setFlash('success', 'Password berhasil diganti.');
    }

    header('Location: profil.php'); exit;
}

/* =========================
   STATISTIK (TETAP)
========================= */
$total_pinjam  = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE user_id=$uid")->fetch_assoc()['c'];
$total_selesai = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE user_id=$uid AND status='dikembalikan'")->fetch_assoc()['c'];
$total_aktif   = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE user_id=$uid AND status IN ('dipinjam','terlambat')")->fetch_assoc()['c'];
$total_denda   = $db->query("SELECT COALESCE(SUM(denda),0) c FROM peminjaman WHERE user_id=$uid AND status='dikembalikan'")->fetch_assoc()['c'];
$total_ulasan  = $db->query("SELECT COUNT(*) c FROM ulasan WHERE user_id=$uid")->fetch_assoc()['c'];

$fav_kategori = $db->query("
    SELECT k.nama_kategori, COUNT(p.id) as total
    FROM peminjaman p 
    JOIN buku b ON p.buku_id = b.id
    JOIN kategori k ON b.kategori_id = k.id
    WHERE p.user_id = $uid
    GROUP BY k.id ORDER BY total DESC LIMIT 3
");

$riwayat = $db->query("
    SELECT p.*, b.judul, b.pengarang 
    FROM peminjaman p 
    JOIN buku b ON p.buku_id=b.id 
    WHERE p.user_id=$uid 
    ORDER BY p.created_at DESC LIMIT 5
");

$page_title = 'Profil Saya';
$base = '../';
include '../includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px">

<!-- =========================
     PROFIL KIRI (UI TIDAK DIUBAH)
========================= -->
<div>
    <div class="card" style="text-align:center;padding:32px 24px">

        <div style="width:90px;height:90px;border-radius:24px;overflow:hidden;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;background:linear-gradient(135deg,var(--brown),var(--brown-light));">

            <?php if (!empty($user['foto'])): ?>
                <img src="../uploads/profile/<?= $user['foto'] ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
                <span style="font-size:2.5rem;font-weight:900;color:var(--gold);font-family:'Playfair Display',serif">
                    <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                </span>
            <?php endif; ?>

        </div>

        <h2 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--brown);font-weight:900">
            <?= htmlspecialchars($user['nama']) ?>
        </h2>

        <p style="font-size:.82rem;color:var(--brown-pale)">
            <?= htmlspecialchars($user['email']) ?>
        </p>

        <span class="badge badge-aktif">● Anggota Aktif</span>

        <div style="margin-top:10px;font-size:.75rem;color:var(--brown-pale)">
            Bergabung: <?= formatTanggal(substr($user['created_at'], 0, 10)) ?>
        </div>
    </div>
</div>

<!-- =========================
     FORM KANAN (UI TIDAK DIUBAH)
========================= -->
<div>

    <!-- EDIT PROFIL -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">✏️ Edit Profil</h3>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profil">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                </div>

                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                </div>

                <div class="form-group full">
                    <label>Alamat</label>
                    <textarea name="alamat"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                </div>

                <!-- FOTO TAMBAHAN (UI TIDAK DIUBAH STRUKTURNYA) -->
                <div class="form-group full">
                    <label>Foto Profil</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
        </form>
    </div>

    <!-- GANTI PASSWORD (TETAP) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🔒 Ganti Password</h3>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="ganti_password">

            <input type="password" name="password_lama" placeholder="Password lama" required>
            <input type="password" name="password_baru" placeholder="Password baru" required>
            <input type="password" name="konfirmasi" placeholder="Konfirmasi" required>

            <button type="submit" class="btn btn-outline">🔑 Ganti Password</button>
        </form>
    </div>

</div>
</div>

<?php include '../includes/footer.php'; ?>