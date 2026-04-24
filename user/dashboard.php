<?php
require_once '../includes/auth.php';
requireUser();
$db = getDB();
$uid = (int)$_SESSION['user_id'];

// Update terlambat
$db->query("UPDATE peminjaman SET status='terlambat' WHERE status='dipinjam' AND tanggal_kembali_rencana < CURDATE() AND user_id=$uid");

$stats = [
    'dipinjam'     => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE user_id=$uid AND status='dipinjam'")->fetch_assoc()['c'],
    'menunggu'     => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE user_id=$uid AND status='menunggu'")->fetch_assoc()['c'],
    'terlambat'    => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE user_id=$uid AND status='terlambat'")->fetch_assoc()['c'],
    'total_pinjam' => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE user_id=$uid")->fetch_assoc()['c'],
];

$total_denda = $db->query("SELECT COALESCE(SUM(denda),0) c FROM peminjaman WHERE user_id=$uid AND status='dikembalikan'")->fetch_assoc()['c'];

// Pinjaman aktif
$aktif = $db->query("SELECT p.*, b.judul, b.pengarang, b.lokasi_rak FROM peminjaman p JOIN buku b ON p.buku_id=b.id WHERE p.user_id=$uid AND p.status IN ('dipinjam','terlambat','menunggu') ORDER BY p.created_at DESC");

// Buku baru (katalog)
$buku_baru = $db->query("SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id ORDER BY b.created_at DESC LIMIT 6");

$page_title = 'Beranda';
$base = '../';
include '../includes/header.php';
?>

<div style="background:linear-gradient(135deg,var(--brown),var(--brown-light));border-radius:20px;padding:28px 32px;margin-bottom:24px;color:#fff;position:relative;overflow:hidden">
    <div style="position:absolute;right:-20px;top:-20px;font-size:8rem;opacity:.08">📚</div>
    <div style="font-size:.8rem;letter-spacing:1px;text-transform:uppercase;opacity:.7;margin-bottom:6px">Selamat Datang</div>
    <h2 style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:900;margin-bottom:4px"><?= htmlspecialchars($_SESSION['nama']) ?> 👋</h2>
    <p style="opacity:.7;font-size:.88rem">Jelajahi koleksi buku dan nikmati membaca!</p>
    <a href="katalog.php" class="btn btn-gold" style="margin-top:16px;display:inline-flex">📚 Jelajahi Katalog →</a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card blue"><div class="stat-icon">📖</div><div><div class="stat-label">Sedang Dipinjam</div><div class="stat-value"><?= $stats['dipinjam'] ?></div></div></div>
    <div class="stat-card orange"><div class="stat-icon">⏳</div><div><div class="stat-label">Menunggu</div><div class="stat-value"><?= $stats['menunggu'] ?></div></div></div>
    <div class="stat-card red"><div class="stat-icon">⚠️</div><div><div class="stat-label">Terlambat</div><div class="stat-value"><?= $stats['terlambat'] ?></div></div></div>
    <div class="stat-card green"><div class="stat-icon">📚</div><div><div class="stat-label">Total Riwayat</div><div class="stat-value"><?= $stats['total_pinjam'] ?></div></div></div>
</div>

<?php if ($stats['terlambat'] > 0): ?>
<div class="alert alert-danger">
    ⚠️ Anda memiliki <strong><?= $stats['terlambat'] ?> buku terlambat</strong>. Denda berjalan <?= formatRupiah(DENDA_PER_HARI) ?>/hari. Segera kembalikan!
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:3fr 2fr;gap:24px">

<!-- Pinjaman Aktif -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Pinjaman Aktif Saya</h3>
        <a href="peminjaman.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
    </div>
    <?php if ($aktif->num_rows > 0): ?>
    <?php while ($row = $aktif->fetch_assoc()):
        $denda = in_array($row['status'],['dipinjam','terlambat']) ? hitungDenda($row['tanggal_kembali_rencana']) : 0;
    ?>
    <div style="display:flex;gap:16px;padding:14px 0;border-bottom:1px solid var(--cream)">
        <div style="width:44px;height:56px;background:var(--cream);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">📗</div>
        <div style="flex:1">
            <div style="font-weight:700;font-size:.88rem"><?= htmlspecialchars($row['judul']) ?></div>
            <div style="font-size:.75rem;color:var(--brown-pale)"><?= htmlspecialchars($row['pengarang']) ?></div>
            <div style="display:flex;gap:10px;align-items:center;margin-top:6px;flex-wrap:wrap">
                <span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                <span style="font-size:.72rem;color:var(--brown-pale)">📅 Kembali: <?= formatTanggal($row['tanggal_kembali_rencana']) ?></span>
                <?php if ($denda > 0): ?>
                <span style="font-size:.72rem;color:var(--red);font-weight:700">💸 <?= formatRupiah($denda) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <code style="font-size:.68rem;color:var(--brown-pale);background:var(--cream);padding:3px 7px;border-radius:6px;align-self:flex-start"><?= $row['kode_pinjam'] ?></code>
    </div>
    <?php endwhile; ?>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">😊</div>
        <p>Tidak ada pinjaman aktif. <a href="katalog.php" style="color:var(--gold)">Pinjam buku sekarang!</a></p>
    </div>
    <?php endif; ?>
</div>

<!-- Buku Terbaru -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">✨ Buku Terbaru</h3>
        <a href="katalog.php" class="btn btn-sm btn-secondary">Semua</a>
    </div>
    <?php while ($row = $buku_baru->fetch_assoc()): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--cream)">
        <div>
            <div style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($row['judul']) ?></div>
            <div style="font-size:.72rem;color:var(--brown-pale)"><?= htmlspecialchars($row['pengarang']) ?></div>
        </div>
        <?php if ($row['stok_tersedia'] > 0): ?>
        <a href="katalog.php?pinjam=<?= $row['id'] ?>" class="btn btn-sm btn-gold">Pinjam</a>
        <?php else: ?>
        <span style="font-size:.72rem;color:var(--red)">Habis</span>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>

</div>

<?php include '../includes/footer.php'; ?>
