# SIPAR

Sistem Informasi Peminjaman Alat RPL SMK Negeri 5 Surakarta. Aplikasi Laravel 12 dengan antarmuka Bootstrap 5, database MySQL, akses berbasis peran, katalog QR, approval peminjaman, pengembalian, dan rekap transaksi.

## Menjalankan aplikasi

1. Pastikan PHP 8.2+ dengan ekstensi `gd`, `zip`, dan `xml` aktif, Composer, Node.js/npm, dan MySQL tersedia. Pada XAMPP, aktifkan `extension=gd` di `php.ini` sebelum menjalankan Composer.
2. Pasang dependency dan siapkan konfigurasi:

   ```powershell
   composer install
   if (!(Test-Path .env)) { Copy-Item .env.example .env }
   php artisan key:generate
   ```

3. Buat database MySQL bernama `sipar`, lalu atur `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` pada `.env`.
4. Atur `ENABLE_QR_SCANNER=true` pada `.env` untuk mengaktifkan kamera QR pada katalog siswa, atau `ENABLE_QR_SCANNER=false` untuk menggunakan pencarian kode alat manual pada katalog. Pada halaman pengembalian admin, pemindaian QR dan pencarian kode alat manual tersedia bersamaan. Setelah mengubah nilai pada deployment yang memakai config cache, jalankan `php artisan config:clear` atau bangun ulang cache konfigurasi.
5. Jalankan migrasi dan data awal:

   ```powershell
   php artisan migrate --seed
   ```

6. Jalankan aplikasi:

   ```powershell
   php artisan serve
   ```

   Buka `http://127.0.0.1:8000`. Aset CSS/JS utama menggunakan Bootstrap CDN. Vite dapat dibangun dengan `npm install` dan `npm run build` bila diperlukan.

## Deploy ke Railway dengan database persisten

SQLite di filesystem container Railway tidak cocok untuk penyimpanan permanen karena filesystem aplikasi dapat diganti saat deploy/restart. Gunakan layanan MySQL Railway:

1. Tambahkan layanan **MySQL** di project Railway yang sama dengan aplikasi.
2. Pada service aplikasi, buka **Variables** dan tambahkan referensi ke service MySQL. Jika nama service databasenya `MySQL`, atur:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   ```

   Ganti `MySQL` pada setiap referensi jika layanan database Anda memakai nama lain. Gunakan private/internal host yang diberikan Railway; jangan gunakan `127.0.0.1` untuk koneksi antar-service.
3. Atur **Pre-deploy Command** pada service aplikasi menjadi `php artisan migrate --force` agar migrasi berjalan ke database Railway setiap deploy. Jangan gunakan `migrate:fresh` pada database berisi data.
4. Deploy ulang, lalu pastikan log deploy menunjukkan migrasi berhasil. Data yang sebelumnya tersimpan di SQLite lokal/container tidak otomatis dipindahkan; impor sekali ke database Railway jika perlu dipertahankan.

Konfigurasi juga mengenali variabel bawaan service MySQL Railway (`MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, atau `MYSQL_URL`) sebagai fallback. Tetap set `DB_CONNECTION=mysql` pada service aplikasi agar aplikasi tidak mencoba memakai SQLite.

## Akun demo

Seeder membuat akun demo berikut. Ganti password sebelum dipakai di lingkungan bersama.

| Peran | NIS/NIP | Email | Password |
| --- | --- | --- | --- |
| Admin / Toolman | `ADMIN` | `admin@sipar.sch.id` | `ADMIN123` |
| Siswa | `20230001` | `siswa@sipar.sch.id` | `password123` |

Siswa juga dapat mendaftar sendiri melalui halaman masuk. Pendaftaran publik hanya dapat membuat akun dengan role `siswa`.

## Modul

- Admin: dashboard, CRUD alat/kategori/akun siswa, QR unik per alat, approval/tolak pengajuan, dan pengembalian melalui pemindaian QR atau pencarian kode alat manual. Kedua metode pencarian tersedia bersamaan. Pemindaian mengenali QR alat terdaftar dan menampilkan status/stok; formulir pengembalian hanya tersedia jika ada sisa transaksi aktif untuk alat tersebut. Modul juga menyediakan laporan.
- Siswa: katalog dan pencarian QR (lookup alat tanpa login, hanya menampilkan data katalog), pengajuan dengan validasi stok (tetap wajib login), status/riwayat, profil, serta perubahan password.
- Stok baru berkurang saat admin menyetujui pengajuan. Stok dikunci dan diperiksa ulang dalam transaksi database saat pengajuan diproses. Jumlah total direkonsiliasi sebagai tersedia, maintenance, rusak, dan sedang dipinjam.
- Pengembalian dicatat per alat dan jumlah, sehingga satu transaksi multi-alat dapat dikembalikan bertahap. Status transaksi menjadi `Selesai` setelah seluruh jumlah item kembali. Kondisi `Baik` mengembalikan unit ke stok tersedia, `Rusak` memindahkan unit ke stok maintenance, dan `Hilang` mengurangi stok total. Denda keterlambatan Rp 5.000 per hari dibagi proporsional menurut jumlah item transaksi, dengan koreksi pembulatan saat item terakhir kembali.
- `Export Excel (.xlsx)` menghasilkan workbook Excel asli melalui Laravel Excel. `Unduh PDF` menghasilkan laporan PDF dari server.
- Pemindaian kamera membutuhkan izin kamera dan secure context browser (HTTPS atau localhost). QR berisi token unik alat, bukan data pribadi.

## Pemeriksaan

```powershell
php artisan test
```

Suite berjalan pada SQLite in-memory dan mencakup migrasi, pembatasan role, serta alur pengajuan sampai pengembalian terlambat.
