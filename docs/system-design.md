# System Design — SuaraLokal

## 1. Ringkasan Sistem

**SuaraLokal** adalah aplikasi PWA (Progressive Web App) yang menghubungkan **UMKM lokal** dengan **Pengguna** melalui fitur pencarian berbasis lokasi dan chat negosiasi, dengan layanan antar-jemput barang yang difasilitasi oleh mitra logistik **Bangjek** (Admin & Driver).

Alur inti: Pengguna mencari UMKM terdekat → negosiasi via chat → UMKM membuat pesanan → pembayaran (Non-Tunai via link atau COD Talangan) → order diteruskan ke Bangjek → driver menjemput & mengantar → bukti pengiriman diunggah → transaksi selesai.

### Aktor Sistem
| Aktor | Peran Utama |
|---|---|
| 👤 Pengguna | Mencari UMKM, chat nego, konfirmasi & bayar pesanan |
| 🏪 UMKM | Mengatur profil toko, balas chat, membuat tagihan pesanan |
| 🎧 Admin Bangjek | Memantau order masuk, menugaskan driver |
| 🛵 Driver | Menjemput & mengantar barang, upload bukti kirim |

---

## 2. Arsitektur Sistem

```mermaid
graph TD
    subgraph Client_Tier["📱 Client Tier (PWA di HP/Browser)"]
        UI["Web UI<br/>(Blade, Alpine.js, Tailwind)"]
        SW["Service Worker<br/>(vite-plugin-pwa)"]
        Cache[("Browser Cache<br/>(CSS, JS, Ikon)")]
        Manifest["Web App Manifest<br/>(Add to Home Screen)"]

        UI <--> SW
        SW <--> Cache
        SW -.-> Manifest
    end

    subgraph Server_Tier["⚙️ App Tier (Laravel Monolith)"]
        Nginx["Web Server<br/>(FlyEnv Nginx)"]
        Laravel["Laravel 13 App<br/>(Controllers, Usecase)"]
        Queue["Background Queue<br/>(Kirim Notif, Kompres Foto)"]

        Nginx <--> Laravel
        Laravel <--> Queue
    end

    subgraph Data_Tier["🗄️ Data Tier"]
        DB[("Local Database<br/>(PostgreSQL di FlyEnv/Docker)")]
    end

    subgraph External_Services["🌐 External Services"]
        FCM["Firebase Cloud Messaging<br/>(FCM)"]
        OSM["OpenStreetMap<br/>(Leaflet Tiles)"]
    end

    SW <==>|HTTP/HTTPS Requests| Nginx
    UI -.->|AJAX / Polling Chat| Nginx
    Laravel <==>|Query Builder / Eloquent| DB
    UI -.->|Load Peta| OSM
    Queue -.->|Trigger Notifikasi| FCM
    FCM -.->|Kirim Push Notif| SW
```

**Catatan arsitektur:**
- **Client Tier**: PWA agar bisa di-*install* ke home screen tanpa perlu App Store, dengan Service Worker untuk caching aset statis.
- **App Tier**: Monolith Laravel — cukup untuk skala awal, memisahkan proses berat (kompresi foto, notifikasi) ke background queue supaya request utama tetap cepat.
- **Data Tier**: PostgreSQL dipilih karena dukungan native PostGIS untuk query geospasial (cari UMKM terdekat).
- **External Services**: OpenStreetMap/Leaflet untuk peta (gratis, tanpa API key berbayar), FCM untuk push notification ke PWA.

---

## 3. Use Case Diagram

```mermaid
flowchart LR
    P([👤 Pengguna])
    U([🏪 UMKM])
    A([🎧 Admin Bangjek])
    D([🛵 Driver])

    subgraph SuaraLokal [Aplikasi SuaraLokal]
        direction TB
        UC1(Cari UMKM Terdekat di Peta)
        UC2(Kirim & Balas Chat Nego)
        UC3(Buat Detail & Tagihan Pesanan)
        UC4(Konfirmasi & Pilih Pembayaran)
        UC5(Lihat Antrean Pesanan Masuk)
        UC6(Tugaskan Pesanan ke Driver)
        UC7(Lihat Titik Jemput & Antar)
        UC8(Upload Foto Bukti Pengiriman)
    end

    P --> UC1
    P --> UC2
    P --> UC4

    U --> UC2
    U --> UC3

    A --> UC5
    A --> UC6

    D --> UC7
    D --> UC8
```

---

## 4. Alur Proses Bisnis (Flowchart)

```mermaid
flowchart TD
    Start([Pengguna mencari UMKM terdekat di Maps/Pencarian]) --> Chat[Pengguna & UMKM berdiskusi via Chat]
    Chat --> Nego[Kesepakatan detail pesanan & harga]
    Nego --> BuatPesanan[UMKM menekan tombol 'Buat Pesanan']

    BuatPesanan --> PilihBayar{Pilih Metode Pembayaran}

    PilihBayar -->|Non-Tunai / Payment Link| KirimLink[UMKM mengirim Link Pembayaran di Chat]
    KirimLink --> BayarLink[Pengguna membayar lunas via Link]
    BayarLink --> StatusLunas[UMKM set status order: 'Lunas']
    StatusLunas --> Konfirm[Pengguna mengonfirmasi pesanan]

    PilihBayar -->|COD Talangan| Konfirm

    Konfirm --> MasukAdmin[Order masuk ke Dashboard Admin Bangjek]
    MasukAdmin --> Assign[Admin Bangjek menugaskan Driver]
    Assign --> Jemput[Driver tiba di lokasi UMKM]

    Jemput --> CekDriver{Metode Pembayaran?}

    CekDriver -->|Non-Tunai| JemputNonTunai[UMKM memberikan uang Ongkir ke Driver]
    CekDriver -->|COD Talangan| JemputCOD[Driver menalangi/membayar harga barang ke UMKM]

    JemputNonTunai --> AntarBarang[Driver mengantar barang]
    JemputCOD --> AntarBarang

    AntarBarang --> Tiba{Barang Tiba di Pengguna}

    Tiba -->|Non-Tunai| SelesaiNon[Pengguna menerima barang]
    Tiba -->|COD Talangan| SelesaiCOD[Pengguna membayar uang tunai 'Barang + Ongkir' ke Driver]

    SelesaiNon --> UploadFoto[Driver mengunggah Foto Bukti Pengiriman]
    SelesaiCOD --> UploadFoto

    UploadFoto --> End([Transaksi Selesai])
```

**Poin penting logika bisnis:**
- **COD Talangan** = driver "menalangi" pembayaran barang ke UMKM di lokasi jemput, lalu ditagih kembali (barang + ongkir) ke pengguna saat serah terima. Ini artinya driver menanggung risiko dana sementara — perlu dipertimbangkan mekanisme *settlement*/reimbursement driver di modul finansial.
- **Non-Tunai** = pengguna bayar lunas via link sebelum barang dijemput; UMKM hanya perlu membayar ongkir tunai ke driver saat penjemputan.
- Foto bukti pengiriman wajib di kedua alur sebagai penutup transaksi (proof of delivery).

---

## 5. Sequence Diagram (Alur Antar Sistem)

```mermaid
sequenceDiagram
    autonumber
    actor P as Pengguna
    participant S as Sistem SuaraLokal
    actor U as UMKM
    actor A as Admin Bangjek
    actor D as Driver

    rect rgb(240, 248, 255)
        Note over P, U: Fase 1: Discovery & Nego
        P->>S: Buka peta & kirim lokasi saat ini
        S-->>P: Tampilkan UMKM terdekat (Radius PostGIS)
        P->>S: Kirim pesan chat ke UMKM (Tanya ketersediaan/jasa)
        S-->>U: Notifikasi chat masuk
        U->>S: Balas chat (Konfirmasi harga/stok)
        S-->>P: Pesan diterima (via Axios polling)
    end

    rect rgb(245, 245, 245)
        Note over P, U: Fase 2: Order & Konfirmasi Pembayaran
        U->>S: Input rincian barang, harga, & ongkir (Klik 'Buat Pesanan')
        S-->>P: Munculkan form tagihan di layar chat
        P->>S: Pilih Metode Pembayaran (COD / Link Non-Tunai) & Konfirmasi
        S-->>U: Status order berubah menjadi 'Tunggu Driver'
        S-->>A: Order baru muncul di Dashboard Admin Bangjek
    end

    rect rgb(255, 245, 238)
        Note over A, D: Fase 3: Penugasan Driver
        A->>S: Pilih order & Assign ke Driver spesifik
        S-->>D: Notifikasi tugas baru di HP Driver (PWA)
        D->>S: Buka order, cek titik lokasi & metode pembayaran
    end

    rect rgb(240, 255, 240)
        Note over P, D: Fase 4: Eksekusi Lapangan & Selesai
        Note over U, D: Driver tiba di lokasi UMKM (Penjemputan)

        alt COD Talangan
            D-->>U: (Fisik) Driver bayar lunas harga barang pakai uang tunai pribadi
        else Non-Tunai
            U-->>D: (Fisik) UMKM serahkan uang ongkir ke Driver (karena sudah dibayar via link)
        end

        Note over P, D: Driver tiba di lokasi Pengguna (Pengantaran)

        alt COD Talangan
            P-->>D: (Fisik) Pengguna bayar tunai (Total Barang + Ongkir) ke Driver
        else Non-Tunai
            P-->>D: (Fisik) Pengguna menerima pesanan (Sudah lunas)
        end

        D->>S: Upload Foto Bukti Pengiriman & Ubah status 'Selesai'
        S-->>P: Kirim notifikasi pesanan selesai
        S-->>U: Status order ditutup (Selesai) di riwayat
    end
```

---

## 6. Desain Basis Data (ERD)

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email
        string password
        string role "ENUM: superadmin, umkm, ojek_admin, driver, pengguna"
        string phone
        timestamp created_at
        timestamp updated_at
    }

    UMKM_PROFILES {
        bigint id PK
        bigint user_id FK
        string store_name
        text description
        string address
        geometry coordinates "Tipe Point (PostGIS)"
        string item_dimension "ENUM: ringan, sedang, besar"
        boolean is_open
    }

    CONVERSATIONS {
        bigint id PK
        bigint pengguna_id FK "Milik users.id (Role: Pengguna)"
        bigint umkm_id FK "Milik users.id (Role: UMKM)"
        timestamp created_at
    }

    MESSAGES {
        bigint id PK
        bigint conversation_id FK
        bigint sender_id FK "Milik users.id"
        text content
        timestamp created_at
    }

    ORDERS {
        bigint id PK
        bigint pengguna_id FK "Pembeli"
        bigint umkm_id FK "Penjual"
        bigint driver_id FK "Nullable (Berisi saat di-assign)"
        decimal total_items_price "Total harga barang"
        decimal shipping_fee "Harga ongkir Bangjek"
        string payment_method "ENUM: cod_talangan, non_tunai"
        string order_status "ENUM: tunggu_konfirm, cari_driver, dijemput, diantar, selesai"
        string proof_of_delivery_url "Nullable (Foto bukti dari driver)"
        timestamp created_at
        timestamp updated_at
    }

    ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        string item_name
        integer quantity
        decimal price
    }

    USERS ||--o| UMKM_PROFILES : "memiliki profil (jika role UMKM)"
    USERS ||--o{ CONVERSATIONS : "memulai (sebagai pengguna)"
    USERS ||--o{ CONVERSATIONS : "menerima (sebagai UMKM)"
    CONVERSATIONS ||--o{ MESSAGES : "berisi pesan"
    USERS ||--o{ ORDERS : "membeli (sebagai pengguna)"
    USERS ||--o{ ORDERS : "menjual (sebagai UMKM)"
    USERS ||--o{ ORDERS : "mengantar (sebagai driver)"
    ORDERS ||--o{ ORDER_ITEMS : "memiliki rincian barang"
```

### Catatan Skema
- Tabel `USERS` bersifat polymorphic melalui kolom `role` — satu tabel menampung 5 peran berbeda, disederhanakan lewat foreign key `pengguna_id`, `umkm_id`, `driver_id` di `ORDERS` yang semuanya merujuk ke `users.id`.
- `UMKM_PROFILES.coordinates` bertipe `geometry (Point)` agar bisa dipakai query spasial PostGIS (`ST_DWithin`, `ST_Distance`) untuk fitur "UMKM terdekat".
- `ORDERS.order_status` merepresentasikan state machine: `tunggu_konfirm → cari_driver → dijemput → diantar → selesai`.

---

## 7. User Story (Ringkasan per Aktor)

```mermaid
mindmap
  root((User Story
  SuaraLokal))
    Pengguna
      [Cari UMKM via koordinat]
      [Diskusi & Nego via Chat]
      [Konfirmasi pesanan lunas/COD]
    UMKM
      [Atur lokasi spasial toko]
      [Tentukan dimensi barang]
      [Buat struk/tagihan di chat]
    Admin Bangjek
      [Pantau order terkonfirmasi]
      [Lempar order ke driver]
    Driver
      [Akses navigasi lokasi]
      [Tutup order pakai foto]
```

| Aktor | Sebagai... | Saya ingin... | Agar... |
|---|---|---|---|
| Pengguna | pembeli | mencari UMKM terdekat berdasarkan lokasi saya | tidak perlu mencari manual jarak jauh |
| Pengguna | pembeli | negosiasi harga/pesanan lewat chat | dapat kesepakatan sesuai kebutuhan sebelum bayar |
| UMKM | penjual | mengatur lokasi & dimensi barang toko saya | driver bisa menemukan & menangani barang dengan tepat |
| UMKM | penjual | membuat tagihan pesanan langsung dari chat | proses order lebih cepat tanpa aplikasi terpisah |
| Admin Bangjek | operator logistik | melihat semua order yang masuk dalam satu dashboard | bisa menugaskan driver secara efisien |
| Driver | kurir | melihat titik jemput & antar dengan jelas | pengiriman tidak salah alamat |
| Driver | kurir | mengunggah foto bukti pengiriman | ada bukti transaksi selesai bagi semua pihak |

---

## 8. Ringkasan Tumpukan Teknologi (Tech Stack)

| Layer | Teknologi | Alasan |
|---|---|---|
| Frontend | Blade + Alpine.js + Tailwind CSS | Ringan, cocok untuk monolith Laravel tanpa build SPA terpisah |
| PWA | vite-plugin-pwa | Install ke home screen + offline caching aset |
| Backend | Laravel 13 | Ekosistem matang, cepat untuk MVP |
| Database | PostgreSQL + PostGIS | Query geospasial native untuk pencarian UMKM terdekat |
| Queue | Laravel Queue (background job) | Memisahkan proses berat (kompresi foto, kirim notifikasi) dari request utama |
| Peta | OpenStreetMap + Leaflet | Gratis, tanpa biaya API map komersial |
| Notifikasi | Firebase Cloud Messaging (FCM) | Push notification lintas platform untuk PWA |

## 9. Potensi Pengembangan Lanjutan
- **Real-time chat**: Migrasi dari AJAX polling ke WebSocket (Laravel Reverb) untuk mengurangi latensi & beban server.
- **Rekonsiliasi dana driver**: Modul settlement untuk COD Talangan agar dana yang ditalangi driver bisa direkonsiliasi otomatis dengan Bangjek.
- **Rating & review**: Tambahan tabel `REVIEWS` untuk membangun kepercayaan antar UMKM-Pengguna.
- **Multi-driver assignment logic**: Algoritma otomatis (bukan manual admin) berdasarkan jarak & ketersediaan driver.
- **Mobile App Wrapper**: Menggunakan **PWABuilder** (Trusted Web Activity) untuk konversi instan ke `.apk` Play Store, atau **Capacitor** jika di masa depan driver membutuhkan akses hardware native tingkat lanjut (seperti background geolocation yang konstan).