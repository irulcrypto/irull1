<?php
require_once '../includes/auth.php';
requireUser();
$db = getDB();
$uid = (int)$_SESSION['user_id'];

if (isset($_GET['read_all'])) {
    $db->query("UPDATE notifikasi SET is_read=1 WHERE user_id=$uid");
    header('Location: notifikasi.php'); exit;
}

$notifs = $db->query("SELECT * FROM notifikasi WHERE user_id=$uid ORDER BY created_at DESC LIMIT 50");
$db->query("UPDATE notifikasi SET is_read=1 WHERE user_id=$uid");

$page_title = 'Notifikasi';
$base = '../';
include '../includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <p style="color:var(--brown-pale)">Notifikasi terbaru Anda</p>
    <a href="?read_all=1" class="btn btn-sm btn-secondary">✓ Tandai Semua Dibaca</a>
</div>

<div class="card">
    <?php if ($notifs->num_rows === 0): ?>
    <div class="empty-state"><div class="empty-icon">🔔</div><p>Tidak ada notifikasi</p></div>
    <?php else: 
    $icons = ['success'=>'✅','danger'=>'❌','warning'=>'⚠️','info'=>'ℹ️'];
    while ($row = $notifs->fetch_assoc()): ?>
    <div style="display:flex;gap:14px;padding:16px 0;border-bottom:1px solid var(--cream);<?= !$row['is_read']?'background:rgba(201,168,76,.05);padding:16px;border-radius:12px;margin:4px 0;':'' ?>">
        <div style="font-size:1.4rem;flex-shrink:0;margin-top:2px"><?= $icons[$row['tipe']] ?? 'ℹ️' ?></div>
        <div style="flex:1">
            <div style="font-weight:700;font-size:.88rem;color:var(--brown)"><?= htmlspecialchars($row['judul']) ?></div>
            <div style="font-size:.82rem;color:var(--brown-pale);margin-top:4px;line-height:1.5"><?= htmlspecialchars($row['pesan']) ?></div>
            <div style="font-size:.72rem;color:#bbb;margin-top:8px">🕐 <?= date('d M Y, H:i', strtotime($row['created_at'])) ?></div>
        </div>
        <?php if (!$row['is_read']): ?><div style="width:8px;height:8px;background:var(--gold);border-radius:50%;flex-shrink:0;margin-top:6px"></div><?php endif; ?>
    </div>
    <?php endwhile; endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
