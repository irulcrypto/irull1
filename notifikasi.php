<?php
require_once '../includes/auth.php';
requireAdmin();
$db = getDB();
// Mark all read
if (isset($_GET['read_all'])) {
    $db->query("UPDATE notifikasi SET is_read=1 WHERE user_id={$_SESSION['user_id']}");
    header('Location: notifikasi.php'); exit;
}
$notifs = $db->query("SELECT * FROM notifikasi WHERE user_id={$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 50");
$db->query("UPDATE notifikasi SET is_read=1 WHERE user_id={$_SESSION['user_id']}");
$page_title = 'Notifikasi';
$base = '../';
include '../includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <p style="color:var(--brown-pale)">Notifikasi sistem</p>
    <a href="?read_all=1" class="btn btn-secondary btn-sm">✓ Tandai Semua Dibaca</a>
</div>
<div class="card">
    <?php if ($notifs->num_rows === 0): ?>
    <div class="empty-state"><div class="empty-icon">🔔</div><p>Tidak ada notifikasi</p></div>
    <?php else: while ($row = $notifs->fetch_assoc()): 
        $icons = ['success'=>'✅','danger'=>'❌','warning'=>'⚠️','info'=>'ℹ️'];
    ?>
    <div style="display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--cream);<?= !$row['is_read']?'background:rgba(201,168,76,.05);border-radius:10px;padding:14px;':'' ?>">
        <div style="font-size:1.3rem;flex-shrink:0"><?= $icons[$row['tipe']] ?? 'ℹ️' ?></div>
        <div style="flex:1">
            <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($row['judul']) ?></div>
            <div style="font-size:.82rem;color:var(--brown-pale);margin-top:3px"><?= htmlspecialchars($row['pesan']) ?></div>
            <div style="font-size:.72rem;color:#bbb;margin-top:6px"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></div>
        </div>
        <?php if (!$row['is_read']): ?><div style="width:8px;height:8px;background:var(--gold);border-radius:50%;flex-shrink:0;margin-top:4px"></div><?php endif; ?>
    </div>
    <?php endwhile; endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
