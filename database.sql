-- ================================================
-- SISTEM PEMINJAMAN BUKU - DATABASE SETUP
-- ================================================

CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;

-- Tabel Users (Admin & Member)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    no_hp VARCHAR(20),
    alamat TEXT,
    foto VARCHAR(255) DEFAULT 'default.png',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Kategori Buku
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Buku
CREATE TABLE IF NOT EXISTS buku (
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
);

-- Tabel Peminjaman
CREATE TABLE IF NOT EXISTS peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pinjam VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali_rencana DATE NOT NULL,
    tanggal_kembali_aktual DATE,
    status ENUM('menunggu', 'dipinjam', 'dikembalikan', 'terlambat', 'ditolak') DEFAULT 'menunggu',
    denda DECIMAL(10,2) DEFAULT 0,
    catatan TEXT,
    admin_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Ulasan Buku
CREATE TABLE IF NOT EXISTS ulasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
);

-- Tabel Notifikasi
CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    pesan TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    tipe ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ================================================
-- DATA AWAL
-- ================================================

-- Admin default (password: admin123)
INSERT INTO users (nama, email, password, role, no_hp) VALUES
('Administrator', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '08123456789'),
('Budi Santoso', 'budi@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '08198765432'),
('Siti Rahayu', 'siti@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '08177778888');

-- Kategori
INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Fiksi', 'Novel dan cerita fiksi'),
('Non-Fiksi', 'Buku pengetahuan dan informasi faktual'),
('Sains & Teknologi', 'Buku ilmu pengetahuan dan teknologi'),
('Sejarah', 'Buku sejarah dan biografi'),
('Pendidikan', 'Buku pelajaran dan referensi'),
('Sastra', 'Karya sastra Indonesia dan dunia'),
('Bisnis', 'Buku bisnis, manajemen, dan keuangan'),
('Agama', 'Buku keagamaan dan spiritual');

-- Buku
INSERT INTO buku (isbn, judul, pengarang, penerbit, tahun_terbit, kategori_id, stok, stok_tersedia, deskripsi, lokasi_rak) VALUES
('978-602-8019-23-1', 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 6, 5, 4, 'Novel tentang semangat anak-anak Belitung dalam meraih pendidikan', 'A1'),
('978-979-433-012-0', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Lentera Dipantara', 1980, 6, 3, 3, 'Kisah Minke di era kolonial Belanda', 'A2'),
('978-602-220-111-5', 'Atomic Habits', 'James Clear', 'Gramedia', 2019, 7, 4, 3, 'Cara membangun kebiasaan baik dan menghilangkan kebiasaan buruk', 'B1'),
('978-979-22-9879-2', 'Sapiens: Riwayat Singkat Umat Manusia', 'Yuval Noah Harari', 'KPG', 2017, 4, 3, 2, 'Sejarah singkat umat manusia dari zaman batu hingga era modern', 'B2'),
('978-602-391-318-1', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 3, 2, 2, 'Panduan menulis kode yang bersih dan mudah dipelihara', 'C1'),
('978-979-655-234-5', 'Harry Potter dan Batu Bertuah', 'J.K. Rowling', 'Gramedia', 1997, 1, 6, 5, 'Petualangan seorang penyihir muda di Hogwarts', 'A3'),
('978-602-03-3025-9', 'Rich Dad Poor Dad', 'Robert T. Kiyosaki', 'Gramedia', 2000, 7, 4, 4, 'Pelajaran tentang keuangan dari dua ayah berbeda', 'B3'),
('978-979-91-0356-5', 'The Alchemist', 'Paulo Coelho', 'Gramedia', 1988, 1, 5, 4, 'Perjalanan seorang anak gembala menuju mimpinya', 'A4'),
('978-602-8799-34-2', 'Matematika Dasar SMA', 'Tim Penulis', 'Erlangga', 2020, 5, 8, 7, 'Buku pelajaran matematika untuk SMA', 'D1'),
('978-979-420-176-5', 'Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Serambi', 2010, 4, 3, 3, 'Sejarah Indonesia dari 1200 hingga masa kini', 'B4');
