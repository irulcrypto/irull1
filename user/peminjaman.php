<?php
require_once '../includes/auth.php';
requireUser();
$db = getDB();
$uid = (int)$_SESSION['user_id'];

// Update terlambat
$db->query("UPDATE peminjaman SET status='terlambat' WHERE status='dipinjam' AND tanggal_kembali_rencana < CURDATE() AND user_id=$uid");

// Batalkan permintaan
if (isset($_GET['batal']) && is_numeric($_GET['batal'])) {
    $id = (int)$_GET['batal'];
    $r  = $db->query("SELECT * FROM peminjaman WHERE id=$id AND user_id=$uid AND status='menunggu'")->fetch_assoc();
    if ($r) {
        $db->query("UPDATE peminjaman SET status='ditolak' WHERE id=$id");
        setFlash('success', 'Permintaan peminjaman dibatalkan.');
    }
    header('Location: peminjaman.php'); exit;
}

$filter = clean($_GET['filter'] ?? 'semua');
$where  = "WHERE p.user_id=$uid";
if ($filter !== 'semua') $where .= " AND p.status='$filter'";

$per_page = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$total  = $db->query("SELECT COUNT(*) c FROM peminjaman p $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

$loans  = $db->query("SELECT p.*, b.judul, b.pengarang, b.lokasi_rak FROM peminjaman p JOIN buku b ON p.buku_id=b.id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");

$status_opts = ['semua','menunggu','dipinjam','dikembalikan','terlambat','ditolak'];

$total_denda = $db->query("SELECT COALESCE(SUM(denda),0) c FROM peminjaman WHERE user_id=$uid AND status='dikembalikan'")->fetch_assoc()['c'];
$denda_aktif = 0;
$aktif_loans = $db->query("SELECT tanggal_kembali_rencana FROM peminjaman WHERE user_id=$uid AND status IN ('dipinjam','terlambat')");
while ($r = $aktif_loans->fetch_assoc()) {
    $denda_aktif += hitungDenda($r['tanggal_kembali_rencana']);
}

$page_title = 'Pinjaman Saya';
$base = '../';
include '../includes/header.php';
?>

<!-- Summary -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
    <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:var(--shadow-sm);border-left:4px solid var(--gold)">
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.8px;color:var(--brown-pale)">Total Peminjaman</div>
        <div style="font-size:2rem;font-weight:700;color:var(--brown)"><?= $total ?></div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:var(--shadow-sm);border-left:4px solid <?= $denda_aktif>0?'var(--red)':'var(--green)' ?>">
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.8px;color:var(--brown-pale)">Denda Berjalan</div>
        <div style="font-size:1.5rem;font-weight:700;color:<?= $denda_aktif>0?'var(--red)':'var(--green)' ?>"><?= $denda_aktif>0 ? formatRupiah($denda_aktif) : 'Tidak Ada' ?></div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:var(--shadow-sm);border-left:4px solid var(--blue)">
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.8px;color:var(--brown-pale)">Total Denda Dibayar</div>
        <div style="font-size:1.5rem;font-weight:700;color:var(--brown)"><?= formatRupiah($total_denda) ?></div>
    </div>
</div>

<!-- Filter -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <?php foreach ($status_opts as $s): ?>
    <a href="?filter=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-primary' : 'btn-secondary' ?>">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- List -->
<div class="card">
    <?php if ($loans->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <p>Tidak ada riwayat peminjaman. <a href="katalog.php" style="color:var(--gold)">Pinjam buku sekarang!</a></p>
    </div>
    <?php else: while ($row = $loans->fetch_assoc()):
        $denda = in_array($row['status'],['dipinjam','terlambat']) ? hitungDenda($row['tanggal_kembali_rencana']) : $row['denda'];
    ?>
    <div style="display:flex;gap:18px;padding:18px 0;border-bottom:1px solid var(--cream);align-items:flex-start">
        <div style="width:52px;height:68px;background:<?= $row['status']==='terlambat' ? '#FEE2E2' : 'var(--cream)' ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0">
            <?= $row['status']==='dikembalikan' ? '📘' : ($row['status']==='terlambat' ? '⚠️' : '📗') ?>
        </div>
        <div style="flex:1">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                <div>
                    <a href="../user/katalog.php?detail=<?= $row['buku_id'] ?>" style="font-weight:700;color:var(--brown);text-decoration:none;font-size:.92rem"><?= htmlspecialchars($row['judul']) ?></a>
                    <div style="font-size:.78rem;color:var(--brown-pale)"><?= htmlspecialchars($row['pengarang']) ?></div>
                </div>
                <span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
            </div>

            <div style="display:flex;gap:20px;margin-top:10px;flex-wrap:wrap">
                <div>
                    <span style="font-size:.7rem;color:var(--brown-pale);display:block">Kode</span>
                    <code style="font-size:.75rem;background:var(--cream);padding:2px 7px;border-radius:6px"><?= $row['kode_pinjam'] ?></code>
                </div>
                <div>
                    <span style="font-size:.7rem;color:var(--brown-pale);display:block">Pinjam</span>
                    <span style="font-size:.8rem;font-weight:600"><?= formatTanggal($row['tanggal_pinjam']) ?></span>
                </div>
                <div>
                    <span style="font-size:.7rem;color:var(--brown-pale);display:block">Kembali</span>
                    <span style="font-size:.8rem;font-weight:600;color:<?= (strtotime($row['tanggal_kembali_rencana'])<time() && !in_array($row['status'],['dikembalikan','ditolak'])) ? 'var(--red)' : 'var(--brown)' ?>">
                        <?= formatTanggal($row['tanggal_kembali_rencana']) ?>
                    </span>
                </div>
                <?php if ($row['tanggal_kembali_aktual']): ?>
                <div>
                    <span style="font-size:.7rem;color:var(--green);display:block">Dikembalikan</span>
                    <span style="font-size:.8rem;font-weight:600"><?= formatTanggal($row['tanggal_kembali_aktual']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($denda > 0): ?>
                <div>
                    <span style="font-size:.7rem;color:var(--red);display:block">Denda</span>
                    <span style="font-size:.8rem;font-weight:700;color:var(--red)"><?= formatRupiah($denda) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($row['lokasi_rak']): ?>
                <div>
                    <span style="font-size:.7rem;color:var(--brown-pale);display:block">Lokasi Rak</span>
                    <span style="font-size:.8rem;font-weight:600">📍 Rak <?= htmlspecialchars($row['lokasi_rak']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($row['status'] === 'menunggu'): ?>
        <a href="?batal=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Batalkan permintaan ini?')" style="flex-shrink:0">Batalkan</a>
        <?php endif; ?>
    </div>
    <?php endwhile; endif; ?>
</div>

<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php for ($i=1;$i<=$total_pages;$i++): ?>
    <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
