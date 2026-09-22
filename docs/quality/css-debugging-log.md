# CSS Debugging Log — Sidebar Stacking Context

Ditulis mengikuti siklus reproduce → inspect → isolate → fix → retest →
document (Bab 11.1) dan template field di Lampiran D modul CSS & Styling
Frameworks.

## Issue
Audit topik "z-index" (silabus #10, #33) menemukan `.sidebar` dipasang
`position: sticky` tanpa `z-index` eksplisit sama sekali di seluruh
`app.css`. Dampak langsung: belum ada bug yang terlihat user hari ini
(karena belum ada elemen lain yang membentuk stacking context lebih
tinggi), tapi begitu ada fitur baru yang butuh overlay — dropdown, modal
konfirmasi, toast — elemen itu berisiko tertutup sidebar atau membentuk
stacking context yang urutannya nggak terprediksi, dan perbaikannya
biasanya berujung menaikkan angka z-index secara serampangan (persis
RED FLAG yang disebut Bab 4.2).

## Environment
- File: `public/assets/css/app.css`
- Elemen: `.sidebar` (`position: sticky; top: 0;`)
- Tidak ada `isolation`, `transform`, atau `opacity` lain di ancestor
  yang membentuk stacking context tambahan pada saat audit ini ditulis.

## Reproduction
1. Buka halaman manapun yang menampilkan sidebar (butuh login).
2. Scroll konten `.main` ke bawah.
3. Sidebar tetap menempel (sticky) — perilaku ini sudah benar.
4. Tidak ada langkah untuk memicu overlay tertutup saat ini, karena
   belum ada komponen overlay (dropdown/modal) di aplikasi. Ini adalah
   audit preventif, bukan laporan bug yang sudah dialami user.

## Hypothesis
Karena `.sidebar` tidak punya `z-index` eksplisit, nilainya bergantung
pada default `auto` — yang berarti urutannya ditentukan document order,
bukan keputusan desain. Begitu ada elemen `position: fixed`/`absolute`
lain ditambahkan di kemudian hari, hasilnya jadi tidak terprediksi
tanpa membaca ulang seluruh CSS.

## Evidence
`grep -n "z-index" app.css` sebelum perbaikan: nol hasil di seluruh
file. Confirmed via command yang sama saat audit topik silabus CSS.

## Root cause
Bukan bug cascade/specificity — ini gap desain: layer stacking belum
punya token sama sekali, jadi tidak ada "kontrak" yang mendefinisikan
urutan layer secara sengaja (dibanding, misalnya, Contoh 8 di Bab 4.1
yang mendefinisikan `--layer-dropdown`, `--layer-sticky`, dst di
`:root`).

## Fix
Tambah dua token di `:root`:
```css
--layer-sticky: 30;
--layer-modal: 90;
```
lalu terapkan `z-index: var(--layer-sticky);` ke `.sidebar`. Nilai
sengaja diberi jarak (30, bukan 1) supaya ada ruang menyisipkan layer
baru di antaranya nanti (mis. `--layer-dropdown: 20`) tanpa perlu
menomori ulang token yang sudah dipakai.

## Before–after
- **Before:** `.sidebar { position: sticky; top: 0; ... }` — tanpa
  z-index, stacking order implisit.
- **After:** `.sidebar { position: sticky; top: 0; z-index:
  var(--layer-sticky); ... }` — stacking order eksplisit dan
  terdokumentasi.

## Regression
- Scroll behavior sidebar diverifikasi tetap sama (sticky tetap
  menempel di top: 0).
- Layout mobile (`@media max-width: 768px`, sidebar jadi
  `position: static`) tidak terpengaruh — z-index tidak berlaku pada
  elemen non-positioned/static di breakpoint itu, jadi aman.
- Belum ada regression test otomatis untuk stacking context (di luar
  cakupan PHPUnit backend); verifikasi ini murni manual/visual.

## Next improvement
Begitu aplikasi menambah komponen overlay pertama (dropdown/modal/
toast), token `--layer-dropdown` dan `--layer-toast` perlu ditambahkan
mengikuti pola yang sama — jangan menambah angka z-index baru tanpa
menambah token-nya juga di `:root`.
