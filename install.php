<?php
/**
 * LIBRARYKU - AUTO INSTALLER
 * Akses sekali untuk membuat database dan data awal.
 * HAPUS file ini setelah instalasi selesai!
 */
$step = (int)($_GET['step'] ?? 0);
$errors = [];
$success_msgs = [];

// Step 1: Test koneksi
// Step 2: Buat tabel
// Step 3: Insert data awal

$config = [
    'host' => $_POST['host'] ?? 'localhost',
    'user' => $_POST['user'] ?? 'root',
    'pass' => $_POST['pass'] ?? '',
    'db'   => $_POST['db']   ?? 'library_db',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $conn = @new mysqli($config['host'], $config['user'], $config['pass']);
    
    if ($conn->connect_error) {
        $errors[] = "Koneksi gagal: " . $conn->connect_error;
    } else {
        $conn->set_charset('utf8mb4');
        $db_name = $conn->real_escape_string($config['db']);
        
        // Buat database
        if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            $errors[] = "Gagal membuat database: " . $conn->error;
        } else {
            $conn->select_db($db_name);
            $success_msgs[] = "✅ Database '$db_name' berhasil dibuat";

            // Buat semua tabel
            $tables = [
                "users" => "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nama VARCHAR(100) NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin','user') DEFAULT 'user',
                    no_hp VARCHAR(20),
                    alamat TEXT,
                    foto VARCHAR(255) DEFAULT 'default.png',
                    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
                "kategori" => "CREATE TABLE IF NOT EXISTS kategori (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nama_kategori VARCHAR(100) NOT NULL,
                    deskripsi TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "buku" => "CREATE TABLE IF NOT EXISTS buku (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    isbn VARCHAR(20) UNIQUE,
                    judul VARCHAR(200) NOT NULL,
                    pengarang VARCHAR(150) NOT NULL,
                    penerbit VARCHAR(150),
                    tahun_terbit YEAR,
                    kategori_id INT,
                    stok INT DEFAULT 0,
                    stok_tersedia INT DEFAULT 0,
                    deskripsi TEXT,
                    cover VARCHAR(255) DEFAULT 'default_book.png',
                    lokasi_rak VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
                )",
                "peminjaman" => "CREATE TABLE IF NOT EXISTS peminjaman (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    kode_pinjam VARCHAR(20) UNIQUE NOT NULL,
                    user_id INT NOT NULL,
                    buku_id INT NOT NULL,
                    tanggal_pinjam DATE NOT NULL,
                    tanggal_kembali_rencana DATE NOT NULL,
                    tanggal_kembali_aktual DATE,
                    status ENUM('menunggu','dipinjam','dikembalikan','terlambat','ditolak') DEFAULT 'menunggu',
                    denda DECIMAL(10,2) DEFAULT 0,
                    catatan TEXT,
                    admin_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE,
                    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
                )",
                "ulasan" => "CREATE TABLE IF NOT EXISTS ulasan (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    buku_id INT NOT NULL,
                    rating INT CHECK (rating BETWEEN 1 AND 5),
                    komentar TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
                )",
                "notifikasi" => "CREATE TABLE IF NOT EXISTS notifikasi (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    judul VARCHAR(200) NOT NULL,
                    pesan TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    tipe ENUM('info','warning','success','danger') DEFAULT 'info',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )",
            ];

            foreach ($tables as $name => $sql) {
                if ($conn->query($sql)) {
                    $success_msgs[] = "✅ Tabel '$name' berhasil dibuat";
                } else {
                    $errors[] = "Gagal buat tabel $name: " . $conn->error;
                }
            }

            if (empty($errors)) {
                // Insert data awal
                $pw = password_hash('password', PASSWORD_DEFAULT);

                $check_admin = $conn->query("SELECT id FROM users WHERE email='admin@library.com'")->num_rows;
                if ($check_admin === 0) {
                    $conn->query("INSERT INTO users (nama,email,password,role,no_hp) VALUES
                        ('Administrator','admin@library.com','$pw','admin','08123456789'),
                        ('Budi Santoso','budi@mail.com','$pw','user','08198765432'),
                        ('Siti Rahayu','siti@mail.com','$pw','user','08177778888')");
                    $success_msgs[] = "✅ Data admin & demo user ditambahkan";
                }

                $check_kat = $conn->query("SELECT COUNT(*) c FROM kategori")->fetch_assoc()['c'];
                if ($check_kat === 0) {
                    $conn->query("INSERT INTO kategori (nama_kategori,deskripsi) VALUES
                        ('Fiksi','Novel dan cerita fiksi'),
                        ('Non-Fiksi','Buku pengetahuan dan informasi faktual'),
                        ('Sains & Teknologi','Buku ilmu pengetahuan dan teknologi'),
                        ('Sejarah','Buku sejarah dan biografi'),
                        ('Pendidikan','Buku pelajaran dan referensi'),
                        ('Sastra','Karya sastra Indonesia dan dunia'),
                        ('Bisnis','Buku bisnis, manajemen, dan keuangan'),
                        ('Agama','Buku keagamaan dan spiritual')");
                    $success_msgs[] = "✅ Kategori buku ditambahkan";
                }

                $check_buku = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
                if ($check_buku === 0) {
                    $conn->query("INSERT INTO buku (isbn,judul,pengarang,penerbit,tahun_terbit,kategori_id,stok,stok_tersedia,deskripsi,lokasi_rak) VALUES
                        ('978-602-8019-23-1','Laskar Pelangi','Andrea Hirata','Bentang Pustaka',2005,6,5,5,'Novel tentang semangat anak-anak Belitung dalam meraih pendidikan','A1'),
                        ('978-979-433-012-0','Bumi Manusia','Pramoedya Ananta Toer','Lentera Dipantara',1980,6,3,3,'Kisah Minke di era kolonial Belanda','A2'),
                        ('978-602-220-111-5','Atomic Habits','James Clear','Gramedia',2019,7,4,4,'Cara membangun kebiasaan baik dan menghilangkan kebiasaan buruk','B1'),
                        ('978-979-22-9879-2','Sapiens','Yuval Noah Harari','KPG',2017,4,3,3,'Sejarah singkat umat manusia dari zaman batu hingga era modern','B2'),
                        ('978-602-391-318-1','Clean Code','Robert C. Martin','Prentice Hall',2008,3,2,2,'Panduan menulis kode yang bersih dan mudah dipelihara','C1'),
                        ('978-979-655-234-5','Harry Potter dan Batu Bertuah','J.K. Rowling','Gramedia',1997,1,6,6,'Petualangan seorang penyihir muda di Hogwarts','A3'),
                        ('978-602-03-3025-9','Rich Dad Poor Dad','Robert T. Kiyosaki','Gramedia',2000,7,4,4,'Pelajaran tentang keuangan dari dua ayah berbeda','B3'),
                        ('978-979-91-0356-5','The Alchemist','Paulo Coelho','Gramedia',1988,1,5,5,'Perjalanan seorang anak gembala menuju mimpinya','A4'),
                        ('978-602-8799-34-2','Matematika Dasar SMA','Tim Penulis','Erlangga',2020,5,8,8,'Buku pelajaran matematika untuk SMA','D1'),
                        ('978-979-420-176-5','Sejarah Indonesia Modern','M.C. Ricklefs','Serambi',2010,4,3,3,'Sejarah Indonesia dari 1200 hingga masa kini','B4')");
                    $success_msgs[] = "✅ 10 buku contoh ditambahkan";
                }

                // Update config.php otomatis
                $config_content = '<?php
define(\'DB_HOST\', \'' . addslashes($config['host']) . '\');
define(\'DB_USER\', \'' . addslashes($config['user']) . '\');
define(\'DB_PASS\', \'' . addslashes($config['pass']) . '\');
define(\'DB_NAME\', \'' . addslashes($config['db']) . '\');
define(\'APP_NAME\', \'LibraryKu\');
define(\'APP_URL\', \'http://\' . $_SERVER[\'HTTP_HOST\'] . \'/library\');
define(\'DENDA_PER_HARI\', 1000);

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die(\'<div style="font-family:sans-serif;padding:20px;background:#fee;color:#c00;border:1px solid #c00;margin:20px;border-radius:8px;"><strong>Koneksi Database Gagal!</strong><br>\' . $conn->connect_error . \'</div>\');
        }
        $conn->set_charset(\'utf8mb4\');
    }
    return $conn;
}

function generateKodePinjam() {
    return \'PJM-\' . date(\'Ymd\') . \'-\' . strtoupper(substr(uniqid(), -5));
}
function formatRupiah($angka) {
    return \'Rp \' . number_format($angka, 0, \',\', \'.\');
}
function formatTanggal($tanggal) {
    if (!$tanggal) return \'-\';
    $bulan = [\'\',\'Januari\',\'Februari\',\'Maret\',\'April\',\'Mei\',\'Juni\',\'Juli\',\'Agustus\',\'September\',\'Oktober\',\'November\',\'Desember\'];
    $parts = explode(\'-\', $tanggal);
    return $parts[2] . \' \' . $bulan[(int)$parts[1]] . \' \' . $parts[0];
}
function hitungDenda($tanggal_kembali_rencana, $tanggal_kembali_aktual = null) {
    $tgl_rencana = strtotime($tanggal_kembali_rencana);
    $tgl_aktual = $tanggal_kembali_aktual ? strtotime($tanggal_kembali_aktual) : time();
    $selisih = floor(($tgl_aktual - $tgl_rencana) / 86400);
    return $selisih > 0 ? $selisih * DENDA_PER_HARI : 0;
}
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
';
                file_put_contents(__DIR__ . '/includes/config.php', $config_content);
                $success_msgs[] = "✅ File config.php diperbarui otomatis";
                $step = 2; // Done!
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibraryKu — Installer</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #F5F0E8; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .installer { background: #fff; border-radius: 24px; box-shadow: 0 24px 80px rgba(61,43,31,.15); max-width: 560px; width: 100%; overflow: hidden; }
        .inst-header { background: linear-gradient(135deg, #3D2B1F, #6B4C3B); padding: 32px; color: #fff; text-align: center; }
        .inst-header .icon { font-size: 3rem; margin-bottom: 12px; }
        .inst-header h1 { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 900; }
        .inst-header p { opacity: .7; font-size: .88rem; margin-top: 6px; }
        .inst-body { padding: 32px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: #3D2B1F; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 10px 14px; border: 2px solid #E5E7EB; border-radius: 10px; font-size: .9rem; outline: none; transition: border-color .2s; }
        .form-group input:focus { border-color: #C9A84C; }
        .btn-install { width: 100%; padding: 14px; background: linear-gradient(135deg, #3D2B1F, #6B4C3B); color: #fff; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; transition: transform .2s; }
        .btn-install:hover { transform: translateY(-2px); }
        .alert { padding: 12px 16px; border-radius: 10px; font-size: .85rem; margin-bottom: 12px; }
        .alert-danger  { background: #FEE2E2; color: #991B1B; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .info-box { background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 12px; padding: 16px; font-size: .82rem; color: #92400E; margin-bottom: 20px; }
        .success-box { background: #D1FAE5; border-radius: 14px; padding: 24px; margin-bottom: 20px; }
        .success-list { list-style: none; }
        .success-list li { padding: 5px 0; font-size: .85rem; color: #065F46; }
        .btn-go { display: block; width: 100%; padding: 14px; background: #27AE60; color: #fff; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; margin-top: 12px; }
        .cred-box { background: #DBEAFE; border-radius: 12px; padding: 16px; margin-top: 16px; font-size: .82rem; color: #1E40AF; }
        .cred-box strong { display: block; font-size: .9rem; margin-bottom: 8px; color: #1E3A8A; }
        .cred-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
        .cred-item { background: #fff; padding: 8px 12px; border-radius: 8px; font-size: .78rem; }
        .warn-del { background: #FEE2E2; border-radius: 10px; padding: 12px; font-size: .8rem; color: #991B1B; margin-top: 16px; }
    </style>
</head>
<body>
<div class="installer">
    <div class="inst-header">
        <div class="icon">📚</div>
        <h1>LibraryKu Installer</h1>
        <p>Konfigurasi database dan instalasi awal sistem</p>
    </div>
    <div class="inst-body">

        <?php if ($step === 2 && empty($errors)): ?>
        <!-- SUKSES -->
        <div style="text-align:center;margin-bottom:20px">
            <div style="font-size:4rem">🎉</div>
            <h2 style="font-family:'Playfair Display',serif;color:#3D2B1F;margin-top:8px">Instalasi Berhasil!</h2>
        </div>
        <div class="success-box">
            <ul class="success-list">
                <?php foreach ($success_msgs as $m): ?><li><?= $m ?></li><?php endforeach; ?>
            </ul>
        </div>
        <div class="cred-box">
            <strong>🔑 Akun Demo (password: <code>password</code>)</strong>
            <div class="cred-grid">
                <div class="cred-item"><strong style="color:#C0392B">ADMIN</strong><br>admin@library.com</div>
                <div class="cred-item"><strong style="color:#2563EB">USER</strong><br>budi@mail.com</div>
            </div>
        </div>
        <a href="index.php" class="btn-go">🚀 Mulai Gunakan LibraryKu →</a>
        <div class="warn-del">⚠️ <strong>PENTING:</strong> Segera hapus file <code>install.php</code> ini setelah instalasi untuk keamanan!</div>

        <?php else: ?>
        <!-- FORM INSTALL -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($success_msgs)): ?>
            <?php foreach ($success_msgs as $m): ?>
            <div class="alert alert-success"><?= $m ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="info-box">
            ℹ️ Pastikan MySQL/MariaDB sudah berjalan. Script ini akan otomatis membuat database dan semua tabel yang diperlukan.
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="host" value="<?= htmlspecialchars($config['host']) ?>" placeholder="localhost">
            </div>
            <div class="form-group">
                <label>Username Database</label>
                <input type="text" name="user" value="<?= htmlspecialchars($config['user']) ?>" placeholder="root">
            </div>
            <div class="form-group">
                <label>Password Database</label>
                <input type="password" name="pass" value="<?= htmlspecialchars($config['pass']) ?>" placeholder="(kosong jika tidak ada)">
            </div>
            <div class="form-group">
                <label>Nama Database</label>
                <input type="text" name="db" value="<?= htmlspecialchars($config['db']) ?>" placeholder="library_db">
            </div>
            <button type="submit" name="install" class="btn-install">⚡ Install LibraryKu Sekarang</button>
        </form>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
