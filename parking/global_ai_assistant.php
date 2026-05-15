<?php
// global_ai_assistant.php
// Include this file in any page where you want the AI Assistant to be active.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_admin_page = (isset($isAdmin) && $isAdmin) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$current_client_id = $_SESSION['client_id'] ?? null;
$current_client_name = $_SESSION['client_name'] ?? $_SESSION['name'] ?? 'User';
?>

<!-- AI ASSISTANT GLOBAL STYLES -->
<style>
    /* AI Orb Styles */
    .ai-orb-container-global {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        pointer-events: none;
    }
    .ai-orb-global {
        width: 60px;
        height: 60px;
        background: radial-gradient(circle at 30% 30%, #00e5ff, #004e92);
        border-radius: 50%;
        box-shadow: 0 0 20px rgba(0, 229, 255, 0.5), inset 0 0 10px rgba(255, 255, 255, 0.5);
        position: relative;
        transition: all 0.3s ease;
        pointer-events: auto;
        cursor: pointer;
    }
    .ai-orb-global::after {
        content: '';
        position: absolute;
        top: -5px; left: -5px; right: -5px; bottom: -5px;
        border: 2px solid #00e5ff;
        border-radius: 50%;
        opacity: 0.3;
        animation: orb-pulse 2s infinite;
    }
    .ai-orb-global.speaking {
        animation: orb-speaking 0.5s infinite alternate;
        box-shadow: 0 0 40px #00e5ff;
    }
    @keyframes orb-pulse {
        0% { transform: scale(1); opacity: 0.3; }
        50% { transform: scale(1.2); opacity: 0.1; }
        100% { transform: scale(1); opacity: 0.3; }
    }
    @keyframes orb-speaking {
        from { transform: scale(1); filter: brightness(1); }
        to { transform: scale(1.1); filter: brightness(1.5); }
    }

    /* Emoji Flyer */
    .emoji-flyer {
        position: fixed;
        pointer-events: none;
        z-index: 10000;
        font-size: 2rem;
        user-select: none;
        filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));
    }
    @keyframes emoji-burst {
        0% { transform: translate(0, 0) scale(0) rotate(0deg); opacity: 0; }
        20% { transform: translate(var(--tx), var(--ty)) scale(1.5) rotate(45deg); opacity: 1; }
        100% { transform: translate(calc(var(--tx) * 1.5), calc(var(--ty) * 1.5 + 200px)) scale(1) rotate(180deg); opacity: 0; }
    }

    /* Notif Overlay for Welcome/Goodbye */
    .notif-overlay-global {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(15px);
        z-index: 10001;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: notifFadeIn 0.5s ease;
    }
    .notif-card-global {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 40px;
        border-radius: 30px;
        text-align: center;
        max-width: 500px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
    @keyframes notifFadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* Chat Notification Badge */
    .chat-badge-global {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 0.6rem;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        animation: badge-pop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        z-index: 10;
    }
    @keyframes badge-pop {
        0% { transform: scale(0); }
        100% { transform: scale(1); }
    }
</style>

<div class="ai-orb-container-global">
    <div id="ai-status-text-global" style="font-size: 0.65rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; opacity: 0;">Sistem Berbicara...</div>
    <div id="ai-orb-global" class="ai-orb-global" title="AI Assistant - Klik untuk test suara"></div>
</div>

<script>
    const G_IS_ADMIN = <?= json_encode($is_admin_page) ?>;
    const G_CLIENT_ID = <?= json_encode($current_client_id) ?>;
    const G_CLIENT_NAME = <?= json_encode($current_client_name) ?>;
    let G_LAST_KNOWN_ID = 0;
    let G_AUDIO_UNLOCKED = false;
    let G_ELEVENLABS_DISABLED_UNTIL = 0;

    function triggerEmojiAnimation(type, isBurst = false) {
        const container = document.body;
        let emojis = ['✨', '⭐'];
        if (type === 'welcome') emojis = ['👋', '😊', '✨', '🚗', '🅿️', '🎉'];
        if (type === 'thanks') emojis = ['🙏', '💖', '✅', '🌟', '🚗', '💨'];
        if (type === 'money') emojis = ['💰', '💎', '🚀', '💳'];
        if (type === 'alert') emojis = ['⚠️', '❗', '🔔', '🔥'];

        const count = isBurst ? 30 : 12;
        for (let i = 0; i < count; i++) {
            setTimeout(() => {
                const el = document.createElement('div');
                el.className = 'emoji-flyer';
                el.innerText = emojis[Math.floor(Math.random() * emojis.length)];
                
                const angle = Math.random() * Math.PI * 2;
                const velocity = 15 + Math.random() * 25;
                const vx = Math.cos(angle) * velocity;
                const vy = Math.sin(angle) * velocity;
                
                el.style.left = '50%';
                el.style.top = '50%';
                el.style.setProperty('--tx', (vx * 15) + 'px');
                el.style.setProperty('--ty', (vy * 15 - 100) + 'px');
                el.style.animation = `emoji-burst 3s ease-out forwards`;
                
                container.appendChild(el);
                setTimeout(() => el.remove(), 3000);
            }, i * 50);
        }
    }





    function pickBrowserVoice() {
        if (!('speechSynthesis' in window)) return null;

        const voices = window.speechSynthesis.getVoices();
        return voices.find(v => v.lang === 'id-ID') ||
            voices.find(v => v.lang && v.lang.toLowerCase().startsWith('id')) ||
            voices.find(v => /indonesia|bahasa/i.test(v.name)) ||
            voices.find(v => v.lang && v.lang.toLowerCase().startsWith('en')) ||
            voices[0] ||
            null;
    }

    function speakWithBrowserVoice(text) {
        return new Promise((resolve) => {
            if (!text || text.trim() === "" || !('speechSynthesis' in window)) {
                resolve(false);
                return;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.95;
            utterance.pitch = 0.95;
            utterance.volume = 1;

            const voice = pickBrowserVoice();
            if (voice) utterance.voice = voice;

            let finished = false;
            const done = (played) => {
                if (finished) return;
                finished = true;
                resolve(played);
            };

            utterance.onend = () => done(true);
            utterance.onerror = () => done(false);

            try {
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
                setTimeout(() => done(true), Math.max(2500, text.length * 90));
            } catch (e) {
                console.warn('Browser voice failed:', e);
                done(false);
            }
        });
    }

    async function playElevenLabsTts(text, voiceId = '9zOaLLJKBwYOwr8bOPDj') {
        if (!text || text.trim() === "") return false;
        if (Date.now() < G_ELEVENLABS_DISABLED_UNTIL) return false;

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000);
            const response = await fetch('api_elevenlabs_tts.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text, voice_id: voiceId }),
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const contentType = response.headers.get('Content-Type') || '';
            if (contentType.includes('application/json')) {
                const payload = await response.json().catch(() => ({}));
                if (payload && payload.success === false) {
                    G_ELEVENLABS_DISABLED_UNTIL = Date.now() + 10 * 60 * 1000;
                    console.warn('ElevenLabs TTS unavailable, using browser voice:', payload.message || payload.reason || response.status);
                    return false;
                }
            }

            if (!response.ok) {
                console.warn('ElevenLabs TTS failed:', response.status, await response.text());
                G_ELEVENLABS_DISABLED_UNTIL = Date.now() + 2 * 60 * 1000;
                return false;
            }

            const audioBlob = await response.blob();
            if (!audioBlob || audioBlob.size === 0) return false;

            const audioUrl = URL.createObjectURL(audioBlob);
            const audio = new Audio(audioUrl);
            return await new Promise((resolve) => {
                audio.onended = () => {
                    URL.revokeObjectURL(audioUrl);
                    resolve(true);
                };
                audio.onerror = () => {
                    URL.revokeObjectURL(audioUrl);
                    resolve(false);
                };
                audio.play().catch(() => {
                    URL.revokeObjectURL(audioUrl);
                    resolve(false);
                });
            });
        } catch (e) {
            console.warn('ElevenLabs TTS fallback:', e);
            G_ELEVENLABS_DISABLED_UNTIL = Date.now() + 2 * 60 * 1000;
            return false;
        }
    }

    async function playAiVoiceTts(text) {
        const playedWithElevenLabs = await playElevenLabsTts(text, '9zOaLLJKBwYOwr8bOPDj');
        if (playedWithElevenLabs) return true;
        return await speakWithBrowserVoice(text);
    }



    function speakText(text) {
        if (!text || text.trim() === "") return;
        
        console.log("Global AI Speaking: " + text);
        const orb = document.getElementById('ai-orb-global');
        const statusText = document.getElementById('ai-status-text-global');

        // Detect type for emojis
        let type = 'system';
        if (text.toLowerCase().includes('selamat datang')) type = 'welcome';
        if (text.toLowerCase().includes('terima kasih') || text.toLowerCase().includes('sampai jumpa')) type = 'thanks';
        if (text.toLowerCase().includes('saldo') || text.toLowerCase().includes('top up')) type = 'money';
        if (text.toLowerCase().includes('peringatan') || text.toLowerCase().includes('bahaya')) type = 'alert';
        
        triggerEmojiAnimation(type, type === 'welcome' || type === 'thanks');

        const startSpeaking = () => {
            orb.classList.add('speaking'); 
            statusText.style.opacity = '1';
        };
        const stopSpeaking = () => {
            orb.classList.remove('speaking'); 
            statusText.style.opacity = '0';
        };

        startSpeaking();
        playAiVoiceTts(text).then((played) => {
            stopSpeaking();
        });
    }

    let G_AUDIO_CTX = null;

    function unlockAudio() {
        if (G_AUDIO_UNLOCKED) return;
        
        try {
            G_AUDIO_CTX = new (window.AudioContext || window.webkitAudioContext)();
            const osc = G_AUDIO_CTX.createOscillator();
            const gain = G_AUDIO_CTX.createGain();
            gain.gain.value = 0;
            osc.connect(gain);
            gain.connect(G_AUDIO_CTX.destination);
            osc.start();
            osc.stop(G_AUDIO_CTX.currentTime + 0.01);
        } catch(e) {}

        G_AUDIO_UNLOCKED = true;
        console.log("Global AI Audio Unlocked.");
    }


    function showGlobalNotification(type, name, customMessage = "") {
        const isWelcome = type === 'welcome';
        if (isWelcome) {
            sessionStorage.setItem('sf_notif_shown', 'yes');
        } else {
            sessionStorage.removeItem('sf_notif_shown');
        }
        const title = isWelcome ? 'Selamat Datang!' : 'Selamat Jalan!';
        const icon = isWelcome ? 'fa-car-side' : 'fa-hand-peace';
        const subtitle = isWelcome 
            ? `Halo <strong>${name}</strong>,<br>Selamat datang di SpotFinder!`
            : `Terima kasih <strong>${name}</strong>,<br>Sampai jumpa kembali!`;

        const overlay = document.createElement('div');
        overlay.className = 'notif-overlay-global';
        overlay.innerHTML = `
            <div class="notif-card-global ${type}" style="border-top: 5px solid ${isWelcome ? '#4ade80' : '#f87171'};">
                <div style="font-size:3rem; color:${isWelcome ? '#4ade80' : '#f87171'}; margin-bottom:20px;"><i class="fas ${icon}"></i></div>
                <div style="font-size:1.8rem; font-weight:800; color:#fff; margin-bottom:10px;">${title}</div>
                <div style="font-size:1rem; color:#94a3b8; line-height:1.5;">${subtitle}</div>
                <div style="margin-top:15px; font-size:0.85rem; font-weight:bold; color:#00e5ff;">${customMessage}</div>
                <div style="margin-top:25px; height:4px; background:rgba(255,255,255,0.1); border-radius:2px; overflow:hidden;">
                    <div style="width:100%; height:100%; background:${isWelcome ? '#4ade80' : '#f87171'}; animation: progress 4s linear forwards;"></div>
                </div>
            </div>
            <style>
                @keyframes progress { from { width: 100%; } to { width: 0%; } }
            </style>
        `;
        document.body.appendChild(overlay);
        
        let voiceText = isWelcome ? `Selamat datang ${name}. Silahkan parkir.` : `Terima kasih ${name}. Sampai jumpa kembali!`;
        if (customMessage) {
            const normalizedMessage = customMessage.toLowerCase();
            const messageMatchesType = isWelcome
                ? !normalizedMessage.includes('selamat jalan') && !normalizedMessage.includes('terima kasih')
                : !normalizedMessage.includes('selamat datang');
            if (messageMatchesType) voiceText = customMessage;
        }
        
        speakText(voiceText);

        setTimeout(() => {
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                overlay.remove();
                if (window.location.pathname.includes('dashboard')) location.reload();
            }, 500);
        }, 4500);
    }

    function checkGlobalEvents() {
        $.get("get_status.php", function(data) {
            if (G_LAST_KNOWN_ID === 0) {
                G_LAST_KNOWN_ID = data.latest_id;
                return;
            }

            if (data.latest_id > G_LAST_KNOWN_ID) {
                const newEvent = data.history[0];
                if (newEvent) {
                    if (G_IS_ADMIN) {
                        let text = "";
                        if (newEvent.action === "IN") text = `Sistem: Kendaraan ${newEvent.name} telah masuk.`;
                        else if (newEvent.action === "OUT") text = `Sistem: Kendaraan ${newEvent.name} telah keluar.`;
                        else if (newEvent.action === "TOPUP") text = `Pemberitahuan: Top up saldo ${newEvent.name} berhasil.`;
                        if (text) speakText(text);
                    } else if (newEvent.name === G_CLIENT_NAME) {
                        if (newEvent.action === "IN") {
                            showGlobalNotification('welcome', G_CLIENT_NAME, newEvent.ai_message);
                        } else if (newEvent.action === "OUT") {
                            showGlobalNotification('goodbye', G_CLIENT_NAME, newEvent.ai_message);
                        } else if (newEvent.action === "BOOK") {
                            speakText(`Booking Bay ${newEvent.slot_id || ''} berhasil. Slot Anda sudah diamankan.`);
                            triggerEmojiAnimation('money', false);
                        } else if (newEvent.action === "CANCEL") {
                            speakText("Booking dibatalkan. Saldo booking sudah dikembalikan.");
                        }
                    }
                }
                G_LAST_KNOWN_ID = data.latest_id;
            }
        }, "json");
    }

    function playChatNotificationSound() {
        playAiVoiceTts("Ada pesan baru.");
    }

    let lastUnreadCount = 0;
    function checkUnreadChat() {
        $.get("api_get_unread_chat.php", function(res) {
            if (res.success) {
                const count = parseInt(res.unread_count);
                const chatTabs = document.querySelectorAll('a[href="admin_chat.php"], a[href="client_chat.php"]');
                
                if (count > 0) {
                    if (count > lastUnreadCount) {
                        playChatNotificationSound();
                    }
                    
                    chatTabs.forEach(tab => {
                        tab.style.position = 'relative';
                        let badge = tab.querySelector('.chat-badge-global');
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'chat-badge-global';
                            tab.appendChild(badge);
                        }
                        badge.innerText = count > 99 ? '99+' : count;
                    });
                } else {
                    chatTabs.forEach(tab => {
                        const badge = tab.querySelector('.chat-badge-global');
                        if (badge) badge.remove();
                    });
                }
                lastUnreadCount = count;
            }
        }, "json");
    }

    $(document).ready(function() {
        $(document).one('click touchstart', unlockAudio);
        document.getElementById('ai-orb-global').onclick = function() {
            unlockAudio();
            speakText("Halo " + G_CLIENT_NAME + ", asisten AI SpotFinder siap membantu Anda di halaman mana pun!");
        };

        setInterval(checkGlobalEvents, 2000);
        setInterval(checkUnreadChat, 5000);
        checkUnreadChat(); // Check immediately on load
    });
</script>
