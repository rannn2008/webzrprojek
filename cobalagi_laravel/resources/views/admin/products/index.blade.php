@extends('layouts.admin')

@section('header_title', 'Menu Produk')
@section('header_subtitle', 'Kelola ketersediaan dan informasi menu es teller')

@section('styles')
    <style>
        .product-img-container {
            position: relative;
            height: 180px;
            border-radius: 12px;
            overflow: hidden;
            background: #f0f0f0;
            margin-bottom: 20px;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .admin-card:hover .product-img {
            transform: scale(1.05);
        }

        .category-chip {
            font-size: 0.75rem;
            color: var(--primary-dark);
            background: rgba(139, 90, 43, 0.1);
            padding: 4px 12px;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 10px;
        }

        .availability-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 10px;
            background: #fdfbf7;
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: var(--transition);
        }

        .availability-toggle:hover {
            border-color: var(--primary-light);
        }

        .form-label {
            display: block;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1.5px solid #eee;
            font-family: 'Outfit', sans-serif;
            transition: var(--transition);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.05);
        }
    </style>
@endsection

@section('content')
    <div class="cards-grid">
        <!-- Add New Card - Premium Style -->
        <div class="admin-card"
            style="border:2px dashed var(--primary-light); display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; min-height:350px; background: rgba(139,90,43,0.02);"
            onclick="openProductModal()">
            <div
                style="width:70px; height:70px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.8rem; margin-bottom:15px; box-shadow: var(--shadow);">
                <i class="fas fa-plus"></i>
            </div>
            <h3 style="color:var(--primary-dark); font-family: 'Playfair Display', serif;">Tambah Menu Baru</h3>
            <p style="color:var(--gray); font-size:0.85rem; margin-top:5px;">Klik untuk input produk baru</p>
        </div>

        @foreach ($products as $p)
            <div class="admin-card" id="product-card-{{ $p->id }}">
                <div class="product-img-container">
                    @php
                        $imgSrc = $p->gambar ? asset('assets/images/products/' . $p->gambar) : 'https://placehold.co/400x300?text=' . urlencode($p->nama);
                    @endphp
                    <img src="{{ $imgSrc }}" class="product-img"
                        onerror="this.src='https://placehold.co/400x300?text=Coffee+Menu'">
                    <span class="status-badge {{ $p->tersedia ? 'st-done' : 'st-cancel' }}" style="top:15px; right:15px;">
                        {{ $p->tersedia ? 'Tersedia' : 'Habis' }}
                    </span>
                </div>

                <span class="category-chip">{{ $p->kategori }}</span>
                <h3
                    style="font-size:1.2rem; margin-bottom:8px; font-family: 'Playfair Display', serif; font-weight: 800; color: var(--dark);">
                    {{ $p->nama }}</h3>
                <h2 style="color:var(--primary); margin-bottom:20px; font-weight: 800;">Rp
                    {{ number_format($p->harga, 0, ',', '.') }}</h2>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button class="availability-toggle" onclick="toggleAvailability({{ $p->id }}, {{ $p->tersedia ? 0 : 1 }})">
                        <i class="fas {{ $p->tersedia ? 'fa-toggle-on' : 'fa-toggle-off' }}"
                            style="font-size: 1.4rem; color: {{ $p->tersedia ? '#2E7D32' : 'var(--gray)' }};"></i>
                        <span style="font-weight: 700; font-size: 0.85rem; color: var(--text-dark);">Status Ketersediaan</span>
                    </button>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
                        <button class="btn btn-warning" onclick='editProduct({!! json_encode($p) !!})'
                            style="background: #FFF3E0; color: #EF6C00;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger"
                                style="width: 100%; background: #FFEBEE; color: #C62828;">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- PRODUCT MODAL -->
    <div id="productModal" class="modal-ov">
        <div class="modal-content" style="max-width:850px; padding:0; overflow:hidden;">
            <div
                style="padding: 25px; border-bottom: 1px solid #eee; display:flex; justify-content:space-between; align-items:center; background: #fdfbf7;">
                <h2 id="productModalTitle" style="font-family: 'Playfair Display', serif; font-size: 1.5rem;">Tambah Produk
                    Baru</h2>
                <button onclick="closeProductModal()"
                    style="background:none; border:none; font-size:1.8rem; cursor:pointer; color: #ccc;">&times;</button>
            </div>

            <form id="productForm" method="POST" enctype="multipart/form-data" style="padding: 30px;">
                @csrf
                <div id="method_container"></div>
                <input type="hidden" name="id" id="productId">

                <div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 30px;">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" name="nama" id="productNama"
                                placeholder="Contoh: Es Teller Durian" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" name="harga" id="productHarga" placeholder="25000"
                                    required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select class="form-control" name="kategori" id="productKategori" required>
                                    <option value="Makanan">Makanan</option>
                                    <option value="Minuman">Minuman</option>
                                    <option value="Dessert">Dessert</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi Menu</label>
                            <textarea class="form-control" rows="4" name="deskripsi" id="productDeskripsi"
                                placeholder="Jelaskan kelezatan menu ini..."></textarea>
                        </div>
                    </div>

                    <div>
                        <div
                            style="border: 2px dashed #ddd; border-radius: 18px; padding: 10px; background: white; margin-bottom: 20px;">
                            <img id="productImagePreview"
                                style="width: 100%; height: 220px; border-radius: 12px; object-fit: cover;"
                                src="https://placehold.co/600x400?text=Preview+Image">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload Foto</label>
                            <input type="file" class="form-control" name="gambar" id="productGambar" accept="image/*">
                        </div>
                        <label
                            style="display: flex; align-items: center; gap: 12px; margin-top: 15px; cursor: pointer; background: #F5F7FA; padding: 12px; border-radius: 12px;">
                            <input type="checkbox" name="tersedia" id="productTersedia" value="1" checked
                                style="width: 20px; height: 20px; accent-color: var(--primary);">
                            <span style="font-weight: 700; color: var(--dark);">Aktifkan Ketersediaan Menu</span>
                        </label>
                    </div>
                </div>

                <div
                    style="display:flex; gap:12px; justify-content:flex-end; margin-top:30px; padding-top:20px; border-top: 1px solid #eee;">
                    <button type="button" class="btn" style="background:#f5f5f5; color:#666; padding: 12px 25px;"
                        onclick="closeProductModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="productSubmitBtn" style="padding: 12px 35px;">
                        <i class="fas fa-save" style="margin-right:8px;"></i> Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleAvailability(id, val) {
            fetch(`/admin/products/${id}/toggle`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ tersedia: val })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                });
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function openProductModal(prod = null) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('productModalTitle');
            const form = document.getElementById('productForm');
            const methodBox = document.getElementById('method_container');
            const preview = document.getElementById('productImagePreview');

            form.reset();
            methodBox.innerHTML = '';
            document.getElementById('productId').value = '';
            preview.src = 'https://placehold.co/600x400?text=Preview+Image';

            if (prod) {
                title.innerText = 'Edit Produk Menu';
                methodBox.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                form.action = `/admin/products/${prod.id}`;
                document.getElementById('productId').value = prod.id;
                document.getElementById('productNama').value = prod.nama;
                document.getElementById('productHarga').value = prod.harga;
                document.getElementById('productKategori').value = prod.kategori;
                document.getElementById('productDeskripsi').value = prod.deskripsi || '';
                document.getElementById('productTersedia').checked = parseInt(prod.tersedia) === 1;
                if (prod.gambar) preview.src = `/assets/images/products/${prod.gambar}`;
            } else {
                title.innerText = 'Tambah Produk Baru';
                form.action = "{{ route('admin.products.store') }}";
            }
            modal.style.display = 'flex';
        }

        function editProduct(p) { openProductModal(p); }

        document.getElementById('productGambar').addEventListener('change', function () {
            const f = this.files[0];
            if (f) {
                const r = new FileReader();
                r.onload = e => document.getElementById('productImagePreview').src = e.target.result;
                r.readAsDataURL(f);
            }
        });

        window.onclick = e => { if (e.target.id === 'productModal') closeProductModal(); }
    </script>
@endsection