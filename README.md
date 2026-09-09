# Inventory & Order Management System

Final project - Intermediate Programmer (Neuronworks).

## Quickstart (Fase 0 — skeleton)

1. Copy environment file:
   ```
   cp .env.example .env
   ```
   Lalu ganti `DB_PASSWORD` dan `DB_ROOT_PASSWORD` dengan password sendiri.

2. Build & jalankan:
   ```
   docker compose up --build
   ```

3. Buka `http://localhost:8080` — kalau muncul "Koneksi database berhasil", skeleton sudah beres.

4. Install dependency PHP (kalau belum ke-install otomatis saat build):
   ```
   docker compose exec app composer install
   ```

## Known limitations (skeleton stage)
- `public/index.php` masih smoke-test sementara, belum jadi router asli.
- `database/schema-and-seed.sql` baru berisi tabel placeholder — tabel sebenarnya
  ditambahkan bertahap per fase pengerjaan.
- Demo account & seed data (30 produk, 25 order) belum ada.
