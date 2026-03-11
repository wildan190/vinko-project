# Vinko Product Management System

Sistem manajemen ekspor produk berbasis Laravel 12 dengan fitur penggabungan gambar (Image Merging) otomatis dan antarmuka Drag & Drop yang modern.

## 🛠 Prasyarat Server (Server Requirements)

Pastikan server Anda memenuhi spesifikasi berikut sebelum melakukan instalasi:

*   **PHP 8.2+**
*   **Ekstensi PHP Wajib**:
    *   `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pcre`, `tokenizer`, `xml`.
    *   `gd` atau `imagick` (Disarankan **GD** untuk performa standar, atau **Imagick** untuk kualitas pemrosesan gambar yang lebih tinggi).
*   **Composer** (PHP dependency manager).
*   **Node.js 18+** & **npm** (untuk kompilasi aset frontend).
*   **Database**: MySQL 8.0+ atau MariaDB 10.4+.
*   **Redis**: Wajib diinstal dan dijalankan (untuk sistem antrean/Queue & Laravel Horizon).

## 🚀 Langkah Instalasi (Installation Steps)

### 1. Persiapan File
```bash
git clone <repository-url>
cd vinko-project
composer install
npm install
```

### 2. Konfigurasi Environment
```bash
cp .env.example .env
php artisan key:generate
```
Edit file `.env` dan sesuaikan bagian berikut:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vinko_db
DB_USERNAME=root
DB_PASSWORD=

# Wajib untuk Horizon & Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

### 3. Database & Storage
```bash
# Buat database terlebih dahulu di MySQL
php artisan migrate --seed
php artisan storage:link
```
*Note: Seeder akan membuat akun admin default (User: `admin`, Pass: `admin`).*

### 4. Kompilasi Aset
```bash
npm run build
```

## ⚙️ Menjalankan di Server Produksi

Aplikasi ini menggunakan sistem antrean (Queue) untuk proses yang berat (Import Excel & Merge Gambar). **Horizon** harus selalu berjalan:

```bash
# Menjalankan Horizon (Queue Worker)
php artisan horizon
```

Untuk server produksi, sangat disarankan menggunakan **Supervisor** untuk menjaga agar `php artisan horizon` tetap berjalan di latar belakang.

## 📁 Fitur Utama
*   **Import Excel**: Mendukung ribuan baris data dengan sistem Batch Processing.
*   **Bulk Image Upload**: Mencocokkan gambar secara otomatis berdasarkan SKU/No Pesanan.
*   **Image Merging**: Menggabungkan gambar produk dengan QR Code/Informasi tambahan secara massal.
*   **Dashboard Horizon**: Pantau status antrean dan pekerjaan yang gagal secara real-time.

## 🐛 Troubleshooting
*   **Gagal Merge/Upload**: Pastikan ekstensi `gd` sudah aktif di PHP.
*   **Progress Bar Berhenti**: Pastikan Redis sudah berjalan dan perintah `php artisan horizon` sedang aktif.
*   **Error Permission**: Beri izin akses pada folder storage: `chmod -R 775 storage bootstrap/cache`.
