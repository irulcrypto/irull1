<?php
// includes/header.php
// Usage: include dengan $page_title dan $base (relative path ke root)
$base = $base ?? '../';
$notif_count = countUnreadNotif();
$user = getCurrentUser();
$is_admin = isAdmin();
$nav_items = $is_admin ? [
    ['icon' => '📊', 'label' => 'Dashboard',    'href' => 'dashboard.php'],
    ['icon' => '📚', 'label' => 'Kelola Buku',  'href' => 'buku.php'],
    ['icon' => '📋', 'label' => 'Peminjaman',   'href' => 'peminjaman.php'],
    ['icon' => '👥', 'label' => 'Anggota',      'href' => 'anggota.php'],
    ['icon' => '🏷️', 'label' => 'Kategori',    'href' => 'kategori.php'],
    ['icon' => '📈', 'label' => 'Laporan',      'href' => 'laporan.php'],
] : [
    ['icon' => '🏠', 'label' => 'Beranda',      'href' => 'dashboard.php'],
    ['icon' => '📚', 'label' => 'Katalog Buku', 'href' => 'katalog.php'],
    ['icon' => '📋', 'label' => 'Pinjaman Saya','href' => 'peminjaman.php'],
    ['icon' => '🔔', 'label' => 'Notifikasi',   'href' => 'notifikasi.php'],
    ['icon' => '👤', 'label' => 'Profil',       'href' => 'profil.php'],
];
$current_file = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($page_title ?? 'Halaman') . ' — ' . APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --cream: #F5F0E8;
            --cream-dark: #EDE7D9;
            --brown: #3D2B1F;
            --brown-light: #6B4C3B;
            --brown-pale: #A07860;
            --gold: #C9A84C;
            --gold-light: #E8C96E;
            --sidebar-w: 260px;
            --header-h: 64px;
            --red: #C0392B;
            --green: #27AE60;
            --blue: #2563EB;
            --orange: #D97706;
            --shadow-sm: 0 2px 8px rgba(61,43,31,.08);
            --shadow-md: 0 8px 24px rgba(61,43,31,.12);
            --radius: 14px;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--brown);
            min-height: 100vh;
            display: flex;
        }
        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--brown);
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0;
            display: flex; flex-direction: column;
            z-index: 100;
            transition: transform .3s;
        }
        .sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-logo .logo-row {
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-logo .logo-icon {
            width: 42px; height: 42px;
            background: var(--gold);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .sidebar-logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            color: #fff;
            font-weight: 900;
        }
        .sidebar-logo .role-badge {
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--gold-light);
            font-weight: 600;
        }
        .sidebar-nav {
            flex: 1;
            padding: 16px 0;
            overflow-y: auto;
        }
        .nav-section {
            padding: 0 16px 8px;
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,.3);
            margin-top: 16px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 20px;
            color: rgba(255,255,255,.65);
            text-decoration: none;
            font-size: .88rem;
            font-weight: 500;
            border-radius: 0;
            transition: all .2s;
            margin: 1px 8px;
            border-radius: 10px;
            position: relative;
        }
        .nav-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .nav-item.active {
            background: rgba(201,168,76,.2);
            color: var(--gold-light);
            font-weight: 600;
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -8px; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 60%;
            background: var(--gold);
            border-radius: 2px;
        }
        .nav-icon { width: 20px; text-align: center; font-size: 1rem; }
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .user-card {
            display: flex; align-items: center; gap: 10px;
            padding: 10px;
            background: rgba(255,255,255,.06);
            border-radius: 12px;
            text-decoration: none;
        }
        .user-avatar {
            width: 36px; height: 36px;
            background: var(--gold);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: var(--brown);
        }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: .82rem; color: #fff; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-email { font-size: .7rem; color: rgba(255,255,255,.4); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .logout-btn {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 12px;
            margin-top: 8px;
            background: rgba(192,57,43,.2);
            color: #F87171;
            border-radius: 10px;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 600;
            transition: background .2s;
        }
        .logout-btn:hover { background: rgba(192,57,43,.35); }

        /* ===== MAIN ===== */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* ===== TOPBAR ===== */
        .topbar {
            height: var(--header-h);
            background: #fff;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px;
            border-bottom: 1px solid var(--cream-dark);
            position: sticky; top: 0; z-index: 50;
        }
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--brown);
        }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .notif-btn {
            width: 38px; height: 38px;
            background: var(--cream);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            font-size: 1.1rem;
            position: relative;
            transition: background .2s;
        }
        .notif-btn:hover { background: var(--cream-dark); }
        .notif-badge {
            position: absolute; top: 4px; right: 4px;
            width: 16px; height: 16px;
            background: var(--red);
            border-radius: 50%;
            font-size: .6rem;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .hamburger {
            display: none;
            background: none; border: none;
            font-size: 1.5rem; cursor: pointer;
            color: var(--brown);
        }
        /* ===== CONTENT ===== */
        .content {
            flex: 1;
            padding: 28px;
        }
        /* ===== COMPONENTS ===== */
        .alert {
            padding: 13px 18px;
            border-radius: 12px;
            font-size: .875rem;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-danger  { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
        .alert-warning { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
        .alert-info    { background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }

        .card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 24px;
            margin-bottom: 24px;
        }
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--cream);
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brown);
        }
        /* Stat Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            display: flex; align-items: center; gap: 16px;
            border-left: 4px solid transparent;
        }
        .stat-card.blue  { border-color: var(--blue); }
        .stat-card.green { border-color: var(--green); }
        .stat-card.gold  { border-color: var(--gold); }
        .stat-card.red   { border-color: var(--red); }
        .stat-card.orange{ border-color: var(--orange); }
        .stat-icon { font-size: 2rem; }
        .stat-label { font-size: .78rem; text-transform: uppercase; letter-spacing: .8px; color: var(--brown-pale); font-weight: 600; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--brown); line-height: 1; margin-top: 2px; }

        /* Table */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th {
            background: var(--cream);
            padding: 11px 14px;
            text-align: left;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--brown-pale);
            font-weight: 600;
            white-space: nowrap;
        }
        th:first-child { border-radius: 8px 0 0 8px; }
        th:last-child  { border-radius: 0 8px 8px 0; }
        td { padding: 12px 14px; border-bottom: 1px solid var(--cream); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(245,240,232,.5); }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .badge-menunggu { background: #FEF3C7; color: #92400E; }
        .badge-dipinjam { background: #DBEAFE; color: #1E40AF; }
        .badge-dikembalikan { background: #D1FAE5; color: #065F46; }
        .badge-terlambat { background: #FEE2E2; color: #991B1B; }
        .badge-ditolak { background: #F3F4F6; color: #6B7280; }
        .badge-aktif { background: #D1FAE5; color: #065F46; }
        .badge-nonaktif { background: #FEE2E2; color: #991B1B; }
        .badge-admin { background: rgba(201,168,76,.2); color: var(--brown); }
        .badge-user { background: #DBEAFE; color: #1E40AF; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .15s;
        }
        .btn-sm { padding: 5px 11px; font-size: .78rem; border-radius: 8px; }
        .btn-primary { background: var(--brown); color: #fff; }
        .btn-primary:hover { background: var(--brown-light); transform: translateY(-1px); }
        .btn-gold { background: var(--gold); color: var(--brown); }
        .btn-gold:hover { background: var(--gold-light); }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-danger:hover { background: #A93226; }
        .btn-success { background: var(--green); color: #fff; }
        .btn-success:hover { background: #219A52; }
        .btn-outline { background: transparent; color: var(--brown); border: 2px solid var(--brown); }
        .btn-outline:hover { background: var(--cream); }
        .btn-secondary { background: var(--cream-dark); color: var(--brown); }
        .btn-secondary:hover { background: #DDD6C8; }

        /* Form */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
        .form-group { margin-bottom: 18px; }
        .form-group.full { grid-column: 1 / -1; }
        label { display: block; font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: var(--brown); margin-bottom: 6px; }
        input, select, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            color: var(--brown);
            background: #FAFAFA;
            transition: all .2s;
            outline: none;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(201,168,76,.12);
        }
        textarea { resize: vertical; min-height: 80px; }

        /* Search bar */
        .search-bar {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 20px;
        }
        .search-input-wrap { position: relative; flex: 1; }
        .search-input-wrap input { padding-left: 38px; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: .9rem; color: #999; }

        /* Pagination */
        .pagination { display: flex; gap: 6px; justify-content: center; margin-top: 20px; }
        .page-link {
            padding: 7px 13px;
            border-radius: 8px;
            background: #fff;
            color: var(--brown);
            text-decoration: none;
            font-size: .83rem;
            font-weight: 600;
            border: 2px solid var(--cream-dark);
            transition: all .15s;
        }
        .page-link:hover, .page-link.active { background: var(--brown); color: #fff; border-color: var(--brown); }

        /* Empty state */
        .empty-state { text-align: center; padding: 48px 24px; color: var(--brown-pale); }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 12px; }
        .empty-state p { font-size: .9rem; }

        /* Mobile */
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 90; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .overlay.show { display: block; }
            .main { margin-left: 0; }
            .hamburger { display: block; }
            .form-grid, .form-grid-3 { grid-template-columns: 1fr; }
            .content { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-row">
            <div class="logo-icon">📚</div>
            <div>
                <h1><?= APP_NAME ?></h1>
                <div class="role-badge"><?= $is_admin ? '⚙️ Administrator' : '👤 Anggota' ?></div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menu Utama</div>
        <?php foreach ($nav_items as $item): 
            $is_active = basename($_SERVER['PHP_SELF']) === $item['href'];
        ?>
        <a href="<?= $item['href'] ?>" class="nav-item <?= $is_active ? 'active' : '' ?>">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
            <?php if ($item['href'] === 'notifikasi.php' && $notif_count > 0): ?>
                <span style="margin-left:auto;background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:.68rem;font-weight:700;"><?= $notif_count ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="profil.php" class="user-card">
            <div class="user-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
            </div>
        </a>
        <a href="<?= $base ?>logout.php" class="logout-btn" onclick="return confirm('Yakin ingin keluar?')">
            🚪 Keluar
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:14px">
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
            <span class="page-title"><?= $page_title ?? 'Halaman' ?></span>
        </div>
        <div class="topbar-right">
            <?php $notif_href = $is_admin ? 'notifikasi.php' : 'notifikasi.php'; ?>
            <a href="<?= $notif_href ?>" class="notif-btn">
                🔔
                <?php if ($notif_count > 0): ?>
                    <span class="notif-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>
    <div class="content">
        <?php showFlash(); ?>
