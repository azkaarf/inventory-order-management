# BLISS — Beauty Logistics, Inventory & Sales System

**Peserta:** Azka
**Program:** Intermediate Programmer — PT Neuronworks Indonesia
**Final Project:** Inventory & Order Management System (3 role, multi-gudang)

Aplikasi web pengelolaan inventori dan order untuk bisnis beauty/skincare —
PHP 8.2+ Native (OOP berlapis, tanpa framework), MySQL 8, Docker Compose.

---

## Daftar isi

- [Fitur](#fitur)
- [Tech stack](#tech-stack--requirement)
- [Instalasi](#instalasi)
- [Akun demo](#akun-demo)
- [Perintah Docker & Test](#perintah-docker--test)
- [Struktur project](#struktur-project)
- [Dokumentasi teknis](#dokumentasi-teknis)
- [Known limitations](#known-limitations)

---

## Fitur

| Area | Fitur |
|---|---|
| **Auth & User** | Login/logout session-based, password di-hash (`password_hash`), CSRF token di semua form, CRUD user 3 role (Admin/Sales/WarehouseStaff) dengan guard "tidak bisa menonaktifkan Admin aktif terakhir" |
| **Master Data** | Kategori, gudang, produk (SKU unik, reorder point, upload gambar dengan validasi tipe/ukuran), supplier, customer — semua soft-delete (nonaktif, bukan hapus) |
| **Stok Multi-Gudang** | Satu produk punya baris stok terpisah per gudang; tampilan total + rincian per gudang |
| **Purchase Order** | Draft → Ordered → PartiallyReceived/Received → Cancelled; goods receipt (termasuk partial) menambah stok + catat `stock_ledger` dalam satu transaksi |
| **Sales Order** | Draft → PendingApproval → Approved → Fulfilled/Cancelled; approval disetujui Admin (Sales tidak bisa approve order sendiri — ditegakkan di server); goods issue mengurangi stok dengan `SELECT...FOR UPDATE` (aman dari race condition, menolak kalau stok tidak cukup) |
| **Dashboard** | Beda tampilan per role — Admin lihat semua, Sales lihat order miliknya, Warehouse lihat antrean receipt/issue + produk low-stock. Breakdown status pakai progress bar, semua angka dari query agregasi live |
| **Search/Filter/Pagination** | Produk (nama/SKU, kategori, status stok), PO/SO (nomor/pihak terkait, status, sort tanggal) — 10 data/halaman, filter tetap aktif saat pindah halaman |
| **Laporan CSV** | Export stock ledger & status order per rentang tanggal, plus export langsung dari tabel Produk/PO/SO |
| **API JSON** | `GET /api/products/{sku}/availability` — stok per gudang, status 200/401/404 sesuai konteks |
| **Scheduled Job** | `scripts/check-low-stock.php` — ringkasan produk di bawah reorder point, jalan manual lewat Docker |

## Tech stack & requirement

- **Backend:** PHP 8.2+ Native OOP (Controller → Service → Repository → Entity), Dependency Inversion pada boundary Repository
- **Frontend:** HTML semantik, CSS buatan sendiri (tanpa framework), Vanilla JavaScript + Fetch API
- **Database:** MySQL 8, PDO prepared statement, transaksi eksplisit
- **Environment:** Docker Compose (app + db)
- **Testing:** PHPUnit (unit + integration), PHPStan level 5, PHP_CodeSniffer PSR-12, Jest (bonus, untuk logic JS)

Yang dibutuhkan di komputer kamu: **Docker Desktop** saja. Tidak ada dependency lain yang perlu diinstall manual — PHP, Composer, dan MySQL semua berjalan di dalam container.

## Instalasi

```bash
# 1. Copy environment file, lalu isi password sendiri
cp .env.example .env
# edit .env: ganti DB_PASSWORD dan DB_ROOT_PASSWORD

# 2. Build & jalankan (app + database)
docker compose up --build

# 3. Buka di browser
http://localhost:8080
```

Schema dan seed data (30 produk, 25+ order gabungan dengan variasi status)
otomatis dibuat saat database pertama kali start — tidak ada langkah manual
tambahan.

**Reset total** (hapus semua data, mulai dari kosong lagi):
```bash
docker compose down -v
docker compose up --build
```

## Akun demo

| Role | Email | Password |
|---|---|---|
| Admin | `admin@demo.test` | `admin123` |
| Sales | `sales1@demo.test` | `sales123` |
| Sales | `sales2@demo.test` | `sales123` |
| Warehouse Staff | `gudang1@demo.test` | `gudang123` |
| Warehouse Staff | `gudang2@demo.test` | `gudang123` |

## Perintah Docker & Test

```bash
# Jalankan seluruh test suite (unit + integration)
docker compose exec app vendor/bin/phpunit

# Static analysis
docker compose exec app vendor/bin/phpstan analyse --level=5 app public config scripts
docker compose exec app vendor/bin/phpcs --standard=PSR12 app config public scripts

# Jalankan scheduled job (low-stock check) manual
docker compose exec app php scripts/check-low-stock.php

# Test logic JS (bonus, dijalankan di host — butuh Node.js)
npm install
npx jest
```

Integration test menyasar database **terpisah** (`inventory_test`), bukan
`inventory` yang dipakai aplikasi — jadi menjalankan test tidak akan pernah
mengotori data demo yang sedang kamu lihat di browser.

## Struktur project

```
app/
  Controller/   — HTTP handling, routing target, session, redirect
  Service/      — business rule, validasi
  Repository/   — akses data (interface + implementasi MySQL)
  Entity/       — plain data object
  Support/      — AuthGuard, Csrf, Database, dll (cross-cutting concern)
config/         — bootstrap, autoload, error handler
database/       — schema-and-seed.sql, migration contoh
public/         — entry point (index.php = front controller), asset CSS/JS
views/          — template, dikelompokkan per modul
tests/
  Unit/         — business logic, tanpa DB/session nyata
  Integration/  — menyentuh MySQL sungguhan (goods receipt/issue)
  js/           — Jest, untuk logic JS yang diekstrak jadi pure function
docs/
  planning/     — user story, ERD, class diagram initial, backlog
  architecture/ — class diagram as-built, ADR
  quality/      — refactor log, SRP audit, tech-debt register, static analysis
  testing/      — skenario test, hasil, known bugs
```

## Dokumentasi teknis

- **Arsitektur & keputusan desain:** [`docs/architecture/`](docs/architecture/) — class diagram as-built, 3 ADR (kenapa Repository pattern, kenapa mekanisme concurrency yang dipilih, kenapa transaction boundary di Repository)
- **ERD:** [`docs/planning/erd.md`](docs/planning/erd.md)
- **Kualitas kode:** [`docs/quality/`](docs/quality/) — refactor log, audit SRP, tech-debt register, laporan static analysis
- **Hasil test:** [`docs/testing/test-report.md`](docs/testing/test-report.md)
- **Penggunaan AI:** [`ai-usage-log.md`](ai-usage-log.md)

## Known limitations

Dicatat jujur — detail lengkap beserta alasan di
[`docs/quality/tech-debt.md`](docs/quality/tech-debt.md):

1. Router pakai satu regex khusus untuk endpoint API dinamis, bukan router berparameter penuh — cukup untuk 1 route, akan perlu direfaktor kalau route dinamis bertambah banyak.
2. Goods issue bersifat all-or-nothing per item (tidak bisa partial issue), beda dari goods receipt yang mendukung partial.
3. Belum ada alur password reset mandiri — semua akun dibuat dan direset manual oleh Admin.
4. File gambar produk yang digantikan (setelah edit) tidak otomatis terhapus dari disk.
5. Master data selain Produk (Kategori, Gudang, Supplier, Customer, User) belum punya pagination — baris datanya masih sedikit sehingga dampaknya minor.
