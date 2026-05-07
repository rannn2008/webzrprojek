# ✅ Testing Checklist - Pondok Es Teller ZR

## 🎯 Testing Sebelum Go Live

Checklist ini untuk memastikan semua fitur berfungsi dengan baik sebelum dan setelah deployment.

---

## 🌓 Dark Mode / Light Mode

### Desktop
- [ ] Klik tombol theme toggle (kiri bawah)
- [ ] Tema berubah dari light ke dark
- [ ] Semua elemen UI berubah warna dengan benar
- [ ] Klik lagi untuk kembali ke light mode
- [ ] Refresh halaman, tema tersimpan sesuai pilihan terakhir
- [ ] Hover effect pada tombol berfungsi (scale + rotate)

### Mobile
- [ ] Tombol theme toggle terlihat dan accessible
- [ ] Tidak overlap dengan tombol lain
- [ ] Smooth transition saat toggle

### Auto-detect
- [ ] Buka di browser dengan dark mode system preference
- [ ] Website otomatis menggunakan dark mode
- [ ] Setelah manual toggle, preferensi manual lebih prioritas

---

## 🤖 Smart AI Chatbot

### Basic Functionality
- [ ] Klik tombol AI (kanan bawah)
- [ ] Window chatbot muncul
- [ ] Quick reply buttons terlihat
- [ ] Klik quick reply, response muncul
- [ ] Input text field berfungsi
- [ ] Tombol "Kirim" berfungsi
- [ ] Enter key untuk kirim pesan

### Smart Responses
Test dengan pertanyaan berikut:

#### Menu & Harga
- [ ] "Menu apa saja?" → Menampilkan list menu dengan harga
- [ ] "Berapa harga es teller?" → Menampilkan harga menu
- [ ] "Menu termurah?" → Response tentang menu

#### Jam Buka
- [ ] "Jam buka?" → "10.00 - 22.00 WIB"
- [ ] "Kapan tutup?" → Response tentang jam operasional
- [ ] "Buka hari minggu?" → Response tentang jadwal

#### Lokasi
- [ ] "Dimana lokasinya?" → Alamat lengkap + saran buka Google Maps
- [ ] "Alamat?" → Response lokasi
- [ ] "Jauh dari pusat kota?" → Response lokasi

#### Delivery
- [ ] "Bisa delivery?" → Info delivery + estimasi waktu
- [ ] "Ongkir berapa?" → Info ongkir via Gojek/Grab
- [ ] "Berapa lama sampai?" → Estimasi 15-30 menit

#### Pembayaran
- [ ] "Cara bayar?" → Info cash/transfer
- [ ] "Bisa transfer?" → Response pembayaran
- [ ] "Terima kartu kredit?" → Response metode pembayaran

#### Rekomendasi
- [ ] "Menu enak apa?" → Rekomendasi menu rating tinggi
- [ ] "Best seller?" → Response menu favorit
- [ ] "Rekomendasi dong" → Saran menu

#### Promo
- [ ] "Ada promo?" → Info promo via WhatsApp
- [ ] "Diskon?" → Response tentang promo

#### Bahan
- [ ] "Pakai bahan apa?" → Info buah segar, alpukat, gula asli
- [ ] "Alpukatnya asli?" → Response tentang bahan

#### Cara Pesan
- [ ] "Gimana cara pesan?" → Step-by-step cara order
- [ ] "Cara order?" → Response proses pemesanan

#### Greeting & Thanks
- [ ] "Halo" → Greeting response
- [ ] "Terima kasih" → Response ucapan terima kasih

### UI/UX
- [ ] Typing indicator muncul sebelum response
- [ ] Delay response 800-1500ms (terasa natural)
- [ ] Auto-scroll ke pesan terbaru
- [ ] Quick reply buttons hilang setelah diklik
- [ ] Chat history tetap ada saat window ditutup-buka lagi

### Mobile
- [ ] Window chatbot responsive di mobile
- [ ] Input keyboard tidak overlap dengan chat
- [ ] Scroll berfungsi dengan baik

---

## 📅 Delivery Time Picker

### Date Picker
- [ ] Field tanggal muncul di form pemesanan
- [ ] Minimum date = hari ini
- [ ] Maximum date = 7 hari dari sekarang
- [ ] Tidak bisa pilih tanggal yang sudah lewat
- [ ] Default value = hari ini

### Time Picker
- [ ] Dropdown jam muncul (10:00 - 22:00)
- [ ] Semua jam tersedia (13 pilihan)
- [ ] Format: "HH:00 WIB"

### Mobile
- [ ] Native date picker muncul di mobile
- [ ] Native time picker muncul di mobile
- [ ] User-friendly untuk touch input

---

## ⏱️ Estimasi Waktu Delivery

### Normal Case
- [ ] Pilih tanggal besok + jam 15:00
- [ ] Estimasi muncul: "✅ Pesanan untuk besok jam 15:00 WIB..."
- [ ] Background hijau, text hijau tua

### Urgent Case (< 2 jam dari sekarang)
- [ ] Pilih hari ini + jam 2 jam dari sekarang
- [ ] Estimasi muncul: "⚡ Pesanan mendesak!..."
- [ ] Background kuning, text warning

### Past Time Case
- [ ] Pilih hari ini + jam yang sudah lewat
- [ ] Estimasi muncul: "⚠️ Jam yang dipilih sudah lewat..."
- [ ] Background orange/merah, text danger

### Real-time Update
- [ ] Ubah tanggal → estimasi update otomatis
- [ ] Ubah jam → estimasi update otomatis
- [ ] Tidak ada delay/lag

---

## 🗺️ Valid Sitemap.xml

### Accessibility
- [ ] Buka: https://estellerzrpadang.vercel.app/sitemap.xml
- [ ] File XML muncul (tidak 404)
- [ ] Content-Type: application/xml

### Structure
- [ ] XML declaration ada: `<?xml version="1.0" encoding="UTF-8"?>`
- [ ] Namespace correct: `xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"`
- [ ] Semua URL ada:
  - [ ] Homepage (/)
  - [ ] Menu (#menu)
  - [ ] Keunggulan (#keunggulan)
  - [ ] Testimoni (#testimoni)
  - [ ] Lokasi (#lokasi)
  - [ ] Pesan (#pesan)

### Metadata
- [ ] Setiap URL punya `<lastmod>`
- [ ] Setiap URL punya `<changefreq>`
- [ ] Setiap URL punya `<priority>`
- [ ] Priority homepage = 1.0 (tertinggi)

### Validation
- [ ] Validate di: https://www.xml-sitemaps.com/validate-xml-sitemap.html
- [ ] Tidak ada error
- [ ] Submit ke Google Search Console

---

## 📦 Integrasi WhatsApp

### Basic Order
- [ ] Isi form pemesanan lengkap
- [ ] Tambah 2-3 menu ke keranjang
- [ ] Klik "Kirim Pesanan ke WhatsApp"
- [ ] WhatsApp terbuka dengan pesan terformat

### Message Format
Pesan harus berisi:
- [ ] Greeting: "Halo Pondok Es Teller ZR..."
- [ ] List menu dengan harga dan subtotal
- [ ] Total item
- [ ] Total estimasi harga
- [ ] Metode (Ambil Sendiri / Delivery)
- [ ] **Waktu**: Tanggal lengkap + Jam (NEW!)
- [ ] Nama pemesan
- [ ] Alamat (jika delivery)
- [ ] Catatan (jika ada)
- [ ] Closing: "Apakah pesanan saya bisa diproses?"

### Delivery Order
- [ ] Pilih metode "Delivery"
- [ ] Field alamat muncul dan required
- [ ] Isi alamat lengkap
- [ ] Alamat masuk ke pesan WhatsApp

### Pickup Order
- [ ] Pilih metode "Ambil Sendiri"
- [ ] Field alamat hilang
- [ ] Pesan WhatsApp tidak ada alamat

---

## 🎨 UI/UX General

### Desktop
- [ ] Layout rapi di 1920x1080
- [ ] Layout rapi di 1366x768
- [ ] Tidak ada horizontal scroll
- [ ] Semua tombol hover effect berfungsi
- [ ] Smooth scroll ke section

### Tablet
- [ ] Layout rapi di 768px width
- [ ] Grid menu jadi 2 kolom
- [ ] Semua elemen readable

### Mobile
- [ ] Layout rapi di 375px width (iPhone)
- [ ] Layout rapi di 360px width (Android)
- [ ] Grid menu jadi 1 kolom
- [ ] Tombol floating tidak overlap
- [ ] Form input tidak terlalu kecil
- [ ] Text readable tanpa zoom

### Animations
- [ ] Scroll reveal animation berfungsi
- [ ] Skeleton loading muncul saat load menu
- [ ] Smooth transition saat toggle dark mode
- [ ] Floating button hover effect
- [ ] Ken Burns effect di hero background

---

## 🔒 Security & Performance

### Security
- [ ] Tidak ada console error
- [ ] Tidak ada warning di console
- [ ] Input sanitization berfungsi (escapeHtml)
- [ ] Tidak ada XSS vulnerability

### Performance
- [ ] Page load < 3 detik
- [ ] Lighthouse Performance > 90
- [ ] Lighthouse SEO > 95
- [ ] Lighthouse Accessibility > 90
- [ ] Lighthouse Best Practices > 90

### Offline Support
- [ ] Buka website online
- [ ] Matikan internet
- [ ] Refresh halaman
- [ ] Menu masih muncul (dari localStorage)
- [ ] Keranjang masih ada

---

## 📊 Analytics & SEO

### Google Analytics
- [ ] Pageview tercatat
- [ ] Event tracking berfungsi
- [ ] Real-time users terlihat di GA dashboard

### SEO
- [ ] Title tag correct
- [ ] Meta description ada
- [ ] Open Graph tags ada
- [ ] Schema.org JSON-LD ada
- [ ] Canonical URL correct
- [ ] Robots.txt accessible

### Social Media
- [ ] Share link di WhatsApp → preview muncul
- [ ] Share link di Facebook → preview muncul
- [ ] Preview image correct
- [ ] Preview title & description correct

---

## 🗄️ Database (Supabase)

### Menu Sync
- [ ] Menu load dari Supabase
- [ ] Badge "Cloud Sync" muncul
- [ ] Update menu di Supabase → refresh website → menu update

### Reviews
- [ ] Submit review baru
- [ ] Review masuk ke database
- [ ] Top 3 reviews update otomatis
- [ ] Smart algorithm pilih review terbaik (rating 5 + kata positif)

### Fallback
- [ ] Disconnect Supabase (ubah config jadi invalid)
- [ ] Website masih berfungsi dengan data lokal
- [ ] Badge "Data Lokal" muncul

---

## 🐛 Bug Testing

### Edge Cases
- [ ] Submit form tanpa isi → validation error
- [ ] Pilih jam yang sudah lewat → warning muncul
- [ ] Keranjang kosong → pesan "Belum ada menu"
- [ ] Hapus semua item dari keranjang → summary reset
- [ ] Refresh halaman → keranjang tetap ada (localStorage)

### Browser Compatibility
- [ ] Test di Chrome
- [ ] Test di Firefox
- [ ] Test di Safari
- [ ] Test di Edge
- [ ] Test di mobile browser (iOS Safari)
- [ ] Test di mobile browser (Chrome Android)

---

## ✅ Final Checklist

- [ ] Semua fitur baru berfungsi 100%
- [ ] Tidak ada breaking changes
- [ ] Mobile responsive perfect
- [ ] Dark mode smooth
- [ ] AI chatbot smart
- [ ] Delivery time picker accurate
- [ ] Sitemap valid
- [ ] Performance optimal
- [ ] No console errors
- [ ] Ready for production! 🚀

---

## 📝 Notes

Jika ada bug atau issue, catat di sini:

1. **Bug**: [Deskripsi bug]
   - **Steps to reproduce**: [Langkah-langkah]
   - **Expected**: [Yang diharapkan]
   - **Actual**: [Yang terjadi]
   - **Fix**: [Solusi]

---

**Last Updated**: 7 Mei 2026
**Tested By**: [Nama Tester]
**Status**: ✅ All tests passed / ⚠️ Issues found
