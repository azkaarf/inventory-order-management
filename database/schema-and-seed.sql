-- Placeholder schema & seed.
-- Diisi bertahap sesuai fase pengerjaan: auth/user -> master data -> purchase order ->
-- sales order -> stock ledger -> seed data untuk pagination (>=30 produk, >=25 order).
--
-- File ini dijalankan otomatis oleh container MySQL saat volume db_data dibuat pertama
-- kali (lewat docker-entrypoint-initdb.d). Kalau sudah pernah jalan sekali dan kamu ubah
-- isi file ini, volume lama harus dihapus dulu (docker compose down -v) supaya init ulang.

CREATE TABLE IF NOT EXISTS _healthcheck (
    id INT PRIMARY KEY AUTO_INCREMENT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
