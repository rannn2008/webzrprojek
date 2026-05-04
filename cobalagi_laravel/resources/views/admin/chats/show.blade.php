@extends('layouts.admin')

@section('header_title', $customer->nama)
@section('header_subtitle', 'Percakapan aktif dengan pelanggan')

@section('styles')
    <style>
        .chat-wrapper {
            background: white;
            border-radius: 25px;
            box-shadow: var(--shadow);
            height: calc(100vh - 250px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(139, 90, 43, 0.05);
        }

        .chat-header {
            padding: 15px 25px;
            background: #fdfbf7;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-body {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .msg {
            max-width: 70%;
            padding: 15px 20px;
            border-radius: 20px;
            font-size: 0.95rem;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            line-height: 1.5;
        }

        .msg-customer {
            align-self: flex-start;
            background: white;
            color: var(--dark);
            border-bottom-left-radius: 5px;
            border: 1px solid #eee;
        }

        .msg-admin {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-bottom-right-radius: 5px;
        }

        .msg-timestamp {
            font-size: 0.7rem;
            margin-top: 8px;
            display: block;
            opacity: 0.6;
            font-weight: 700;
        }

        .chat-footer {
            padding: 20px 25px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 15px;
        }

        .chat-input-pill {
            flex: 1;
            background: #f5f5f5;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            outline: none;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
        }

        .chat-input-pill:focus {
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 90, 43, 0.1);
        }

        .send-btn {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: var(--transition);
            box-shadow: 0 5px 15px rgba(139, 90, 43, 0.2);
        }

        .send-btn:hover {
            transform: scale(1.05) rotate(-10deg);
            background: var(--primary-dark);
        }
    </style>
@endsection

@section('content')
    <div class="chat-wrapper">
        <div class="chat-header">
            <div
                style="width: 45px; height: 45px; border-radius: 50%; background: white; overflow: hidden; border: 2px solid var(--primary-light);">
                @if($customer->foto_profil)
                    <img src="{{ asset('assets/images/profiles/' . $customer->foto_profil) }}"
                        style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    <div
                        style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--primary);">
                        {{ strtoupper(substr($customer->nama, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div>
                <h4 style="margin: 0; font-family: 'Playfair Display', serif; font-weight: 800;">{{ $customer->nama }}</h4>
                <small style="color: #25D366; font-weight: 700;"><i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                    Online</small>
            </div>
        </div>

        <div class="chat-body" id="chatBody">
            @foreach($chats as $c)
                <div class="msg {{ $c->sender_type == 'customer' ? 'msg-customer' : 'msg-admin' }}">
                    {{ $c->message }}
                    <span class="msg-timestamp">{{ \Carbon\Carbon::parse($c->created_at)->format('H:i') }}</span>
                </div>
            @endforeach
        </div>

        <div class="chat-footer">
            <input type="text" id="chatInput" class="chat-input-pill" placeholder="Ketik pesan balasan..."
                onkeypress="if(event.key==='Enter') sendMessage()">
            <button class="send-btn" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const chatBody = document.getElementById('chatBody');
        chatBody.scrollTop = chatBody.scrollHeight;

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            if (!msg) return;

            fetch("{{ route('admin.chats.send') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ customer_id: {{ $customer->id }}, message: msg })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const div = document.createElement('div');
                        div.className = 'msg msg-admin';
                        div.innerHTML = `${msg} <span class="msg-timestamp">${new Date().getHours()}:${String(new Date().getMinutes()).padStart(2, '0')}</span>`;
                        chatBody.appendChild(div);
                        chatBody.scrollTop = chatBody.scrollHeight;
                        input.value = '';
                    }
                });
        }
    </script>
@endsection