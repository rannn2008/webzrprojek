# Setup Supabase untuk Pondok Es Teller ZR

Ini dipakai kalau web sudah upload ke Vercel dan admin harus bisa update menu untuk semua pengunjung.

## 1. Buat Project Supabase

1. Buka Supabase.
2. Buat project baru.
3. Ambil `Project URL` dan `anon public key` dari menu API settings.

## 2. Buat Tabel Menu

1. Buka SQL Editor di Supabase.
2. Jalankan isi file `supabase-setup.sql`.

## 3. Buat Storage Foto

1. Buka Storage.
2. Buat bucket bernama `menu-images`.
3. Jadikan bucket public supaya foto menu bisa tampil di website.

## 4. Buat Akun Admin

1. Buka Authentication.
2. Tambah user admin dengan email dan password.
3. Login di `admin.html` pakai akun itu.

## 5. Isi Konfigurasi

Edit `supabase-config.js`:

```js
window.ZR_SUPABASE = {
    url: "https://xxxxx.supabase.co",
    anonKey: "anon-key-kamu",
    imageBucket: "menu-images"
};
```

Kalau konfigurasi masih placeholder, web tetap jalan memakai `localStorage`.
