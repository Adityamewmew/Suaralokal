# MVP 2 SuaraLokal

Dokumen ini mendefinisikan fase lanjutan setelah MVP pertama SuaraLokal selesai. Fokusnya adalah memperkuat kualitas produk yang sudah berjalan, bukan mengubah arah produk.

## 1. Tujuan MVP 2

MVP 2 bertujuan membuat SuaraLokal lebih siap dipakai secara rutin di lapangan dengan pengalaman HP yang lebih halus, pencarian yang lebih akurat, notifikasi yang lebih efektif, dan proses operasional yang lebih jelas.

Fokus utama fase ini adalah hardening, usability, dan operasional.

## 2. Target Pengguna

MVP 2 tetap melayani aktor yang sama:

- `pengguna`: warga lokal yang mencari barang atau jasa.
- `umkm`: penjual atau penyedia jasa mikro.
- `ojek_admin`: operator Bangjek.
- `driver`: mitra Bangjek.
- `superadmin`: pengelola sistem internal bila diperlukan.

## 3. Scope MVP 2

MVP 2 harus mencakup peningkatan berikut:

1. Pencarian UMKM berbasis pencarian radius spasial yang lebih matang.
2. Pengalaman PWA yang lebih stabil di HP.
3. Notifikasi yang lebih cepat dan lebih jelas.
4. Validasi backend yang lebih ketat untuk chat dan order.
5. Dukungan settlement dan status operasional yang lebih jelas.
6. Observability dasar untuk memantau error dan aktivitas penting.

## 4. Fitur Wajib

### 4.1 Pencarian UMKM Berbasis Spasial

> **Catatan implementasi:** PostGIS tidak terpasang pada server Postgres target dan tidak bisa diaktifkan, jadi pencarian radius memakai ekstensi contrib `cube` + `earthdistance` (great-circle distance via `earth_distance(ll_to_earth(...), ...)`) dengan GiST expression index pada `ll_to_earth(latitude, longitude)`. Tidak ada kolom geometry PostGIS. Spesifikasi berikut membaca "spasial" sebagai path `earthdistance` tersebut; jika nantinya PostGIS tersedia, path ini boleh diganti ke geometry column tanpa mengubah kontrak pencarian.

- Pencarian radius harus memakai fungsi spasial `earthdistance` (atau PostGIS bila tersedia) secara penuh, bukan kalkulasi Haversine di sisi aplikasi.
- Index spasial (GiST pada `ll_to_earth`) harus aktif untuk mendukung query radius dan nearest-neighbour.
- Hasil pencarian harus tetap terurut dari yang paling dekat.
- Filter radius, kategori, dan kata kunci harus stabil di data yang lebih besar.
- Lokasi UMKM harus disimpan konsisten dan tervalidasi.

### 4.2 PWA dan UX Stabil

- UI mobile harus lebih rapi pada layar kecil.
- Loading state, empty state, dan error state harus konsisten.
- Navigasi bawah dan tombol utama harus mudah dijangkau ibu jari.
- Aplikasi harus tetap nyaman saat koneksi lambat.
- Asset penting harus lebih agresif dicache.

### 4.3 Validasi Backend yang Lebih Kuat

- Chat hanya boleh dibuka oleh peserta percakapan yang sah.
- Order hanya boleh dibuat dari conversation yang benar.
- Confirm order hanya boleh dilakukan oleh pengguna yang berhak.
- Driver hanya boleh memproses order miliknya sendiri.
- Setiap transisi status order harus mengikuti state machine yang jelas.

### 4.4 Notifikasi

- Notifikasi push untuk driver dan pengguna harus aktif.
- Order assignment harus memberi sinyal yang jelas ke driver.
- Status selesai dan perubahan penting harus bisa diterima cepat.
- Job notifikasi harus berjalan via queue.

### 4.5 Settlement dan Operasi

- COD Talangan harus punya batas dan aturan operasional yang lebih tegas.
- Alur reimbursement atau settlement driver perlu disiapkan.
- Admin Bangjek harus punya visibilitas status order yang lebih lengkap.
- Riwayat order harus mudah ditelusuri.

### 4.6 Observability Dasar

- Error penting harus tercatat di log.
- Flow order dan assignment harus mudah diaudit.
- Minimal ada indikator untuk queue, notifikasi, dan delivery status.
- Failure pada upload proof harus bisa ditelusuri.

## 5. Data Minimum

Peningkatan data yang dibutuhkan:

- `umkm_profiles` dengan lokasi yang lebih konsisten.
- `orders` dengan status operasional yang lebih jelas.
- `order_events` atau log aktivitas bila dibutuhkan untuk audit.
- tabel pendukung settlement bila proses talangan mulai dipakai penuh.
- tabel notifikasi atau delivery log bila diperlukan untuk tracking.

## 6. Status Operasional

Status order tetap mengikuti alur dasar MVP pertama, tetapi MVP 2 boleh menambah status operasional jika dibutuhkan, misalnya:

- `lunas`
- `gagal_bayar`
- `dibatalkan`
- `menunggu_settlement`

Penambahan status harus dijaga agar state machine tetap bisa dipahami.

## 7. Acceptance Criteria

MVP 2 dianggap layak jika:

- Pencarian UMKM lebih akurat dan cepat di data yang lebih besar.
- Aplikasi lebih nyaman dipakai di layar HP kecil.
- Chat dan order tidak bisa dimanipulasi lewat payload liar.
- Driver menerima notifikasi assignment dengan andal.
- Admin bisa melihat status order dan settlement dengan lebih jelas.
- Proses selesai order lebih mudah diaudit.

## 8. Non-Functional Requirements

- Query spasial harus tetap responsif pada skala data yang naik.
- Layout mobile tidak boleh pecah di viewport umum HP.
- Notifikasi harus diproses lewat queue.
- Validation error harus jelas untuk pengguna.
- Upload proof tetap dibatasi ukuran dan format.
- Logging harus cukup untuk diagnosis masalah lapangan.

## 9. Out of Scope

Fitur berikut tidak menjadi target utama MVP 2:

- Native app Android/iOS.
- Full realtime chat berbasis WebSocket jika polling masih cukup.
- Algoritma otomatis matching driver yang kompleks.
- Rating dan review besar-besaran.
- Marketplace monetisasi penuh.
- Dynamic pricing.
- Navigasi turn-by-turn native.

## 10. KPI MVP 2

Target fase ini:

- 95% pencarian UMKM radius merespons dengan konsisten pada data uji.
- Notifikasi assignment terkirim ke driver dalam waktu yang masuk akal untuk operasional lapangan.
- 0 kasus order dibuat dari conversation yang tidak sah pada pengujian regresi.
- 0 kasus confirm order oleh pengguna yang bukan pemilik order pada pengujian regresi.
- Pengalaman HP terasa lebih stabil pada browser mobile dan emulator.

## 11. Urutan Implementasi

Prioritas implementasi:

1. Spasial hardening (`earthdistance` + index GiST; ganti ke PostGIS bila ekstensi tersedia) dan index spasial.
2. Validasi backend chat dan order.
3. Push notification dan queue reliability.
4. UX polish mobile.
5. Settlement dan status operasional.
6. Logging dan audit trail.

## 12. Standar Implementasi

- Ikuti pola `Controller -> Usecase -> View`.
- Tetap gunakan PostgreSQL sebagai database target.
- Pertahankan Query Builder untuk logic inti jika itu masih pola proyek.
- Jangan memperlebar scope ke fitur marketplace penuh sebelum flow operasional stabil.
- Setiap perubahan state order harus punya test regresi.

