# 🔧 CARA MEMPERBAIKI ERROR ORDER.PHP

## Error yang Terjadi:
```
Gagal menyimpan pesanan: Unknown column 'alamat' in 'field list'
```

## Penyebab:
Database belum memiliki kolom `alamat` di tabel `orders`.

## ✅ SOLUSI TERCEPAT (Pilih salah satu):

### Opsi 1: Menggunakan Script Migration Otomatis (RECOMMENDED)
1. Buka browser (Chrome/Firefox)
2. Ketik di address bar: `http://localhost/cobalagi/migrate_database.php`
3. Tekan Enter
4. Script akan otomatis memperbaiki database
5. Klik tombol "Coba Pesan Sekarang" untuk test

### Opsi 2: Manual via phpMyAdmin
1. Buka browser dan akses: `http://localhost/phpmyadmin`
2. Login (biasanya username: `root`, password: kosong)
3. Klik database `esteller_db` di sidebar kiri
4. Klik tab **SQL** di atas
5. Copy-paste query ini:
```sql
ALTER TABLE `orders` ADD COLUMN `alamat` TEXT NULL AFTER `whatsapp`;
ALTER TABLE `orders` ADD COLUMN `metode_bayar` VARCHAR(20) DEFAULT 'cod' AFTER `alamat`;
```
6. Klik tombol **Go** atau **Kirim**
7. Jika muncul pesan sukses, database sudah siap!

### Opsi 3: Via XAMPP/MySQL Command Line
1. Buka XAMPP Control Panel
2. Klik tombol **Shell** (terminal)
3. Ketik: `mysql -u root esteller_db`
4. Copy-paste query dari Opsi 2
5. Ketik `exit` untuk keluar

## 🧪 Testing
Setelah menjalankan salah satu opsi di atas:
1. Buka: `http://localhost/cobalagi/index.php`
2. Tambah beberapa produk ke keranjang
3. Klik "Lanjut ke Pembayaran"
4. Isi form dan submit
5. Jika berhasil, akan redirect ke halaman sukses dengan confetti 🎉

## ❓ Troubleshooting
Jika masih error:
- Pastikan XAMPP Apache dan MySQL sudah running
- Cek nama database di `config.php` apakah `esteller_db`
- Refresh halaman pemesanan setelah migration

---
**Dibuat oleh Antigravity AI Assistant**
