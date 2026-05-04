<?php
require_once '../config/config.php';
require_once '../api/chat_bootstrap.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true || !isset($_SESSION['customer_id'])) {
    header('Location: customer_login.php');
    exit();
}

$cust_id = $_SESSION['customer_id'];
$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: customer_dashboard.php?chat_error=1');
    exit();
}

$customer_data = fetch_one(secure_query($conn, "SELECT * FROM customers WHERE id = ?", "i", [$cust_id]));

$q_order = secure_query($conn, "SELECT id, order_code, status, metode_pengiriman FROM orders WHERE id = ? AND customer_id = ? LIMIT 1", "ii", [$order_id, $cust_id]);
$order = fetch_one($q_order);

if (!$order) {
    header('Location: customer_dashboard.php?chat_error=2');
    exit();
}

$status_raw = strtolower((string) ($order['status'] ?? ''));
if (!is_order_chat_allowed_for_customer($status_raw)) {
    header('Location: customer_dashboard.php?chat_error=3');
    exit();
}

$can_send_chat = is_order_chat_send_allowed($status_raw);
$status_map = [
    'new' => 'Baru',
    'baru' => 'Baru',
    'process' => 'Diterima',
    'preparing' => 'Diracik',
    'ready' => 'Siap Diambil',
    'done' => 'Selesai',
    'selesai' => 'Selesai',
    'cancel' => 'Dibatalkan',
    'batal' => 'Dibatalkan'
];
$status_text = $status_map[$status_raw] ?? ucfirst($status_raw);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Support - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5a2b;
            --primary-dark: #5c3a18;
            --primary-light: #c19a6b;
            --bg-color: #fdfbf7;
            --white: #ffffff;
            --gray: #795548;
            --light-gray: #f4eee6;
            --shadow: 0 5px 15px rgba(0,0,0,0.05);
            --bubble-customer: #8b5a2b;
            --bubble-admin: #ffffff;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-color); height: 100vh; display: flex; flex-direction: column; }

        /* Header */
        .chat-header {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow);
            z-index: 10;
        }
        .back-btn { color: white; text-decoration: none; font-size: 1.2rem; }
        .admin-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .admin-info h3 { font-size: 1.1rem; margin: 0; }
        .admin-info span { font-size: 0.8rem; opacity: 0.8; }
        .order-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.75rem;
        }

        /* Chat Area */
        .chat-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background-image: url('https://www.transparenttextures.com/patterns/cubes.png'); /* Subtle pattern */
        }

        .message-row { display: flex; width: 100%; }
        .message-row.customer { justify-content: flex-end; }
        .message-row.admin { justify-content: flex-start; }

        .message-bubble {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            font-size: 0.95rem;
            line-height: 1.4;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .customer .message-bubble {
            background: var(--bubble-customer);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .admin .message-bubble {
            background: var(--bubble-admin);
            color: var(--primary-dark);
            border-bottom-left-radius: 4px;
            border: 1px solid rgba(139, 90, 43, 0.1);
        }

        .message-time {
            display: block;
            font-size: 0.7rem;
            margin-top: 5px;
            opacity: 0.7;
            text-align: right;
        }

        /* Input Area */
        .chat-input-area {
            background: var(--white);
            padding: 15px 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            border-top: 1px solid var(--light-gray);
        }
        .chat-input-wrapper {
            flex: 1;
            background: var(--light-gray);
            border-radius: 25px;
            padding: 5px 20px;
            display: flex;
            align-items: center;
        }
        .chat-input-wrapper input {
            width: 100%;
            border: none;
            background: transparent;
            padding: 10px 0;
            outline: none;
            font-family: inherit;
            font-size: 1rem;
        }
        .send-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: 0.3s;
        }
        .send-btn:hover { background: var(--primary-dark); transform: scale(1.05); }
        .send-btn:disabled { cursor: not-allowed; opacity: 0.6; transform: none; }

        /* Welcome Message */
        .welcome-msg {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }
        .welcome-msg i { font-size: 3rem; margin-bottom: 20px; opacity: 0.5; }

        /* Custom Scrollbar */
        .chat-container::-webkit-scrollbar { width: 6px; }
        .chat-container::-webkit-scrollbar-track { background: transparent; }
        .chat-container::-webkit-scrollbar-thumb { background: rgba(139, 90, 43, 0.2); border-radius: 10px; }
    </style>
</head>
<body>

    <header class="chat-header">
        <a href="customer_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="admin-avatar">
            <i class="fas fa-headset"></i>
        </div>
        <div class="admin-info">
            <h3>Customer Service</h3>
            <span>Online | Kami siap membantu</span>
            <div class="order-pill">
                <i class="fas fa-receipt"></i>
                <span><?php echo htmlspecialchars($order['order_code']); ?> - <?php echo htmlspecialchars($status_text); ?></span>
            </div>
        </div>
    </header>

    <div class="chat-container" id="chatMessages">
        <div class="welcome-msg">
            <i class="fas fa-comments"></i>
            <h3>Hai <?php echo htmlspecialchars($customer_data['nama']); ?>!</h3>
            <p>Ada yang bisa kami bantu hari ini?</p>
        </div>
        <!-- Messages will be loaded here -->
    </div>

    <div class="chat-input-area">
        <div class="chat-input-wrapper">
            <input type="text" id="chatInput" placeholder="<?php echo $can_send_chat ? 'Ketik pesan di sini...' : 'Chat ditutup karena pesanan sudah selesai'; ?>" onkeypress="if(event.key === 'Enter') sendMessage()" <?php echo $can_send_chat ? '' : 'disabled'; ?>>
        </div>
        <button class="send-btn" id="chatSendBtn" onclick="sendMessage()" <?php echo $can_send_chat ? '' : 'disabled'; ?>><i class="fas fa-paper-plane"></i></button>
    </div>

    <script>
        const orderId = <?php echo intval($order_id); ?>;
        let canSend = <?php echo $can_send_chat ? 'true' : 'false'; ?>;
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const chatSendBtn = document.getElementById('chatSendBtn');
        let lastMessageCount = -1;
        let isSending = false;

        function loadMessages() {
            fetch(`../api/get_chats.php?order_id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        console.error(data.error || 'Gagal memuat chat');
                        return;
                    }

                    if (data.order) {
                        canSend = !!data.order.can_send;
                        setInputAvailability();
                    }

                    if (data.chats && data.chats.length !== lastMessageCount) {
                        renderMessages(data.chats);
                        lastMessageCount = data.chats.length;
                    }
                })
                .catch(err => console.error('Error loading messages:', err));
        }

        function renderMessages(chats) {
            let html = '';

            if (!chats || chats.length === 0) {
                html = `
                    <div class="welcome-msg">
                        <i class="fas fa-comments"></i>
                        <h3>Hai <?php echo htmlspecialchars($customer_data['nama']); ?>!</h3>
                        <p>Chat untuk pesanan <?php echo htmlspecialchars($order['order_code']); ?>.</p>
                    </div>
                `;
            }
            
            chats.forEach(chat => {
                const isMe = chat.sender_type === 'customer';
                html += `
                    <div class="message-row ${isMe ? 'customer' : 'admin'}">
                        <div class="message-bubble">
                            ${chat.message}
                            <span class="message-time">${chat.created_at}</span>
                        </div>
                    </div>
                `;
            });
            
            chatMessages.innerHTML = html;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function setInputAvailability() {
            if (canSend) {
                chatInput.disabled = false;
                chatSendBtn.disabled = false;
                chatInput.placeholder = 'Ketik pesan di sini...';
            } else {
                chatInput.disabled = true;
                chatSendBtn.disabled = true;
                chatInput.placeholder = 'Chat ditutup karena pesanan sudah selesai';
            }
        }

        function sendMessage() {
            const message = chatInput.value.trim();
            if (!canSend || !message || isSending) return;

            isSending = true;
            chatSendBtn.disabled = true;
            chatSendBtn.style.opacity = '0.7';

            chatInput.value = '';
            
            const formData = new FormData();
            formData.append('sender_type', 'customer');
            formData.append('order_id', orderId);
            formData.append('message', message);

            fetch('../api/send_chat.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadMessages();
                } else {
                    alert('Gagal mengirim pesan: ' + (data.error || 'Terjadi kesalahan'));
                }
            })
            .catch(err => {
                console.error('Error sending message:', err);
                alert('Gagal mengirim pesan. Cek koneksi internet Anda.');
            })
            .finally(() => {
                isSending = false;
                chatSendBtn.disabled = false;
                chatSendBtn.style.opacity = '1';
                chatInput.focus();
            });
        }

        // Initial load
        setInputAvailability();
        loadMessages();
        // Polling
        setInterval(loadMessages, 2500);
    </script>
</body>
</html>
