# Test Report

Cara menjalankan (satu perintah, sesuai README):
```
docker compose exec app vendor/bin/phpunit
```

Unit test dan integration test berjalan dalam satu perintah yang sama lewat
`phpunit.xml`, tapi menyasar database berbeda — unit test tidak pernah
menyentuh database sama sekali (TEST-01: "tidak menyentuh session, PDO nyata,
atau layanan eksternal"), sedangkan integration test menyasar `inventory_test`,
terpisah dari `inventory` (data development), lewat override
`<env name="DB_NAME" value="inventory_test" force="true"/>`.

## Ringkasan

| | Jumlah | Area |
|---|---|---|
| Unit test (TEST-01) | 29 test case | 6 area: Auth, Dashboard, PurchaseOrder, SalesOrder, User, Warehouse |
| Integration test (TEST-02) | 4 test case | 2 area: Goods Issue, Goods Receipt — keduanya menyentuh MySQL nyata |
| **Total** | **33 test case** | **Semua PASS pada commit terakhir** |

## Skenario unit test per area

### AuthServiceTest (4 test) — AUTH-01
| Skenario | Requirement yang diverifikasi |
|---|---|
| `test_login_succeeds_with_correct_credentials` | Login valid mengarah ke sesi yang benar |
| `test_login_fails_when_password_is_wrong` | Password salah ditolak dengan pesan aman |
| `test_login_fails_when_user_is_inactive` | User nonaktif tidak bisa login walau password benar |
| `test_login_fails_when_email_is_not_registered` | Email tidak terdaftar ditolak, pesan sama seperti password salah (tidak membocorkan bagian mana yang salah) |

### DashboardServiceTest (3 test) — DASH-01, REPORT-01
| Skenario | Requirement yang diverifikasi |
|---|---|
| `test_validation_fails_for_invalid_from_date` | Rentang tanggal laporan CSV tervalidasi |
| `test_validation_fails_when_from_is_after_to` | Rentang tanggal terbalik ditolak |
| `test_validation_passes_for_a_valid_range` | Rentang valid diterima |

### PurchaseOrderServiceTest (7 test) — PO-01
| Skenario | Requirement yang diverifikasi |
|---|---|
| `test_header_validation_fails_for_invalid_date_format` | Tanggal PO tervalidasi |
| `test_header_validation_does_not_flag_a_correctly_formatted_date` | Tanggal valid tidak salah ditolak (negative-of-negative check) |
| `test_item_validation_fails_when_po_is_not_draft` | Item hanya bisa ditambah saat PO masih Draft |
| `test_item_validation_fails_for_non_positive_quantity` | Qty harus > 0 |
| `test_receipt_validation_fails_when_qty_exceeds_remaining` | Goods receipt tidak bisa melebihi sisa qty yang dipesan |
| `test_receipt_validation_fails_when_po_status_not_receivable` | Receipt ditolak kalau status PO bukan Ordered/PartiallyReceived |
| `test_receipt_validation_passes_for_a_valid_partial_receipt` | Partial receipt diterima (ketentuan minimum PO-01) |

### SalesOrderServiceTest (5 test) — SO-01
| Skenario | Requirement yang diverifikasi |
|---|---|
| `test_header_validation_fails_for_invalid_date_format` | Tanggal SO tervalidasi |
| `test_item_validation_fails_when_so_is_not_draft` | Item hanya bisa ditambah saat SO masih Draft |
| `test_item_validation_fails_for_non_positive_quantity` | Qty harus > 0 |
| `test_goods_issue_validation_fails_when_not_approved` | Goods issue ditolak kalau SO belum Approved |
| `test_goods_issue_validation_passes_when_approved` | Goods issue diterima saat SO Approved |

### UserServiceTest (7 test) — USR-01
| Skenario | Requirement yang diverifikasi |
|---|---|
| `test_validation_fails_when_email_already_used` | Email unik ditegakkan |
| `test_validation_fails_when_role_is_invalid` | Role di luar Admin/Sales/WarehouseStaff ditolak |
| `test_validation_passes_with_correct_data` | Data valid diterima |
| `test_update_does_not_treat_own_email_as_duplicate` | Edit user tidak salah anggap email sendiri sebagai duplikat |
| `test_cannot_change_role_of_only_active_admin` | Guard Admin aktif terakhir — tidak bisa ganti role |
| `test_can_change_admin_role_if_another_admin_exists` | Guard tidak over-block kalau Admin lain masih ada |
| `test_cannot_deactivate_only_active_admin` | Guard Admin aktif terakhir — tidak bisa dinonaktifkan |

### WarehouseServiceTest (3 test) — WH-01
| Skenario | Requirement yang diverifikasi |
|---|---|
| `test_validation_fails_when_name_is_empty` | Nama gudang wajib diisi |
| `test_validation_fails_when_location_is_empty` | Lokasi gudang wajib diisi |
| `test_validation_passes_with_complete_data` | Data lengkap diterima |

## Skenario integration test (menyentuh MySQL nyata) — ARCH-02

| Skenario | Requirement yang diverifikasi |
|---|---|
| `GoodsReceiptIntegrationTest::test_goods_receipt_increases_stock_end_to_end` | Goods receipt benar-benar menambah `product_stock` di database, bukan cuma logika di memory |
| `GoodsReceiptIntegrationTest::test_full_receipt_marks_purchase_order_as_received` | Status PO otomatis berubah jadi Received setelah qty diterima penuh |
| `GoodsIssueIntegrationTest::test_second_goods_issue_is_rejected_once_stock_is_exhausted` | **Ini bukti utama ARCH-02** — goods issue kedua pada produk+gudang yang sama ditolak begitu stok habis oleh goods issue pertama, membuktikan `SELECT ... FOR UPDATE` mencegah oversell |
| `GoodsIssueIntegrationTest::test_stock_is_unchanged_after_a_rejected_issue_attempt` | Transaksi yang gagal benar-benar rollback — stok tidak berubah sama sekali, bukan berubah sebagian |

## Known bugs / keterbatasan pengujian

Dicatat jujur, bukan disembunyikan (konsisten dengan `docs/quality/tech-debt.md`):

1. **Belum ada automated end-to-end test** untuk alur UI (login → PO → SO → dashboard) — di luar scope brief (§4.3 eksplisit mengecualikan automated end-to-end test), diverifikasi manual lewat demo.
2. **Screenshot desktop + mobile (UI-01)** belum dilampirkan di folder ini — perlu diambil manual dari browser sebelum submission final, tidak bisa dihasilkan dari sesi kerja ini.
3. **Simulasi concurrency pada ARCH-02** diuji lewat skenario terkontrol berurutan (issue kedua ditolak setelah issue pertama menghabiskan stok), bukan request paralel sungguhan — ini eksplisit DIPERBOLEHKAN oleh brief (§3.1: "simulasi thread/paralel sungguhan tidak wajib").
4. **Test JS (Jest)** untuk `stock-availability.js` ada di `tests/js/` sebagai bonus, di luar cakupan wajib TEST-01/02 yang spesifik untuk PHPUnit.
