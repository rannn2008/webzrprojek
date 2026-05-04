<?php
// c:/xampp/htdocs/parking/client_chat.php
include "config.php";
include "auth.php";
restrictToClient();

$clientId = (int)($_SESSION["client_id"] ?? 0);
$clientName = $_SESSION["client_name"] ?? "Client";
$user = $conn->query("SELECT name, plate_number, avatar FROM users WHERE id = $clientId")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Chat | SpotFinder</title>
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
        }
        .card {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-bottom: 0;
        }
        #chat-box::-webkit-scrollbar {
            width: 6px;
        }
        #chat-box::-webkit-scrollbar-track {
            background: transparent;
        }
        #chat-box::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
        }
        #chat-box::-webkit-scrollbar-thumb:hover {
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
        /* Client is right, admin is left */
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
        @media (max-width: 768px), (max-height: 700px) {
            .container { padding: 10px; }
            header { padding: 10px 15px; margin-bottom: 10px; border-radius: 12px; }
            .header-top h1 { font-size: 1.2rem; }
            .tagline { display: none; }
            .tabs-container { margin-bottom: 10px; }
            .tabs { padding: 4px; gap: 5px; }
            .tab-btn { padding: 8px 12px; font-size: 0.75rem; text-align: center; flex: 1; }
            .card { padding: 12px; border-radius: 16px; }
            .card-header { margin-bottom: 10px; }
            .card-title { font-size: 0.95rem; }
            #chat-box { padding: 10px; border-radius: 8px; }
        }
    </style>
</head>

<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <div class="container">
<?php include 'global_ai_assistant.php'; ?>
        <header>
            <div class="header-top">
                <div>
                    <h1><i class="fas fa-comments"></i> CHAT SUPPORT</h1>
                    <p class="tagline">Komunikasi langsung dengan admin</p>
                </div>
<?php
$cavatar = !empty($user['avatar']) ? $user['avatar'] . "?t=" . time() : "assets/img/default-avatar.png";
?>
                <div style="display:flex; align-items:center; gap:12px;">
                    <img src="<?= $cavatar ?>" style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:1px solid var(--accent-primary);">
                    <div style="text-align: right;">
                        <div style="font-size:0.8rem;color:var(--accent-primary);font-weight:600;">
                            <?= htmlspecialchars($clientName) ?> <?= isset($user["plate_number"]) ? "• " . htmlspecialchars($user["plate_number"]) : "" ?>
                        </div>
                        <a href="logout.php" style="font-size: 0.75rem; color: var(--danger); text-decoration: none;"><i
                                class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-house"></i> Public View</a>
                <a href="client_dashboard.php" class="tab-btn"><i class="fas fa-gauge-high"></i> My Account</a>
                <a href="client_chat.php" class="tab-btn active"><i class="fas fa-comments"></i> Chat</a>
                <a href="client_profile.php" class="tab-btn"><i class="fas fa-user-gear"></i> Profile</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2 class="card-title"><i class="fas fa-headset"></i> Chat dengan Admin</h2>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" class="btn btn-warning" style="padding:6px 12px;font-size:0.75rem;" onclick="loadChat(true)">
                        <i class="fas fa-rotate"></i> Refresh
                    </button>
                    <span style="width:7px;height:7px;background:#4ade80;border-radius:50%;display:inline-block;"></span>
                    <span style="font-size:0.75rem;color:#4ade80;">LIVE</span>
                </div>
            </div>

            <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; flex-shrink:0;">
                <button onclick="quickReport('Palang tidak mau terbuka, tolong buka manual!')" class="btn btn-warning"
                    style="padding:6px 12px; font-size:0.75rem;">
                    <i class="fas fa-exclamation-triangle"></i> Palang Macet
                </button>
                <button onclick="quickReport('Saldo saya habis, tolong bantu top-up')" class="btn btn-primary"
                    style="padding:6px 12px; font-size:0.75rem;">
                    <i class="fas fa-wallet"></i> Saldo Habis
                </button>
                <button onclick="quickReport('Butuh bantuan, ada masalah dengan parkir')" class="btn btn-danger"
                    style="padding:6px 12px; font-size:0.75rem;">
                    <i class="fas fa-life-ring"></i> Bantuan
                </button>
            </div>

            <div id="chat-box"
                style="flex:1; overflow-y:auto; background:#0b141a; border-radius:12px; padding:15px; margin-bottom:10px; display:flex; flex-direction:column; gap:6px; scroll-behavior:smooth;">
                <div style="text-align:center; color:var(--text-muted); font-size:0.82rem; padding:20px;">Loading chat...</div>
            </div>

            <div id="input-mode-text" style="display:flex; gap:8px; flex-shrink:0;">
                <input type="text" id="chat-input" class="form-control" placeholder="Ketik pesan ke admin..."
                    style="flex:1; margin-bottom:0; background:#202c33; color:#fff; border:none; border-radius:8px;" onkeypress="if(event.key==='Enter') sendTextMessage()">
                <button type="button" id="btn-send-text" class="btn btn-success" style="padding:10px 14px; border-radius:8px;" onclick="sendTextMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
                <button type="button" id="btn-record" class="btn btn-warning" style="padding:10px 12px; border-radius:8px;" onclick="startRecord()">
                    <i class="fas fa-microphone"></i>
                </button>
                <button type="button" id="btn-stop-record" class="btn btn-danger" style="padding:10px 12px; display:none; border-radius:8px;" onclick="stopRecord()">
                    <i class="fas fa-stop"></i>
                </button>
            </div>

            <div id="input-mode-audio" style="display:none; gap:8px; flex-shrink:0; align-items:center; background:#202c33; padding:6px 12px; border-radius:12px;">
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

    <script>
        let chatLastId = 0;
        let polling = false;
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

        function renderMsg(m) {
            const isMe = m.sender_type === "client";
            const alignClass = isMe ? "bubble-right" : "bubble-left";
            const icon = isMe ? "fa-user" : "fa-user-shield";
            const time = (m.created_at || "").substring(11, 16);
            const deleted = Number(m.is_deleted || 0) === 1;
            const canDelete = isMe && !deleted;
            const senderColor = isMe ? "#4ade80" : "#00e5ff";

            let rawMsg = m.message || "";
            let msgBody = "";
            
            if (m.message_type === "voice" && m.media_path && !deleted) {
                msgBody = `<audio controls preload="none" src="${escAttr(m.media_path)}" style="height:35px; width:220px; border-radius: 8px;"></audio>`;
            } else if (rawMsg.startsWith("[REQ_CANCEL:") && !deleted) {
                const parts = rawMsg.match(/\[REQ_CANCEL:(\d+)\](.*)/);
                const text = parts ? parts[2] : rawMsg;
                msgBody = `
                    <div style="background:rgba(239,68,68,0.1); border-left:4px solid #ef4444; padding:10px; border-radius:8px; margin:5px 0;">
                        <div style="font-weight:700; color:#ef4444; font-size:0.7rem;"><i class="fas fa-history"></i> PERMINTAAN PEMBATALAN DIKIRIM</div>
                        <div style="font-size:0.8rem; margin:5px 0;">${escHtml(text)}</div>
                        <div style="font-size:0.65rem; color:rgba(255,255,255,0.6);">Mohon tunggu konfirmasi admin...</div>
                    </div>
                `;
            } else if (rawMsg.startsWith("[CANCEL_CONFIRMED:") && !deleted) {
                 msgBody = `
                    <div style="background:rgba(139,92,246,0.1); border-left:4px solid #8b5cf6; padding:10px; border-radius:8px; margin:5px 0;">
                        <div style="font-weight:700; color:#8b5cf6; font-size:0.7rem;"><i class="fas fa-check-circle"></i> PEMBATALAN SUKSES</div>
                        <div style="font-size:0.8rem; margin:5px 0;">${escHtml(rawMsg.split(']')[1] || rawMsg)}</div>
                    </div>
                `;
            } else {
                msgBody = `<div style="${deleted ? "font-style:italic;color:rgba(255,255,255,0.5);" : ""}">${escHtml(rawMsg)}</div>`;
            }

            let deleteMenu = "";
            if (!deleted) {
                let forEveryoneHtml = isMe ? `<button onclick="deleteMsg(${Number(m.id)}, 'for_everyone')"><i class="fas fa-trash" style="color:#ef4444;"></i> Hapus untuk Semua</button>` : '';
                deleteMenu = `
                    <div class="dropdown" style="float:right;">
                        <i class="fas fa-chevron-down dropbtn" style="cursor:pointer; color:rgba(255,255,255,0.6); font-size:0.65rem;" onclick="toggleDropdown(${Number(m.id)})"></i>
                        <div id="dropdown-${Number(m.id)}" class="dropdown-content" style="${isMe ? 'right: 0; left: auto; min-width: 170px;' : 'left: 0; right: auto; min-width: 170px;'}">
                            <button onclick="deleteMsg(${Number(m.id)}, 'for_me')"><i class="fas fa-eye-slash"></i> Hapus untuk Saya</button>
                            ${forEveryoneHtml}
                        </div>
                    </div>
                `;
            }

            let ticks = "";
            if (isMe) {
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

        function loadChat(showMsg = false) {
            $.get("api_chat.php", function (data) {
                const messages = data.messages || [];
                if (!messages.length) {
                    $("#chat-box").html('<div style="text-align:center; color:var(--text-muted); font-size:0.82rem; padding:20px;">Belum ada pesan. Mulai chat dengan admin.</div>');
                    chatLastId = 0;
                    lastMessagesJSON = "[]";
                } else {
                    lastMessagesJSON = JSON.stringify(messages);
                    $("#chat-box").html(messages.map(renderMsg).join(""));
                    chatLastId = Number(data.latest_id || 0);
                    scrollChat();
                }
                if (showMsg) {
                    $("#record-status").text("Chat diperbarui.");
                    setTimeout(() => { if (!isRecording) $("#record-status").text(""); }, 1000);
                }
            }, "json");
        }

        function pollChat() {
            if (polling) return;
            polling = true;
            $.get("api_chat.php", function (data) {
                const messages = data.messages || [];
                const currentJSON = JSON.stringify(messages);
                if (currentJSON !== lastMessagesJSON && messages.length > 0) {
                    lastMessagesJSON = currentJSON;
                    const box = document.getElementById("chat-box");
                    const isAtBottom = box && (box.scrollHeight - box.scrollTop - box.clientHeight < 40);
                    
                    $("#chat-box").html(messages.map(renderMsg).join(""));
                    chatLastId = Number(data.latest_id || chatLastId);
                    
                    if (isAtBottom) scrollChat();
                }
            }, "json").always(function () {
                polling = false;
            });
        }

        function sendTextMessage() {
            const input = $("#chat-input");
            const msg = (input.val() || "").trim();
            if (!msg) return;
            input.val("");
            $.post("api_chat.php", {
                action: "send_text",
                message: msg
            }, function (res) {
                if (res.success) {
                    loadChat();
                } else {
                    alert(res.message || "Gagal kirim pesan");
                }
            }, "json");
        }

        function quickReport(msg) {
            $.post("api_chat.php", {
                action: "send_text",
                message: msg
            }, function (res) {
                if (res.success) {
                    loadChat();
                } else {
                    alert(res.message || "Gagal kirim laporan");
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
                    loadChat();
                } else {
                    alert(res.message || "Gagal menghapus pesan");
                }
            }, "json");
        }

        let currentAudioBlob = null;

        async function startRecord() {
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
            $("#chat-input").prop("disabled", false);
            $("#btn-send-text").prop("disabled", false);
        }

        function sendVN() {
            if (!currentAudioBlob) return;
            $("#record-status").text("Mengirim voice note...");
            $("#input-mode-audio").hide();
            $("#input-mode-text").css("display", "flex");
            sendVoiceBlob(currentAudioBlob);
            currentAudioBlob = null;
            $("#audio-preview").attr("src", "");
            $("#chat-input").prop("disabled", false);
            $("#btn-send-text").prop("disabled", false);
        }

        function sendVoiceBlob(blob) {
            const fd = new FormData();
            fd.append("action", "send_voice");
            fd.append("voice_note", blob, "voice_note.webm");

            $.ajax({
                url: "api_chat.php",
                method: "POST",
                data: fd,
                processData: false,
                contentType: false,
                dataType: "json"
            }).done(function (res) {
                if (res.success) {
                    $("#record-status").text("Voice note terkirim.");
                    loadChat();
                } else {
                    $("#record-status").text("");
                    alert(res.message || "Gagal kirim voice note.");
                }
            }).fail(function () {
                $("#record-status").text("");
                alert("Gagal upload voice note.");
            });
        }

        $(document).ready(function () {
            if (!canUseMediaRecorder()) {
                $("#btn-record").prop("disabled", true);
                $("#record-status").text("Voice note tidak didukung browser ini.");
            }
            loadChat();
            setInterval(pollChat, 1800);
        });
    </script>
</body>

</html>
