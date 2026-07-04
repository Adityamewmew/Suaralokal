# MVP SuaraLokal

Dokumen ini mendefinisikan batas minimum produk SuaraLokal yang harus dibangun untuk validasi pasar awal. MVP ini mengikuti BRD, PRD, system design, dan guideline proyek.

## 1. Tujuan MVP

MVP SuaraLokal bertujuan membuktikan bahwa warga lokal dapat menemukan UMKM terdekat, bernegosiasi lewat chat, membuat pesanan, dan menyelesaikan pengiriman melalui driver Bangjek dalam satu alur PWA yang ringan di HP.

Fokus utama MVP adalah validasi adopsi dan alur transaksi, bukan monetisasi.

## 2. Target Pengguna

MVP harus melayani empat aktor utama:

- `pengguna`: warga lokal yang mencari barang atau jasa terdekat.
- `umkm`: penjual atau penyedia jasa mikro yang menerima chat dan membuat pesanan.
- `ojek_admin`: operator Bangjek yang melihat order masuk dan menugaskan driver.
- `driver`: mitra Bangjek yang menjemput, mengantar, dan menyelesaikan order.

## 3. Scope MVP

MVP harus mencakup alur end-to-end berikut:

1. Pengguna membuka PWA dan mengizinkan lokasi.
2. Sistem menampilkan UMKM terdekat berdasarkan jarak.
3. Pengguna membuka profil UMKM dan memulai chat.
4. Pengguna dan UMKM menyepakati barang, harga, dan ongkos kirim.
5. UMKM membuat tagihan pesanan dari ruang chat.
6. Pengguna mengonfirmasi pesanan.
7. Order masuk ke dashboard Admin Bangjek.
8. Admin Bangjek menugaskan driver.
9. Driver melihat detail order, titik jemput, titik antar, dan metode pembayaran.
10. Driver mengubah status order saat barang dijemput dan diantar.
11. Driver mengunggah bukti pengiriman.
12. Order berubah menjadi selesai.

## 4. Fitur Wajib

### 4.1 Autentikasi dan Role

- Login dan logout.
- Role minimal: `pengguna`, `umkm`, `ojek_admin`, `driver`, `superadmin`.
- Route penting harus dibatasi berdasarkan role.
- User internal dapat dikelola dari admin panel yang sudah ada.

### 4.2 Profil UMKM

- UMKM dapat mengisi profil toko.
- Data minimal: nama toko, deskripsi, alamat, nomor telepon, kategori, dimensi barang default, status buka/tutup, latitude, longitude.
- Lokasi UMKM wajib tersimpan untuk pencarian jarak.
- UMKM yang belum melengkapi profil diarahkan ke halaman lengkapi profil.

### 4.3 Discovery UMKM Terdekat

- Pengguna dapat memberi izin lokasi melalui browser.
- Sistem menampilkan daftar UMKM dalam radius awal 5-10 km.
- Daftar diurutkan dari jarak terdekat.
- Jika izin lokasi ditolak, sistem menampilkan fallback input lokasi manual atau pesan instruksi aktifkan lokasi.
- Peta menggunakan OpenStreetMap + Leaflet.

### 4.4 Chat Negosiasi

- Pengguna dapat memulai percakapan dengan UMKM.
- Jika percakapan sudah ada, sistem membuka percakapan yang sama.
- Pesan tersimpan di database.
- MVP cukup memakai AJAX polling 3-5 detik, belum perlu WebSocket.
- Ruang chat menampilkan pesan, waktu kirim, dan pengirim.

### 4.5 Pembuatan Pesanan dari Chat

- UMKM dapat membuat pesanan dari percakapan.
- Data pesanan minimal: item, qty, harga item, total harga barang, ongkos kirim, metode pembayaran.
- Metode pembayaran MVP: `cod_talangan` dan `non_tunai`.
- Jika total barang lebih dari Rp100.000, opsi `cod_talangan` dinonaktifkan.
- Pesanan awal berstatus `tunggu_konfirm`.

### 4.6 Konfirmasi Pesanan oleh Pengguna

- Pengguna melihat kartu tagihan di chat.
- Pengguna dapat menekan konfirmasi.
- Setelah dikonfirmasi, status order berubah menjadi `cari_driver`.
- Order yang sudah `cari_driver` muncul di dashboard Admin Bangjek.

### 4.7 Dashboard Admin Bangjek

- Admin Bangjek dapat melihat order dengan status `cari_driver`.
- Admin Bangjek dapat memilih driver dari daftar user role `driver`.
- Setelah driver dipilih, status order berubah menjadi `dijemput`.
- Order mencatat `driver_id`.

### 4.8 Dashboard Driver

- Driver melihat daftar order miliknya.
- Driver dapat melihat detail toko, alamat jemput, alamat antar, harga barang, ongkir, dan metode pembayaran.
- Driver dapat menekan `Sudah Diambil` untuk mengubah status menjadi `diantar`.
- Driver dapat menekan `Selesaikan Pesanan` dan wajib mengunggah foto bukti pengiriman.
- Setelah bukti berhasil diunggah, status order berubah menjadi `selesai`.

### 4.9 PWA Mobile

- Aplikasi harus mobile-first.
- Aplikasi dapat dibuka dengan baik di browser HP dan Android Emulator.
- Manifest PWA tersedia.
- Service Worker meng-cache aset penting.
- Tampilan utama tidak bergantung pada layout desktop.

## 5. Data Minimum

Tabel minimum yang dibutuhkan:

- `users`
- `umkm_profiles`
- `conversations`
- `messages`
- `orders`
- `order_items`

Kolom penting:

- `users.role` atau pemetaan role yang ekuivalen dengan standar starter kit.
- `umkm_profiles.latitude`
- `umkm_profiles.longitude`
- `orders.payment_method`
- `orders.order_status`
- `orders.driver_id`
- `orders.proof_of_delivery_url`

Jika PostGIS sudah siap, lokasi UMKM idealnya disimpan sebagai `geometry(Point)`. Jika belum siap pada fase development awal, latitude dan longitude boleh dipakai dulu dengan catatan migrasi ke PostGIS tetap menjadi kebutuhan MVP sebelum rilis.

## 6. Status Order

Status order MVP:

- `tunggu_konfirm`: tagihan dibuat UMKM dan menunggu persetujuan pengguna.
- `cari_driver`: pengguna sudah konfirmasi dan order menunggu assignment Bangjek.
- `dijemput`: driver sudah ditugaskan dan menuju lokasi UMKM.
- `diantar`: barang sudah diambil dari UMKM dan sedang diantar ke pengguna.
- `selesai`: barang sudah diterima dan bukti pengiriman sudah diunggah.

Transisi status harus satu arah dan tidak boleh melompati tahap.

## 7. Acceptance Criteria

MVP dianggap layak untuk uji lapangan jika semua kriteria ini terpenuhi:

- Pengguna bisa melihat daftar UMKM terdekat dari HP.
- Pengguna bisa chat dengan UMKM.
- UMKM bisa membuat tagihan dari chat.
- Pengguna bisa mengonfirmasi tagihan.
- Admin Bangjek bisa melihat order masuk dan assign driver.
- Driver bisa melihat order yang ditugaskan.
- Driver bisa mengubah status order sampai selesai.
- Driver wajib upload bukti pengiriman sebelum order selesai.
- Alur `cod_talangan` menampilkan total barang + ongkir.
- Alur `non_tunai` memberi ruang untuk link pembayaran manual dari UMKM.

## 8. Non-Functional Requirements

- Halaman mobile utama harus ringan dan cepat.
- Query pencarian UMKM harus ditargetkan di bawah 500 ms pada data MVP.
- Route mutasi order harus terlindungi auth dan role.
- File bukti pengiriman harus divalidasi sebagai gambar.
- Proses upload gambar harus memiliki batas ukuran.
- PWA harus tetap bisa memuat aset dasar pada koneksi tidak stabil.

## 9. Out of Scope

Fitur berikut tidak masuk MVP:

- Payment gateway otomatis.
- Saldo wallet.
- Settlement dana driver.
- Rating dan review.
- WebSocket atau real-time chat penuh.
- Algoritma otomatis pencarian driver.
- Native app Android/iOS.
- Tracking driver real-time.
- Navigasi turn-by-turn di dalam aplikasi.
- Sponsored pin atau fitur monetisasi.

## 10. KPI MVP

Target validasi awal:

- 20 UMKM memiliki profil lengkap dan koordinat valid.
- 10 driver tersedia sebagai akun aktif.
- 100 interaksi chat unik terjadi dalam 30 hari.
- 15 transaksi selesai dengan status `selesai`.
- GMV awal mencapai Rp2.000.000 dari transaksi COD Talangan dan Non-Tunai.

## 11. Urutan Implementasi

Prioritas implementasi:

1. Data model, role, dan akses dasar.
2. Profil UMKM dan koordinat.
3. Discovery UMKM terdekat.
4. Chat polling.
5. Pembuatan dan konfirmasi pesanan.
6. Dashboard Admin Bangjek.
7. Dashboard Driver dan proof of delivery.
8. PWA manifest, service worker, dan pengujian di Android Emulator.

## 12. Standar Implementasi

- Ikuti pola `Controller -> Usecase -> View`.
- Gunakan Query Builder di Usecase sesuai standar starter kit.
- Gunakan Blade, Tailwind CSS 4, Preline UI, dan Alpine.js secukupnya.
- Jangan menambah dependency besar tanpa kebutuhan langsung.
- Buat feature test untuk alur order utama.
- Perubahan UI harus diuji pada viewport HP dan Android Emulator.

