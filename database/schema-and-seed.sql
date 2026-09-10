-- Fase 1: Auth & User Management
-- Tabel lain (warehouse, product, purchase_order, dst) ditambahkan bertahap
-- di fase-fase berikutnya sesuai urutan pembangunan pada brief.

CREATE TABLE IF NOT EXISTS user (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Sales', 'WarehouseStaff') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed satu akun Admin demo untuk uji login.
-- Email   : admin@demo.test
-- Password: admin123
-- (hash di bawah dibuat dengan bcrypt cost 10 — kompatibel dengan password_verify() PHP)
INSERT INTO user (name, email, password_hash, role, is_active)
VALUES (
    'Admin Demo',
    'admin@demo.test',
    '$2b$10$v3dFCnUz0.Zvj4QzqqUIM.cJ5Gjj9rmTmqfjEl0C.E7CUVYePP7WK',
    'Admin',
    1
)
ON DUPLICATE KEY UPDATE email = email;

-- Dua akun tambahan buat uji segregation of duties (USR-01 & nanti SO-01):
-- Sales Demo   : sales1@demo.test / sales123
-- Warehouse Demo: gudang1@demo.test / gudang123
INSERT INTO user (name, email, password_hash, role, is_active)
VALUES (
    'Sales Demo',
    'sales1@demo.test',
    '$2b$10$UeVLD0qPUMMjy48LM7yH6uz/n50BJG1wL4BPN1Odp.8dmscKFcZ.a',
    'Sales',
    1
)
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO user (name, email, password_hash, role, is_active)
VALUES (
    'Warehouse Demo',
    'gudang1@demo.test',
    '$2b$10$LTOqX2u01mV7Q18JzYiB2uYTvCN10JgvfFvGuM5c6IAt3TCtJMpD6',
    'WarehouseStaff',
    1
)
ON DUPLICATE KEY UPDATE email = email;
