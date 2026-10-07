# Laporan SonarQube

## Ringkasan hasil akhir

| Item | Hasil |
|---|---|
| Versi SonarQube | Community Build `26.9.0.129388` (Docker image `sonarqube:26.9.0.129388-community`) |
| Quality Gate | **Passed** (gate bawaan "Sonar way") |
| Coverage on New Code | **98%** (syarat: ≥ 80%) |
| Issue terbuka | 0 |
| Issue berstatus *Accepted* | 1 — Replace "require" with "require_once". app/Controller/BaseController.php. View template, sengaja di-include setiap kali render  |
| Duplications | **1.04%** (syarat: ≤ 3%) |
| Security Hotspots | **Deprecated** |
| Tanggal scan akhir | **12.23 PM. 10/7/2026** |

## Cara menjalankan ulang

```
docker compose up -d sonarqube
docker compose exec app vendor/bin/phpunit --coverage-clover coverage.xml --coverage-text
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html --network=host \
  sonarsource/sonar-scanner-cli -Dsonar.host.url=http://localhost:9000 -Dsonar.token=<TOKEN>
```

Token tidak disimpan di repository. Konfigurasi project ada di
`sonar-project.properties`. Laporan coverage (`coverage.xml`) dihasilkan oleh
PHPUnit dengan PCOV, tidak di-commit, dan folder dipasang pada path yang sama
dengan container PHP supaya path file di laporan cocok dengan yang dibaca scanner.

## Riwayat dan temuan utama

### 1. Coverage awal 0%, bukan karena tidak ada test
Scan pertama menampilkan coverage 0% sehingga Quality Gate gagal. Penyebabnya:
SonarQube tidak menjalankan test, ia hanya membaca laporan coverage dan laporan
itu belum ada. Container PHP juga belum punya alat pencatat coverage. Perbaikan:
memasang PCOV di `docker/Dockerfile`, menambahkan `<source>` di `phpunit.xml`,
dan mengarahkan `sonar.php.coverage.reportPaths` ke `coverage.xml`.

### 2. SonarQube menangkap bug sungguhan: variabel dipakai tanpa diinisialisasi
Issue *"Review the data-flow - use of uninitialized value"* di
`MySqlProductRepository::create()`. Setelah ditelusuri, itu bukan false positive:
parameter method sudah diganti menjadi objek `ProductData`, tetapi isi method
masih memakai variabel lama (`$name`, `$categoryId`, dst.) yang tidak ada lagi.
Dampaknya nyata: menyimpan produk baru menghasilkan *Undefined variable* dan
error 500. `update()` memiliki cacat yang sama.

Perbaikan: kedua method memakai `$data->name`, `$data->categoryId`, dan
seterusnya. Pada saat yang sama PHPUnit menangkap bahwa dua integration test
masih memanggil `create()` dengan signature lama; keduanya diperbarui.
Verifikasi: PHPUnit hijau dan scan ulang tidak lagi menampilkan issue tersebut.

Pelajaran yang dicatat: PHPStan hanya dijalankan pada `app public config
scripts`, sehingga test yang memakai API lama tidak terdeteksi olehnya dan baru
ketahuan oleh PHPUnit. Setelah refactor apa pun, kedua alat dijalankan.

### 3. Peningkatan coverage lewat test pada jalur yang relevan
Jumlah test naik dari 33 menjadi 73:

| Penambahan | Jenis | Cakupan |
|---|---|---|
| `ProductServiceTest` (13 test) | Unit, repository di-mock | `ProductService` 100% |
| `SupportHelpersTest` (7 test) | Unit | `LogSanitizer`, `StatusBadge` 100% |
| `ProductRepositoryIntegrationTest` (11 test) | Integration, MySQL nyata | `MySqlProductRepository` 100% |
| `MasterDataRepositoriesIntegrationTest` (9 test) | Integration, MySQL nyata | Repository Category, Customer, Supplier, Warehouse 100% |

Jalur paling kritis menurut brief juga tercakup: `MySqlGoodsIssueRepository`
100% dan `MySqlGoodsReceiptRepository` 89%, yang membuktikan stok tidak bisa
menjadi negatif.

## Cakupan coverage yang diukur (keputusan yang disengaja)

`sonar.coverage.exclusions=app/Controller/**,public/index.php,config/**,scripts/**`

- **Alasan:** Controller hanya penghubung HTTP (membaca request, memanggil
  Service, redirect/render). Logika bisnis berada di Service dan Repository,
  yang diukur penuh. Brief (§4.3) mengecualikan automated end-to-end test,
  sehingga Controller tidak dites otomatis di project ini.
- **Yang tidak berubah:** file yang dikecualikan tetap dianalisis SonarQube
  untuk bug, kerentanan, dan duplikasi. Hanya perhitungan coverage-nya yang
  dibatasi.
- **Angka 98% adalah Coverage on New Code dengan pengecualian di atas.**
  Coverage keseluruhan lapisan `app/` yang diukur PHPUnit adalah **32,53%**
  (550 dari 1.691 baris). Kedua angka ini disampaikan bersama, bukan hanya yang
  lebih tinggi.

## Keterbatasan yang disadari dan rencana berikutnya

1. Controller belum memiliki test otomatis. Prioritas berikutnya: test lewat
   request HTTP simulasi.
2. Beberapa Service masih di bawah 50% (`DashboardService`, `PurchaseOrderService`,
   `SalesOrderService`) karena baru metode validasi yang dites. Menambah test
   untuk alur lengkap berikutnya.
3. `MySqlPurchaseOrderRepository` dan `MySqlSalesOrderRepository` baru sekitar
   50% karena hanya jalur goods receipt/issue yang diuji integration.
