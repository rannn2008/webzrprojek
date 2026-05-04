<?php
// c:/xampp/htdocs/parking/admin_chat.php
include "config.php";
include "auth.php";
restrictToAdmin();

$adminName = $_SESSION["admin_name"] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat | SpotFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            overflow: hidden !important; 
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding-bottom: 10px;
            width: 100%;
            margin: 0 auto;
        }
        .card {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-bottom: 0;
        }
        #chat-box::-webkit-scrollbar, #chat-clients::-webkit-scrollbar {
            width: 6px;
        }
        #chat-box::-webkit-scrollbar-track, #chat-clients::-webkit-scrollbar-track {
            background: transparent;
        }
        #chat-box::-webkit-scrollbar-thumb, #chat-clients::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
        }
        #chat-box::-webkit-scrollbar-thumb:hover, #chat-clients::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }
        .chat-bubble {
            max-width: 80%;
            padding: 6px 10px 8px 10px;
            font-size: 0.85rem;
            margin-bottom: 4px;
            position: relative;
            word-break: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .bubble-right {
            align-self: flex-end;
            background: #005c4b;
            color: #e9edef;
            border-radius: 12px 0 12px 12px;
        }
        .bubble-left {
            align-self: flex-start;
            background: #202c33;
            color: #e9edef;
            border-radius: 0 12px 12px 12px;
        }
        .bubble-sender {
            font-size: 0.72rem;
            font-weight: 600;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bubble-info {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.6);
            float: right;
            margin-left: 10px;
            margin-top: 5px;
        }
        .bubble-body::after {
            content: ""; clear: both; display: table;
        }

        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none; position: absolute; right: 0; background-color: #2a3942; min-width: 150px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.4); z-index: 10; border-radius: 6px; overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1); margin-top: 5px;
        }
        .dropdown-content button {
            color: #d1d5db; padding: 10px 14px; text-decoration: none; display: block; border: none;
            background: none; width: 100%; text-align: left; font-size: 0.75rem; cursor: pointer;
        }
        .dropdown-content button:hover { background-color: #111b21; }
        .show { display: block; }
        .dropbtn { padding: 4px; border-radius: 4px; }
        .dropbtn:hover { background: rgba(255,255,255,0.1); }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            header { padding: 10px 15px; margin-bottom: 10px; border-radius: 12px; }
            .header-top h1 { font-size: 1.2rem; }
            .tagline { display: none; }
            .tabs-container { margin-bottom: 10px; }
            .tabs { padding: 4px; gap: 5px; flex-wrap: wrap; }
            .tab-btn { padding: 8px 12px; font-size: 0.75rem; text-align: center; flex: 1 1 40%; }
            .card { padding: 12px; border-radius: 16px; margin-bottom: 0px !important; }

            /* Toggle Logic */
            #chat-wrapper { flex-direction: column !important; }
            #chat-sidebar { width: 100% !important; flex: 1 !important; display: flex !important; }
            #chat-main { display: none !important; width: 100% !important; flex: 1 !important; }

            .mobile-chat-open #chat-sidebar { display: none !important; }
            .mobile-chat-open #chat-main { display: flex !important; }

            #btn-mobile-back { display: block !important; }
        }
    </style>
</head>

<body>
    <div class="container">
<?php include 'global_ai_assistant.php'; ?>
        <header>
            <div class="header-top">
                <div>
                    <h1><i class="fas fa-comments"></i> ADMIN CHAT</h1>
                    <p class="tagline">Komunikasi terpisah dengan setiap client</p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.8rem; color: var(--accent-primary); font-weight: 600;">ADMIN:
                        <?= htmlspecialchars($adminName) ?>
                    </div>
                    <a href="logout.php" style="font-size: 0.75rem; color: var(--danger); text-decoration: none;"><i
                            class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-chart-pie"></i> Public View</a>
                <a href="users.php" class="tab-btn"><i class="fas fa-user-shield"></i> Users & RFID</a>
                <a href="history.php" class="tab-btn"><i class="fas fa-clock-rotate-left"></i> History Log</a>
                <a href="settings.php" class="tab-btn"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_chat.php" class="tab-btn active"><i class="fas fa-comments"></i> Chat</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2 class="card-title"><i class="fas fa-comment-dots"></i> Private Chat (Admin)</h2>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" class="btn btn-warning" style="padding:6px 12px;font-size:0.75rem;" onclick="loadConversations(true)">
                        <i class="fas fa-rotate"></i> Refresh
                    </button>
                    <span style="width:7px;height:7px;background:#4ade80;border-radius:50%;display:inline-block;"></span>
                    <span style="font-size:0.75rem;color:#4ade80;">LIVE</span>
                </div>
            </div>

            <div id="chat-wrapper" style="display:flex; gap:15px; flex:1; overflow:hidden;">
                <div id="chat-sidebar" style="width:260px; flex-shrink:0; display:flex; flex-direction:column; gap:10px; overflow:hidden;">
                    
                    <!-- GATE CONTROL INTEGRATED -->
                    <div style="display:flex; gap:6px; flex-shrink:0;">
                        <button onclick="sendGateCmd('OPEN')" class="btn btn-success" style="flex:1; padding:8px; font-size:0.75rem;" title="Buka Palang Secara Manual">
                            <i class="fas fa-lock-open"></i> BUKA
                        </button>
                        <button onclick="sendGateCmd('CLOSE')" class="btn btn-danger" style="flex:1; padding:8px; font-size:0.75rem;" title="Tutup Palang Secara Manual">
                            <i class="fas fa-lock"></i> TUTUP
                        </button>
                    </div>
                    <div id="gate-status" style="text-align:center; font-size:0.75rem; margin-top:-6px; flex-shrink:0;"></div>

                    <input id="conversation-search" type="text" class="form-control" placeholder="Cari nama / plat..."
                        style="margin-bottom:0; flex-shrink:0;">
                    <div id="chat-clients"
                        style="flex:1; overflow-y:auto; background:rgba(0,0,0,0.2); border-radius:12px; padding:10px;">
                        <div style="text-align:center;color:var(--text-muted);font-size:0.8rem;padding:12px;">Loading...</div>
                    </div>
                </div>

                <div id="chat-main" style="flex:1; display:flex; flex-direction:column; min-width:0; overflow:hidden;">
                    <div id="chat-header"
                        style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; flex-shrink:0;">
                        <button id="btn-mobile-back" class="btn btn-secondary" style="display:none; padding:7px 10px; font-size:0.75rem; border-radius:8px;"
                            onclick="closeMobileChat()" title="Kembali ke Kontak">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div style="font-size:0.9rem; color:var(--accent-primary); font-weight:600; flex:1;">Pilih client untuk mulai chat</div>
                        <button id="btn-delete-conversation" class="btn btn-danger" style="display:none; padding:7px 10px; font-size:0.75rem;"
                            onclick="deleteActiveConversation()">
                            <i class="fas fa-user-xmark"></i> Hapus Kontak
                        </button>
                    </div>

                    <div id="chat-box"
                        style="flex:1; overflow-y:auto; background:#0b141a; border-radius:12px; padding:15px; display:flex; flex-direction:column; gap:6px; scroll-behavior:smooth;">
                        <div style="text-align:center; color:var(--text-muted); font-size:0.85rem; padding:20px;">← Pilih client di kiri</div>
                    </div>
                    <div id="input-mode-text" style="display:flex; gap:8px; margin-top:10px; flex-shrink:0;">
                         <input type="text" id="chat-input" class="form-control" placeholder="Balas pesan client..."
                            style="flex:1; margin-bottom:0; background:#202c33; color:#fff; border:none; border-radius:8px;" onkeypress="if(event.key==='Enter') sendTextMessage()" disabled>
                        <button type="button" id="btn-send-text" class="btn btn-success" style="padding:10px 14px; border-radius:8px;" onclick="sendTextMessage()" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" id="btn-record" class="btn btn-warning" style="padding:10px 12px; border-radius:8px;" onclick="startRecord()" disabled>
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button type="button" id="btn-stop-record" class="btn btn-danger" style="padding:10px 12px; display:none; border-radius:8px;" onclick="stopRecord()">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>

                    <div id="input-mode-audio" style="display:none; gap:8px; margin-top:10px; flex-shrink:0; align-items:center; background:#202c33; padding:6px 12px; border-radius:12px;">
                        <button type="button" class="btn btn-danger" style="padding:8px 12px; border-radius:50%;" onclick="cancelVN()" title="Batal">
                            <i class="fas fa-trash"></i>
                        </button>
                        <audio id="audio-preview" controls src="" style="flex:1; height:35px; outline:none;"></audio>
                        <button type="button" class="btn btn-success" style="padding:8px 12px; border-radius:50%;" onclick="sendVN()" title="Kirim">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div id="record-status" style="font-size:0.72rem; color:var(--text-muted); margin-top:4px; flex-shrink:0;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let activeClientId = 0;
        let activeClientName = "";
        let chatLastId = 0;
        let polling = false;
        let allConversations = [];
        let lastMessagesJSON = "";

        let mediaRecorder = null;
        let mediaStream = null;
        let audioChunks = [];
        let isRecording = false;

        function escHtml(v) {
            return String(v ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
        }

        function escAttr(v) {
            return escHtml(v).replace(/`/g, "");
        }

        function canUseMediaRecorder() {
            return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
        }

        function renderConversations() {
            const q = ($("#conversation-search").val() || "").toLowerCase().trim();
            const list = allConversations.filter(c => {
                if (!q) return true;
                const name = String(c.name || "").toLowerCase();
                const plate = String(c.plate_number || "").toLowerCase();
                return name.includes(q) || plate.includes(q);
            });

            if (list.length === 0) {
                $("#chat-clients").html('<div style="text-align:center;color:var(--text-muted);font-size:0.75rem;padding:14px;">Belum ada percakapan</div>');
                return;
            }

            const html = list.map(c => {
                const isActive = Number(c.client_id) === Number(activeClientId);
                const label = c.name || ("User #" + c.client_id);
                const preview = c.last_msg || "-";
                const unreadBadge = c.unread_count > 0 ? `<span style="background:#ef4444; color:white; padding:1px 5px; border-radius:10px; font-size:0.6rem; font-weight:bold; margin-left:5px;">${c.unread_count}</span>` : "";
                return `
                    <div onclick="selectConversationById(${Number(c.client_id)})"
                         style="padding:9px; margin-bottom:7px; border-radius:9px; cursor:pointer;
                         background:${isActive ? "rgba(0,229,255,0.2)" : "rgba(255,255,255,0.03)"};
                         border:1px solid ${isActive ? "#00e5ff" : "rgba(255,255,255,0.06)"};">
                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">
                            <div style="min-width:0;">
                                <div style="font-weight:600; color:${isActive ? "#00e5ff" : "#e2e8f0"}; font-size:0.82rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    ${escHtml(label)} ${unreadBadge}
                                </div>
                                <div style="font-size:0.66rem; color:#64748b;">${escHtml(c.plate_number || "-")}</div>
                                <div style="font-size:0.65rem; color:#94a3b8; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    ${escHtml(preview)}
                                </div>
                            </div>
                            <button onclick="event.stopPropagation(); deleteConversationById(${Number(c.client_id)})"
                                class="btn btn-danger" style="padding:3px 6px; font-size:0.6rem;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join("");

            $("#chat-clients").html(html);
        }

        function loadConversations(showToast = false) {
            $.get("api_chat.php?conversations=1", function (data) {
                allConversations = data.conversations || [];
                renderConversations();
                if (showToast) {
                    $("#record-status").text("Percakapan diperbarui.");
                    setTimeout(() => { if (!isRecording) $("#record-status").text(""); }, 1200);
                }
            }, "json");
        }

        function toggleDropdown(id) {
            document.getElementById("dropdown-" + id).classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        function renderMessage(m) {
            const isMine = m.sender_type === "admin";
            const alignClass = isMine ? "bubble-right" : "bubble-left";
            const icon = isMine ? "fa-user-shield" : "fa-user";
            const time = (m.created_at || "").substring(11, 16);
            const deleted = Number(m.is_deleted || 0) === 1;
            const senderColor = isMine ? "#00e5ff" : "#4ade80";

            let rawMsg = m.message || "";
            let msgBody = "";
            
            if (m.message_type === "voice" && m.media_path && !deleted) {
                msgBody = `<audio controls preload="none" src="${escAttr(m.media_path)}" style="height:35px; width:220px; border-radius: 8px;"></audio>`;
            } else if (rawMsg.startsWith("[REQ_CANCEL:") && !deleted) {
                const parts = rawMsg.match(/\[REQ_CANCEL:(\d+)\](.*)/);
                const slotId = parts ? parts[1] : "?";
                const text = parts ? parts[2] : rawMsg;
                msgBody = `
                    <div style="background:rgba(239,68,68,0.1); border-left:4px solid #ef4444; padding:10px; border-radius:8px; margin:5px 0;">
                        <div style="font-weight:700; color:#ef4444; font-size:0.75rem;"><i class="fas fa-exclamation-circle"></i> PERMINTAAN PEMBATALAN</div>
                        <div style="font-size:0.85rem; margin:5px 0;">${escHtml(text)}</div>
                        <button onclick="confirmCancel(${m.id}, ${slotId}, ${m.client_id})" class="btn btn-danger" style="width:100%; padding:8px; font-size:0.7rem; font-weight:700; border-radius:8px; margin-top:5px;">
                            <i class="fas fa-check"></i> KONFIRMASI PEMBATALAN
                        </button>
                    </div>
                `;
            } else if (rawMsg.startsWith("[CANCEL_CONFIRMED:") && !deleted) {
                 msgBody = `
                    <div style="background:rgba(139,92,246,0.1); border-left:4px solid #8b5cf6; padding:10px; border-radius:8px; margin:5px 0;">
                        <div style="font-weight:700; color:#8b5cf6; font-size:0.75rem;"><i class="fas fa-check-circle"></i> PEMBATALAN SUKSES</div>
                        <div style="font-size:0.85rem; margin:5px 0;">${escHtml(rawMsg.split(']')[1] || rawMsg)}</div>
                    </div>
                `;
            } else {
                msgBody = `<div style="${deleted ? "font-style:italic;color:rgba(255,255,255,0.5);" : ""}">${escHtml(rawMsg)}</div>`;
            }

            let deleteMenu = "";
            if (!deleted) {
                // Admin can delete everything for everyone or for themselves
                deleteMenu = `
                    <div class="dropdown" style="float:right;">
                        <i class="fas fa-chevron-down dropbtn" style="cursor:pointer; color:rgba(255,255,255,0.6); font-size:0.65rem;" onclick="toggleDropdown(${Number(m.id)})"></i>
                        <div id="dropdown-${Number(m.id)}" class="dropdown-content" style="${isMine ? 'right: 0; left: auto; min-width: 170px;' : 'left: 0; right: auto; min-width: 170px;'}">
                            <button onclick="deleteMsg(${Number(m.id)}, 'for_me')"><i class="fas fa-eye-slash"></i> Hapus untuk Saya</button>
                            <button onclick="deleteMsg(${Number(m.id)}, 'for_everyone')"><i class="fas fa-trash" style="color:#ef4444;"></i> Hapus untuk Semua</button>
                        </div>
                    </div>
                `;
            }

            let ticks = "";
            if (isMine) {
                ticks = `<i class="fas fa-check-double" style="margin-left:5px; font-size:0.6rem; color:${Number(m.is_read) === 1 ? '#38bdf8' : 'rgba(255,255,255,0.6)'};"></i>`;
            }

            return `
                <div class="chat-bubble ${alignClass}">
                    <div class="bubble-sender" style="color: ${senderColor};">
                        <span><i class="fas ${icon}"></i> ${escHtml(m.sender_name || "-")}</span>
                        ${deleteMenu}
                    </div>
                    <div class="bubble-body">
                        ${msgBody}
                        <div class="bubble-info">${escHtml(time)}${ticks}</div>
                    </div>
                </div>
            `;
        }

        function scrollChat() {
            const box = document.getElementById("chat-box");
            if (box) box.scrollTop = box.scrollHeight;
        }

        function updateComposerState() {
            const enabled = activeClientId > 0;
            $("#chat-input").prop("disabled", !enabled);
            $("#btn-send-text").prop("disabled", !enabled);
            $("#btn-record").prop("disabled", !enabled || !canUseMediaRecorder());
            $("#btn-delete-conversation").toggle(enabled);
            if (!enabled) {
                $("#chat-input").attr("placeholder", "Pilih client dulu...");
            } else {
                $("#chat-input").attr("placeholder", "Balas ke " + activeClientName + "...");
            }
        }

        function selectConversationById(clientId) {
            const convo = allConversations.find(c => Number(c.client_id) === Number(clientId)) || null;
            const clientName = (convo && convo.name) ? convo.name : ("User #" + clientId);
            activeClientId = Number(clientId);
            activeClientName = clientName || ("User #" + clientId);
            chatLastId = 0;
            $("#chat-header > div:first").html('<i class="fas fa-user" style="color:#4ade80"></i> Chat dengan <strong>' + escHtml(activeClientName) + '</strong>');
            updateComposerState();
            renderConversations();
            loadMessages();
            $("#chat-wrapper").addClass("mobile-chat-open");
        }

        function closeMobileChat() {
            $("#chat-wrapper").removeClass("mobile-chat-open");
            activeClientId = 0;
            activeClientName = "";
            chatLastId = 0;
            $("#chat-header > div:first").html('<div style="font-size:0.9rem; color:var(--accent-primary); font-weight:600;">Pilih client untuk mulai chat</div>');
            $("#chat-box").html('<div style="text-align:center; color:var(--text-muted); font-size:0.85rem; padding:20px;">← Pilih client di kiri</div>');
            updateComposerState();
            renderConversations();
        }

        function loadMessages() {
            if (activeClientId <= 0) {
                return;
            }
            $.get("api_chat.php?client_id=" + activeClientId, function (data) {
                const messages = data.messages || [];
                if (messages.length === 0) {
                    $("#chat-box").html('<div style="text-align:center; color:var(--text-muted); font-size:0.8rem; padding:20px;">Belum ada pesan.</div>');
                    chatLastId = 0;
                    lastMessagesJSON = "[]";
                    return;
                }
                lastMessagesJSON = JSON.stringify(messages);
                $("#chat-box").html(messages.map(renderMessage).join(""));
                chatLastId = Number(data.latest_id || 0);
                scrollChat();
            }, "json");
        }

        function pollMessages() {
            if (polling || activeClientId <= 0) return;
            polling = true;
            $.get("api_chat.php?client_id=" + activeClientId, function (data) {
                const messages = data.messages || [];
                const currentJSON = JSON.stringify(messages);
                if (currentJSON !== lastMessagesJSON && messages.length > 0) {
                    lastMessagesJSON = currentJSON;
                    const box = document.getElementById("chat-box");
                    const isAtBottom = box && (box.scrollHeight - box.scrollTop - box.clientHeight < 40);
                    
                    $("#chat-box").html(messages.map(renderMessage).join(""));
                    chatLastId = Number(data.latest_id || chatLastId);
                    
                    if (isAtBottom) scrollChat();
                    loadConversations();
                }
            }, "json").always(function () {
                polling = false;
            });
        }

        function sendTextMessage() {
            const input = $("#chat-input");
            const msg = (input.val() || "").trim();
            if (!msg || activeClientId <= 0) return;

            input.val("");
            $.post("api_chat.php", {
                action: "send_text",
                message: msg,
                client_id: activeClientId
            }, function (res) {
                if (res.success) {
                    loadMessages();
                    loadConversations();
                } else {
                    alert(res.message || "Gagal kirim pesan");
                }
            }, "json");
        }

        function deleteMsg(id, type) {
            const msgConfirm = type === 'for_everyone' ? 'Hapus pesan ini untuk semua orang?' : 'Hapus pesan ini hanya untuk Anda?';
            if (!confirm(msgConfirm)) return;
            $.post("api_chat.php", {
                action: "delete_message",
                message_id: id,
                delete_type: type
            }, function (res) {
                if (res.success) {
                    loadMessages();
                    loadConversations();
                } else {
                    alert(res.message || "Gagal menghapus pesan");
                }
            }, "json");
        }

        function deleteActiveConversation() {
            if (activeClientId <= 0) return;
            deleteConversationById(activeClientId);
        }

        function deleteConversationById(clientId) {
            const convo = allConversations.find(c => Number(c.client_id) === Number(clientId)) || null;
            const clientName = (convo && convo.name) ? convo.name : ("User #" + clientId);
            if (!confirm("Hapus seluruh percakapan dengan " + clientName + "?")) return;
            $.post("api_chat.php", {
                action: "delete_conversation",
                client_id: clientId
            }, function (res) {
                if (!res.success) {
                    alert(res.message || "Gagal menghapus percakapan");
                    return;
                }
                if (Number(clientId) === Number(activeClientId)) {
                    activeClientId = 0;
                    activeClientName = "";
                    chatLastId = 0;
                    $("#chat-header > div:first").text("Pilih client untuk mulai chat");
                    $("#chat-box").html('<div style="text-align:center; color:var(--text-muted); font-size:0.85rem; padding:20px;">Percakapan dihapus.</div>');
                }
                updateComposerState();
                loadConversations(true);
            }, "json");
        }

        async function startRecord() {
            if (activeClientId <= 0) {
                alert("Pilih client dulu.");
                return;
            }
            if (!canUseMediaRecorder()) {
                alert("Browser tidak mendukung perekam suara.");
                return;
            }
            if (isRecording) return;

            try {
                // Konfigurasi audio yang spesifik meminta "microphone" asli dengan fitur kejernihan suara
                const constraints = {
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                };
                mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                audioChunks = [];
                
                let options = {};
                if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                    options = { mimeType: 'audio/webm;codecs=opus' };
                } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
                    options = { mimeType: 'audio/mp4' };
                }
                
                mediaRecorder = new MediaRecorder(mediaStream, options);

                mediaRecorder.ondataavailable = e => {
                    if (e.data && e.data.size > 0) {
                        audioChunks.push(e.data);
                    }
                };
                
                mediaRecorder.onstop = function () {
                    // Matikan track stream segera setelah stop
                    if (mediaStream) {
                        mediaStream.getTracks().forEach(t => t.stop());
                        mediaStream = null;
                    }
                    if (!audioChunks.length) {
                        $("#record-status").text("Rekaman kosong.");
                        cancelVN();
                        return;
                    }
                    // Buat Blob menggunakan mimeType asli dari recorder
                    currentAudioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType });
                    
                    $("#input-mode-text").hide();
                    $("#input-mode-audio").css("display", "flex");
                    $("#audio-preview").attr("src", URL.createObjectURL(currentAudioBlob));
                    $("#record-status").text("Pratinjau voice note...");
                };
                
                // Gunakan Timeslice 250ms agar data direkam berkala, mencegah file corrupt/diam
                mediaRecorder.start(250);
                
                isRecording = true;
                $("#btn-record").hide();
                $("#btn-stop-record").show();
                $("#chat-input").prop("disabled", true);
                $("#btn-send-text").prop("disabled", true);
                $("#record-status").text("Merekam... (Bicara sekarang)");
            } catch (e) {
                console.error(e);
                alert("Izin mikrofon ditolak. Pastikan mic terhubung & izinkan di browser.");
            }
        }

        function stopRecord() {
            if (!isRecording || !mediaRecorder) return;
            isRecording = false;
            $("#btn-stop-record").hide();
            $("#btn-record").show();
            $("#record-status").text("Memproses rekaman...");
            mediaRecorder.stop();
        }

        function cancelVN() {
            currentAudioBlob = null;
            $("#audio-preview").attr("src", "");
            $("#input-mode-audio").hide();
            $("#input-mode-text").css("display", "flex");
            $("#record-status").text("");
            updateComposerState(); // re-enable/disable based on activeClientId
        }

        function sendVN() {
            if (!currentAudioBlob) return;
            $("#record-status").text("Mengirim voice note...");
            $("#input-mode-audio").hide();
            $("#input-mode-text").css("display", "flex");
            sendVoiceBlob(currentAudioBlob);
            currentAudioBlob = null;
            $("#audio-preview").attr("src", "");
            updateComposerState();
        }

        function sendVoiceBlob(blob) {
            const formData = new FormData();
            formData.append("action", "send_voice");
            formData.append("client_id", String(activeClientId));
            formData.append("voice_note", blob, "voice_note.webm");

            $.ajax({
                url: "api_chat.php",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json"
            }).done(function (res) {
                if (res.success) {
                    $("#record-status").text("Voice note terkirim.");
                    loadMessages();
                    loadConversations();
                } else {
                    $("#record-status").text("");
                    alert(res.message || "Gagal kirim voice note.");
                }
            }).fail(function () {
                $("#record-status").text("");
                alert("Gagal upload voice note.");
            });
        }

        // ========== GATE CONTROL ==========
        function sendGateCmd(cmd) {
            $.post("api_gate.php", { command: cmd, client_id: activeClientId }, function (data) {
                let color = cmd === 'OPEN' ? '#4ade80' : '#f87171';
                let icon = cmd === 'OPEN' ? '🔓' : '🔒';
                let extra = (cmd === 'OPEN' && activeClientId > 0) ? ' (Auto Check-In)' : '';
                $("#gate-status").html(`<span style="color:${color}">${icon} Perintah ${cmd} terkirim!${extra}</span>`);
                setTimeout(() => $("#gate-status").html(""), 4000);
            }, "json").fail(function () {
                $("#gate-status").html('<span style="color:#f87171">⚠ Gagal mengirim perintah</span>');
            });
        }

        function confirmCancel(msgId, slotId, clientId) {
            if (!confirm("Konfirmasi pembatalan Bay 0" + slotId + "?\nUang akan dikembalikan 100%.")) return;
            $.post("api_cancel_booking.php", {
                slot_id: slotId,
                client_id: clientId
            }, function (res) {
                if (res.success) {
                    alert(res.message);
                    loadMessages();
                } else {
                    alert(res.message || "Gagal membatalkan booking");
                }
            }, "json");
        }

        $(document).ready(function () {
            loadConversations();
            updateComposerState();
            setInterval(loadConversations, 5000);
            setInterval(pollMessages, 1800);
            $("#conversation-search").on("input", renderConversations);
        });
    </script>
</body>

</html>
