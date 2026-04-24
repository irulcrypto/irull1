<?php
require_once '../includes/auth.php';
requireUser();
$db = getDB();
$uid = (int)$_SESSION['user_id'];

/* =========================
   PINJAM REQUEST
========================= */
if (isset($_GET['pinjam']) && is_numeric($_GET['pinjam'])) {
    $buku_id = (int)$_GET['pinjam'];
    $buku = $db->query("SELECT * FROM buku WHERE id=$buku_id")->fetch_assoc();

    if (!$buku) {
        setFlash('danger', 'Buku tidak ditemukan.');
    } elseif ($buku['stok_tersedia'] < 1) {
        setFlash('danger', 'Stok buku habis.');
    } else {
        $sudah = $db->query("SELECT id FROM peminjaman 
            WHERE user_id=$uid AND buku_id=$buku_id 
            AND status IN ('menunggu','dipinjam','terlambat')")->num_rows;

        if ($sudah > 0) {
            setFlash('warning', 'Anda sudah meminjam buku ini.');
        } else {
            $_SESSION['buku_pinjam'] = $buku;
        }
    }
}

/* =========================
   SUBMIT PINJAM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_pinjam') {
    $buku_id = (int)$_POST['buku_id'];
    $tgl_pinjam = clean($_POST['tanggal_pinjam']);
    $tgl_kembali = clean($_POST['tanggal_kembali_rencana']);

    $buku = $db->query("SELECT * FROM buku WHERE id=$buku_id")->fetch_assoc();

    if (!$buku || $buku['stok_tersedia'] < 1) {
        setFlash('danger', 'Stok tidak tersedia.');
    } else {

        $kode = 'PJ' . time();

        $stmt = $db->prepare("INSERT INTO peminjaman 
        (kode_pinjam,user_id,buku_id,tanggal_pinjam,tanggal_kembali_rencana,status)
        VALUES (?,?,?,?,?,'menunggu')");

        $stmt->bind_param('siiss', $kode,$uid,$buku_id,$tgl_pinjam,$tgl_kembali);
        $stmt->execute();

        setFlash('success', "Permintaan terkirim. Kode: $kode");
    }

    header('Location: katalog.php'); exit;
}

/* =========================
   DETAIL
========================= */
$detail = null;
if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $bid = (int)$_GET['detail'];

    $detail = $db->query("
        SELECT b.*, k.nama_kategori,
        AVG(u.rating) as avg_rating,
        COUNT(u.id) as jml_ulasan
        FROM buku b
        LEFT JOIN kategori k ON b.kategori_id=k.id
        LEFT JOIN ulasan u ON b.id=u.buku_id
        WHERE b.id=$bid
        GROUP BY b.id
    ")->fetch_assoc();
}

/* =========================
   SEARCH + FILTER
========================= */
$search = clean($_GET['q'] ?? '');
$kat_filter = (int)($_GET['kategori'] ?? 0);
$tersedia = $_GET['tersedia'] ?? '';

$where = "WHERE 1=1";

if ($search) {
    $where .= " AND (b.judul LIKE '%$search%' 
                OR b.pengarang LIKE '%$search%' 
                OR b.penerbit LIKE '%$search%')";
}

if ($kat_filter) {
    $where .= " AND b.kategori_id=$kat_filter";
}

if ($tersedia == '1') {
    $where .= " AND b.stok_tersedia > 0";
}

/* =========================
   PAGINATION
========================= */
$per_page = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$total = $db->query("SELECT COUNT(*) c FROM buku b $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

/* =========================
   DATA BOOKS
========================= */
$books = $db->query("
    SELECT b.*, k.nama_kategori,
    AVG(u.rating) as avg_rating
    FROM buku b
    LEFT JOIN kategori k ON b.kategori_id=k.id
    LEFT JOIN ulasan u ON b.id=u.buku_id
    $where
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT $per_page OFFSET $offset
");

$kategoris = $db->query("SELECT * FROM kategori ORDER BY nama_kategori");

$page_title = 'Katalog Buku';
$base = '../';
include '../includes/header.php';
?>

<?php if ($detail): ?>
<!-- DETAIL -->
<div class="card">
    <h2><?= htmlspecialchars($detail['judul']) ?></h2>
    <p><?= htmlspecialchars($detail['pengarang']) ?></p>
</div>

<?php else: ?>

<!-- SEARCH -->
<div class="card">
<form method="GET">
    <input type="text" name="q" placeholder="Cari buku..." value="<?= htmlspecialchars($search) ?>">

    <select name="kategori">
        <option value="">Semua</option>
        <?php while($k = $kategoris->fetch_assoc()): ?>
        <option value="<?= $k['id'] ?>" <?= $kat_filter==$k['id']?'selected':'' ?>>
            <?= $k['nama_kategori'] ?>
        </option>
        <?php endwhile; ?>
    </select>

    <label>
        <input type="checkbox" name="tersedia" value="1" <?= $tersedia?'checked':'' ?>>
        Tersedia
    </label>

    <button type="submit">Cari</button>
</form>
</div>

<!-- BOOK GRID -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px">

<?php while($row = $books->fetch_assoc()): ?>
<div class="card">

<!-- COVER FIX FULL -->
<div style="height:180px;overflow:hidden;background:#f5f5f5;display:flex;align-items:center;justify-content:center">
<?php if (!empty($row['cover'])): ?>
    <img src="../uploads/cover/<?= $row['cover'] ?>"
         style="width:100%;height:100%;object-fit:cover">
<?php else: ?>
    📗
<?php endif; ?>
</div>

<div style="padding:10px">
    <b><?= htmlspecialchars($row['judul']) ?></b>
    <div><?= htmlspecialchars($row['pengarang']) ?></div>

    <small>
        <?= $row['stok_tersedia'] ?> tersedia
    </small>

    <div style="margin-top:8px">
        <a href="?detail=<?= $row['id'] ?>">Detail</a>

        <?php if ($row['stok_tersedia'] > 0): ?>
        <a href="?pinjam=<?= $row['id'] ?>">Pinjam</a>
        <?php endif; ?>
    </div>
</div>

</div>
<?php endwhile; ?>

</div>

<!-- PAGINATION -->
<div style="margin-top:20px">
<?php for($i=1;$i<=$total_pages;$i++): ?>
<a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&kategori=<?= $kat_filter ?>&tersedia=<?= $tersedia ?>">
    <?= $i ?>
</a>
<?php endfor; ?>
</div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>