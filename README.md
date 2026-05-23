# VANN Market - Website Top Up Game
## Panduan Instalasi di Laragon

---

## Struktur Folder
```
vannmarket/
├── index.php                  ← Halaman utama
├── database.sql               ← Script database (import dulu!)
├── config/
│   ├── db.php                 ← Koneksi database
│   └── helpers.php            ← Fungsi helper
├── components/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
├── assets/
│   ├── css/style.css          ← CSS user
│   └── image/                 ← Gambar game (copy dari folder lama)
├── public/
│   ├── game.php               ← Halaman detail + form top up
│   ├── login.php              ← Login user
│   ├── register.php           ← Daftar user
│   ├── transactions.php       ← Cek transaksi
│   └── api/
│       ├── search.php         ← API live search
│       ├── voucher.php        ← API validasi voucher
│       └── order.php          ← API submit pesanan
├── admin/
│   ├── index.php              ← Router dashboard admin
│   ├── login.php              ← Login admin
│   ├── logout.php
│   ├── assets/admin.css       ← CSS admin
│   ├── includes/sidebar.php
│   └── pages/
│       ├── dashboard.php      ← Statistik & transaksi terbaru
│       ├── games.php          ← CRUD game + upload gambar
│       ├── packages.php       ← CRUD harga diamond per game
│       ├── payments.php       ← CRUD metode pembayaran
│       ├── vouchers.php       ← CRUD voucher diskon
│       ├── transactions.php   ← Kelola & update status transaksi
│       └── users.php          ← CRUD users
└── uploads/games/             ← Folder upload gambar game
```

---

## Langkah Instalasi

### 1. Copy folder ke Laragon
```
C:\laragon\www\vannmarket\
```

### 2. Import database
- Buka **phpMyAdmin** → http://localhost/phpmyadmin
- Buat database baru: `vansstore`
- Import file `database.sql`

### 3. Copy gambar game
Salin semua file dari `assets/image/` lama ke:
```
vannmarket/assets/image/
```

### 4. Buat folder uploads
```
vannmarket/uploads/games/        ← buat manual, pastikan bisa ditulis
```

### 5. Akses website
- **User**: http://localhost/vannmarket/
- **Admin**: http://localhost/vannmarket/admin/login.php

---

## Login Admin Default
| Username | Password |
|----------|----------|
| admin    | password |

> ⚠️ **Segera ganti password** setelah login pertama melalui phpMyAdmin:
> ```sql
> UPDATE admins SET password='$2y$10$...' WHERE username='admin';
> ```
> Atau gunakan fungsi `password_hash('password_baru', PASSWORD_DEFAULT)` di PHP.

---

## Fitur Admin Dashboard

| Halaman | Fitur |
|---------|-------|
| **Dashboard** | Statistik total game, revenue, transaksi, pending |
| **Kelola Game** | Tambah/edit/hapus game, upload gambar, set populer/featured |
| **Harga Diamond** | Atur paket diamond per game dengan harga, bonus, kategori |
| **Metode Bayar** | Kelola metode pembayaran (e-wallet, bank, minimarket) |
| **Voucher** | Buat kode diskon, set masa berlaku, pilih game tertentu |
| **Transaksi** | Lihat semua transaksi, update status (sukses/gagal/refund) |
| **Users** | Tambah/edit/hapus/nonaktifkan akun user |

---

## Konfigurasi Database
Edit file `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // default Laragon kosong
define('DB_NAME', 'vansstore');
```
