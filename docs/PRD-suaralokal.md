# Product Requirements Document (PRD) — SuaraLokal

**Nama Produk:** SuaraLokal
**Versi:** 1.0 (MVP)
**Platform:** Progressive Web App (PWA)
**Target Rilis:** [Isi Bulan/Tahun]

---

## 1. Latar Belakang & Visi Produk
**Problem Statement:**
Konsumen kesulitan menemukan UMKM atau penyedia jasa mikro hiper-lokal (contoh: sol sepatu, penambal ban, toko kelontong spesifik) di radius terdekat karena tidak terindeks di mesin pencari. Di sisi lain, UMKM lokal enggan menggunakan e-commerce besar karena sistem katalog yang rumit dan preferensi transaksi berbasis negosiasi fleksibel. 

**Visi:**
Menjadi platform *Conversational Commerce* hiper-lokal yang menjembatani warga dengan UMKM terdekat melalui negosiasi *chat* dan integrasi armada ojek lokal (Bangjek) untuk distribusi instan.

---

## 2. Metrik Kesuksesan (SMART Goals untuk MVP)
* **Acquisition:** 20 UMKM mendaftar dan memetakan koordinat toko mereka di bulan pertama.
* **Activation:** 100 interaksi *chat* unik terjadi antara Pengguna dan UMKM dalam 30 hari pertama.
* **Retention:** 30% pengguna yang menyelesaikan transaksi pertama akan kembali membuka aplikasi dalam 14 hari berikutnya.
* **Revenue/Transaction:** Tercapai 15 transaksi sukses yang diselesaikan oleh Driver Bangjek (status: `selesai`).

---

## 3. Detail User Persona
| Peran | Profil | Pain Points (Masalah) | Goals (Kebutuhan Utama) |
|---|---|---|---|
| **Pengguna** | Warga lokal, sibuk, butuh solusi cepat. | Malas jauh-jauh mencari barang/jasa tanpa kepastian harga. | Mengetahui toko terdekat dan bisa *chat* sebelum pesan. |
| **UMKM** | Usaha mikro, gaptek sistem katalog rumit. | Harga dan stok sering berubah, tidak punya armada antar. | Bisa bernegosiasi via *chat* dan dibantu kurir lokal. |
| **Admin Bangjek**| Operator logistik lokal. | Sulit melacak orderan warga secara terpusat. | Memiliki *dashboard* antrean pesanan yang jelas. |
| **Driver** | Mitra ojek/kurir lokal. | Sering miskomunikasi soal siapa yang harus bayar barang. | Titik map akurat, tahu metode pembayaran, dan bisa *upload* bukti. |

---

## 4. Functional Requirements (FR) Spesifik
Kebutuhan sistem dipecah berdasarkan modul agar mudah dilacak pada *board* manajemen proyek (Trello/Jira).

### Modul 1: Discovery & Profil UMKM
* **FR-1.1:** Sistem harus memverifikasi *role* pengguna. Jika pengguna mendaftar sebagai UMKM, sistem mewajibkan pengisian form profil (Nama Toko, Alamat, Kategori Dimensi: `ringan, sedang, besar`).
* **FR-1.2:** Sistem harus menyimpan koordinat GPS (Latitude/Longitude) UMKM menggunakan tipe data `geometry(Point)` pada PostgreSQL.
* **FR-1.3:** Halaman beranda Pengguna harus meminta izin lokasi *browser* (Geolocation API).
* **FR-1.4:** Sistem menampilkan daftar UMKM terdekat dengan radius maksimal 5-10 KM menggunakan PostGIS (`ST_DWithin` & `ST_Distance`), diurutkan dari yang terdekat.

### Modul 2: Conversational Commerce (Chat)
* **FR-2.1:** Pengguna dapat menekan tombol "Chat Penjual" di profil UMKM untuk membuat entitas `CONVERSATIONS` baru (jika belum ada).
* **FR-2.2:** Sistem harus memuat pesan menggunakan metode AJAX Polling (interval 3-5 detik via Axios) untuk memeriksa pesan baru di tabel `MESSAGES`.
* **FR-2.3:** UMKM memiliki tombol khusus "Buat Pesanan" di dalam ruang *chat*.

### Modul 3: Order & Pembayaran
* **FR-3.1:** Saat UMKM menekan "Buat Pesanan", sistem menampilkan *form* (Nama Barang, Qty, Harga Barang, Ongkos Kirim Bangjek, dan Pilihan Pembayaran: `cod_talangan` atau `non_tunai`).
* **FR-3.2:** Pengguna menerima pop-up/kartu tagihan di dalam *chat* dan harus menekan "Konfirmasi" agar status order berubah menjadi `cari_driver`.
* **FR-3.3:** Jika metode `non_tunai` dipilih, UMKM wajib mengirimkan tautan pembayaran pihak ketiga (GoPay/DANA/QRIS) di *chat* terlebih dahulu.

### Modul 4: Logistik (Bangjek Dashboard)
* **FR-4.1:** Pengguna dengan *role* `ojek_admin` dapat melihat tabel pesanan yang berstatus `cari_driver`.
* **FR-4.2:** Admin Bangjek dapat memilih pesanan dan memilih *Driver* (dari *dropdown* daftar pengguna berstatus `driver`) untuk menugaskan (update `driver_id`).
* **FR-4.3:** Driver melihat pesanan miliknya yang berstatus `dijemput`. UI menampilkan detail harga, ongkir, metode pembayaran, dan tombol aksi "Sudah Diambil" (update status: `diantar`).
* **FR-4.4:** Driver menekan tombol "Selesaikan Pesanan", yang mewajibkan input unggah *file* gambar ke `proof_of_delivery_url`. Status pesanan berubah menjadi `selesai`.

---

## 5. Acceptance Criteria (Contoh: Fitur Buat Pesanan)
*Kriteria penerimaan untuk tim QA/Testing menggunakan format Given-When-Then.*

**Skenario: UMKM membuat tagihan pesanan dengan metode COD Talangan.**
* **Given** UMKM berada di dalam ruang *chat* dengan Pengguna yang sudah sepakat.
* **When** UMKM mengisi form pesanan (Rp50.000 barang, Rp10.000 ongkir) dan memilih metode `cod_talangan`, lalu menekan "Kirim Tagihan".
* **Then** Pengguna melihat tagihan Rp60.000 di layar mereka.
* **And** Ketika Pengguna menekan "Konfirmasi", tabel `ORDERS` menyimpan data dengan status `cari_driver`.
* **And** Pesanan tersebut langsung muncul di *dashboard* Admin Bangjek.

---

## 6. Non-Functional Requirements (NFR)
Batasan teknis dan performa agar aplikasi tidak *crash* atau lambat.
* **Performa (Latensi):** Proses *query* pencarian radius UMKM (PostGIS) harus merespons di bawah 500ms. Wajib menggunakan *index* `GiST` pada kolom koordinat.
* **Keamanan:** Semua akses *route* yang memanipulasi pesanan wajib diproteksi oleh *Middleware Role* bawaan Laravel (RBAC). 
* **PWA & Caching:** Aplikasi wajib dikonfigurasi menggunakan `vite-plugin-pwa`. Aset statis (CSS, Alpine.js, *Icon*) harus di-*cache* agar proses *loading* halaman utama terjadi di bawah 2 detik.
* **Push Notification:** Modul pengiriman notifikasi FCM ke PWA Driver harus dilempar ke Laravel Queue (berjalan di *background*) agar proses *assign* order oleh Admin Bangjek tidak *lagging*.

---

## 7. Asumsi dan Mitigasi Risiko
| Risiko / Asumsi | Dampak | Mitigasi |
|---|---|---|
| Driver menolak nalangin COD karena harga barang terlalu mahal. | Transaksi batal, Pengguna kecewa. | Menambahkan batasan UI: Jika total harga > Rp100.000, opsi `cod_talangan` otomatis ter- *disable* oleh sistem. |
| Pengguna memblokir izin lokasi browser (GPS). | Peta dan jarak tidak berfungsi. | Menampilkan halaman *fallback* (Peringatan) yang mengedukasi cara mengaktifkan GPS. |
| Pengiriman foto bukti pengiriman gagal karena koneksi lambat. | Order menggantung di status `diantar`. | Menggunakan kompresi gambar (contoh: kompresi canvas via Alpine.js/JS) sebelum diunggah ke *server* Laravel. |

---

## 8. Out of Scope (Tidak Masuk Versi 1.0)
* Payment Gateway Otomatis (Midtrans/Xendit/Stripe).
* Fitur *Rating* dan *Review* Bintang untuk UMKM.
* Sistem navigasi *real-time turn-by-turn* bawaan (Driver akan diarahkan (*deep-link*) membuka Google Maps).
* Aplikasi *Native Mobile* (APK/AAB) di Play Store.