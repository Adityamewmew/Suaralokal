# Business Requirements Document (BRD) — SuaraLokal

**Nama Proyek:** SuaraLokal
**Model Bisnis:** B2B2C (Business-to-Business-to-Consumer) / Hyper-local Marketplace
**Target Wilayah Operasional (Fase 1):** Banyuwangi (Area Kota hingga Kalipuro)
**Fase:** Minimum Viable Product (MVP)

---

## 1. Ringkasan Eksekutif (Executive Summary)
**Peluang Bisnis:**
Banyak transaksi harian di wilayah kabupaten/kota madya terjadi secara mikro dan impulsif (kebutuhan mendadak). UMKM lokal dan penyedia jasa (bengkel, sol sepatu, warung kelontong) memiliki potensi pasar yang besar di radius 1-5 KM, namun kehilangan pelanggan karena tidak terindeks secara digital. Platform *e-commerce* raksasa tidak relevan bagi mereka karena menuntut manajemen stok yang kaku, perang harga, dan waktu pengiriman yang bisa memakan waktu berhari-hari.

**Solusi SuaraLokal:**
SuaraLokal menjembatani *gap* ini dengan menghadirkan platform *Conversational Commerce* (transaksi berbasis percakapan). Pendekatan ini mempertahankan budaya tawar-menawar lokal, dipadukan dengan kepastian logistik instan melalui kemitraan dengan ojek lokal (Bangjek). 

---

## 2. Tujuan Bisnis & Indikator Kinerja Utama (KPI)
Fokus pada MVP ini adalah *market validation* (validasi pasar) dan adopsi, bukan monetisasi instan.

**Tujuan Bisnis (Business Goals):**
1. Mendorong digitalisasi UMKM lokal tanpa memaksa mereka mengubah budaya jualan konvensional (lewat *chat*).
2. Membuka aliran pendapatan baru bagi mitra *driver* Bangjek melalui sistem pengiriman barang (bukan hanya penumpang).
3. Membangun basis data spasial (titik koordinat) UMKM lokal yang akurat.

**KPI Bisnis (Kuartal 1):**
* **Merchant Acquisition:** 50 UMKM lokal terdaftar dan aktif membuka profil.
* **Logistics Readiness:** 10 *driver* Bangjek aktif menerima order melalui sistem.
* **Transaction Volume (GMV):** Mencapai Total Nilai Transaksi (GMV) sebesar Rp2.000.000 dari transaksi COD Talangan dan Non-Tunai dalam 1 bulan pertama.

---

## 3. Analisis Pasar & Target Audiens
**Segmentasi Geografis:**
Peluncuran awal difokuskan pada area dengan kepadatan menengah hingga tinggi di Banyuwangi, mencakup area pusat kota hingga wilayah Kalipuro, untuk memastikan jarak antar (*routing*) tetap ekonomis bagi *driver*.

**Target Konsumen (B2C):**
* Warga lokal berusia 18-40 tahun.
* Memiliki mobilitas tinggi atau terlalu sibuk untuk keluar rumah membeli kebutuhan mendadak.
* Terbiasa menggunakan WhatsApp untuk memesan barang namun sering terkendala masalah pengantaran.

**Target Mitra (B2B - UMKM):**
* Warung makan/camilan lokal, toko kelontong, penyedia jasa mikro (penjahit, bengkel, reparasi).
* Belum memiliki sistem kasir digital (POS) atau admin khusus *e-commerce*.

---

## 4. Model Bisnis & Rencana Monetisasi (Revenue Stream)
*Catatan: Pada fase MVP, penggunaan platform digratiskan (0% komisi) untuk mempercepat akuisisi pengguna.*

**Potensi Monetisasi di Fase Selanjutnya (V2.0):**
1. **Bagi Hasil Ongkos Kirim (Take Rate Logistik):** Pemotongan komisi tetap (misal: Rp1.000 - Rp2.000) per transaksi sukses dari total ongkos kirim yang dibayarkan ke sistem Bangjek.
2. **Prioritas Pencarian (Sponsored Pin):** UMKM dapat membayar biaya berlangganan murah agar titik lokasi mereka di- *highlight* atau muncul paling atas pada sistem pencarian radius PostGIS.
3. **Platform Fee (Konsumen):** Biaya layanan aplikasi flat (misal: Rp1.000/transaksi) yang dibebankan kepada konsumen akhir pada saat *checkout* metode Non-Tunai.

---

## 5. Kebutuhan Sumber Daya & Pemangku Kepentingan (Stakeholders)
Pengembangan dan operasional MVP ini dirancang dengan pendekatan ramping (*lean/agile*) agar pengembangan berjalan cepat tanpa *overhead* yang besar.

**Tim Inti (Core Team):**
* **1 UI/UX Designer:** Bertanggung jawab atas riset pengalaman pengguna, *wireframing*, dan desain antarmuka PWA agar mudah digunakan oleh warga lokal.
* **1 Frontend Developer:** Berfokus pada implementasi UI (Tailwind CSS, Alpine.js) dan interaktivitas peta (Leaflet.js) serta sinkronisasi AJAX.
* **1 Backend Developer:** Bertanggung jawab atas arsitektur sistem (Laravel), perancangan *database* spasial (PostgreSQL + PostGIS), antrean *server*, dan keamanan data.

**Mitra Strategis:**
* **Manajemen Bangjek:** Sebagai penyedia armada *driver* lokal dan pengelola operasional *dashboard* Admin Bangjek.

---

## 6. Asumsi Finansial & Operasional (Financial & Operational Assumptions)
* **Infrastruktur IT (Fase Development):** Biaya *server* masih Rp0 karena *development* dan *testing* dilakukan secara lokal (FlyEnv).
* **Infrastruktur IT (Fase Rilis MVP):** Estimasi biaya Rp150.000 - Rp300.000/bulan untuk sewa VPS dasar yang mendukung instalasi PostgreSQL dan PostGIS.
* **Operasional Logistik:** Risiko kegagalan bayar atau penipuan (order fiktif) pada metode "COD Talangan" dimitigasi dengan penetapan batas maksimal talangan (misal: Rp100.000) pada sistem.

---

## 7. Risiko Bisnis & Strategi Mitigasi
| Kategori Risiko | Deskripsi | Rencana Mitigasi |
|---|---|---|
| **Adopsi UMKM** | UMKM enggan menginstal aplikasi baru karena memori HP penuh. | Penggunaan teknologi PWA (tanpa *download* dari Play Store, ukuran di bawah 2MB). |
| **Finansial (COD)** | *Driver* kehabisan uang tunai untuk menalangi barang UMKM, pesanan terbengkalai. | Edukasi opsi *Payment Link* (Non-Tunai) agar *driver* hanya fokus mengantar barang tanpa menalangi. |
| **Kompetisi** | Kebiasaan warga yang sudah nyaman langsung WhatsApp ke tukang ojek langganan. | Promosikan fitur "Pencarian UMKM Terdekat" yang tidak dimiliki oleh kontak WhatsApp biasa. |