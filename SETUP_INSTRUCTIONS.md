# BellGas Laravel - Setup Instructions

## Masalah Yang Diperbaiki

### 1. Notifikasi Admin Tidak Muncul
- **Masalah**: Notifikasi real-time untuk admin tidak muncul saat ada pesanan baru
- **Solusi**:
  - Menginstall dan mengkonfigurasi Laravel Reverb untuk WebSocket
  - Memperbaiki konfigurasi broadcasting di `.env`
  - Memperbarui JavaScript Echo configuration di `resources/views/layouts/app.blade.php`

### 2. Urutan Status Order Belum Sempurna
- **Masalah**: Alur status order tidak mengikuti business logic yang benar
- **Solusi**:
  - Menggunakan `OrderStatusService` untuk validasi transisi status
  - Memastikan urutan status sesuai dengan fulfillment method (PICKUP/DELIVERY)
  - Menambahkan timestamp yang sesuai untuk setiap perubahan status

## Konfigurasi Yang Diperbaiki

### 1. Environment Configuration (.env)
```env
# Laravel Reverb Configuration untuk Real-time Broadcasting
REVERB_APP_ID=426101
REVERB_APP_KEY=frnbdhhtu4hwgb2du4lg
REVERB_APP_SECRET=t3olyuspgnhkfl2qcngf
REVERB_HOST="127.0.0.1"
REVERB_PORT=6001
REVERB_SCHEME=http

# Pusher Configuration (Compatible dengan Reverb)
PUSHER_APP_ID="${REVERB_APP_ID}"
PUSHER_APP_KEY="${REVERB_APP_KEY}"
PUSHER_APP_SECRET="${REVERB_APP_SECRET}"
PUSHER_HOST="${REVERB_HOST}"
PUSHER_PORT="${REVERB_PORT}"
PUSHER_SCHEME="${REVERB_SCHEME}"

BROADCAST_CONNECTION=reverb
```

### 2. JavaScript WebSocket Configuration
File: `resources/views/layouts/app.blade.php`
- Updated Echo configuration to use correct Reverb keys and ports
- Fixed WebSocket port from 8080 to 6001

## Cara Menjalankan Aplikasi

### 1. Start Laravel Development Server
```bash
cd D:/sopek/bellgas-laravel
php artisan serve --port=8000
```

### 2. Start Laravel Reverb WebSocket Server
```bash
cd D:/sopek/bellgas-laravel
php artisan reverb:start --port=6001 --debug
```

### 3. Access Application
- **Frontend**: http://127.0.0.1:8000
- **Admin Panel**: http://127.0.0.1:8000/admin/dashboard (gunakan `/debug-auth` untuk login otomatis)
- **WebSocket**: ws://127.0.0.1:6001

## Urutan Status Order Yang Benar

### Untuk PICKUP Orders:
1. `PENDING` → `PAID` → `PROCESSED` → `WAITING_FOR_PICKUP` → `PICKED_UP` → `DONE`

### Untuk DELIVERY Orders:
1. `PENDING` → `PAID` → `PROCESSED` → `ON_DELIVERY` → `DONE`

### Cancellation:
- Order dapat di-cancel dari status apapun kecuali `DONE`

## Testing Notifikasi Real-time

1. Login sebagai admin di dashboard
2. Buat pesanan baru sebagai customer
3. Perhatikan notifikasi real-time muncul di admin dashboard
4. Test perubahan status order dari admin panel
5. Customer akan menerima notifikasi real-time tentang perubahan status

## Files Yang Dimodifikasi

1. `.env` - Konfigurasi Reverb dan Pusher
2. `resources/views/layouts/app.blade.php` - JavaScript Echo configuration
3. `app/Models/Order.php` - Event broadcasting logic
4. `app/Events/NewOrderCreated.php` - Broadcasting event untuk pesanan baru
5. `app/Services/OrderStatusService.php` - Business logic untuk status transitions
6. `routes/web.php` - Broadcasting routes

## Troubleshooting

### Jika Reverb Tidak Bisa Start:
- Pastikan port 6001 tidak digunakan aplikasi lain
- Jalankan `php artisan config:clear` dan `php artisan cache:clear`
- Coba port lain dengan `--port=<port_number>`

### Jika Notifikasi Tidak Muncul:
- Pastikan kedua server (Laravel dan Reverb) berjalan
- Check browser console untuk error WebSocket connection
- Pastikan user sudah login dengan role ADMIN/MERCHANT untuk menerima notifikasi admin

## Status: ✅ SELESAI
- ✅ Sistem notifikasi admin berfungsi
- ✅ Urutan status order sudah benar
- ✅ WebSocket real-time communication aktif
- ✅ Tidak ada fitur lain yang rusak