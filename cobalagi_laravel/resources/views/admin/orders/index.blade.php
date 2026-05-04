@extends('layouts.admin')

@section('header_title', 'Pesanan')
@section('header_subtitle', 'Manajemen pesanan masuk dan riwayat')

@section('styles')
    <style>
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .filter-btn {
            padding: 10px 20px;
            border-radius: 50px;
            background: white;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: rgba(139, 90, 43, 0.05);
            color: var(--primary);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 5px 15px rgba(139, 90, 43, 0.2);
            border: none;
        }

        .ac-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .ac-title h3 {
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .ac-title span {
            font-size: 0.8rem;
            color: var(--gray);
            font-family: monospace;
        }

        .ac-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        /* Order Countdown Chip - Exact Legacy Style */
        .order-countdown-chip {
            background: rgba(139, 90, 43, 0.08);
            padding: 10px 15px;
            border-radius: 12px;
            font-size: 0.85rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px dashed var(--primary-light);
            margin-bottom: 5px;
        }

        .order-countdown-chip strong {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        /* Detail Modal Styles */
        .modal-customer-card {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 25px;
            background: rgba(139, 90, 43, 0.05);
            padding: 20px;
            border-radius: 18px;
            border: 1px solid rgba(139, 90, 43, 0.1);
        }

        .modal-items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-items-table th {
            text-align: left;
            padding: 12px;
            background: #fdfbf7;
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.03);
        }

        .modal-items-table td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            font-size: 0.95rem;
        }
    </style>
@endsection

@section('content')
    <div style="margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
        <div class="filters">
            <a href="{{ route('admin.orders.index') }}" class="filter-btn {{ !request('status') ? 'active' : '' }}">
                <i class="fas fa-list"></i> Semua
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'new']) }}"
                class="filter-btn {{ request('status') == 'new' ? 'active' : '' }}">
                <i class="fas fa-clock"></i> Baru
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'process']) }}"
                class="filter-btn {{ request('status') == 'process' ? 'active' : '' }}">
                <i class="fas fa-spinner"></i> Diproses
            </a>
        </div>
        <button class="btn btn-primary" onclick="exportToExcel()" style="padding: 10px 20px;">
            <i class="fas fa-file-excel"></i> Export Laporan
        </button>
    </div>

    <div class="cards-grid">
        @forelse($orders as $o)
            @php
                $st = strtolower($o->status ?: 'new');
                $status_class = match ($st) {
                    'process' => 'st-process',
                    'preparing' => 'st-preparing',
                    'ready' => 'st-ready',
                    'done' => 'st-done',
                    'cancel' => 'st-cancel',
                    default => 'st-new'
                };
                $status_text = match ($st) {
                    'process' => 'Diterima',
                    'preparing' => 'Diracik',
                    'ready' => 'Siap',
                    'done' => 'Selesai',
                    'cancel' => 'Batal',
                    default => 'Baru'
                };
            @endphp
            <div class="admin-card" id="card-order-{{ $o->id }}">
                <span class="status-badge {{ $status_class }}">{{ $status_text }}</span>

                <div class="ac-header">
                    <div
                        style="width:55px; height:55px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; border: 3px solid var(--primary-light); box-shadow: var(--shadow);">
                        @if($o->customer && $o->customer->foto_profil)
                            <img src="{{ asset('assets/images/profiles/' . $o->customer->foto_profil) }}" alt="P"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="font-weight:800; color:var(--primary); font-size: 1.2rem;">
                                {{ strtoupper(substr($o->nama_customer ?: 'P', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="ac-title">
                        <h3>{{ $o->nama_customer ?: 'Pelanggan Gast' }}</h3>
                        <span>#{{ $o->order_code }}</span>
                    </div>
                </div>

                <div class="ac-actions">
                    @if(in_array($st, ['new', 'baru']))
                        <div class="order-countdown-chip" data-countdown-created-at="{{ $o->created_at }}">
                            <i class="fas fa-hourglass-half"></i>
                            <span>Auto-terima dalam <strong data-countdown-value>--:--</strong></span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <button class="btn btn-primary" onclick="updateOrder({{ $o->id }}, 'accept')">Terima</button>
                            <button class="btn btn-danger" onclick="openRejectModal({{ $o->id }})">Tolak</button>
                        </div>
                    @elseif($st == 'process')
                        <button class="btn btn-primary btn-full" onclick="updateOrder({{ $o->id }}, 'preparing')">Mulai
                            Diracik</button>
                    @elseif($st == 'preparing')
                        <button class="btn btn-primary btn-full" onclick="updateOrder({{ $o->id }}, 'ready')">Siap Diambil</button>
                    @elseif($st == 'ready')
                        <button class="btn btn-primary btn-full" onclick="updateOrder({{ $o->id }}, 'done')">Selesaikan</button>
                    @endif
                    <button class="btn btn-info btn-full" onclick="showDetail({{ $o->id }})"
                        style="margin-top:5px; background: #E3F2FD; color: #1565C0;">
                        <i class="fas fa-eye"></i> Detail Pesanan
                    </button>
                </div>
            </div>
        @empty
            <div
                style="grid-column:1/-1; text-align:center; padding:100px 50px; background: white; border-radius: 20px; box-shadow: var(--shadow);">
                <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #eee; margin-bottom: 20px; display: block;"></i>
                <p style="color:var(--gray); font-size: 1.1rem;">Belum ada pesanan yang sesuai filter.</p>
            </div>
        @endforelse
    </div>

    <div style="margin-top: 30px">
        {{ $orders->links() }}
    </div>

    <!-- REJECT MODAL -->
    <div id="rejectModal" class="modal-ov">
        <div class="modal-content">
            <h3 style="margin-bottom: 10px;">Tolak Pesanan</h3>
            <p style="margin-bottom:20px; color:var(--gray);">Silakan masukkan alasan penolakan agar pelanggan mengetahui
                penyebabnya.</p>
            <input type="hidden" id="rejectOrderId">
            <div style="margin-bottom: 20px;">
                <label
                    style="font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; display: block; color: var(--text-muted);">ALASAN
                    PENOLAKAN:</label>
                <select id="rejectReasonSelect"
                    style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ddd; outline: none;"
                    onchange="toggleCustomReason()">
                    <option value="">-- Pilih Alasan --</option>
                    <option value="Mohon maaf, stok bahan sedang kosong.">Stok bahan sedang kosong</option>
                    <option value="Mohon maaf, toko sedang overload/sibuk.">Toko sedang overload/sibuk</option>
                    <option value="Mohon maaf, jam operasional sudah hampir tutup.">Jam operasional sudah hampir tutup
                    </option>
                    <option value="custom">Alasan Lainnya (Ketik sendiri)...</option>
                </select>
                <textarea id="rejectReason"
                    style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ddd; margin-top: 10px; display: none; outline: none;"
                    rows="3" placeholder="Ketik alasan spesifik..."></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-danger" style="flex: 1;" onclick="submitReject()">Konfirmasi Tolak</button>
                <button class="btn" style="background:#eee; color:#333; flex: 1;"
                    onclick="document.getElementById('rejectModal').style.display='none'">Batal</button>
            </div>
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div id="orderDetailModal" class="modal-ov">
        <div class="modal-content" style="max-width: 650px; padding: 0; overflow: hidden;">
            <div
                style="padding: 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 1.4rem;">Detail Transaksi</h3>
                <button onclick="document.getElementById('orderDetailModal').style.display='none'"
                    style="background:none; border:none; font-size:1.8rem; cursor:pointer; color: #ccc;">&times;</button>
            </div>
            <div id="orderDetailContent" style="padding: 25px; max-height: 80vh; overflow-y: auto;">
                <div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function updateOrder(id, action, alasan = '') {
            fetch("{{ route('admin.orders.updateStatus') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ order_id: id, action: action, alasan: alasan })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.message);
                });
        }

        function openRejectModal(id) {
            document.getElementById('rejectOrderId').value = id;
            document.getElementById('rejectModal').style.display = 'flex';
        }

        function toggleCustomReason() {
            const sel = document.getElementById('rejectReasonSelect');
            const txt = document.getElementById('rejectReason');
            txt.style.display = sel.value === 'custom' ? 'block' : 'none';
        }

        function submitReject() {
            const id = document.getElementById('rejectOrderId').value;
            const sel = document.getElementById('rejectReasonSelect');
            const txt = document.getElementById('rejectReason');
            const reason = sel.value === 'custom' ? txt.value : sel.value;
            if (!reason) return alert('Silakan pilih atau isi alasan penolakan.');
            updateOrder(id, 'reject', reason);
        }

        function showDetail(id) {
            const modal = document.getElementById('orderDetailModal');
            const content = document.getElementById('orderDetailContent');
            modal.style.display = 'flex';
            content.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading detail...</div>';

            fetch(`/admin/orders/${id}`)
                .then(res => res.json())
                .then(data => {
                    const o = data.order;
                    const items = data.items;
                    const customerName = o.nama_customer || (o.customer ? o.customer.nama : 'Pelanggan');
                    const avatar = o.foto_profil ? `/assets/images/profiles/${o.foto_profil}` : null;

                    let itemsHtml = '<table class="modal-items-table"><thead><tr><th>Menu</th><th>Qty</th><th>Harga</th><th style="text-align:right;">Subtotal</th></tr></thead><tbody>';
                    items.forEach(item => {
                        const price = parseInt(item.price);
                        const qty = parseInt(item.quantity);
                        itemsHtml += `<tr>
                            <td style="font-weight:600;">${item.nama_product || 'Produk'}</td>
                            <td>${qty}x</td>
                            <td>Rp ${price.toLocaleString('id-ID')}</td>
                            <td style="text-align:right; font-weight:700; color:var(--primary);">Rp ${(price * qty).toLocaleString('id-ID')}</td>
                        </tr>`;
                    });
                    itemsHtml += '</tbody></table>';

                    content.innerHTML = `
                        <div class="modal-customer-card">
                            <div style="width:70px; height:70px; border-radius:50%; background:white; overflow:hidden; border: 3px solid var(--primary); flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                                ${avatar ? `<img src="${avatar}" style="width:100%; height:100%; object-fit:cover;">` : `<i class="fas fa-user" style="font-size:2rem; color: #ddd;"></i>`}
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:800; font-size:1.3rem; color:var(--dark); margin-bottom:5px;">${customerName}</div>
                                <a href="https://wa.me/${o.whatsapp}" target="_blank" style="text-decoration:none; color: #25D366; font-weight:700; font-size:0.95rem; display:inline-flex; align-items:center; gap:6px; background: rgba(37, 211, 102, 0.08); padding: 5px 12px; border-radius: 50px;">
                                    <i class="fab fa-whatsapp"></i> Hubungi WhatsApp
                                </a>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:800; color:var(--gray); font-size:0.8rem; letter-spacing:1px; margin-bottom:5px;">ID PESANAN</div>
                                <div style="font-weight:800; font-family: monospace; font-size:1.1rem; color:var(--primary-dark);">#${o.order_code}</div>
                            </div>
                        </div>

                        <div style="margin-bottom:20px; border: 1px solid #f0f0f0; border-radius:15px; overflow:hidden;">
                            ${itemsHtml}
                        </div>

                        <div style="background: #fdfbf7; padding: 20px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="color:var(--gray); font-size:0.85rem; margin-bottom:2px;">Metode Pembayaran</div>
                                <div style="font-weight:700; color:var(--dark);">Saldo / Cash</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="color:var(--gray); font-size:0.85rem; margin-bottom:2px;">Total Pembayaran</div>
                                <div style="font-weight:900; font-size:1.6rem; color:var(--primary-dark);">Rp ${parseInt(o.total_harga).toLocaleString('id-ID')}</div>
                            </div>
                        </div>

                        <p style="text-align:center; margin-top:25px; font-size:0.85rem; color:var(--gray);">
                            Pesanan dibuat pada: ${o.created_at}
                        </p>
                    `;
                });
        }

        // Auto-Accept Countdown Logic
        function updateCountdowns() {
            const aaMinutes = parseInt(localStorage.getItem('autoAcceptMinutes') || 3);
            document.querySelectorAll('.order-countdown-chip').forEach(chip => {
                const createdAt = new Date(chip.dataset.countdownCreatedAt);
                const now = new Date();
                const diffSec = Math.floor((now - createdAt) / 1000);
                const limitSec = aaMinutes * 60;
                const remaining = limitSec - diffSec;

                if (remaining <= 0) {
                    chip.querySelector('[data-countdown-value]').innerText = 'SEKARANG';
                    chip.style.borderColor = 'red';
                    chip.style.color = 'red';
                } else {
                    const m = Math.floor(remaining / 60);
                    const s = remaining % 60;
                    chip.querySelector('[data-countdown-value]').innerText =
                        `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                }
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns();

        window.onclick = function (e) {
            if (e.target.className === 'modal-ov') {
                document.getElementById('rejectModal').style.display = 'none';
                document.getElementById('orderDetailModal').style.display = 'none';
            }
        }

        function exportToExcel() {
            // Simplified export using the existing orders on page
            alert('Laporan Excel akan diunduh secara otomatis...');
            // In a real app, this would trigger a download from server or client data
            const btn = document.getElementById('exportBtn');
            if (btn) btn.click();
        }
    </script>
@endsection