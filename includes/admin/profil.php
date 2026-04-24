<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$user = getCurrentUser();

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profil') {
    $nama  = clean($_POST['nama'] ?? '');
    $no_hp = clean($_POST['no_hp'] ?? '');
    if (empty($nama)) { setFlash('danger', 'Nama tidak boleh kosong.'); }
    else {
        $stmt = $db->prepare("UPDATE users SET nama=?, no_hp=? WHERE id=?");
        $stmt->bind_param('ssi', $nama, $no_hp, $uid);
        $stmt->execute();
        $_SESSION['nama'] = $nama;
        setFlash('success', 'Profil berhasil diperbarui.');
    }
    header('Location: profil.php'); exit;
}

// Ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ganti_password') {
    $lama  = $_POST['password_lama'] ?? '';
    $baru  = $_POST['password_baru'] ?? '';
    $konf  = $_POST['konfirmasi'] ?? '';

    if (!password_verify($lama, $user['password'])) {
        setFlash('danger', 'Password lama tidak sesuai.');
    } elseif (strlen($baru) < 6) {
        setFlash('danger', 'Password baru minimal 6 karakter.');
    } elseif ($baru !== $konf) {
        setFlash('danger', 'Konfirmasi password tidak cocok.');
    } else {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password='$hash' WHERE id=$uid");
        setFlash('success', 'Password berhasil diganti.');
    }
    header('Location: profil.php'); exit;
}

// Statistik sistem
$sys = [
    'total_buku'      => $db->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'],
    'total_anggota'   => $db->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'],
    'total_pinjam'    => $db->query("SELECT COUNT(*) c FROM peminjaman")->fetch_assoc()['c'],
    'total_denda'     => $db->query("SELECT COALESCE(SUM(denda),0) c FROM peminjaman WHERE status='dikembalikan'")->fetch_assoc()['c'],
    'pending_today'   => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='menunggu'")->fetch_assoc()['c'],
    'terlambat'       => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='terlambat'")->fetch_assoc()['c'],
];

// Aktivitas admin (peminjaman yang dia approve)
$my_activity = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE admin_id=$uid")->fetch_assoc()['c'];

$page_title = 'Profil Admin';
$base = '../';
include '../includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px">

<!-- Kartu Admin -->
<div>
    <div class="card" style="text-align:center;padding:32px 24px">
        <!-- Avatar -->
        <div style="width:90px;height:90px;background:linear-gradient(135deg,var(--brown),var(--gold));border-radius:24px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:900;color:#fff;margin:0 auto 16px;font-family:'Playfair Display',serif;box-shadow:0 8px 24px rgba(61,43,31,.25)">
            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
        </div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--brown);font-weight:900"><?= htmlspecialchars($user['nama']) ?></h2>
        <p style="font-size:.82rem;color:var(--brown-pale);margin-top:4px"><?= htmlspecialchars($user['email']) ?></p>
        <div style="display:flex;justify-content:center;gap:8px;margin-top:10px">
            <span class="badge badge-admin">⚙️ Administrator</span>
            <span class="badge badge-aktif">● Aktif</span>
        </div>

        <div style="border-top:2px solid var(--cream);margin-top:20px;padding-top:16px">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.8px;color:var(--brown-pale);margin-bottom:10px">Aktivitas Saya</div>
            <div style="background:var(--cream);border-radius:12px;padding:16px">
                <div style="font-size:2rem;font-weight:700;color:var(--brown)"><?= $my_activity ?></div>
                <div style="font-size:.75rem;color:var(--brown-pale)">Peminjaman Diproses</div>
            </div>
        </div>

        <div style="border-top:2px solid var(--cream);margin-top:20px;padding-top:16px;text-align:left">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.8px;color:var(--brown-pale);margin-bottom:12px">📊 Statistik Sistem</div>
            <?php $stat_items = [
                ['icon'=>'📚','label'=>'Total Buku','val'=>number_format($sys['total_buku']),'color'=>'var(--blue)'],
                ['icon'=>'👥','label'=>'Total Anggota','val'=>number_format($sys['total_anggota']),'color'=>'var(--green)'],
                ['icon'=>'📋','label'=>'Total Peminjaman','val'=>number_format($sys['total_pinjam']),'color'=>'var(--gold)'],
                ['icon'=>'⏳','label'=>'Permintaan Pending','val'=>$sys['pending_today'],'color'=>'var(--orange)'],
                ['icon'=>'⚠️','label'=>'Buku Terlambat','val'=>$sys['terlambat'],'color'=>'var(--red)'],
                ['icon'=>'💰','label'=>'Total Denda','val'=>formatRupiah($sys['total_denda']),'color'=>'var(--brown)'],
            ]; foreach ($stat_items as $si): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--cream)">
                <span style="font-size:.82rem"><?= $si['icon'] ?> <?= $si['label'] ?></span>
                <span style="font-size:.85rem;font-weight:700;color:<?= $si['color'] ?>"><?= $si['val'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:16px;font-size:.75rem;color:var(--brown-pale)">
            Bergabung: <?= formatTanggal(substr($user['created_at'], 0, 10)) ?>
        </div>
    </div>
</div>

<!-- Form Edit -->
<div>
    <!-- Edit Profil -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">✏️ Edit Profil Admin</h3>
        </div>
        <form method="POST">
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
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f0f0f0;cursor:not-allowed;opacity:.7">
                    <small style="color:var(--brown-pale);font-size:.72rem">Email tidak dapat diubah</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
        </form>
    </div>

    <!-- Ganti Password -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🔒 Ganti Password</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ganti_password">
            <div class="form-group">
                <label>Password Lama *</label>
                <input type="password" name="password_lama" placeholder="••••••••" required>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Password Baru *</label>
                    <input type="password" name="password_baru" placeholder="Min. 6 karakter" required id="pw-baru">
                </div>
                <div class="form-group">
                    <label>Konfirmasi *</label>
                    <input type="password" name="konfirmasi" placeholder="Ulangi" required id="pw-konf">
                </div>
            </div>
            <div id="pw-msg" style="font-size:.8rem;margin-bottom:12px;display:none"></div>
            <button type="submit" class="btn btn-outline">🔑 Ganti Password</button>
        </form>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚡ Akses Cepat</h3>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <a href="peminjaman.php?filter=menunggu" class="btn btn-gold" style="justify-content:center;padding:14px">
                ⏳ Lihat Permintaan Pending
                <?php if ($sys['pending_today'] > 0): ?>
                <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:.7rem"><?= $sys['pending_today'] ?></span>
                <?php endif; ?>
            </a>
            <a href="peminjaman.php?filter=terlambat" class="btn btn-danger" style="justify-content:center;padding:14px">
                ⚠️ Buku Terlambat
                <?php if ($sys['terlambat'] > 0): ?>
                <span style="background:#fff;color:var(--red);border-radius:10px;padding:1px 7px;font-size:.7rem;font-weight:700"><?= $sys['terlambat'] ?></span>
                <?php endif; ?>
            </a>
            <a href="buku.php" class="btn btn-primary" style="justify-content:center;padding:14px">📚 Kelola Buku</a>
            <a href="laporan.php" class="btn btn-secondary" style="justify-content:center;padding:14px">📈 Lihat Laporan</a>
            <a href="anggota.php" class="btn btn-secondary" style="justify-content:center;padding:14px">👥 Kelola Anggota</a>
            <a href="kategori.php" class="btn btn-secondary" style="justify-content:center;padding:14px">🏷️ Kelola Kategori</a>
        </div>
    </div>
</div>

</div>

<script>
const pwBaru = document.getElementById('pw-baru');
const pwKonf = document.getElementById('pw-konf');
const msg = document.getElementById('pw-msg');
function checkMatch() {
    if (!pwKonf.value) { msg.style.display='none'; return; }
    if (pwBaru.value === pwKonf.value) {
        msg.style.display='block'; msg.style.color='var(--green)'; msg.textContent='✅ Password cocok';
    } else {
        msg.style.display='block'; msg.style.color='var(--red)'; msg.textContent='❌ Password tidak cocok';
    }
}
pwBaru.addEventListener('input', checkMatch);
pwKonf.addEventListener('input', checkMatch);
</script>

<?php include '../includes/footer.php'; ?>
