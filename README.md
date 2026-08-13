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
