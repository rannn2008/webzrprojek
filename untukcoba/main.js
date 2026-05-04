// Data Menu Es Teller
const menuItems = [
    {
        id: 1,
        nama: "Es Teller Original",
        deskripsi: "Kelapa muda, alpukat, nata de coco, kolang-kaling, sirup merah, es serut",
        harga: 10000,
        gambar: "estelleroriginal.jpg",
        kategori: "original"
    },
    {
        id: 2,
        nama: "Es Teller Premium Ice Cream",
        deskripsi: "Es teller spesial dengan tambahan es krim premium vanilla",
        harga: 25000,
        gambar: "estellerpremium.jpg",
        kategori: "premium icecream"
    },
    {
        id: 3,
        nama: "Sop Buah Premium Kelapa Muda Ice Cream",
        deskripsi: "Kombinasi lengkap buah-buahan segar, kelapa muda, es krim vanilla",
        harga: 20000,
        gambar: "test.jpg",
        kategori: "premium icecream"
    },
    {
        id: 4,
        nama: "Es Rumput Laut Premium Ice Cream",
        deskripsi: "Rumput laut asli dengan buah segar, kelapa muda, es krim vanilla",
        harga: 20000,
        gambar: "esrumputlautpremium.jpg",
        kategori: "premium icecream"
    },
    {
        id: 5,
        nama: "Es Campur Spesial",
        deskripsi: "Buah-buahan segar, cincau, kolang-kaling, kelapa muda, alpukat",
        harga: 15000,
        gambar: "escampurpremium.jpg",
        kategori: "original"
    },
    {
        id: 6,
        nama: "Es Teller Premium",
        deskripsi: "Dengan daging pokat asli yang lembut dan wangi",
        harga: 28000,
        gambar: "estellerpremium.jpg",
        kategori: "premium"
    }
];

// State Pesanan Baru (Multi-item)
let pesanan = {
    items: [], // Array of {menu, quantity}
    nama: "",
    whatsapp: "",
    alamat: "",
    tanggal: "",
    waktu: "12:00",
    metode: "Ambil di Tempat",
    catatan: ""
};

// Preloader
window.addEventListener('load', function () {
    const preloader = document.getElementById('preloader');
    setTimeout(() => {
        preloader.style.opacity = '0';
        setTimeout(() => {
            preloader.style.display = 'none';

            // Inisialisasi setelah preloader selesai
            initMenuItems();
            initFormListeners();
            setMinDate();
            initFAQ();
            initMenuFilter();
            initModalMenu();
        }, 500);
    }, 1500);
});

// Mobile menu toggle
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const navMenu = document.getElementById('navMenu');

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function () {
        navMenu.classList.toggle('active');

        // Change icon
        const icon = mobileMenuBtn.querySelector('i');
        if (navMenu.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();

        const targetId = this.getAttribute('href');
        if (targetId === '#') return;

        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            // Close mobile menu if open
            if (navMenu && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                if (mobileMenuBtn) {
                    const icon = mobileMenuBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            }

            const headerHeight = document.querySelector('header').offsetHeight;
            window.scrollTo({
                top: targetElement.offsetTop - headerHeight,
                behavior: 'smooth'
            });
        }
    });
});

// Header background on scroll
window.addEventListener('scroll', function () {
    const header = document.querySelector('header');
    if (header) {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }
});

// Fungsi untuk menginisialisasi menu items
function initMenuItems() {
    const menuContainer = document.querySelector('.menu-items');
    if (!menuContainer) return;

    menuItems.forEach((item, index) => {
        const menuItem = document.createElement('div');
        menuItem.className = 'menu-item glass-card';
        menuItem.dataset.category = item.kategori;
        menuItem.setAttribute('data-aos', 'fade-up');
        menuItem.setAttribute('data-aos-delay', (index * 100).toString());
        menuItem.innerHTML = `
            <div class="menu-img">
                <img src="${item.gambar}" alt="${item.nama}" onerror="this.src='https://images.unsplash.com/photo-1628991837865-05023f5d4e6a?ixlib=rb-4.0.3&auto=format&fit=crop&w=687&q=80'">
            </div>
            <div class="menu-details">
                <h3>${item.nama}</h3>
                <p>${item.deskripsi}</p>
                <div class="menu-rating">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="rating-text">(4.7/5)</span>
                </div>
                <div class="price">Rp ${item.harga.toLocaleString('id-ID')} <span class="price-tag">${item.kategori === 'premium' ? 'Premium' : 'Terlaris'}</span></div>
            </div>
        `;
        menuContainer.appendChild(menuItem);
    });
}

// Fungsi untuk menginisialisasi modal pilih menu
function initModalMenu() {
    const modalGrid = document.getElementById('menuPilihanGrid');
    if (!modalGrid) return;

    menuItems.forEach(item => {
        const menuItem = document.createElement('div');
        menuItem.className = 'menu-pilihan-item';
        menuItem.dataset.id = item.id;
        menuItem.innerHTML = `
            <h4>${item.nama}</h4>
            <p>${item.deskripsi}</p>
            <div class="harga">Rp ${item.harga.toLocaleString('id-ID')}</div>
            <button class="btn-pilih-menu" data-id="${item.id}">Pilih Menu</button>
        `;

        modalGrid.appendChild(menuItem);
    });

    document.querySelectorAll('.btn-pilih-menu').forEach(btn => {
        btn.addEventListener('click', function () {
            const menuId = parseInt(this.dataset.id);
            const selectedMenu = menuItems.find(item => item.id === menuId);

            const existingItem = pesanan.items.find(item => item.menu.id === menuId);

            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                pesanan.items.push({
                    menu: selectedMenu,
                    quantity: 1
                });
            }

            updateKeranjangPesanan();
            const modal = document.getElementById('modalPilihMenu');
            if (modal) modal.classList.remove('active');
        });
    });
}

// Fungsi untuk update tampilan keranjang pesanan
function updateKeranjangPesanan() {
    const keranjang = document.getElementById('keranjang-pesanan');
    const pesanKosong = document.getElementById('pesan-kosong');
    if (!keranjang) return;

    keranjang.innerHTML = '';

    if (pesanan.items.length === 0) {
        if (pesanKosong) pesanKosong.style.display = 'block';
        return;
    }

    if (pesanKosong) pesanKosong.style.display = 'none';

    pesanan.items.forEach((item, index) => {
        const itemRow = document.createElement('div');
        itemRow.className = 'item-pesanan-row';
        itemRow.innerHTML = `
            <div class="item-info-row">
                <h4>${item.menu.nama}</h4>
                <p>${item.menu.deskripsi}</p>
                <div class="item-harga-row">Rp ${item.menu.harga.toLocaleString('id-ID')}</div>
            </div>
            <div class="item-controls">
                <button class="qty-btn-small" data-index="${index}" data-action="decrease">-</button>
                <input type="number" class="qty-input-small" data-index="${index}" value="${item.quantity}" min="1" max="20" readonly>
                <button class="qty-btn-small" data-index="${index}" data-action="increase">+</button>
                <button class="item-remove" data-index="${index}">×</button>
            </div>
        `;

        keranjang.appendChild(itemRow);
    });

    document.querySelectorAll('.qty-btn-small').forEach(btn => {
        btn.addEventListener('click', function () {
            const index = parseInt(this.dataset.index);
            const action = this.dataset.action;

            if (action === 'increase') {
                if (pesanan.items[index].quantity < 20) {
                    pesanan.items[index].quantity += 1;
                }
            } else if (action === 'decrease') {
                if (pesanan.items[index].quantity > 1) {
                    pesanan.items[index].quantity -= 1;
                }
            }

            updateKeranjangPesanan();
            updateRincianPesanan();
        });
    });

    document.querySelectorAll('.item-remove').forEach(btn => {
        btn.addEventListener('click', function () {
            const index = parseInt(this.dataset.index);
            pesanan.items.splice(index, 1);
            updateKeranjangPesanan();
            updateRincianPesanan();
        });
    });

    updateRincianPesanan();
}

// Fungsi untuk menginisialisasi form listeners
function initFormListeners() {
    const btnTambahMenu = document.getElementById('btnTambahMenu');
    if (btnTambahMenu) {
        btnTambahMenu.addEventListener('click', function () {
            const modal = document.getElementById('modalPilihMenu');
            if (modal) modal.classList.add('active');
        });
    }

    const btnCloseModal = document.getElementById('btnCloseModal');
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function () {
            const modal = document.getElementById('modalPilihMenu');
            if (modal) modal.classList.remove('active');
        });
    }

    const modalPilihMenu = document.getElementById('modalPilihMenu');
    if (modalPilihMenu) {
        modalPilihMenu.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    }

    const fields = ['nama', 'whatsapp', 'alamat', 'catatan'];
    fields.forEach(field => {
        const el = document.getElementById(field);
        if (el) {
            el.addEventListener('input', function (e) {
                pesanan[field] = e.target.value;
            });
        }
    });

    const tanggal = document.getElementById('tanggal');
    if (tanggal) {
        tanggal.addEventListener('change', function (e) {
            pesanan.tanggal = e.target.value;
            updateRincianPesanan();
        });
    }

    const waktu = document.getElementById('waktu');
    if (waktu) {
        waktu.addEventListener('change', function (e) {
            pesanan.waktu = e.target.value;
            updateRincianPesanan();
        });
    }

    document.querySelectorAll('input[name="metode"]').forEach(radio => {
        radio.addEventListener('change', function (e) {
            pesanan.metode = e.target.value;
            updateRincianPesanan();

            const alamatField = document.getElementById('alamat');
            if (alamatField) {
                if (e.target.value === 'Delivery (Diantar)') {
                    alamatField.parentElement.style.borderLeft = '4px solid var(--orange-tua)';
                    alamatField.parentElement.style.paddingLeft = '15px';
                    alamatField.required = true;
                } else {
                    alamatField.parentElement.style.borderLeft = 'none';
                    alamatField.parentElement.style.paddingLeft = '0';
                    alamatField.required = false;
                }
            }
        });
    });

    const btnPesanWA = document.getElementById('btnPesanWA');
    if (btnPesanWA) {
        btnPesanWA.addEventListener('click', function () {
            if (validateForm()) {
                showModalKonfirmasi();
            }
        });
    }

    const btnEditPesanan = document.getElementById('btnEditPesanan');
    if (btnEditPesanan) {
        btnEditPesanan.addEventListener('click', function () {
            const modal = document.getElementById('modalKonfirmasi');
            if (modal) modal.classList.remove('active');
        });
    }

    const btnKirimWA = document.getElementById('btnKirimWA');
    if (btnKirimWA) {
        btnKirimWA.addEventListener('click', function () {
            sendToWhatsApp();
        });
    }
}

// Fungsi untuk mengatur tanggal minimum (hari ini)
function setMinDate() {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const formatted = tomorrow.toISOString().split('T')[0];
    const el = document.getElementById('tanggal');
    if (el) {
        el.min = formatted;
        el.value = formatted;
        pesanan.tanggal = formatted;
        updateRincianPesanan();
    }
}

// Fungsi untuk update rincian pesanan
function updateRincianPesanan() {
    let totalHarga = 0;
    let totalPorsi = 0;
    let menuList = [];

    pesanan.items.forEach(item => {
        totalHarga += item.menu.harga * item.quantity;
        totalPorsi += item.quantity;
        menuList.push(`${item.menu.nama} (${item.quantity}x)`);
    });

    const detailMenu = document.getElementById('detail-menu');
    if (detailMenu) detailMenu.textContent = menuList.length > 0 ? menuList.join(', ') : "Belum dipilih";

    const detailJumlah = document.getElementById('detail-jumlah-item');
    if (detailJumlah) detailJumlah.textContent = pesanan.items.length;

    const detailPorsi = document.getElementById('detail-total-porsi');
    if (detailPorsi) detailPorsi.textContent = totalPorsi;

    const detailTanggal = document.getElementById('detail-tanggal');
    if (detailTanggal) detailTanggal.textContent = pesanan.tanggal ? formatDate(pesanan.tanggal) : "-";

    const detailWaktu = document.getElementById('detail-waktu');
    if (detailWaktu) detailWaktu.textContent = pesanan.waktu || "-";

    const detailMetode = document.getElementById('detail-metode');
    if (detailMetode) detailMetode.textContent = pesanan.metode;

    const totalHargaEl = document.getElementById('total-harga');
    if (totalHargaEl) totalHargaEl.textContent = `Rp ${totalHarga.toLocaleString('id-ID')}`;
}

// Fungsi untuk validasi form
function validateForm() {
    const nama = document.getElementById('nama') ? document.getElementById('nama').value.trim() : '';
    const whatsapp = document.getElementById('whatsapp') ? document.getElementById('whatsapp').value.trim() : '';
    const tanggal = document.getElementById('tanggal') ? document.getElementById('tanggal').value : '';
    const waktu = document.getElementById('waktu') ? document.getElementById('waktu').value : '';

    if (!nama) {
        showToast("Silakan isi nama lengkap Anda", "error");
        if (document.getElementById('nama')) document.getElementById('nama').focus();
        return false;
    }

    if (!whatsapp) {
        showToast("Silakan isi nomor WhatsApp Anda", "error");
        if (document.getElementById('whatsapp')) document.getElementById('whatsapp').focus();
        return false;
    }

    const whatsappRegex = /^[0-9]{10,14}$/;
    if (!whatsappRegex.test(whatsapp.replace(/\D/g, ''))) {
        showToast("Silakan masukkan nomor WhatsApp yang valid (10-14 digit)", "error");
        if (document.getElementById('whatsapp')) document.getElementById('whatsapp').focus();
        return false;
    }

    if (pesanan.items.length === 0) {
        showToast("Silakan pilih minimal satu menu es teller", "error");
        if (document.getElementById('btnTambahMenu')) document.getElementById('btnTambahMenu').focus();
        return false;
    }

    if (!tanggal) {
        showToast("Silakan pilih tanggal pengambilan/pengantaran", "error");
        if (document.getElementById('tanggal')) document.getElementById('tanggal').focus();
        return false;
    }

    if (!waktu) {
        showToast("Silakan pilih waktu pengambilan/pengantaran", "error");
        if (document.getElementById('waktu')) document.getElementById('waktu').focus();
        return false;
    }

    if (pesanan.metode === "Delivery (Diantar)" && !pesanan.alamat.trim()) {
        showToast("Silakan isi alamat lengkap untuk pengantaran", "error");
        if (document.getElementById('alamat')) document.getElementById('alamat').focus();
        return false;
    }

    return true;
}

// Fungsi untuk menampilkan modal konfirmasi
function showModalKonfirmasi() {
    const modal = document.getElementById('modalKonfirmasi');
    const modalDetail = document.getElementById('modal-detail');
    if (!modal || !modalDetail) return;

    const formattedDate = formatDate(pesanan.tanggal);
    let totalHarga = 0;
    let detailItems = '';

    pesanan.items.forEach(item => {
        const itemTotal = item.menu.harga * item.quantity;
        totalHarga += itemTotal;

        detailItems += `
        <div class="modal-detail-item">
            <span class="label">${item.menu.nama} (${item.quantity}x)</span>
            <span class="value">Rp ${itemTotal.toLocaleString('id-ID')}</span>
        </div>
        `;
    });

    modalDetail.innerHTML = `
        <div class="modal-detail-item">
            <span class="label">Nama Pemesan</span>
            <span class="value">${pesanan.nama}</span>
        </div>
        <div class="modal-detail-item">
            <span class="label">No. WhatsApp</span>
            <span class="value">${pesanan.whatsapp}</span>
        </div>
        ${detailItems}
        <div class="modal-detail-item" style="border-top: 2px dashed var(--orange-muda); padding-top: 15px; margin-top: 15px;">
            <span class="label">Total Harga</span>
            <span class="value" style="font-size: 1.3rem; color: var(--orange-tua);">Rp ${totalHarga.toLocaleString('id-ID')}</span>
        </div>
        <div class="modal-detail-item">
            <span class="label">Tanggal</span>
            <span class="value">${formattedDate}</span>
        </div>
        <div class="modal-detail-item">
            <span class="label">Waktu</span>
            <span class="value">${pesanan.waktu} WIB</span>
        </div>
        <div class="modal-detail-item">
            <span class="label">Metode</span>
            <span class="value">${pesanan.metode}</span>
        </div>
        ${pesanan.metode === "Delivery (Diantar)" ? `
        <div class="modal-detail-item">
            <span class="label">Alamat</span>
            <span class="value">${pesanan.alamat}</span>
        </div>
        ` : ''}
        ${pesanan.catatan ? `
        <div class="modal-detail-item">
            <span class="label">Catatan</span>
            <span class="value">${pesanan.catatan}</span>
        </div>
        ` : ''}
    `;

    modal.classList.add('active');
}

// Fungsi untuk mengirim pesanan ke WhatsApp
function sendToWhatsApp() {
    const formattedDate = formatDate(pesanan.tanggal);
    let totalHarga = 0;

    let message = `Halo Pondok Es Teller ZR, saya mau pesan es teller:\n\n`;
    message += `*Nama:* ${pesanan.nama}\n`;
    message += `*WhatsApp:* ${pesanan.whatsapp}\n\n`;
    message += `*Detail Pesanan:*\n`;

    pesanan.items.forEach(item => {
        const itemTotal = item.menu.harga * item.quantity;
        totalHarga += itemTotal;
        message += `- ${item.menu.nama}\n`;
        message += `  Jumlah: ${item.quantity} porsi\n`;
        message += `  Harga: Rp ${item.menu.harga.toLocaleString('id-ID')}/porsi\n`;
        message += `  Subtotal: Rp ${itemTotal.toLocaleString('id-ID')}\n\n`;
    });

    message += `*Total Pesanan:* Rp ${totalHarga.toLocaleString('id-ID')}\n\n`;
    message += `*Pengambilan/Pengantaran:*\n`;
    message += `- Tanggal: ${formattedDate}\n`;
    message += `- Waktu: ${pesanan.waktu} WIB\n`;
    message += `- Metode: ${pesanan.metode}\n`;

    if (pesanan.metode === "Delivery (Diantar)") {
        message += `- Alamat: ${pesanan.alamat}\n`;
    }

    if (pesanan.catatan) {
        message += `\n*Catatan:* ${pesanan.catatan}\n`;
    }

    message += `\nSilakan konfirmasi ketersediaan dan total pembayaran. Terima kasih!`;

    const encodedMessage = encodeURIComponent(message);
    const phoneNumber = "6281374110444";
    const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;

    window.open(whatsappUrl, '_blank');

    const modal = document.getElementById('modalKonfirmasi');
    if (modal) modal.classList.remove('active');
}

// Fungsi untuk format tanggal
function formatDate(dateString) {
    if (!dateString) return "-";
    const date = new Date(dateString);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

// Fungsi untuk inisialisasi FAQ
function initFAQ() {
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const faqItem = question.parentElement;
            const isActive = faqItem.classList.contains('active');

            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });

            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });
}

// Fungsi untuk inisialisasi menu filter
function initMenuFilter() {
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const category = this.dataset.category;
            const menuItemsEl = document.querySelectorAll('.menu-item');

            menuItemsEl.forEach(item => {
                if (category === 'all' || item.dataset.category.includes(category)) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
}

// Promo Timer
function updateTimer() {
    const endDate = new Date('2026-12-31T23:59:59').getTime();
    const now = new Date().getTime();
    const timeLeft = endDate - now;

    const promoTimer = document.querySelector('.promo-timer');
    if (!promoTimer) return;

    if (timeLeft > 0) {
        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

        const daysEl = document.getElementById('days');
        const hoursEl = document.getElementById('hours');
        const minutesEl = document.getElementById('minutes');
        const secondsEl = document.getElementById('seconds');

        if (daysEl) daysEl.textContent = days.toString().padStart(2, '0');
        if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, '0');
        if (minutesEl) minutesEl.textContent = minutes.toString().padStart(2, '0');
        if (secondsEl) secondsEl.textContent = seconds.toString().padStart(2, '0');
    } else {
        promoTimer.innerHTML = '<h3>Promo telah berakhir</h3>';
    }
}

setInterval(updateTimer, 1000);
updateTimer();

// Back to Top Button
const backToTopBtn = document.querySelector('.back-to-top');
if (backToTopBtn) {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopBtn.style.display = 'block';
            setTimeout(() => {
                backToTopBtn.style.opacity = '1';
            }, 10);
        } else {
            backToTopBtn.style.opacity = '0';
            setTimeout(() => {
                backToTopBtn.style.display = 'none';
            }, 300);
        }
    });

    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Toast Notification System
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${icon}"></i></div>
        <div class="toast-message">${message}</div>
    `;

    container.appendChild(toast);

    // Fade in
    setTimeout(() => toast.classList.add('active'), 10);

    // Remove
    setTimeout(() => {
        toast.classList.remove('active');
        setTimeout(() => toast.remove(), 500);
    }, 4000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    updateRincianPesanan();

    // Initialize AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            mirror: false,
            offset: 100
        });
    }
});
