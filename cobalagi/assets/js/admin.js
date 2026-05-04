// assets/js/admin.js - Admin Dashboard Logic

// Colors from Coffee Theme
const coffeeColors = {
    primary: '#8b5a2b',
    primaryLight: '#c19a6b',
    secondary: '#d2a679',
    dark: '#3e2723',
    success: '#E8F5E9',
    warning: '#FFF3E0',
    danger: '#FFEBEE',
    info: '#E3F2FD'
};

const PRODUCT_PREVIEW_PLACEHOLDER = 'https://via.placeholder.com/600x400?text=Preview+Produk';

// Global state for chat
let currentActiveCustomer = null;
let currentActiveOrder = null;

// Global state for notifications
const AUTO_ACCEPT_STORAGE_KEY = 'admin_auto_accept_minutes';
const ORDER_SOUND_STORAGE_KEY = 'admin_order_sound_enabled';
const AUTO_ACCEPT_MIN = 1;
const AUTO_ACCEPT_MAX = 30;
const ORDER_ALERT_SOUND_INTERVAL = 4500;
const ORDER_ALERT_AUDIO_SRC = '../assets/sounds/pesanan-masuk-harap-diterima.mp3';

let pendingOrders = [];
let knownPendingOrderIds = new Set();
let acceptingOrderIds = new Set();
let orderAlertAudioContext = null;
let orderAlertAudio = null;
let orderAlertSoundTimer = null;
let orderCountdownInterval = null;
let orderAlertSoundEnabled = true;

// Initialization
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.initCharts === 'function') {
        window.initCharts(coffeeColors);
    }
    
    initProductModalEvents();
    initOrderNotifications();
    updateAdminGlobalBadge();

    // Set polling intervals
    setInterval(() => {
        updateAdminGlobalBadge();
        pollPendingOrders();
        const chatPage = document.getElementById('chats');
        if (chatPage && chatPage.classList.contains('active')) {
            loadAdminChatList();
            if (currentActiveCustomer && currentActiveOrder) {
                loadAdminChatHistory();
            }
        }
    }, 2500);
});

// Navigation Logic
function showPage(pageId, element) {
    document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
    const targetPage = document.getElementById(pageId);
    if(targetPage) targetPage.classList.add('active');

    document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
    if (element) element.classList.add('active');

    // Update Header
    const titles = {
        'dashboard': ['Overview', 'Ringkasan aktivitas hari ini'],
        'orders': ['Manajemen Pesanan', 'Kelola semua pesanan masuk'],
        'products': ['Katalog Produk', 'Atur menu dan harga'],
        'reviews': ['Ulasan Pelanggan', 'Masukan dan rating dari pelanggan'],
        'inventory': ['Stok & Inventori', 'Pantau ketersediaan bahan baku'],
        'chats': ['Pesan Pelanggan', 'Live chat dengan pelanggan']
    };

    if (titles[pageId]) {
        const titleEl = document.getElementById('pageTitle');
        const subEl = document.getElementById('pageSub');
        if (titleEl) titleEl.innerText = titles[pageId][0];
        if (subEl) subEl.innerText = titles[pageId][1];
    }

    // Show/Hide FAB
    const fab = document.getElementById('fabMain');
    if (fab) {
        if (pageId === 'products') {
            fab.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
        } else {
            fab.style.display = 'none';
        }
    }

    // Handle specific page behaviors
    if (pageId === 'chats') {
        loadAdminChatList();
    }
}

// Ajax Order Update
function updateOrder(id, action, options = {}) {
    const settings = Object.assign({
        skipConfirm: false,
        reloadOnSuccess: true,
        confirmText: 'Anda yakin ingin mengubah status pesanan ini?',
        onSuccess: null,
        onError: null
    }, options || {});

    if (!settings.skipConfirm && !confirm(settings.confirmText)) {
        return Promise.resolve(false);
    }

    const formData = new FormData();
    formData.append('action', action);
    formData.append('order_id', id);

    return fetch('admin.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                if (typeof settings.onError === 'function') {
                    settings.onError(data);
                } else {
                    alert(data.message || 'Gagal mengubah status pesanan.');
                }
                return false;
            }

            if (typeof settings.onSuccess === 'function') {
                settings.onSuccess(data);
            }

            if (settings.reloadOnSuccess) {
                location.reload();
            }

            return true;
        })
        .catch(() => {
            if (typeof settings.onError === 'function') {
                settings.onError({ message: 'Koneksi gagal. Coba lagi.' });
            } else {
                alert('Koneksi gagal. Coba lagi.');
            }
            return false;
        });
}

// Show Detail Modal
function showDetail(id) {
    const modal = document.getElementById('orderDetailModal');
    const content = document.getElementById('orderDetailContent');
    if (!modal || !content) return;

    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    fetch(`get_orders_detail.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }

            const o = data.order;
            const items = data.items || [];

            let itemsHtml = '<table style="width:100%; border-collapse:collapse; margin-top:10px;">';
            itemsHtml += '<tr style="background:#f8f9fa; text-align:left;"><th style="padding:10px;">Menu</th><th style="padding:10px;">Qty</th><th style="padding:10px;">Harga</th></tr>';

            items.forEach(item => {
                itemsHtml += `
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;">${item.nama_product}</td>
                        <td style="padding:10px;">${item.quantity}x</td>
                        <td style="padding:10px;">Rp ${parseInt(item.harga).toLocaleString('id-ID')}</td>
                    </tr>
                `;
            });
            itemsHtml += '</table>';

            content.innerHTML = `
                <div style="display:flex; gap:20px; align-items:center; margin-bottom:25px; background: rgba(139, 90, 43, 0.05); padding: 15px; border-radius: 15px;">
                    <div style="width:65px; height:65px; border-radius:50%; background:white; overflow:hidden; border: 3px solid var(--primary); flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                        ${o.foto_profil ? `<img src="../assets/images/profiles/${o.foto_profil}" style="width:100%; height:100%; object-fit:cover;">` : `<i class="fas fa-user" style="font-size:1.8rem; color:var(--primary-light);"></i>`}
                    </div>
                    <div style="flex:1;">
                        <small style="color:var(--gray); display:block; margin-bottom:2px;">Customer</small>
                        <div style="font-weight:700; font-size:1.2rem; color:var(--dark);">${o.nama_customer}</div>
                        <div style="font-size:0.9rem; color:var(--primary-dark); cursor:pointer;" onclick="window.open('https://wa.me/${o.whatsapp}', '_blank')">
                            <i class="fab fa-whatsapp"></i> +${o.whatsapp}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <small style="color:var(--gray); display:block; margin-bottom:2px;">Order ID</small>
                        <div style="font-weight:700; font-size:1.1rem;">#${o.order_code || o.id}</div> 
                        <div style="font-size:0.8rem; color:var(--gray);">${o.created_at}</div>
                    </div>
                </div>
                
                <div style="background:#fdfbf7; padding:20px; border-radius:15px; margin-bottom:25px; border: 1px solid rgba(139, 90, 43, 0.1);">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <i class="fas fa-map-marker-alt" style="color:var(--primary);"></i>
                        <small style="color:var(--gray); font-weight:600; text-transform:uppercase; letter-spacing:1px;">Alamat Pengiriman</small>
                    </div>
                    <p style="font-weight:500; color:var(--dark); line-height:1.6; padding-left:25px;">${o.alamat || o.alamat_pengiriman || '-'}</p>
                    <div style="margin-top:15px; display:flex; gap:15px; flex-wrap:wrap; padding-left:25px;">
                        <div style="background:rgba(139,90,43,0.08); padding:6px 14px; border-radius:20px; font-size:0.8rem; font-weight:600; color:var(--primary-dark);">
                            <i class="fas fa-truck"></i> ${o.metode_pengiriman === 'delivery' ? 'Delivery' : 'Jemput Sendiri'}
                        </div>
                        <div style="background:rgba(139,90,43,0.08); padding:6px 14px; border-radius:20px; font-size:0.8rem; font-weight:600; color:var(--primary-dark);">
                            <i class="fas fa-wallet"></i> ${(o.metode_bayar || 'cod').toUpperCase()}
                        </div>
                    </div>
                    ${o.catatan ? `<div style="margin-top:15px; border-top:1px dashed rgba(139, 90, 43, 0.2); padding-top:10px; padding-left:25px;"><small style="color:var(--gray);">Catatan Pelanggan:</small><br><i style="color:var(--text-muted);">"${o.catatan}"</i></div>` : ''}
                    ${o.alasan_batal ? `<div style="margin-top:15px; padding:10px; padding-left:25px; background:#FFEBEE; border-radius:8px;"><small style="color:#C62828; font-weight:bold;">Alasan Penolakan (Admin):</small><br><i style="color:#C62828;">"${o.alasan_batal}"</i></div>` : ''}
                </div>
                
                <h4 style="margin-bottom:10px;">Rincian Pesanan</h4>
                ${itemsHtml}
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:15px; border-top:2px solid #eee;">
                    <span style="font-weight:600; font-size:1.1rem;">Total Pembayaran</span>
                    <span style="font-weight:800; font-size:1.4rem; color:var(--primary-dark);">Rp ${parseInt(o.total_harga).toLocaleString('id-ID')}</span>
                </div>

                ${o.receipt_code ? `
                <div style="margin-top:16px; background:#E3F2FD; border:1px solid #BBDEFB; border-radius:12px; padding:14px;">
                    <div style="font-size:0.78rem; color:#1565C0; text-transform:uppercase; font-weight:700; margin-bottom:6px;">Struk Online</div>
                    <div style="font-weight:800; color:#0D47A1; margin-bottom:6px;"><i class="fas fa-file-invoice"></i> ${o.receipt_code}</div>
                    <div style="font-size:0.82rem; color:#0D47A1;">Dibuat: ${o.receipt_generated_at || '-'} | Oleh: ${(o.receipt_generated_by || 'system').toUpperCase()}</div>
                    ${o.pickup_confirmed_at ? `<div style="font-size:0.82rem; color:#0D47A1; margin-top:4px;">Pickup dikonfirmasi customer: ${o.pickup_confirmed_at}</div>` : ''}
                </div>
                ` : ''}

                <div style="margin-top:20px; display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                     ${!o.status || o.status === 'new' || o.status === 'baru' ? `
                        <button class="btn btn-primary" onclick="updateOrder(${o.id}, 'accept')">Terima Pesanan</button>
                        <button class="btn btn-danger" onclick="openRejectModal(${o.id})">Tolak Pesanan</button>
                     ` : ''}
                     ${o.status === 'process' ? `
                        <button class="btn btn-primary btn-full" onclick="updateOrder(${o.id}, 'preparing')">Mulai Diracik</button>
                     ` : ''}
                     ${o.status === 'preparing' ? `
                        <button class="btn btn-primary btn-full" onclick="updateOrder(${o.id}, 'ready')">Siap Diambil/Kirim</button>
                     ` : ''}
                     ${o.status === 'ready' ? `
                        ${o.metode_pengiriman === 'delivery'
                                 ? `<button class="btn btn-primary btn-full" onclick="updateOrder(${o.id}, 'done')">Selesaikan Pesanan</button>`
                                 : `<div class="btn btn-warning btn-full" style="cursor:default;">Menunggu konfirmasi pickup customer</div>`}
                     ` : ''}
                </div>
            `;
        });
}

// Modal Reject Logic
function openRejectModal(id) {
    const orderInput = document.getElementById('rejectOrderId');
    const select = document.getElementById('rejectReasonSelect');
    const textarea = document.getElementById('rejectReason');
    const detailModal = document.getElementById('orderDetailModal');
    const rejectModal = document.getElementById('rejectModal');

    if (orderInput) orderInput.value = id;
    if (select) select.value = '';
    if (textarea) {
        textarea.value = '';
        textarea.style.display = 'none';
    }
    if (detailModal) detailModal.style.display = 'none';
    if (rejectModal) rejectModal.style.display = 'flex';
}

function submitReject() {
    const id = document.getElementById('rejectOrderId').value;
    const selVal = document.getElementById('rejectReasonSelect').value;
    let reason = document.getElementById('rejectReason').value.trim();

    if (selVal !== 'custom' && selVal !== '') {
        reason = selVal;
    }

    if (!reason) {
        alert("Harap pilih atau isi alasan penolakan!");
        return;
    }

    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('order_id', id);
    formData.append('alasan', reason);

    fetch('admin.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const modal = document.getElementById('rejectModal');
                if(modal) modal.style.display = 'none';
                location.reload();
            }
        });
}

function setProductPreview(src) {
    const preview = document.getElementById('productImagePreview');
    if (!preview) return;
    preview.src = src || PRODUCT_PREVIEW_PLACEHOLDER;
    preview.onerror = function () {
        preview.src = PRODUCT_PREVIEW_PLACEHOLDER;
    };
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'none';
}

function openProductModal(prod = null) {
    const modal = document.getElementById('productModal');
    const title = document.getElementById('productModalTitle');
    const form = document.getElementById('productForm');
    if (!modal || !title || !form) return;

    form.reset();
    document.getElementById('productId').value = '';
    document.getElementById('productGambarExisting').value = '';
    document.getElementById('productTersedia').checked = true;
    setProductPreview(PRODUCT_PREVIEW_PLACEHOLDER);

    if (prod && typeof prod === 'object') {
        title.innerText = 'Edit Produk';
        document.getElementById('productId').value = prod.id || '';
        document.getElementById('productNama').value = prod.nama || '';
        document.getElementById('productHarga').value = prod.harga || '';
        document.getElementById('productKategori').value = prod.kategori || '';
        document.getElementById('productDeskripsi').value = prod.deskripsi || '';
        document.getElementById('productTersedia').checked = parseInt(prod.tersedia, 10) === 1;
        document.getElementById('productGambarExisting').value = prod.gambar || '';
        if (prod.gambar) {
            setProductPreview(`../assets/images/products/${encodeURIComponent(prod.gambar)}`);
        }
    } else {
        title.innerText = 'Tambah Produk';
    }

    modal.style.display = 'flex';
}

function editProduct(prod) {
    openProductModal(prod);
}

function submitProductForm(event) {
    event.preventDefault();

    const form = document.getElementById('productForm');
    const submitBtn = document.getElementById('productSubmitBtn');
    if (!form || !submitBtn) return;

    const nama = document.getElementById('productNama').value.trim();
    const kategori = document.getElementById('productKategori').value.trim();
    const harga = parseInt(document.getElementById('productHarga').value || '0', 10);
    if (!nama || !kategori || Number.isNaN(harga) || harga <= 0) {
        alert('Nama, kategori, dan harga produk wajib valid.');
        return;
    }

    const formData = new FormData(form);
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    fetch('save_product.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeProductModal();
                alert(data.message || 'Produk berhasil disimpan.');
                location.reload();
            } else {
                alert(data.message || 'Gagal menyimpan produk.');
            }
        })
        .catch(() => {
            alert('Koneksi gagal saat menyimpan produk.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Produk';
        });
}

function initProductModalEvents() {
    const input = document.getElementById('productGambar');
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeProductModal();
            }
        });
    }

    if (!input) return;

    input.addEventListener('change', function () {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) {
            const existing = document.getElementById('productGambarExisting').value;
            if (existing) {
                setProductPreview(`../assets/images/products/${encodeURIComponent(existing)}`);
            } else {
                setProductPreview(PRODUCT_PREVIEW_PLACEHOLDER);
            }
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran gambar maksimal 2MB.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (ev) {
            setProductPreview(ev.target.result);
        };
        reader.readAsDataURL(file);
    });
}

function deleteProduct(id) {
    if (confirm('Yakin ingin menghapus produk ini?')) {
        window.location.href = 'delete_product.php?id=' + id;
    }
}

// INCOMING ORDER NOTIFICATION LOGIC
function clampNumber(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function parseMysqlDate(value) {
    if (!value) return null;
    const text = String(value).trim();
    if (!text) return null;

    const quick = new Date(text.replace(' ', 'T'));
    if (!Number.isNaN(quick.getTime())) return quick;

    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/);
    if (!match) return null;

    return new Date(
        parseInt(match[1], 10),
        parseInt(match[2], 10) - 1,
        parseInt(match[3], 10),
        parseInt(match[4], 10),
        parseInt(match[5], 10),
        parseInt(match[6], 10)
    );
}

function formatDuration(totalSeconds) {
    const safe = Math.max(0, parseInt(totalSeconds, 10) || 0);
    const minutes = Math.floor(safe / 60);
    const seconds = safe % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function getSavedAutoAcceptMinutes() {
    const fallback = 3;
    try {
        const raw = localStorage.getItem(AUTO_ACCEPT_STORAGE_KEY);
        const parsed = parseInt(raw || '', 10);
        if (Number.isNaN(parsed)) return fallback;
        return clampNumber(parsed, AUTO_ACCEPT_MIN, AUTO_ACCEPT_MAX);
    } catch (e) {
        return fallback;
    }
}

function saveAutoAcceptMinutes() {
    const input = document.getElementById('autoAcceptMinutesInput');
    if (!input) return;
    let value = parseInt(input.value || '', 10);
    if (Number.isNaN(value)) value = 3;
    value = clampNumber(value, AUTO_ACCEPT_MIN, AUTO_ACCEPT_MAX);
    input.value = String(value);

    try {
        localStorage.setItem(AUTO_ACCEPT_STORAGE_KEY, String(value));
    } catch (e) { }

    renderOrderNotificationWindow();
    syncOrderCountdownChips();
}

function loadSavedAutoAcceptMinutes() {
    const input = document.getElementById('autoAcceptMinutesInput');
    if (!input) return;
    input.value = String(getSavedAutoAcceptMinutes());
}

function setOrderSoundPreference(enabled) {
    orderAlertSoundEnabled = !!enabled;
    const button = document.getElementById('toggleOrderSoundBtn');
    if (button) {
        button.innerText = orderAlertSoundEnabled ? 'Suara ON' : 'Suara OFF';
        button.classList.toggle('off', !orderAlertSoundEnabled);
    }

    try {
        localStorage.setItem(ORDER_SOUND_STORAGE_KEY, orderAlertSoundEnabled ? '1' : '0');
    } catch (e) { }

    if (!orderAlertSoundEnabled) {
        stopOrderAlertSoundLoop();
    } else if (pendingOrders.length > 0) {
        startOrderAlertSoundLoop();
    }
}

function toggleOrderAlertSound() {
    const nextState = !orderAlertSoundEnabled;
    if (nextState) unlockOrderAlertAudio();
    setOrderSoundPreference(nextState);
}

function loadOrderSoundPreference() {
    let enabled = true;
    try {
        const raw = localStorage.getItem(ORDER_SOUND_STORAGE_KEY);
        if (raw === '0') enabled = false;
    } catch (e) {
        enabled = true;
    }
    setOrderSoundPreference(enabled);
}

function initOrderAlertAudioContext() {
    if (orderAlertAudioContext) return orderAlertAudioContext;
    const AudioClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioClass) return null;
    orderAlertAudioContext = new AudioClass();
    return orderAlertAudioContext;
}

function initOrderAlertAudioElement() {
    if (orderAlertAudio) return orderAlertAudio;
    try {
        orderAlertAudio = new Audio(ORDER_ALERT_AUDIO_SRC);
        orderAlertAudio.preload = 'auto';
        orderAlertAudio.loop = true;
        orderAlertAudio.volume = 1;
        orderAlertAudio.setAttribute('playsinline', '');
    } catch (e) {
        orderAlertAudio = null;
    }
    return orderAlertAudio;
}

function unlockOrderAlertAudio() {
    const ctx = initOrderAlertAudioContext();
    if (ctx && ctx.state === 'suspended') ctx.resume().catch(() => { });

    const audio = initOrderAlertAudioElement();
    if (audio) {
        const previousMuted = audio.muted;
        audio.muted = true;
        const primePromise = audio.play();
        if (primePromise && typeof primePromise.then === 'function') {
            primePromise.then(() => {
                audio.pause();
                audio.currentTime = 0;
                audio.muted = previousMuted;
            }).catch(() => { audio.muted = previousMuted; });
        } else {
            audio.muted = previousMuted;
        }
    }

    window.removeEventListener('pointerdown', unlockOrderAlertAudio);
    window.removeEventListener('keydown', unlockOrderAlertAudio);
}

function playOrderAlertTone() {
    const ctx = initOrderAlertAudioContext();
    if (!ctx) return;
    if (ctx.state === 'suspended') ctx.resume().catch(() => { });

    const start = ctx.currentTime + 0.02;
    const sequence = [
        { freq: 659.25, offset: 0, duration: 0.16, type: 'triangle' },
        { freq: 783.99, offset: 0.16, duration: 0.17, type: 'triangle' },
        { freq: 987.77, offset: 0.33, duration: 0.22, type: 'sine' }
    ];

    sequence.forEach(note => {
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        const noteStart = start + note.offset;
        const noteEnd = noteStart + note.duration;
        oscillator.type = note.type;
        oscillator.frequency.setValueAtTime(note.freq, noteStart);
        gainNode.gain.setValueAtTime(0.0001, noteStart);
        gainNode.gain.exponentialRampToValueAtTime(0.12, noteStart + 0.02);
        gainNode.gain.exponentialRampToValueAtTime(0.0001, noteEnd);
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        oscillator.start(noteStart);
        oscillator.stop(noteEnd + 0.03);
    });
}

function playOrderAlertMp3() {
    const audio = initOrderAlertAudioElement();
    if (!audio) return false;
    const playPromise = audio.play();
    if (playPromise && typeof playPromise.then === 'function') {
        playPromise.catch(err => {
            console.warn('Gagal memutar MP3 notifikasi:', err);
            playOrderAlertTone();
        });
    }
    return true;
}

function playOrderAlertSound() {
    if (!orderAlertSoundEnabled || pendingOrders.length === 0) return;
    if (!playOrderAlertMp3()) playOrderAlertTone();
}

function startOrderAlertSoundLoop() {
    if (!orderAlertSoundEnabled || pendingOrders.length === 0) return;
    playOrderAlertSound();
    if (orderAlertSoundTimer) return;
    orderAlertSoundTimer = setInterval(() => {
        if (!orderAlertSoundEnabled || pendingOrders.length === 0) return;
        if (orderAlertAudio && orderAlertAudio.paused) playOrderAlertSound();
    }, ORDER_ALERT_SOUND_INTERVAL);
}

function stopOrderAlertSoundLoop() {
    if (orderAlertSoundTimer) {
        clearInterval(orderAlertSoundTimer);
        orderAlertSoundTimer = null;
    }
    if (orderAlertAudio) {
        orderAlertAudio.pause();
        orderAlertAudio.currentTime = 0;
    }
}

function getOrderWaitSeconds(order) {
    const serverWait = parseInt(order.waiting_seconds, 10);
    if (!Number.isNaN(serverWait) && serverWait >= 0) return serverWait;
    const createdAt = parseMysqlDate(order.created_at);
    if (!createdAt) return 0;
    return Math.max(0, Math.floor((Date.now() - createdAt.getTime()) / 1000));
}

function refreshNewOrderCounters(count) {
    const badge = document.getElementById('newOrdersBadge');
    if (badge) {
        if (count > 0) {
            badge.innerText = count > 99 ? '99+' : String(count);
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
    const stat = document.getElementById('newOrdersStat');
    if (stat) stat.innerText = String(count);
}

function renderOrderNotificationWindow() {
    const windowEl = document.getElementById('orderNotifWindow');
    const countEl = document.getElementById('orderNotifCount');
    const listEl = document.getElementById('orderNotifList');
    if (!windowEl || !countEl || !listEl) return;

    const count = pendingOrders.length;
    countEl.innerText = count > 99 ? '99+' : String(count);

    if (count === 0) {
        windowEl.style.display = 'none';
        listEl.innerHTML = '';
        stopOrderAlertSoundLoop();
        return;
    }

    const autoAcceptSeconds = getSavedAutoAcceptMinutes() * 60;
    windowEl.style.display = 'flex';

    listEl.innerHTML = pendingOrders.map(order => {
        const waitSeconds = getOrderWaitSeconds(order);
        const remaining = Math.max(0, autoAcceptSeconds - waitSeconds);
        const urgentClass = remaining <= 60 ? 'urgent' : '';
        const total = parseInt(order.total_harga, 10) || 0;
        const orderCode = order.order_code || `#${order.id}`;
        const customerName = order.nama_customer || 'Pelanggan';
        const waitLabel = formatDuration(waitSeconds);
        const remainingLabel = formatDuration(remaining);

        return `
            <div class="order-notif-item ${urgentClass}">
                <h4>${escapeHtml(orderCode)} - ${escapeHtml(customerName)}</h4>
                <p>Total: Rp ${total.toLocaleString('id-ID')} | Masuk: ${escapeHtml(order.created_at || '-')}</p>
                <div class="order-notif-meta">
                    <span>Menunggu ${waitLabel}</span>
                    <span>Auto-terima ${remainingLabel}</span>
                </div>
                <div class="order-notif-actions">
                    <button class="btn btn-primary" onclick="acceptOrderFromNotif(${parseInt(order.id, 10)})">Terima Sekarang</button>
                    <button class="btn btn-info" onclick="showDetail(${parseInt(order.id, 10)})" style="background:var(--card-bg); color:var(--dark); border:1px solid rgba(139,90,43,0.2);">Lihat Detail</button>
                    <button class="btn btn-danger" onclick="openRejectModalFromNotif(${parseInt(order.id, 10)})" style="padding: 10px 15px;"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
    }).join('');

    if (orderAlertSoundEnabled) startOrderAlertSoundLoop();
}

function acceptOrderFromNotif(orderId, isAuto = false) {
    const id = parseInt(orderId, 10);
    if (!id || acceptingOrderIds.has(id)) return;
    acceptingOrderIds.add(id);
    updateOrder(id, 'accept', {
        skipConfirm: true,
        reloadOnSuccess: true,
        onError: () => { acceptingOrderIds.delete(id); if (!isAuto) alert('Gagal menerima pesanan. Coba lagi.'); }
    }).then(success => { if (!success) acceptingOrderIds.delete(id); });
}

function openRejectModalFromNotif(orderId) {
    const id = parseInt(orderId, 10);
    if (!id) return;
    const win = document.getElementById('orderNotifWindow');
    if(win) win.style.display = 'none';
    stopOrderAlertSoundLoop();
    openRejectModal(id);
}

function syncOrderCountdownChips() {
    const autoAcceptSeconds = getSavedAutoAcceptMinutes() * 60;
    document.querySelectorAll('.order-countdown-chip').forEach(chip => {
        const createdAt = chip.getAttribute('data-countdown-created-at');
        const createdDate = parseMysqlDate(createdAt);
        if (!createdDate) return;
        const waitSeconds = Math.max(0, Math.floor((Date.now() - createdDate.getTime()) / 1000));
        const remaining = Math.max(0, autoAcceptSeconds - waitSeconds);
        const valueEl = chip.querySelector('[data-countdown-value]');
        if (valueEl) valueEl.innerText = formatDuration(remaining);
        if (remaining <= 60) {
            chip.style.background = '#ffebee'; chip.style.borderColor = '#ffcdd2'; chip.style.color = '#c62828';
        } else {
            chip.style.background = '#fff8e1'; chip.style.borderColor = '#ffe0b2'; chip.style.color = '#8d6e63';
        }
    });
}

function handleAutoAcceptOrders() {
    if (!pendingOrders.length) return;
    const autoAcceptSeconds = getSavedAutoAcceptMinutes() * 60;
    pendingOrders.forEach(order => {
        const id = parseInt(order.id, 10);
        if (!id || acceptingOrderIds.has(id)) return;
        if (getOrderWaitSeconds(order) < autoAcceptSeconds) return;
        acceptOrderFromNotif(id, true);
    });
}

function pollPendingOrders() {
    fetch(`get_pending_orders.php?limit=30&_=${Date.now()}`, { cache: 'no-store' })
        .then(res => res.json())
        .then(data => {
            if (!data || !data.success) return;
            const latestOrders = Array.isArray(data.orders) ? data.orders : [];
            const latestIds = new Set(latestOrders.map(o => String(o.id)));
            let hasNewlyArrived = false;
            latestIds.forEach(id => { if (!knownPendingOrderIds.has(id)) hasNewlyArrived = true; });
            knownPendingOrderIds = latestIds;
            pendingOrders = latestOrders;
            refreshNewOrderCounters(pendingOrders.length);
            renderOrderNotificationWindow();
            syncOrderCountdownChips();
            handleAutoAcceptOrders();
            if (hasNewlyArrived && orderAlertSoundEnabled) playOrderAlertSound();
        }).catch(err => console.error('Failed to poll pending orders:', err));
}

function initOrderNotifications() {
    loadSavedAutoAcceptMinutes();
    loadOrderSoundPreference();
    syncOrderCountdownChips();
    pollPendingOrders();
    if (orderCountdownInterval) clearInterval(orderCountdownInterval);
    orderCountdownInterval = setInterval(syncOrderCountdownChips, 1000);
    initOrderAlertAudioElement();
    window.addEventListener('pointerdown', unlockOrderAlertAudio, { passive: true });
    window.addEventListener('keydown', unlockOrderAlertAudio);
}

// Chat Logic
function escapeHtml(text) {
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function loadAdminChatList() {
    fetch('../api/get_chat_list.php')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('adminChatList');
            if (!list) return;
            if (!data.success) {
                list.innerHTML = `<div style="text-align:center; padding:30px; color:#c62828;">${data.error || 'Gagal memuat daftar chat'}</div>`;
                return;
            }
            if (!data.customers || data.customers.length === 0) {
                list.innerHTML = `<div style="text-align:center; padding:30px; color:var(--gray);">Belum ada pesan masuk.</div>`;
                return;
            }
            let html = '';
            data.customers.forEach(c => {
                const isActive = currentActiveOrder == c.order_id ? 'active' : '';
                const badge = parseInt(c.unread_count) > 0 ? `<div class="cli-badge" style="margin-left:auto;">${c.unread_count}</div>` : '';
                const customerName = c.nama_customer || 'Customer';
                html += `
                    <div class="chat-list-item ${isActive}" data-order-id="${c.order_id}" data-customer-id="${c.customer_id}" data-customer-name="${encodeURIComponent(customerName)}" data-order-code="${encodeURIComponent(c.order_code || '-')}" data-order-status="${encodeURIComponent(c.order_status_label || c.order_status || '-')}" onclick="openAdminChatFromElement(this)">
                        <div class="cli-avatar">${customerName.charAt(0).toUpperCase()}</div>
                        <div class="cli-info" style="display:flex; flex-direction:column; flex:1;">
                            <div class="cli-name" style="display:flex; justify-content:space-between; align-items:center;">
                                <span>${escapeHtml(customerName)}</span>
                                <span class="cli-time">${c.last_time || ''}</span>
                            </div>
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <span class="cli-last">#${escapeHtml(c.order_code || '-')} - ${escapeHtml(c.last_message || '...')}</span>
                                ${badge}
                            </div>
                        </div>
                    </div>
                `;
            });
            list.innerHTML = html;
        }).catch(err => console.error('Error loading chat list:', err));
}

function openAdminChatFromElement(el) {
    if (!el || !el.dataset) return;
    openAdminChat(parseInt(el.dataset.orderId), parseInt(el.dataset.customerId), decodeURIComponent(el.dataset.customerName), decodeURIComponent(el.dataset.orderCode), decodeURIComponent(el.dataset.orderStatus));
}

function openAdminChat(orderId, customerId, customerName, orderCode, orderStatus) {
    currentActiveOrder = orderId;
    currentActiveCustomer = customerId;
    const idInput = document.getElementById('activeCustomerId');
    const orderInput = document.getElementById('activeOrderId');
    if (idInput) idInput.value = customerId;
    if (orderInput) orderInput.value = orderId;

    const header = document.getElementById('activeChatHeader');
    const inputArea = document.getElementById('adminChatInputArea');
    if (header) header.style.display = 'flex';
    if (inputArea) inputArea.style.display = 'flex';

    const nameEl = document.getElementById('activeChatName');
    const avatarEl = document.getElementById('activeChatAvatar');
    const orderInfoEl = document.getElementById('activeChatOrderInfo');
    if (nameEl) nameEl.innerText = customerName;
    if (avatarEl) avatarEl.innerText = (customerName || 'C').charAt(0).toUpperCase();
    if (orderInfoEl) orderInfoEl.innerText = `Order #${orderCode} (${orderStatus})`;

    loadAdminChatHistory();
    loadAdminChatList();
}

function loadAdminChatHistory() {
    if (!currentActiveCustomer || !currentActiveOrder) return;
    fetch(`../api/get_chats.php?customer_id=${currentActiveCustomer}&order_id=${currentActiveOrder}&is_admin=1`)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('adminChatHistory');
            if (!container) return;
            if (!data.success) {
                container.innerHTML = `<div style="text-align:center; padding:20px; color:#c62828;">${data.error || 'Gagal memuat percakapan.'}</div>`;
                return;
            }
            const input = document.getElementById('adminChatInput');
            const btn = document.querySelector('#adminChatInputArea button');
            const canSend = data.order ? !!data.order.can_send : true;
            if (input && btn) {
                input.disabled = !canSend; btn.disabled = !canSend;
                input.placeholder = canSend ? 'Ketik pesan...' : 'Chat ditutup karena pesanan selesai';
            }
            if (data.chats && data.chats.length > 0) {
                const isBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 100;
                container.innerHTML = data.chats.map(c => `
                    <div class="msg ${c.sender_type === 'admin' ? 'msg-admin' : 'msg-customer'}">
                        ${c.message}
                        <span class="msg-time">${c.created_at}</span>
                    </div>
                `).join('');
                if (isBottom) container.scrollTop = container.scrollHeight;
            } else {
                container.innerHTML = `<div style="text-align:center; padding:20px; color:var(--gray);">Belum ada percakapan.</div>`;
            }
        }).catch(err => console.error('Error fetching history:', err));
}

function sendAdminChatMessage() {
    const cid = document.getElementById('activeCustomerId').value;
    const oid = document.getElementById('activeOrderId').value;
    const input = document.getElementById('adminChatInput');
    const msg = input.value.trim();
    if (!cid || !oid || !msg) return;

    input.value = '';
    const formData = new FormData();
    formData.append('sender_type', 'admin');
    formData.append('receiver_id', cid);
    formData.append('order_id', oid);
    formData.append('message', msg);

    fetch('../api/send_chat.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) { loadAdminChatHistory(); loadAdminChatList(); }
            else alert('Gagal mengirim pesan: ' + (data.error || 'Terjadi kesalahan'));
        }).catch(() => alert('Gagal mengirim pesan. Cek koneksi atau login admin.'));
}

function updateAdminGlobalBadge() {
    fetch(`../api/get_unread_count.php?is_admin=1`)
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('adminGlobalChatBadge');
            if (!badge) return;
            if (data.unread_count > 0) {
                badge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        });
}

// Global functions for inline usage
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.showDetail = showDetail;
window.updateOrder = updateOrder;
window.showPage = showPage;
window.openRejectModal = openRejectModal;
window.submitReject = submitReject;
window.closeProductModal = closeProductModal;
window.openProductModal = openProductModal;
window.submitProductForm = submitProductForm;
window.saveAutoAcceptMinutes = saveAutoAcceptMinutes;
window.toggleOrderAlertSound = toggleOrderAlertSound;
window.sendAdminChatMessage = sendAdminChatMessage;
window.openAdminChatFromElement = openAdminChatFromElement;
window.acceptOrderFromNotif = acceptOrderFromNotif;
window.openRejectModalFromNotif = openRejectModalFromNotif;
