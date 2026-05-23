-- ============================================================
-- DATABASE: vansstore
-- Buat database ini di Laragon / phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS vansstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vansstore;

-- ============================================================
-- TABEL: users (pengguna)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150),
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: games (master game)
-- ============================================================
CREATE TABLE IF NOT EXISTS games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(150) NOT NULL,
    publisher VARCHAR(100),
    category ENUM('topup','via_login','voucher','live_app') DEFAULT 'topup',
    image_path VARCHAR(255),
    description TEXT,
    is_popular TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: diamond_packages (paket/harga per game)
-- ============================================================
CREATE TABLE IF NOT EXISTS diamond_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    package_name VARCHAR(150) NOT NULL,   -- contoh: "86 Diamonds"
    amount INT DEFAULT 0,                  -- jumlah diamond
    price DECIMAL(12,2) NOT NULL,          -- harga IDR
    bonus_amount INT DEFAULT 0,            -- bonus diamond
    category VARCHAR(50) DEFAULT 'topup', -- topup, membership
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: payment_methods
-- ============================================================
CREATE TABLE IF NOT EXISTS payment_methods (
    method_id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(100) NOT NULL,
    method_type ENUM('e-wallet','bank','minimarket','crypto') DEFAULT 'e-wallet',
    is_active TINYINT(1) DEFAULT 1,
    fee_pct DECIMAL(5,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: vouchers
-- ============================================================
CREATE TABLE IF NOT EXISTS vouchers (
    voucher_id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_code VARCHAR(50) NOT NULL UNIQUE,
    game_id INT,
    discount_pct DECIMAL(5,2) DEFAULT 0,
    valid_from DATE,
    valid_until DATE,
    created_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: topup_transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS topup_transactions (
    trx_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    game_id INT,
    package_id INT,
    game_account_id VARCHAR(100),
    payment_method_id INT,
    voucher_id INT,
    base_price DECIMAL(12,2),
    discount_amount DECIMAL(12,2) DEFAULT 0,
    final_price DECIMAL(12,2),
    status ENUM('pending','success','failed','refunded') DEFAULT 'pending',
    created_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES diamond_packages(package_id) ON DELETE SET NULL,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(method_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: admins
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL: admin default
-- password: admin123
-- ============================================================
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
-- DATA AWAL: payment methods
-- ============================================================
INSERT INTO payment_methods (method_name, method_type, is_active) VALUES
('GoPay', 'e-wallet', 1),
('OVO', 'e-wallet', 1),
('Dana', 'e-wallet', 1),
('ShopeePay', 'e-wallet', 1),
('Transfer BCA', 'bank', 1),
('Transfer BNI', 'bank', 1),
('Indomaret', 'minimarket', 1),
('Alfamart', 'minimarket', 1);

-- ============================================================
-- DATA AWAL: games populer
-- ============================================================
INSERT INTO games (game_name, publisher, category, image_path, is_popular, is_featured, is_active) VALUES
('Mobile Legends', 'Moonton', 'topup', 'assets/image/mobile legend.png', 1, 1, 1),
('Free Fire', 'Garena', 'topup', 'assets/image/logoff.png', 1, 1, 1),
('PUBG Mobile', 'Tencent Games', 'topup', 'assets/image/PUBG.png', 1, 0, 1),
('Honor of Kings', 'Tencent', 'topup', 'assets/image/HOK.png', 1, 0, 1),
('Black Clover M', 'Ic Game Studios', 'topup', 'assets/image/BLACK CLOVER.png', 1, 1, 1),
('Clash of Clans', 'Supercell', 'topup', 'assets/image/c6c.png', 0, 0, 1),
('Roblox', 'Roblox Corporation', 'voucher', 'assets/image/roblox.png', 1, 0, 1),
('Call of Duty Mobile', 'Activision', 'topup', 'assets/image/cod.png', 0, 0, 1),
('Zepeto', 'Zepeto', 'topup', 'assets/image/zepeto.png', 0, 0, 1),
('FC Mobile', 'EA Sports', 'topup', 'assets/image/fc mobile.png', 0, 0, 1);

-- ============================================================
-- DATA AWAL: diamond packages untuk Mobile Legends (game_id=1)
-- ============================================================
INSERT INTO diamond_packages (game_id, package_name, amount, price, category) VALUES
(1, '86 Diamonds', 86, 19000, 'topup'),
(1, '172 Diamonds', 172, 38000, 'topup'),
(1, '257 Diamonds', 257, 57000, 'topup'),
(1, '343 Diamonds', 343, 76000, 'topup'),
(1, '514 Diamonds', 514, 114000, 'topup'),
(1, '706 Diamonds', 706, 156000, 'topup'),
(1, '1412 Diamonds', 1412, 310000, 'topup'),
(1, 'Weekly Diamond Pass', 0, 35000, 'membership'),
(1, 'Twilight Pass', 0, 95000, 'membership');

-- DATA AWAL: packages untuk Free Fire (game_id=2)
INSERT INTO diamond_packages (game_id, package_name, amount, price, category) VALUES
(2, '100 Diamonds', 100, 16000, 'topup'),
(2, '200 Diamonds', 200, 32000, 'topup'),
(2, '500 Diamonds', 500, 80000, 'topup'),
(2, '1000 Diamonds', 1000, 160000, 'topup'),
(2, '2000 Diamonds', 2000, 320000, 'topup');

-- DATA AWAL: packages untuk Black Clover M (game_id=5)
INSERT INTO diamond_packages (game_id, package_name, amount, price, category) VALUES
(5, '43 Black Crystals', 43, 9000, 'topup'),
(5, '325 Black Crystals', 325, 65000, 'topup'),
(5, '470 Black Crystals', 470, 95000, 'topup'),
(5, '980 Black Crystals', 980, 195000, 'topup'),
(5, 'Paket Summon Harian 1', 0, 15000, 'membership'),
(5, 'Paket Summon Mingguan 1', 0, 50000, 'membership');
