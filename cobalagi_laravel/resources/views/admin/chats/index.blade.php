@extends('layouts.admin')

@section('header_title', 'Kotak Masuk Chat')
@section('header_subtitle', 'Layanan pelanggan real-time')

@section('styles')
    <style>
        .chat-user-card {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(139, 90, 43, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
        }

        .chat-user-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-light);
            background: #fdfbf7;
        }

        .user-avatar {
            width: 65px;
            height: 65px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 3px solid var(--primary-light);
            box-shadow: var(--shadow);
            flex-shrink: 0;
        }

        .unread-badge {
            background: var(--primary);
            color: white;
            min-width: 24px;
            height: 24px;
            padding: 0 8px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(139, 90, 43, 0.3);
        }
    </style>
@endsection

@section('content')
    <div class="cards-grid">
        @forelse($customers as $c)
            <a href="{{ route('admin.chats.show', $c->id) }}" class="chat-user-card">
                <div class="user-avatar">
                    @if($c->foto_profil)
                        <img src="{{ asset('assets/images/profiles/' . $c->foto_profil) }}"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="font-weight:800; color:var(--primary); font-size:1.4rem;">
                            {{ strtoupper(substr($c->nama, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div style="flex:1;">
                    <h3 style="margin-bottom:4px; font-family: 'Playfair Display', serif; font-weight: 800;">{{ $c->nama }}</h3>
                    <p
                        style="color: #25D366; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                        <i class="fab fa-whatsapp"></i> {{ $c->whatsapp }}
                    </p>
                </div>
                <div style="text-align: right;">
                    @if($c->chats_count > 0)
                        <span class="unread-badge">{{ $c->chats_count }}</span>
                    @else
                        <i class="fas fa-chevron-right" style="color:#ddd;"></i>
                    @endif
                </div>
            </a>
        @empty
            <div
                style="grid-column: 1/-1; background:white; padding:100px 50px; border-radius: 20px; text-align:center; box-shadow: var(--shadow);">
                <i class="fas fa-comments" style="font-size:4rem; color: #eee; margin-bottom:20px; display: block;"></i>
                <p style="color:var(--gray); font-size: 1.1rem;">Belum ada percakapan dengan pelanggan.</p>
            </div>
        @endforelse
    </div>
@endsection