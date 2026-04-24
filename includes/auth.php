<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Cek role
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

// Wajib login, redirect jika belum
function requireLogin($redirect = '../index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

// Wajib admin
function requireAdmin() {
    requireLogin('../index.php');
    if (!isAdmin()) {
        header("Location: ../user/dashboard.php");
        exit;
    }
}

// Wajib user biasa
function requireUser() {
    requireLogin('../index.php');
    if (!isUser()) {
        header("Location: ../admin/dashboard.php");
        exit;
    }
}

// Set flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Get dan hapus flash message
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Tampilkan flash message sebagai HTML
function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = ['success' => '✅', 'danger' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
        $ic = $icon[$flash['type']] ?? 'ℹ️';
        echo "<div class='alert alert-{$flash['type']}'>$ic {$flash['message']}</div>";
    }
}

// Get info user yang sedang login
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $id = (int)$_SESSION['user_id'];
    $result = $db->query("SELECT * FROM users WHERE id = $id");
    return $result->fetch_assoc();
}

// Hitung notifikasi belum dibaca
function countUnreadNotif() {
    if (!isLoggedIn()) return 0;
    $db = getDB();
    $id = (int)$_SESSION['user_id'];
    $result = $db->query("SELECT COUNT(*) as total FROM notifikasi WHERE user_id = $id AND is_read = 0");
    $row = $result->fetch_assoc();
    return $row['total'];
}
