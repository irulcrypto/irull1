<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();

// Statistik
$stats = [];
$stats['total_buku']     = $db->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
$stats['total_anggota']  = $db->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'];
$stats['dipinjam']       = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='dipinjam'")->fetch_assoc()['c'];
$stats['menunggu']       = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='menunggu'")->fetch_assoc()['c'];
$stats['terlambat']      = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='terlambat'")->fetch_assoc()['c'];
$stats['dikembalikan']   = $db->query("SELECT COUNT(*) c FROM peminjaman WHERE status='dikembalikan'")->fetch_assoc()['c'];

// Peminjaman terbaru
$recent_loans = $db->query("
    SELECT p.*, u.nama as nama_user, b.judul as judul_buku, b.pengarang
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b ON p.buku_id = b.id
    ORDER BY p.created_at DESC
    LIMIT 8
");

// Permintaan pending
$pending = $db->query("
    SELECT p.*, u.nama as nama_user, b.judul as judul_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b ON p.buku_id = b.id
    WHERE p.status = 'menunggu'
    ORDER BY p.created_at ASC
");

// Buku populer
$popular = $db->query("
    SELECT b.judul, b.pengarang, COUNT(p.id) as total_pinjam, b.stok_tersedia
    FROM buku b
    LEFT JOIN peminjaman p ON b.id = p.buku_id
    GROUP BY b.id
    ORDER BY total_pinjam DESC
    LIMIT 5
");

// Update status terlambat otomatis
$db->query("UPDATE peminjaman SET status='terlambat' 
    WHERE status='dipinjam' AND tanggal_kembali_rencana < CURDATE()");

$page_title = 'System Dashboard';
$base = '../';
include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">

<style>
    /* THEME PROXY / CYBER-OPS */
    :root {
        --bg-cyber: #0a0b10;
        --card-cyber: #12141d;
        --accent-cyan: #00f2ff;
        --accent-amber: #ffb300;
        --accent-red: #ff2a6d;
        --accent-green: #05ffa1;
        --text-proxy: #d1d5db;
        --border-cyber: rgba(0, 242, 255, 0.2);
    }

    body {
        background-color: var(--bg-cyber);
        color: var(--text-proxy);
        font-family: 'JetBrains Mono', monospace;
        background-image: 
            linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), 
            linear-gradient(90deg, rgba(255, 0, 0, 0.03), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.03));
        background-size: 100% 4px, 3px 100%;
    }

    /* STATS GRID NEON */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--card-cyber);
        border: 1px solid var(--border-cyber);
        padding: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 3px; height: 100%;
    }

    .stat-card.blue::before { background: var(--accent-cyan); box-shadow: 0 0 15px var(--accent-cyan); }
    .stat-card.green::before { background: var(--accent-green); box-shadow: 0 0 15px var(--accent-green); }
    .stat-card.gold::before { background: var(--accent-amber); box-shadow: 0 0 15px var(--accent-amber); }
    .stat-card.orange::before { background: #ff7b00; box-shadow: 0 0 15px #ff7b00; }
    .stat-card.red::before { background: var(--accent-red); box-shadow: 0 0 15px var(--accent-red); }

    .stat-card:hover {
        transform: scale(1.02);
        background: #1a1d29;
        border-color: var(--accent-cyan);
    }

    .stat-icon { font-size: 1.8rem; filter: drop-shadow(0 0 5px rgba(255,255,255,0.2)); }
    .stat-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.7; margin-bottom: 5px; }
    .stat-value { font-size: 1.8rem; font-weight: 800; color: #fff; line-height: 1; }

    /* LAYOUT CARDS */
    .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
    @media (max-width: 992px) { .grid-container { grid-template-columns: 1fr; } }

    .card {
        background: var(--card-cyber);
        border: 1px solid var(--border-cyber);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        display: flex;
        flex-direction: column;
    }

    .card-header {
        background: rgba(0, 242, 255, 0.05);
        border-bottom: 1px solid var(--border-cyber);
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title { color: var(--accent-cyan); font-size: 0.85rem; text-transform: uppercase; margin: 0; font-weight: 700; }

    /* TABLES STYLE */
    .table-responsive { width: 100%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
        background: rgba(255,255,255,0.02);
        color: var(--accent-amber);
        text-transform: uppercase;
        font-size: 0.7rem;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-cyber);
    }
    td { padding: 12px 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.8rem; vertical-align: middle; }
    tr:hover { background: rgba(0, 242, 255, 0.03); }

    /* COMPONENTS */
    .btn {
        text-transform: uppercase;
        font-weight: bold;
        font-size: 0.65rem;
        padding: 6px 12px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: 0.2s;
    }
    .btn-sm { padding: 4px 8px; }
    .btn-success { border: 1px solid var(--accent-green); color: var(--accent-green); background: transparent; }
    .btn-success:hover { background: var(--accent-green); color: #000; box-shadow: 0 0 10px var(--accent-green); }
    .btn-danger { border: 1px solid var(--accent-red); color: var(--accent-red); background: transparent; }
    .btn-danger:hover { background: var(--accent-red); color: #fff; box-shadow: 0 0 10px var(--accent-red); }
    .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }

    .badge { padding: 2px 6px; font-size: 0.65rem; border: 1px solid currentColor; background: rgba(0,0,0,0.2); }
    .badge-dipinjam { color: var(--accent-cyan); }
    .badge-menunggu { color: var(--accent-amber); }
    .badge-terlambat { color: var(--accent-red); }
    .badge-dikembalikan { color: var(--accent-green); }

    code { font-family: 'JetBrains Mono', monospace; color: var(--accent-cyan); background: rgba(0, 242, 255, 0.1); padding: 2px 4px; }
    .empty-state { padding: 40px; text-align: center; color: var(--text-proxy); opacity: 0.5; }
</style>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon">📚</div>
        <div>
            <div class="stat-label">Total Books</div>
            <div class="stat-value"><?= number_format($stats['total_buku']) ?></div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">👤</div>
        <div>
            <div class="stat-label">Users</div>
            <div class="stat-value"><?= number_format($stats['total_anggota']) ?></div>
        </div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon">📑</div>
        <div>
            <div class="stat-label">Active Loans</div>
            <div class="stat-value"><?= number_format($stats['dipinjam']) ?></div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon">📡</div>
        <div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= number_format($stats['menunggu']) ?></div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">🚨</div>
        <div>
            <div class="stat-label">Overdue</div>
            <div class="stat-value"><?= number_format($stats['terlambat']) ?></div>
        </div>
    </div>
</div>

<div class="grid-container">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">> Incoming_Requests.log</h3>
            <a href="peminjaman.php?filter=menunggu" class="btn btn-secondary">View All</a>
        </div>
        <div class="table-responsive">
            <?php if ($pending->num_rows > 0): ?>
            <table>
                <thead><tr><th>User</th><th>Book_Title</th><th>Request_Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($row = $pending->fetch_assoc()): ?>
                <tr>
                    <td><span style="color:#fff"><?= htmlspecialchars($row['nama_user']) ?></span></td>
                    <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= htmlspecialchars($row['judul_buku']) ?>
                    </td>
                    <td><?= formatTanggal($row['tanggal_pinjam']) ?></td>
                    <td>
                        <a href="peminjaman.php?approve=<?= $row['id'] ?>&token=<?= md5($row['id'].$_SESSION['user_id']) ?>"
                           class="btn btn-sm btn-success" onclick="return confirm('Authorize this request?')">ACC</a>
                        <a href="peminjaman.php?reject=<?= $row['id'] ?>&token=<?= md5($row['id'].$_SESSION['user_id']) ?>"
                           class="btn btn-sm btn-danger" onclick="return confirm('Deny access?')">X</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">No pending signals detected.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">> Most_Accessed_Data.db</h3>
            <a href="buku.php" class="btn btn-secondary">Database</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Rank</th><th>Data_Title</th><th>Access</th><th>Stock</th></tr></thead>
                <tbody>
                <?php $i = 1; while ($row = $popular->fetch_assoc()): ?>
                <tr>
                    <td>#0<?= $i++ ?></td>
                    <td>
                        <div style="color:#fff"><?= htmlspecialchars($row['judul']) ?></div>
                        <div style="font-size:0.65rem; opacity:0.6"><?= htmlspecialchars($row['pengarang']) ?></div>
                    </td>
                    <td><span class="badge badge-dipinjam"><?= $row['total_pinjam'] ?> hits</span></td>
                    <td>
                        <span style="color:<?= $row['stok_tersedia'] > 0 ? 'var(--accent-green)' : 'var(--accent-red)' ?>; font-weight:700">
                            <?= $row['stok_tersedia'] ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">> System_Activity_Monitor.exe</h3>
        <a href="peminjaman.php" class="btn btn-secondary">Global Log</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Session_ID</th><th>Member</th><th>Resource</th><th>Release</th><th>Due_Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($row = $recent_loans->fetch_assoc()): ?>
            <tr>
                <td><code><?= $row['kode_pinjam'] ?></code></td>
                <td><?= htmlspecialchars($row['nama_user']) ?></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= htmlspecialchars($row['judul_buku']) ?>
                </td>
                <td><?= formatTanggal($row['tanggal_pinjam']) ?></td>
                <td><?= formatTanggal($row['tanggal_kembali_rencana']) ?></td>
                <td><span class="badge badge-<?= $row['status'] ?>"><?= strtoupper($row['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>