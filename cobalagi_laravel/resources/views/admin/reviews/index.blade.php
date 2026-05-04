@extends('layouts.admin')

@section('header_title', 'Ulasan Pelanggan')
@section('header_subtitle', 'Masukan dan rating jujur dari pelanggan setia Anda')

@section('styles')
    <style>
        .review-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(139, 90, 43, 0.05);
            transition: var(--transition);
        }

        .review-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-light);
        }

        .rating-stars {
            display: flex;
            gap: 4px;
            color: #FFB300;
            margin-top: 5px;
        }

        .review-meta {
            margin-top: 15px;
            font-size: 0.8rem;
            color: var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
@endsection

@section('content')
    <div class="cards-grid">
        @forelse($reviews as $r)
            <div class="review-card">
                <div class="ac-header">
                    <div
                        style="width:55px; height:55px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; border: 3px solid var(--primary-light); box-shadow: var(--shadow);">
                        @if($r->customer && $r->customer->foto_profil)
                            <img src="{{ asset('assets/images/profiles/' . $r->customer->foto_profil) }}"
                                style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <div style="font-weight:800; color:var(--primary); font-size: 1.2rem;">
                                {{ strtoupper(substr($r->customer->nama ?? 'P', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="ac-title">
                        <h3 style="font-size:1.1rem; font-family: 'Playfair Display', serif; font-weight: 800;">
                            {{ $r->customer->nama ?? 'Pelanggan Gast' }}</h3>
                        <div class="rating-stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="{{ $i <= $r->rating ? 'fas' : 'far' }} fa-star"></i>
                            @endfor
                        </div>
                    </div>
                </div>

                <div
                    style="margin: 20px 0; padding:15px; background: #fdfbf7; border-radius:12px; border: 1px dashed rgba(139,90,43,0.1);">
                    <div
                        style="font-size:0.75rem; color:var(--gray); font-weight: 700; text-transform: uppercase; margin-bottom:5px;">
                        ID Pesanan:</div>
                    <div style="font-weight:700; color:var(--primary-dark); font-family: monospace;">
                        #{{ $r->order->order_code ?? 'N/A' }}
                    </div>
                </div>

                <p
                    style="font-size:1rem; color:var(--dark); line-height:1.6; font-style:italic; font-family: 'Outfit', sans-serif;">
                    "{{ $r->comment }}"
                </p>

                <div class="review-meta">
                    <span><i class="far fa-calendar-alt"></i>
                        {{ \Carbon\Carbon::parse($r->created_at)->format('d M Y') }}</span>
                    <span style="font-weight: 700; color: var(--primary-light);">Verified Review</span>
                </div>
            </div>
        @empty
            <div
                style="grid-column: 1/-1; background:white; padding:100px 50px; border-radius: 20px; text-align:center; box-shadow: var(--shadow);">
                <i class="fas fa-comment-slash" style="font-size:4rem; color: #eee; margin-bottom:20px; display: block;"></i>
                <p style="color:var(--gray); font-size: 1.1rem;">Belum ada ulasan yang masuk saat ini.</p>
            </div>
        @endforelse
    </div>
@endsection