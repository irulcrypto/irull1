<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();

$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$bulan_str = sprintf('%04d-%02d', $tahun, $bulan);

// Statistik bulan ini
$stats_bulan = [
    'total_pinjam' => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE DATE_FORMAT(tanggal_pinjam,'%Y-%m')='$bulan_str'")->fetch_assoc()['c'],
    'dikembalikan' => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='dikembalikan' AND DATE_FORMAT(tanggal_kembali_aktual,'%Y-%m')='$bulan_str'")->fetch_assoc()['c'],
    'terlambat'    => $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='terlambat'")->fetch_assoc()['c'],
    'total_denda'  => $db->query("SELECT COALESCE(SUM(denda),0) c FROM peminjaman WHERE status='dikembalikan' AND DATE_FORMAT(tanggal_kembali_aktual,'%Y-%m')='$bulan_str'")->fetch_assoc()['c'],
    'anggota_baru' => $db->query("SELECT COUNT(*) c FROM users WHERE role='user' AND DATE_FORMAT(created_at,'%Y-%m')='$bulan_str'")->fetch_assoc()['c'],
];

// Buku terpopuler bulan ini
$top_buku = $db->query("SELECT b.judul, b.pengarang, COUNT(p.id) as total
    FROM peminjaman p JOIN buku b ON p.buku_id=b.id
    WHERE DATE_FORMAT(p.tanggal_pinjam,'%Y-%m')='$bulan_str'
    GROUP BY b.id ORDER BY total DESC LIMIT 10");

// Anggota teraktif
$top_user = $db->query("SELECT u.nama, u.email, COUNT(p.id) as total
    FROM peminjaman p JOIN users u ON p.user_id=u.id
    WHERE DATE_FORMAT(p.tanggal_pinjam,'%Y-%m')='$bulan_str'
    GROUP BY u.id ORDER BY total DESC LIMIT 10");

// Rekap per kategori
$per_kategori = $db->query("SELECT k.nama_kategori, COUNT(p.id) as total
    FROM peminjaman p JOIN buku b ON p.buku_id=b.id
    LEFT JOIN kategori k ON b.kategori_id=k.id
    WHERE DATE_FORMAT(p.tanggal_pinjam,'%Y-%m')='$bulan_str'
    GROUP BY k.id ORDER BY total DESC");

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$page_title = 'Laporan';
$base = '../';
include '../includes/header.php';
?>

<!-- Filter -->
<div class="card" style="padding:16px 20px;">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label>Bulan</label>
            <select name="bulan" style="width:140px">
                <?php for ($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>" <?= $bulan==$i?'selected':'' ?>><?= $nama_bulan[$i] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label>Tahun</label>
            <select name="tahun" style="width:100px">
                <?php for ($y=date('Y'); $y>=2020; $y--): ?>
                <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-gold" style="align-self:flex-end">📊 Tampilkan</button>
    </form>
</div>

<h2 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--brown-pale);margin-bottom:16px">
    Laporan: <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
</h2>

<!-- Stats bulan -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon">📚</div>
        <div><div class="stat-label">Total Peminjaman</div><div class="stat-value"><?= $stats_bulan['total_pinjam'] ?></div></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">✅</div>
        <div><div class="stat-label">Dikembalikan</div><div class="stat-value"><?= $stats_bulan['dikembalikan'] ?></div></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">⚠️</div>
        <div><div class="stat-label">Terlambat (aktif)</div><div class="stat-value"><?= $stats_bulan['terlambat'] ?></div></div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon">💰</div>
        <div><div class="stat-label">Total Denda</div><div class="stat-value" style="font-size:1.2rem"><?= formatRupiah($stats_bulan['total_denda']) ?></div></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon">👤</div>
        <div><div class="stat-label">Anggota Baru</div><div class="stat-value"><?= $stats_bulan['anggota_baru'] ?></div></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
<div class="card">
    <div class="card-header"><h3 class="card-title">📚 Buku Terpopuler Bulan Ini</h3></div>
    <?php if ($top_buku->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>#</th><th>Buku</th><th>Dipinjam</th></tr></thead>
            <tbody>
            <?php $i=1; while ($row=$top_buku->fetch_assoc()): ?>
            <tr>
                <td><strong style="color:var(--gold)"><?= $i++ ?></strong></td>
                <td>
                    <div style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($row['judul']) ?></div>
                    <div style="font-size:.72rem;color:var(--brown-pale)"><?= htmlspecialchars($row['pengarang']) ?></div>
                </td>
                <td><span class="badge badge-dipinjam"><?= $row['total'] ?>×</span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?><div class="empty-state"><div class="empty-icon">📊</div><p>Belum ada data</p></div><?php endif; ?>
</div>

<div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">👥 Anggota Teraktif</h3></div>
        <?php if ($top_user->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>#</th><th>Anggota</th><th>Pinjam</th></tr></thead>
                <tbody>
                <?php $i=1; while ($row=$top_user->fetch_assoc()): ?>
                <tr>
                    <td><strong style="color:var(--gold)"><?= $i++ ?></strong></td>
                    <td>
                        <div style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($row['nama']) ?></div>
                        <div style="font-size:.72rem;color:var(--brown-pale)"><?= htmlspecialchars($row['email']) ?></div>
                    </td>
                    <td><span class="badge badge-dipinjam"><?= $row['total'] ?>×</span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?><div class="empty-state"><div class="empty-icon">👥</div><p>Belum ada data</p></div><?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">🏷️ Per Kategori</h3></div>
        <?php if ($per_kategori->num_rows > 0): ?>
        <?php while ($row=$per_kategori->fetch_assoc()): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--cream)">
            <span style="font-size:.85rem;font-weight:500"><?= htmlspecialchars($row['nama_kategori']??'Tanpa Kategori') ?></span>
            <span class="badge badge-dipinjam"><?= $row['total'] ?>×</span>
        </div>
        <?php endwhile; ?>
        <?php else: ?><div class="empty-state"><div class="empty-icon">🏷️</div><p>Belum ada data</p></div><?php endif; ?>
    </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
