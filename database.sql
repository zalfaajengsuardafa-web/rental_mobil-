DROP DATABASE IF EXISTS rental_mobil;
CREATE DATABASE rental_mobil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rental_mobil;

-- ============================================================
-- TABEL 1: users
-- ============================================================
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NULL     UNIQUE,
    password      VARCHAR(255) NOT NULL,
    nama_lengkap  VARCHAR(100) NOT NULL,
    role          ENUM('admin','petugas','pelanggan') NOT NULL DEFAULT 'pelanggan',
    no_telp       VARCHAR(20)  NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL 2: kategori_mobil
-- ============================================================
CREATE TABLE kategori_mobil (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nama      VARCHAR(50) NOT NULL UNIQUE,
    deskripsi TEXT
) ENGINE=InnoDB;

-- ============================================================
-- TABEL 3: mobil
-- ============================================================
CREATE TABLE mobil (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori     INT          NOT NULL,
    kode_mobil      VARCHAR(20)  NOT NULL UNIQUE,
    nama_mobil      VARCHAR(50)  NOT NULL,
    merek           VARCHAR(50)  NOT NULL,
    tahun           YEAR         NOT NULL,
    warna           VARCHAR(30)  NOT NULL,
    harga_per_hari  DECIMAL(12,2) NOT NULL,
    kapasitas       INT          NOT NULL DEFAULT 5,
    status          ENUM('tersedia','disewa','maintenance') NOT NULL DEFAULT 'tersedia',
    deskripsi       TEXT,
    gambar          VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori_mobil(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- TABEL 4: pelanggan
-- ============================================================
CREATE TABLE pelanggan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NULL,
    nama        VARCHAR(100) NOT NULL,
    nik         VARCHAR(20)  NOT NULL UNIQUE,
    telepon     VARCHAR(20)  NOT NULL,
    email       VARCHAR(100),
    alamat      TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABEL 5: transaksi
-- ============================================================
CREATE TABLE transaksi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi  VARCHAR(30)  NOT NULL UNIQUE,
    pelanggan_id    INT  NOT NULL,
    mobil_id        INT  NOT NULL,
    tanggal_mulai   DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    jumlah_hari     INT  NOT NULL DEFAULT 0,
    harga_per_hari  DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_harga     DECIMAL(12,2) NOT NULL DEFAULT 0,
    status          ENUM('aktif','selesai','dibatalkan') NOT NULL DEFAULT 'aktif',
    catatan         TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mobil_id)     REFERENCES mobil(id)      ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id)  ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- TABEL 6: log_transaksi
-- ============================================================
CREATE TABLE log_transaksi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id    INT          NOT NULL,
    kode_transaksi  VARCHAR(30)  NOT NULL,
    aksi            VARCHAR(20)  NOT NULL,
    status_lama     VARCHAR(20)  NULL,
    status_baru     VARCHAR(20)  NULL,
    keterangan      TEXT,
    waktu           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (username, email, password, nama_lengkap, role) VALUES
('admin', 'admin@rental.com',
 '$2y$10$2s33uXkDmr0RW/yZuZMomeTudZnl.W/9V1bAws7zCyC8Zu.tdqhIS',
 'Administrator', 'admin'),

('zalfa', 'zalfa@rental.com',
 '$2y$10$2s33uXkDmr0RW/yZuZMomeTudZnl.W/9V1bAws7zCyC8Zu.tdqhIS',
 'Zalfa Ajeng Suardafa', 'admin'),

('ajeng', 'budi@rental.com',
 '$2y$10$n.HBOiOkxAFo.fp8CgJaKegxgmqS.9MMampFYvl0pb0z4W85d9Iw2',
 'Budi Santoso', 'petugas'),

('azka', 'dewi@rental.com',
 '$2y$10$LS0sHdPSgi.uuOYg2RTd1.Ldpri182OAqiNCC9flTsAHBhNqid/ue',
 'Dewi Rahayu', 'petugas');

INSERT INTO kategori_mobil (nama, deskripsi) VALUES
('MPV',      'Multi Purpose Vehicle - cocok untuk keluarga besar'),
('City Car', 'Mobil kompak hemat bahan bakar untuk dalam kota'),
('SUV',      'Sport Utility Vehicle - tangguh di segala medan'),
('Sedan',    'Mobil nyaman dengan kabin senyap');

INSERT INTO mobil (id_kategori, kode_mobil, nama_mobil, merek, tahun, warna, harga_per_hari, kapasitas, status, deskripsi) VALUES
(1, 'MOB-001', 'Avanza',   'Toyota',     2023, 'Putih',   350000, 7, 'tersedia', 'MPV keluarga yang nyaman dan irit bahan bakar.'),
(2, 'MOB-002', 'Brio',     'Honda',      2022, 'Merah',   300000, 5, 'tersedia', 'City car lincah, cocok untuk dalam kota.'),
(1, 'MOB-003', 'Ertiga',   'Suzuki',     2023, 'Silver',  375000, 7, 'disewa',   'MPV lapang dengan kabin senyap.'),
(1, 'MOB-004', 'Xenia',    'Daihatsu',   2021, 'Hitam',   325000, 7, 'tersedia', 'MPV ekonomis untuk keluarga.'),
(1, 'MOB-005', 'Xpander',  'Mitsubishi', 2024, 'Abu-abu', 450000, 7, 'tersedia', 'Desain modern dengan ground clearance tinggi.'),
(1, 'MOB-006', 'Innova',   'Toyota',     2023, 'Putih',   500000, 7, 'tersedia', 'MPV premium, sangat nyaman untuk perjalanan jauh.'),
(2, 'MOB-007', 'Jazz',     'Honda',      2022, 'Biru',    280000, 5, 'disewa',   'Hatchback sporty dan responsif.'),
(3, 'MOB-008', 'Fortuner', 'Toyota',     2023, 'Hitam',   750000, 7, 'tersedia', 'SUV tangguh untuk segala medan.'),
(4, 'MOB-009', 'Civic',    'Honda',      2022, 'Putih',   450000, 5, 'tersedia', 'Sedan elegan dengan performa tinggi.');

INSERT INTO pelanggan (user_id, nama, nik, telepon, alamat, email) VALUES
(NULL, 'Fathur Rizki Adelia',          '3404112233445566', '081234567890', 'Jl. Mawar No.1, Yogyakarta',     'fathur@email.com'),
(NULL, 'Allysa Syifa Ramadhani Rizal', '3404223344556677', '082345678901', 'Jl. Melati No.5, Sleman',        'allysa@email.com'),
(NULL, 'Aulia Nafisatun',              '3404334455667788', '083456789012', 'Jl. Kenanga No.3, Bantul',       'aulia@email.com'),
(NULL, 'Zahra Nadiifa Azka Putri',     '3404445566778899', '084567890123', 'Jl. Anggrek No.7, Gunung Kidul', 'zahra@email.com'),
(NULL, 'Ajeng Lintang Rahayu',         '3404556677889900', '085678901234', 'Jl. Flamboyan No.2, Kulonprogo', 'ajeng@email.com'),
(NULL, 'Rizal Putra Perdana',          '3404667788990011', '086789012345', 'Jl. Cempaka No.9, Yogyakarta',   'rizal@email.com');

INSERT INTO transaksi (kode_transaksi, pelanggan_id, mobil_id, tanggal_mulai, tanggal_selesai, jumlah_hari, harga_per_hari, total_harga, status) VALUES
('TRX-20260601-001', 1, 3, '2026-06-01', '2026-06-03', 2, 375000,  750000,  'aktif'),
('TRX-20260602-001', 2, 7, '2026-06-02', '2026-06-05', 3, 280000,  840000,  'aktif'),
('TRX-20260520-001', 3, 1, '2026-05-20', '2026-05-22', 2, 350000,  700000,  'selesai'),
('TRX-20260515-001', 4, 2, '2026-05-15', '2026-05-17', 2, 300000,  600000,  'selesai'),
('TRX-20260510-001', 5, 5, '2026-05-10', '2026-05-12', 2, 450000,  900000,  'selesai'),
('TRX-20260525-001', 6, 8, '2026-05-25', '2026-05-28', 3, 750000, 2250000,  'selesai'),
('TRX-20260410-001', 1, 4, '2026-04-10', '2026-04-13', 3, 325000,  975000,  'selesai');


-- ============================================================
-- FUNCTIONS
-- ============================================================

DELIMITER $$

CREATE FUNCTION hitung_hari(
    p_tgl_mulai   DATE,
    p_tgl_selesai DATE
)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_hari INT;
    SET v_hari = DATEDIFF(p_tgl_selesai, p_tgl_mulai);
    IF v_hari < 0 THEN
        RETURN 0;
    END IF;
    RETURN v_hari;
END$$

DELIMITER $$

CREATE FUNCTION hitung_total_sewa(
    p_harga_per_hari DECIMAL(12,2),
    p_jumlah_hari    INT
)
RETURNS DECIMAL(12,2)
DETERMINISTIC
BEGIN
    IF p_jumlah_hari <= 0 THEN
        RETURN 0;
    END IF;
    RETURN p_harga_per_hari * p_jumlah_hari;
END$$

DELIMITER ;


-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

CREATE TRIGGER after_transaksi_insert
AFTER INSERT ON transaksi
FOR EACH ROW
BEGIN
    UPDATE mobil SET status = 'disewa' WHERE id = NEW.mobil_id;

    INSERT INTO log_transaksi (transaksi_id, kode_transaksi, aksi, status_lama, status_baru, keterangan)
    VALUES (
        NEW.id,
        NEW.kode_transaksi,
        'INSERT',
        NULL,
        NEW.status,
        CONCAT('Transaksi baru dibuat: mobil_id=', NEW.mobil_id,
               ', pelanggan_id=', NEW.pelanggan_id,
               ', selama ', NEW.jumlah_hari, ' hari')
    );
END$$

CREATE TRIGGER after_transaksi_update
AFTER UPDATE ON transaksi
FOR EACH ROW
BEGIN
    IF NEW.status IN ('selesai', 'dibatalkan') AND OLD.status = 'aktif' THEN
        UPDATE mobil SET status = 'tersedia' WHERE id = NEW.mobil_id;
    END IF;

    IF NEW.mobil_id <> OLD.mobil_id AND OLD.status = 'aktif' THEN
        UPDATE mobil SET status = 'tersedia' WHERE id = OLD.mobil_id;
        UPDATE mobil SET status = 'disewa'   WHERE id = NEW.mobil_id;
    END IF;

    INSERT INTO log_transaksi (transaksi_id, kode_transaksi, aksi, status_lama, status_baru, keterangan)
    VALUES (
        NEW.id,
        NEW.kode_transaksi,
        'UPDATE',
        OLD.status,
        NEW.status,
        CONCAT('Status transaksi berubah dari "', OLD.status, '" menjadi "', NEW.status, '"')
    );
END$$

DELIMITER ;


-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW v_transaksi_detail AS
SELECT
    t.id,
    t.kode_transaksi,
    t.tanggal_mulai,
    t.tanggal_selesai,
    t.jumlah_hari,
    t.harga_per_hari,
    t.total_harga,
    t.status,
    t.catatan,
    t.created_at,
    p.nama   AS nama_pelanggan,
    p.nik,
    p.telepon,
    m.kode_mobil,
    m.nama_mobil,
    m.merek,
    m.gambar   -- <-- PASTIKAN KOLOM INI DITAMBAHKAN
FROM transaksi t
JOIN pelanggan p ON t.pelanggan_id = p.id
JOIN mobil     m ON t.mobil_id     = m.id;

CREATE OR REPLACE VIEW v_statistik_mobil AS
SELECT
    m.id,
    m.kode_mobil,
    m.nama_mobil,
    m.merek,
    m.harga_per_hari,
    m.status,
    COUNT(t.id) AS total_disewa,
    COALESCE(SUM(CASE WHEN t.status = 'selesai' THEN t.total_harga ELSE 0 END), 0) AS total_pendapatan,
    MAX(CASE WHEN t.status = 'selesai' THEN t.tanggal_selesai ELSE NULL END) AS terakhir_disewa
FROM mobil m
LEFT JOIN transaksi t ON m.id = t.mobil_id
GROUP BY m.id, m.kode_mobil, m.nama_mobil, m.merek, m.harga_per_hari, m.status;

CREATE OR REPLACE VIEW v_pelanggan_aktif AS
SELECT
    p.id   AS id_pelanggan,
    p.nama,
    p.nik,
    p.telepon,
    p.email,
    COUNT(t.id)        AS jumlah_transaksi_aktif,
    SUM(t.total_harga) AS total_tagihan_aktif
FROM pelanggan p
JOIN transaksi t ON p.id = t.pelanggan_id
WHERE t.status = 'aktif'
GROUP BY p.id, p.nama, p.nik, p.telepon, p.email;

-- ============================================================
-- QUERY COMPLEX 1 : JOIN 3 TABEL
-- Menampilkan laporan transaksi aktif beserta data pelanggan dan data mobil yang disewa
-- ============================================================

SELECT
    t.kode_transaksi,
    p.nama AS pelanggan,
    p.telepon,
    CONCAT(m.merek, ' ', m.nama_mobil) AS mobil,
    m.kode_mobil,
    t.tanggal_mulai,
    t.tanggal_selesai,
    t.jumlah_hari,
    t.total_harga
FROM transaksi t
JOIN mobil m
    ON t.mobil_id = m.id
JOIN pelanggan p
    ON t.pelanggan_id = p.id
WHERE t.status = 'aktif'
ORDER BY t.tanggal_mulai DESC;


-- ============================================================
-- QUERY COMPLEX 2 : SUBQUERY
-- Menampilkan mobil yang belum pernah disewa
-- ============================================================

SELECT
    m.kode_mobil,
    CONCAT(m.merek, ' ', m.nama_mobil) AS nama_mobil,
    m.harga_per_hari,
    m.status
FROM mobil m
WHERE m.id NOT IN (
    SELECT DISTINCT mobil_id
    FROM transaksi
)
ORDER BY m.merek;


-- ============================================================
-- QUERY COMPLEX 3 : AGREGASI + GROUP BY + HAVING
-- Menampilkan pelanggan yang memiliki transaksi selesai
-- ============================================================

SELECT
    p.nama AS pelanggan,
    p.telepon,
    COUNT(t.id) AS total_transaksi,
    SUM(t.total_harga) AS total_pengeluaran,
    MAX(t.tanggal_mulai) AS transaksi_terakhir
FROM pelanggan p
JOIN transaksi t
    ON p.id = t.pelanggan_id
WHERE t.status = 'selesai'
GROUP BY
    p.id,
    p.nama,
    p.telepon
HAVING COUNT(t.id) >= 1
ORDER BY total_pengeluaran DESC;


-- ============================================================
-- QUERY COMPLEX 4
-- ============================================================

SELECT
    CONCAT(m.merek, ' ', m.nama_mobil) AS mobil,
    m.kode_mobil,
    m.harga_per_hari,

    (
        SELECT COUNT(*)
        FROM transaksi t2
        WHERE t2.mobil_id = m.id
        AND t2.status = 'selesai'
    ) AS total_disewa,

    (
        SELECT COALESCE(
            SUM(t3.total_harga),
            0
        )
        FROM transaksi t3
        WHERE t3.mobil_id = m.id
        AND t3.status = 'selesai'
    ) AS total_pendapatan

FROM mobil m
ORDER BY total_pendapatan DESC;