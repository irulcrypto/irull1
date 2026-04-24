<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'irull_db');
define('APP_NAME', 'LibraryKu');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/library');
define('DENDA_PER_HARI', 1000);

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;padding:20px;background:#fee;color:#c00;border:1px solid #c00;margin:20px;border-radius:8px;"><strong>Koneksi Database Gagal!</strong><br>' . $conn->connect_error . '</div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function generateKodePinjam() {
    return 'PJM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
function formatTanggal($tanggal) {
    if (!$tanggal) return '-';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $parts = explode('-', $tanggal);
    return $parts[2] . ' ' . $bulan[(int)$parts[1]] . ' ' . $parts[0];
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
