# Backlog

Ditulis retrospektif dari histori pembangunan project — bukan
proyeksi ke depan yang belum tentu terjadi. Status "Done" berarti sudah
ada di codebase saat ini; "Open" berarti genuinely belum dikerjakan
dan alasannya (kalau sengaja tidak dikerjakan) dicatat di kolom Catatan.

## Epic: Auth & User Management

| Item | Prioritas | Status | Catatan |
|---|---|---|---|
| Login/logout dengan session | Wajib | Done | AUTH-01 |
| Password di-hash (bcrypt) | Wajib | Done | AUTH-01 |
| Role-based access (Admin/Sales/WarehouseStaff) | Wajib | Done | AUTH-02 |
| CRUD user + guard "last active Admin" | Wajib | Done | USR-01 |
| CSRF token di semua form POST | Wajib (ditambah belakangan) | Done | 32 form, 24 file |
| Password reset / lupa password | Nice-to-have | Open | Dicatat di `tech-debt.md` — semua akun dibuat manual oleh Admin, tidak ada self-service reset |
| Remember-me di login | Nice-to-have | Open | Butuh desain token persistent-login terpisah; sengaja tidak ditambah tanpa itu (checkbox kosong tanpa fungsi lebih buruk daripada tidak ada) |

## Epic: Master Data

| Item | Prioritas | Status | Catatan |
|---|---|---|---|
| CRUD Category, Warehouse, Supplier, Customer | Wajib | Done | Soft-delete via `is_active` |
| CRUD Product + upload gambar | Wajib | Done | PRD-01, validasi MIME/size di `ImageUploader` |
| Stok per gudang (`product_stock`) | Wajib | Done | WH-01, auto-initialize saat produk baru dibuat |
| Pagination master-data (Category/Warehouse/Supplier/Customer) | Nice-to-have | Open | Dicatat di `tech-debt.md` — jumlah baris kecil, dampak minor |

## Epic: Purchasing & Sales

| Item | Prioritas | Status | Catatan |
|---|---|---|---|
| PO lifecycle (Draft→Ordered→Received) | Wajib | Done | PO-01 |
| Goods Receipt (termasuk partial) | Wajib | Done | Transaksi + stock_ledger |
| SO lifecycle + approval | Wajib | Done | SO-01, segregation of duties di server |
| Goods Issue + guard stok tidak cukup | Wajib | Done | ARCH-02, `InsufficientStockException` |
| API JSON cek ketersediaan stok | Wajib | Done | API-01 |
| All-or-nothing goods issue (tidak bisa partial issue) | Nice-to-have | Open | Dicatat di `tech-debt.md` sebagai keputusan sadar, bukan kelupaan |

## Epic: Dashboard, Search, Reports

| Item | Prioritas | Status | Catatan |
|---|---|---|---|
| Dashboard per role (Admin/Sales/Warehouse) | Wajib | Done | DASH-01 |
| Search/filter/sort/pagination Product, PO, SO | Wajib | Done | FIND-01 |
| CSV export (stock ledger, orders) | Wajib | Done | REPORT-01 |
| Index database (status, order_date, created_at) | Wajib (ditambah belakangan) | Done | 5 index eksplisit + auto-index dari FK/UNIQUE |

## Epic: Kualitas & Dokumentasi

| Item | Prioritas | Status | Catatan |
|---|---|---|---|
| Unit test ≥6, ≥3 area | Wajib | Done | TEST-01, 6 area |
| Integration test ≥3 nyentuh MySQL asli | Wajib | Done | TEST-02 |
| Database test terisolasi (`inventory_test`) | Nice-to-have (ditambah belakangan) | Done | Terpisah dari `inventory` (dev) |
| Static analysis (PHPStan + PHPCS) | Wajib | Done | TEST-03, 0 error |
| Jest test untuk logic JS | Nice-to-have (ditambah belakangan) | Done | 6 test, pure logic terpisah dari DOM |
| ADR ×3 | Wajib | Done | DESIGN-02 |
| Refactor log ≥3 + SRP audit + tech debt register | Wajib | Done | DESIGN-03 — sekarang 4 entri |
| Class diagram initial + as-built | Wajib | Done | DESIGN-01 |
| ERD | Wajib | Done | `docs/planning/erd.md` |
| User story + backlog | Wajib | Done | File ini + `user-stories.md` |
| `docs/testing/` (skenario, hasil, known bugs) | Wajib | **Open** | Belum dibuat — lihat catatan di bawah |
| Critique.md (DESIGN-04) | Wajib | **Open** | Menunggu code snippet dari asesor, belum bisa dikerjakan |
| Screenshot desktop+mobile (UI-01) | Wajib | **Open** | Harus diambil manual dari browser, tidak bisa dibuat lewat sesi ini |
| CSS debugging log | Nice-to-have (ditambah belakangan) | Done | `docs/quality/css-debugging-log.md` |
| Git release tag | Wajib | **Open** | Butuh akses ke repo Git asli, di luar sandbox sesi ini |
| `ai-usage-log.md` kolom "Reviewed/Verified/Tested by me" | Wajib | **Open** | Harus diisi oleh yang benar-benar mereview, bukan oleh Claude sendiri |

## Nice-to-have dari audit CSS/JS yang sengaja TIDAK dikerjakan

Dicatat di sini (bukan cuma disebutkan lalu hilang) supaya keputusan
untuk tidak mengerjakannya punya jejak, sama seperti item all-or-nothing
goods issue di atas:

| Item | Kenapa tidak dikerjakan |
|---|---|
| Floating label (CSS) | Struktur `<label>` membungkus input dipakai konsisten di semua form; restrukturisasi HTML besar-besaran cuma demi 1 pattern kosmetik tidak sepadan untuk admin tool internal |
| Event delegation (JS) | Hanya ada 1 elemen yang perlu event listener saat ini; delegasi baru relevan kalau ada elemen dinamis berulang |
| Bitwise & comma operator (JS) | Tidak ada kebutuhan natural di domain bisnis ini — memaksa masuk hanya untuk demonstrasi akan jadi kode yang sulit dijelaskan |
| CSS counter (`counter-reset`/`counter-increment`) | Tidak ada daftar bernomor presentasional di aplikasi; semua penomoran berasal dari data asli (ID, urutan tabel) |

## Known limitations (ringkas, detail lengkap di `tech-debt.md`)

1. Router pakai regex khusus untuk 1 route dinamis (API-01), bukan router berparameter penuh.
2. Goods issue bersifat all-or-nothing per item.
3. Tidak ada alur password reset mandiri.
4. File upload lama yang sudah diganti tidak otomatis terhapus dari disk.
5. Master data (selain Product) belum ada pagination.
