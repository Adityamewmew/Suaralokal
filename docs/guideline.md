# Guideline SuaraLokal

Dokumen ini menjelaskan standar kerja untuk proyek SuaraLokal agar tetap selaras dengan pola starter kit yang sudah ada.

## 1. Identitas Proyek

- Nama produk: SuaraLokal
- Tipe aplikasi: PWA hyper-local marketplace berbasis chat
- Fokus MVP: pencarian UMKM terdekat, percakapan, pembuatan pesanan, dan distribusi lewat Bangjek
- Area awal: Banyuwangi

## 2. Prinsip Utama

- Ikuti pola yang sudah ada di starter kit, bukan membuat arsitektur baru tanpa alasan kuat.
- Jaga aplikasi tetap ringan, modular, dan mudah dirawat.
- Prioritaskan pengalaman pengguna mobile-first karena target utama adalah warga lokal di HP.
- Hindari fitur di luar scope MVP jika belum mendukung validasi pasar.

## 3. Stack dan Batasan Teknis

- Backend: Laravel 13
- PHP: 8.3
- Frontend: Tailwind CSS 4, Preline UI, Alpine.js bila diperlukan
- Build tool: Bun / Vite
- PWA: `vite-plugin-pwa`, Service Worker, caching aset penting, dan perilaku offline-friendly
- Peta: OpenStreetMap + Leaflet
- Notifikasi: Firebase Cloud Messaging (FCM)
- Queue: Laravel Queue untuk job berat dan notifikasi
- Testing: Pest
- Database: PostgreSQL untuk data utama, PostGIS untuk kebutuhan spasial
- Aplikasi utama dijalankan sebagai monolith Laravel
- Target utama: mobile-first PWA untuk HP, bukan native app

## 4. Arsitektur Wajib

Pola utama proyek ini adalah:

`Controller -> Usecase -> View`

Aturan:

- Controller hanya menerima request, memanggil usecase, lalu mengembalikan view atau redirect.
- Semua logika bisnis dan query database ditaruh di Usecase.
- View hanya untuk presentasi.
- Tidak memakai Repository Pattern kecuali ada keputusan eksplisit untuk menambahkannya.
- Untuk query, utamakan Query Builder daripada Eloquent jika mengikuti pola starter kit yang ada.

## 5. Struktur Folder

Ikuti struktur yang sudah tersedia di starter kit:

- `app/Http/Controllers/` untuk controller
- `app/Usecase/` untuk logika bisnis
- `app/Models/` untuk model yang memang dibutuhkan
- `resources/views/_admin/` untuk tampilan admin dan role internal
- `resources/views/components/` untuk komponen Blade
- `resources/views/partials/` untuk potongan UI kecil
- `database/seeders/` untuk seed data
- `db-migrator-with-drizzle/` untuk migrasi dan seed berbasis Drizzle bila digunakan oleh project ini

Jangan membuat base folder baru tanpa alasan yang jelas.

## 6. Standar Controller

- Gunakan property `$page` untuk metadata halaman seperti route dan title.
- Gunakan constructor property promotion untuk inject Usecase.
- Gunakan nama method yang konsisten dengan aksi, misalnya `index`, `detail`, `doCreate`, `doUpdate`, `delete`.
- Return type harus jelas.
- Hindari query langsung di controller.
- Gunakan `ResponseConst` untuk pesan sukses dan error.

Contoh pola yang diikuti:

```php
protected array $page = [
    'route' => 'user',
    'title' => 'Pengguna Aplikasi',
];
```

## 7. Standar Usecase

- Semua logika bisnis masuk ke Usecase.
- Validasi input dilakukan di Usecase bila memang sudah jadi pola proyek.
- Gunakan transaksi database untuk operasi tulis yang penting.
- Gunakan `DB::table()` dan join yang jelas untuk query kompleks.
- Kembalikan response dengan format yang konsisten.
- Gunakan nama method yang deskriptif, bukan singkatan.

## 8. Standar View

- Gunakan layout admin yang sudah ada.
- Ikuti pola Blade yang konsisten untuk halaman list, form, detail, dan modal.
- Gunakan komponen yang sudah tersedia sebelum membuat komponen baru.
- Data record tunggal sebaiknya dicast ke object di controller sebelum masuk view.
- Gunakan nama variabel yang mudah dibaca di Blade.

## 9. Standar UI/UX

- Mobile-first.
- Tampilan harus sederhana, cepat dipahami, dan cocok untuk pengguna lokal.
- Gunakan komponen admin yang konsisten dengan starter kit.
- Jangan membuat desain yang terlalu berat atau terlalu dekoratif jika tidak ada manfaat fungsional.
- Untuk fitur SuaraLokal, utamakan:
  - pencarian UMKM terdekat
  - chat yang jelas
  - status order yang mudah dipahami
  - tombol aksi yang tegas

## 10. Standar Data dan Role

Role utama yang harus didukung:

- `pengguna`
- `umkm`
- `ojek_admin`
- `driver`
- `superadmin` bila memang dibutuhkan oleh admin sistem

Aturan data:

- Simpan koordinat lokasi UMKM secara spasial.
- Gunakan struktur data yang mendukung pencarian radius dan jarak.
- Status order harus jelas dan konsisten.
- Bukti pengiriman harus bisa dilacak.

## 11. Standar Fitur SuaraLokal

Fitur yang dianggap inti:

- cari UMKM terdekat
- chat antara pengguna dan UMKM
- buat pesanan dari chat
- konfirmasi pembayaran
- assign driver oleh admin Bangjek
- update status pengiriman
- upload bukti selesai kirim

Fitur ini harus dijaga tetap sederhana dan tidak dipenuhi fitur tambahan yang belum dibutuhkan.

## 12. Standar Testing

- Gunakan Pest untuk semua test baru.
- Prioritaskan feature test untuk alur utama.
- Test harus mencakup:
  - pencarian UMKM
  - pembuatan chat atau conversation
  - pembuatan order
  - perubahan status order
  - assign driver
  - upload proof of delivery
- Jangan mengandalkan manual testing jika alur sudah bisa ditulis sebagai test.

## 13. Standar Dokumentasi

- Dokumentasi harus singkat, spesifik, dan relevan dengan fitur yang sedang dibangun.
- Jika ada perubahan besar, update dokumen desain atau PRD yang terkait.
- Jangan menulis dokumentasi yang terlalu umum dan tidak bisa dipakai sebagai acuan implementasi.

## 14. Batasan MVP

Yang tidak menjadi prioritas awal:

- payment gateway otomatis
- rating dan review
- native mobile app
- real-time map navigation yang kompleks
- algoritma matching driver otomatis

## 15. Praktik Kerja

- Selalu cek pola file yang sudah ada sebelum menambah file baru.
- Reuse komponen, layout, dan helper yang sudah tersedia.
- Jaga penamaan konsisten antara route, controller, usecase, dan view.
- Jangan menambah dependensi baru tanpa alasan yang kuat.
- Jika ada dua cara yang mungkin, pilih yang paling sesuai dengan pola starter kit ini.
