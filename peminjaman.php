<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();

// Update terlambat otomatis
$db->query("UPDATE peminjaman SET status='terlambat' WHERE status='dipinjam' AND tanggal_kembali_rencana < CURDATE()");

// Approve peminjaman
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $pinjam = $db->query("SELECT * FROM peminjaman WHERE id=$id AND status='menunggu'")->fetch_assoc();
    if ($pinjam) {
        $stok = $db->query("SELECT stok_tersedia FROM buku WHERE id={$pinjam['buku_id']}")->fetch_assoc()['stok_tersedia'];
        if ($stok > 0) {
            $db->query("UPDATE peminjaman SET status='dipinjam', admin_id={$_SESSION['user_id']} WHERE id=$id");
            $db->query("UPDATE buku SET stok_tersedia=stok_tersedia-1 WHERE id={$pinjam['buku_id']}");
            // Notifikasi
            $pesan = "Peminjaman buku Anda telah disetujui. Silakan ambil buku di perpustakaan.";
            $db->query("INSERT INTO notifikasi (user_id, judul, pesan, tipe) VALUES ({$pinjam['user_id']}, 'Peminjaman Disetujui', '$pesan', 'success')");
            setFlash('success', 'Peminjaman berhasil disetujui.');
        } else {
            setFlash('danger', 'Stok buku tidak tersedia.');
        }
    }
    header('Location: peminjaman.php'); exit;
}

// Reject peminjaman
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $pinjam = $db->query("SELECT * FROM peminjaman WHERE id=$id AND status='menunggu'")->fetch_assoc();
    if ($pinjam) {
        $db->query("UPDATE peminjaman SET status='ditolak', admin_id={$_SESSION['user_id']} WHERE id=$id");
        $pesan = "Maaf, permintaan peminjaman buku Anda ditolak. Hubungi petugas untuk informasi lebih lanjut.";
        $db->query("INSERT INTO notifikasi (user_id, judul, pesan, tipe) VALUES ({$pinjam['user_id']}, 'Peminjaman Ditolak', '$pesan', 'danger')");
        setFlash('warning', 'Peminjaman ditolak.');
    }
    header('Location: peminjaman.php'); exit;
}

// Kembalikan buku
if (isset($_GET['kembali']) && is_numeric($_GET['kembali'])) {
    $id = (int)$_GET['kembali'];
    $pinjam = $db->query("SELECT * FROM peminjaman WHERE id=$id AND status IN ('dipinjam','terlambat')")->fetch_assoc();
    if ($pinjam) {
        $denda = hitungDenda($pinjam['tanggal_kembali_rencana']);
        $today = date('Y-m-d');
        $db->query("UPDATE peminjaman SET status='dikembalikan', tanggal_kembali_aktual='$today', denda=$denda WHERE id=$id");
        $db->query("UPDATE buku SET stok_tersedia=stok_tersedia+1 WHERE id={$pinjam['buku_id']}");
        $msg = $denda > 0 ? "Buku dikembalikan. Denda: " . formatRupiah($denda) : "Buku berhasil dikembalikan tepat waktu.";
        $tipe = $denda > 0 ? 'warning' : 'success';
        $db->query("INSERT INTO notifikasi (user_id, judul, pesan, tipe) VALUES ({$pinjam['user_id']}, 'Buku Dikembalikan', '$msg', '$tipe')");
        setFlash('success', 'Buku berhasil dikembalikan. ' . ($denda > 0 ? 'Denda: ' . formatRupiah($denda) : ''));
    }
    header('Location: peminjaman.php'); exit;
}

// Tambah manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $user_id   = (int)$_POST['user_id'];
    $buku_id   = (int)$_POST['buku_id'];
    $tgl_pinjam = clean($_POST['tanggal_pinjam'] ?? date('Y-m-d'));
    $tgl_kembali = clean($_POST['tanggal_kembali_rencana'] ?? '');
    $catatan   = clean($_POST['catatan'] ?? '');

    $stok = $db->query("SELECT stok_tersedia FROM buku WHERE id=$buku_id")->fetch_assoc()['stok_tersedia'] ?? 0;
    if ($stok < 1) {
        setFlash('danger', 'Stok buku tidak tersedia.');
    } elseif (empty($tgl_kembali)) {
        setFlash('danger', 'Tanggal kembali wajib diisi.');
    } else {
        $kode = generateKodePinjam();
        $stmt = $db->prepare("INSERT INTO peminjaman (kode_pinjam,user_id,buku_id,tanggal_pinjam,tanggal_kembali_rencana,status,catatan,admin_id) VALUES (?,?,?,?,?,'dipinjam',?,?)");
        $stmt->bind_param('siisssi', $kode,$user_id,$buku_id,$tgl_pinjam,$tgl_kembali,$catatan,$_SESSION['user_id']);
        $stmt->execute();
        $db->query("UPDATE buku SET stok_tersedia=stok_tersedia-1 WHERE id=$buku_id");
        $pesan = "Buku berhasil dipinjamkan langsung oleh petugas. Kode: $kode";
        $db->query("INSERT INTO notifikasi (user_id,judul,pesan,tipe) VALUES ($user_id,'Peminjaman Dicatat','$pesan','success')");
        setFlash('success', "Peminjaman berhasil dicatat. Kode: $kode");
    }
    header('Location: peminjaman.php'); exit;
}

// Filter
$filter = clean($_GET['filter'] ?? 'semua');
$search = clean($_GET['q'] ?? '');
$where = "WHERE 1=1";
if ($filter !== 'semua') $where .= " AND p.status='$filter'";
if ($search) $where .= " AND (u.nama LIKE '%$search%' OR b.judul LIKE '%$search%' OR p.kode_pinjam LIKE '%$search%')";

$per_page = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$total = $db->query("SELECT COUNT(*) c FROM peminjaman p JOIN users u ON p.user_id=u.id JOIN buku b ON p.buku_id=b.id $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

$loans = $db->query("SELECT p.*,u.nama as nama_user,b.judul as judul_buku,b.pengarang FROM peminjaman p JOIN users u ON p.user_id=u.id JOIN buku b ON p.buku_id=b.id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");

$users  = $db->query("SELECT id,nama FROM users WHERE role='user' AND status='aktif' ORDER BY nama");
$books  = $db->query("SELECT id,judul,stok_tersedia FROM buku ORDER BY judul");

$status_opts = ['semua','menunggu','dipinjam','dikembalikan','terlambat','ditolak'];
$page_title = 'Kelola Peminjaman';
$base = '../';
include '../includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <p style="color:var(--brown-pale);font-size:.85rem"><?= number_format($total) ?> data ditemukan</p>
    <button class="btn btn-primary" onclick="document.getElementById('modal-tambah').style.display='block'">+ Catat Peminjaman</button>
</div>

<!-- Filter Tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <?php foreach ($status_opts as $s): ?>
    <a href="?filter=<?= $s ?>&q=<?= urlencode($search) ?>"
       class="btn btn-sm <?= $filter===$s ? 'btn-primary' : 'btn-secondary' ?>">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Search -->
<div class="card" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:10px">
        <input type="hidden" name="filter" value="<?= $filter ?>">
        <div class="search-input-wrap" style="flex:1">
            <span class="search-icon">🔍</span>
            <input type="text" name="q" placeholder="Cari anggota, judul buku, kode..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-gold">Cari</button>
        <a href="peminjaman.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Kode</th><th>Anggota</th><th>Buku</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Denda</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($loans->num_rows === 0): ?>
            <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📋</div><p>Tidak ada data peminjaman</p></div></td></tr>
            <?php else: while ($row = $loans->fetch_assoc()):
                $denda = ($row['status'] === 'dipinjam' || $row['status'] === 'terlambat') ? hitungDenda($row['tanggal_kembali_rencana']) : $row['denda'];
            ?>
            <tr>
                <td><code style="font-size:.72rem;background:var(--cream);padding:2px 6px;border-radius:4px"><?= $row['kode_pinjam'] ?></code></td>
                <td style="font-size:.85rem;font-weight:600"><?= htmlspecialchars($row['nama_user']) ?></td>
                <td>
                    <div style="font-size:.82rem;font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($row['judul_buku']) ?></div>
                    <div style="font-size:.7rem;color:var(--brown-pale)"><?= htmlspecialchars($row['pengarang']) ?></div>
                </td>
                <td style="font-size:.8rem"><?= formatTanggal($row['tanggal_pinjam']) ?></td>
                <td style="font-size:.8rem">
                    <?= formatTanggal($row['tanggal_kembali_rencana']) ?>
                    <?php if ($row['tanggal_kembali_aktual']): ?>
                    <div style="font-size:.7rem;color:var(--green)">✓ <?= formatTanggal($row['tanggal_kembali_aktual']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-weight:700;color:<?= $denda>0?'var(--red)':'var(--green)' ?>">
                    <?= $denda > 0 ? formatRupiah($denda) : '-' ?>
                </td>
                <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                <td>
                    <?php if ($row['status'] === 'menunggu'): ?>
                    <a href="?approve=<?= $row['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Setujui?')">✓ Setuju</a>
                    <a href="?reject=<?= $row['id'] ?>"  class="btn btn-sm btn-danger"  onclick="return confirm('Tolak?')">✗ Tolak</a>
                    <?php elseif (in_array($row['status'], ['dipinjam','terlambat'])): ?>
                    <a href="?kembali=<?= $row['id'] ?>" class="btn btn-sm btn-gold" onclick="return confirm('Tandai sebagai dikembalikan?')">📥 Kembalikan</a>
                    <?php else: ?>
                    <span style="font-size:.75rem;color:var(--brown-pale)">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?filter=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah Manual -->
<div id="modal-tambah" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;overflow-y:auto;padding:24px">
    <div style="background:#fff;border-radius:20px;max-width:560px;margin:0 auto;padding:32px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:2px solid var(--cream);padding-bottom:16px">
            <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--brown)">📋 Catat Peminjaman Baru</h3>
            <button onclick="document.getElementById('modal-tambah').style.display='none'" style="background:var(--cream);border:none;border-radius:8px;padding:6px 12px;cursor:pointer">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label>Anggota *</label>
                <select name="user_id" required>
                    <option value="">-- Pilih Anggota --</option>
                    <?php $users->data_seek(0); while ($u = $users->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Buku *</label>
                <select name="buku_id" required>
                    <option value="">-- Pilih Buku --</option>
                    <?php $books->data_seek(0); while ($b = $books->fetch_assoc()): ?>
                    <option value="<?= $b['id'] ?>" <?= $b['stok_tersedia']<1?'disabled':'' ?>>
                        <?= htmlspecialchars($b['judul']) ?> (Tersedia: <?= $b['stok_tersedia'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tanggal Pinjam *</label>
                    <input type="date" name="tanggal_pinjam" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Rencana Kembali *</label>
                    <input type="date" name="tanggal_kembali_rencana" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" placeholder="Catatan opsional..."></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" onclick="document.getElementById('modal-tambah').style.display='none'" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
