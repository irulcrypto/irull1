<?php
require_once 'includes/auth.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit;
}

$error = '';
$success = '';
$tab = $_GET['tab'] ?? 'login';

// Handle Login
if ($_POST['action'] ?? '' === 'login') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['foto'] = $user['foto'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
            exit;
        } else {
            $error = 'Email atau password salah, atau akun tidak aktif.';
        }
    }
}

// Handle Register
if ($_POST['action'] ?? '' === 'register') {
    $nama = clean($_POST['nama'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    $no_hp = clean($_POST['no_hp'] ?? '');

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
        $tab = 'register';
    } elseif ($password !== $konfirmasi) {
        $error = 'Password dan konfirmasi tidak cocok.';
        $tab = 'register';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
        $tab = 'register';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
            $tab = 'register';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (nama, email, password, no_hp, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->bind_param('ssss', $nama, $email, $hash, $no_hp);
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
                $tab = 'login';
            } else {
                $error = 'Gagal mendaftar. Coba lagi.';
                $tab = 'register';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Login Modern</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --accent: #8b5cf6; /* Violet */
            --accent-hover: #7c3aed;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.1);
            --error: #ef4444;
            --success: #10b981;
        }

        body {
            min-height: 100vh;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(59, 130, 246, 0.15) 0px, transparent 50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .orb {
            position: fixed;
            width: 400px;
            height: 400px;
            background: var(--accent);
            filter: blur(120px);
            opacity: 0.1;
            z-index: -1;
            border-radius: 50%;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from { transform: translate(-20%, -20%); }
            to { transform: translate(20%, 20%); }
        }

        .container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            z-index: 10;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            display: inline-block;
            filter: drop-shadow(0 0 15px var(--accent));
        }

        .logo h1 {
            font-weight: 800;
            font-size: 2.2rem;
            letter-spacing: -1px;
            background: linear-gradient(to right, #fff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .tabs {
            display: flex;
            background: rgba(15, 23, 42, 0.5);
            padding: 8px;
            margin: 20px 20px 0;
            border-radius: 16px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            background: transparent;
            border: none;
            color: var(--text-dim);
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-btn.active {
            background: var(--accent);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);
        }

        .tab-content { display: none; padding: 30px; animation: fadeIn 0.4s ease; }
        .tab-content.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-dim);
            margin-bottom: 8px;
            margin-left: 4px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            color: white;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.4);
        }

        .alert {
            padding: 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .alert-danger { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border-color: var(--error); }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #a7f3d0; border-color: var(--success); }

        .demo-info {
            margin-top: 25px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            border: 1px dashed var(--glass-border);
        }

        .demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }
        .demo-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 10px;
            font-size: 0.75rem;
            color: var(--text-dim);
        }
        .badge { font-weight: 800; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; margin-bottom: 4px; display: inline-block; }
        .badge-admin { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-user { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="orb"></div>

<div class="container">
    <div class="logo">
        <div class="logo-icon">🚀</div>
        <h1><?= APP_NAME ?></h1>
        <p style="color: var(--text-dim)">The Future of Digital Library</p>
    </div>

    <div class="card">
        <div class="tabs">
            <button class="tab-btn <?= $tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login', event)">Sign In</button>
            <button class="tab-btn <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register', event)">Sign Up</button>
        </div>

        <div class="tab-content <?= $tab === 'login' ? 'active' : '' ?>" id="tab-login">
            <?php if ($error && $tab === 'login'): ?>
                <div class="alert alert-danger">✕ <?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@company.com" required
                           value="<?= clean($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary">Enter Dashboard</button>
            </form>

            <div class="demo-info">
                <p style="font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">Quick Access Demo:</p>
                <div class="demo-grid">
                    <div class="demo-item">
                        <span class="badge badge-admin">Administrator</span><br>
                        admin@library.com
                    </div>
                    <div class="demo-item">
                        <span class="badge badge-user">Standard User</span><br>
                        budi@mail.com
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content <?= $tab === 'register' ? 'active' : '' ?>" id="tab-register">
            <?php if ($error && $tab === 'register'): ?>
                <div class="alert alert-danger">✕ <?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="nama" placeholder="John Doe" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="john@mail.com" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="no_hp" placeholder="0812...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Min. 6 char" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm</label>
                        <input type="password" name="konfirmasi" placeholder="Repeat" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Create Account</button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tab, event) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    // Remove active class from buttons
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    // Show current
    document.querySelector('#tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
</body>
</html>