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
