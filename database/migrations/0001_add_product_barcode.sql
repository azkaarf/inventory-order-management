-- CONTOH/DEMONSTRASI SAJA - file ini SENGAJA TIDAK dijalankan otomatis dan
-- TIDAK disambungkan ke fitur aplikasi manapun (tidak ada field "barcode" di
-- form/Entity Product). Tujuannya murni menunjukkan pola migration yang
-- BEDA dari cara kerja project ini sehari-hari.
--
-- Alur normal project ini: setiap kali skema berubah, kita edit ulang
-- schema-and-seed.sql lalu `docker compose down -v` (buang semua data,
-- bikin ulang dari nol). Itu praktis untuk development, tapi TIDAK BISA
-- dipakai di database production yang sudah berisi data asli - "down -v"
-- akan menghapus semua data pelanggan/transaksi yang sudah ada.
--
-- Migration ALTER TABLE seperti ini adalah cara yang benar untuk mengubah
-- skema database yang SUDAH JALAN tanpa kehilangan data: dijalankan sekali,
-- terhadap database yang sedang aktif, tanpa reset.
--
-- Cara coba (opsional, tidak wajib buat project berjalan):
--   docker compose exec db mysql -u root -p<DB_ROOT_PASSWORD> inventory \
--     < database/migrations/0001_add_product_barcode.sql

ALTER TABLE product
    ADD COLUMN barcode VARCHAR(64) NULL AFTER sku;

CREATE UNIQUE INDEX idx_product_barcode ON product (barcode);

-- Rollback-nya (kalau migration ini mau dibatalkan):
--   ALTER TABLE product DROP INDEX idx_product_barcode;
--   ALTER TABLE product DROP COLUMN barcode;
