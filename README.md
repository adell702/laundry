# AA Laundry — Sistem Kasir Laundry (Laravel)

Aplikasi laundry berbasis web untuk **Super Clean Laundry** (studi kasus skripsi).  
Stack: **Laravel 13 + Breeze + MySQL/SQLite + Tailwind + Maatwebsite Excel**.

## Fitur

- Multi-role: **Admin/Owner** & **Kasir**
- CRUD pelanggan, layanan, transaksi, pengeluaran, karyawan
- Status pengerjaan: diterima → dicuci → disetrika → selesai → diambil
- Status bayar manual: belum lunas / lunas (tunai, transfer, QRIS)
- Cetak nota + link kirim e-nota via WhatsApp
- Dashboard real-time (pemasukan, pending, progress)
- Laporan periodik + **export Excel (.xlsx)**
- Log aktivitas (anti fraud)
- Halaman publik lacak cucian (`/lacak`)

## Batasan (sesuai skripsi)

- Web only (browser desktop/mobile)
- Internal admin + kasir
- Pembayaran manual (tanpa payment gateway)
- Tracking status di sistem (bukan notifikasi push otomatis)

## Instalasi

```bash
composer install
cp .env.example .env   # jika belum
php artisan key:generate
# set DB di .env (default: sqlite)
php artisan migrate:fresh --seed
npm install && npm run build
php artisan serve
```

Buka: http://127.0.0.1:8000

## Menjalankan dengan Docker Compose

Siapkan **Docker Engine atau Docker Desktop** yang sedang berjalan dan **Docker Compose v2+**. Pada Windows, gunakan Docker Desktop dalam mode Linux containers. Jalankan dari direktori proyek; PHP, Composer, Node.js, dan npm tidak perlu dipasang di host.

**Windows PowerShell 5.1+** (tanpa OpenSSL atau Git Bash):

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\docker\setup.ps1
docker compose --env-file .env.docker up -d --build --wait
```

Opsi `ExecutionPolicy` di atas hanya berlaku pada proses setup tersebut.

**macOS/Linux** (memerlukan OpenSSL):

```bash
./docker/setup.sh
docker compose --env-file .env.docker up -d --build --wait
```

Buka **http://localhost:8080**. Build pertama mengunduh image, memasang dependensi, dan membangun aset frontend sehingga membutuhkan koneksi internet.

Script setup membuat `.env.docker` dengan `APP_KEY` dan password database acak. File ini diabaikan Git; simpan kredensialnya dan jangan dibagikan. Menjalankan setup kembali hanya mengisi kredensial yang kosong atau belum ada, sambil mempertahankan kredensial dan pengaturan yang sudah terisi. Konfigurasi `.env` dan database SQLite lokal tidak diubah. Selalu gunakan `--env-file .env.docker` pada perintah Compose agar konfigurasi Docker yang digunakan.

Jika muncul error `DB_PASSWORD` atau `MYSQL_ROOT_PASSWORD` kosong, jalankan script setup sesuai sistem operasi, lalu gunakan perintah Compose lengkap di atas. `docker compose up -d` saja membaca `.env` bawaan untuk interpolasi; `env_file` pada service tidak memasok nilai untuk `${...}` dalam `compose.yaml`.

Stack berisi:

| Service | Fungsi |
|---------|--------|
| `db` | MySQL 8.4; data tersimpan pada volume `mysql-data` |
| `migrate` | Menjalankan migrasi sebelum aplikasi, queue, dan scheduler dimulai; selesai dengan status `Exited (0)` adalah normal |
| `app` | Laravel pada PHP-FPM 8.4 |
| `web` | Nginx pada port host 8080 secara default |
| `queue` | Worker antrean dengan penyimpanan database |
| `scheduler` | Menjalankan Laravel scheduler |

File aplikasi dalam `storage` memakai volume persisten `app-storage`, yang juga dapat dibaca Nginx untuk file publik. Session dan cache disimpan di MySQL. Database hanya diakses melalui jaringan internal Compose.

### Data awal dan akun demo

Migrasi berjalan otomatis, tetapi **seeder tidak dijalankan otomatis**. Untuk database lokal yang baru dan belum pernah diisi, jalankan satu kali:

```bash
docker compose --env-file .env.docker exec app php artisan db:seed --force
```

Seeder membuat data awal dan akun pada tabel **Akun demo** di bawah. Seeder tidak idempoten: jangan mengulanginya pada database yang sudah berisi data karena dapat menyebabkan data ganda atau error.

### Konfigurasi dan perintah harian

Ubah konfigurasi Docker di `.env.docker`:

- `HTTP_PORT`: port host; jika diubah, sesuaikan juga `APP_URL`, misalnya `HTTP_PORT=8081` dan `APP_URL=http://localhost:8081`.
- `HTTP_BIND_ADDRESS=127.0.0.1`: akses hanya dari komputer lokal. Gunakan alamat bind lain jika akses jaringan memang diperlukan.
- `APP_NAME`: nama aplikasi, termasuk nama yang digunakan saat build aset.
- `MAIL_MAILER=log`: email ditulis ke log, bukan dikirim. Isi konfigurasi SMTP untuk pengiriman email sungguhan.

Setelah mengubah source code atau konfigurasi, terapkan kembali dengan build berikut. Source code disalin ke image sehingga perubahan di host memerlukan rebuild:

```bash
docker compose --env-file .env.docker up -d --build --wait
```

Periksa status, log, dan jalankan perintah Laravel:

```bash
docker compose --env-file .env.docker ps -a
docker compose --env-file .env.docker logs --tail=100 app web db queue scheduler migrate
docker compose --env-file .env.docker logs -f app queue scheduler
docker compose --env-file .env.docker exec app php artisan migrate:status
docker compose --env-file .env.docker exec app sh
```

Password MySQL dalam `.env.docker` digunakan untuk inisialisasi database baru. Mengedit password tersebut saja tidak mengubah password pada database yang sudah tersimpan; lakukan perubahan kredensial pada database juga agar tetap cocok.

### Menghentikan dan menghapus data

Perintah berikut menghentikan serta menghapus container dan jaringan, **tetapi mempertahankan volume data**:

```bash
docker compose --env-file .env.docker down
```

**Destruktif:** perintah berikut juga menghapus seluruh database dan file pada volume aplikasi. Gunakan hanya jika memang ingin mereset data Docker, setelah membuat backup yang diperlukan:

```bash
docker compose --env-file .env.docker down -v
```

### Penggunaan produksi

Konfigurasi bawaan ditujukan untuk penggunaan lokal. Untuk produksi, siapkan domain dan TLS/HTTPS pada reverse proxy, set `APP_URL` ke URL HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, dan `SESSION_SECURE_COOKIE=true`. Gunakan kredensial yang sesuai, pertahankan `APP_KEY`, atur backup database serta storage, dan jangan gunakan akun/password demo; hindari seeder demo atau ubah akun tersebut sebelum aplikasi dapat diakses publik.

## Akun demo

| Role  | Email                   | Password  |
|-------|-------------------------|-----------|
| Admin | admin@superclean.test   | password  |
| Kasir | kasir@superclean.test   | password  |

## Struktur utama

```
app/Models/          Customer, Service, Transaction, Expense, ActivityLog
app/Http/Controllers Dashboard, Transaction, Report, Tracking, ...
app/Exports/         TransactionsExport (Excel)
resources/views/     Blade UI multi-role
```

## MySQL (opsional)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aalaundry
DB_USERNAME=root
DB_PASSWORD=
```

Lalu: `php artisan migrate:fresh --seed`
