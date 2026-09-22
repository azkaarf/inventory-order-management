# User Stories

Ditulis dari fitur yang benar-benar sudah dibangun (bukan aspirational) —
setiap story di bawah bisa ditelusuri langsung ke Controller/Service yang
mengimplementasikannya.

## Admin

- Sebagai Admin, saya ingin mengelola akun user (buat, ubah role,
  nonaktifkan), supaya saya bisa mengontrol siapa yang punya akses dan
  menegakkan least-privilege lewat role. *(`UserController`, AUTH-01,
  USR-01)*
- Sebagai Admin, saya ingin sistem mencegah saya menonaktifkan/mengubah
  role Admin aktif terakhir, supaya tidak ada skenario di mana tidak ada
  satupun Admin yang bisa login. *(`UserService::isLastActiveAdmin()`)*
- Sebagai Admin, saya ingin mengelola master data (kategori, gudang,
  supplier, customer, produk), supaya Purchase Order dan Sales Order
  punya data referensi yang bersih dan konsisten. *(PRD-01, WH-01)*
- Sebagai Admin, saya ingin melihat dashboard berisi nilai inventori,
  daftar produk di bawah reorder point, dan breakdown status PO/SO,
  supaya saya punya gambaran kesehatan bisnis sekilas pandang. *(DASH-01)*
- Sebagai Admin, saya ingin menyetujui atau menolak Sales Order yang
  disubmit staf Sales — tapi tidak bisa menyetujui order buatan saya
  sendiri — supaya segregation of duties benar-benar ditegakkan di
  server, bukan cuma disembunyikan di UI. *(`SalesOrderService::approve()`)*
- Sebagai Admin, saya ingin mengunduh laporan CSV (stock ledger, orders)
  untuk rentang tanggal tertentu, supaya saya bisa menganalisis data
  lebih lanjut di luar aplikasi. *(REPORT-01)*

## Sales

- Sebagai staf Sales, saya ingin membuat Sales Order untuk seorang
  customer, supaya permintaan customer tercatat sebelum diproses gudang.
  *(SO-01)*
- Sebagai staf Sales, saya ingin melihat stok tersedia per gudang secara
  live saat menambah item ke order, supaya saya tidak menjanjikan produk
  yang stoknya sudah habis. *(API-01, JS di `sales-orders/show.php`)*
- Sebagai staf Sales, saya ingin men-submit order Draft saya untuk
  disetujui Admin, supaya ada pengecekan sebelum order benar-benar
  diproses.
- Sebagai staf Sales, saya ingin hanya melihat order milik saya sendiri
  di dashboard dan daftar Sales Order — bukan milik semua staf Sales
  lain — supaya saya fokus ke pekerjaan saya sendiri. *(`$createdBy`
  constraint di `SalesOrderRepository::search()`)*

## Warehouse Staff

- Sebagai staf Gudang, saya ingin membuat Purchase Order ke supplier,
  supaya stok bisa direplenish saat menipis. *(PO-01)*
- Sebagai staf Gudang, saya ingin mencatat penerimaan barang (goods
  receipt) terhadap sebuah PO — termasuk penerimaan sebagian — supaya
  kuantitas stok per gudang selalu akurat dan PO otomatis berubah status
  (PartiallyReceived/Received). *(`MySqlGoodsReceiptRepository::receive()`)*
- Sebagai staf Gudang, saya ingin memproses goods issue untuk Sales
  Order yang sudah disetujui, supaya stok berkurang dan order berpindah
  ke status Fulfilled — dan sistem menolak proses ini kalau stok tidak
  cukup, bukan membiarkan stok jadi negatif. *(ARCH-02,
  `InsufficientStockException`)*
- Sebagai staf Gudang, saya ingin melihat dashboard berisi antrean PO
  yang menunggu diterima dan SO yang menunggu di-issue, supaya saya tahu
  prioritas kerja hari ini tanpa harus mencari manual.
- Sebagai staf Gudang, saya ingin melihat daftar produk yang stoknya di
  bawah reorder point, supaya saya bisa merencanakan pemesanan ulang
  secara proaktif, bukan reaktif setelah stok benar-benar habis.

## Semua role

- Sebagai user, saya ingin login dengan email/password yang aman
  (password di-hash, session diregenerasi setelah login), supaya akun
  saya terlindungi. *(AUTH-01, AUTH-02)*
- Sebagai user, saya ingin bisa mencari, memfilter, mengurutkan, dan
  membuka halaman berikutnya pada daftar Produk/PO/SO, supaya saya bisa
  menemukan data spesifik di antara puluhan/ratusan baris tanpa scroll
  manual. *(FIND-01)*
- Sebagai user, saya ingin form saya tidak kehilangan isian saat validasi
  gagal, supaya saya tidak perlu mengetik ulang semuanya dari awal.
  *(VAL-01)*
- Sebagai user, saya ingin pesan error yang jelas tanpa stack trace
  bocor ke layar, supaya saya tahu apa yang salah tanpa terekspos detail
  teknis internal. *(ERR-01)*
