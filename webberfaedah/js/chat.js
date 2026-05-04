/* ============================================
   RUANG CURHAT ANONIM — Private Chat Logic
   Polling-based anonymous private chat room
   ============================================ */

const CHAT_API = 'api/chat.php';
const POLL_INTERVAL_MS = 3000;

let roomCode = null;
let myPersonaName = '';
let myPersonaAvatar = '';
let lastMessageId = 0;
let pollTimer = null;
let isCreator = false;
let chatClosed = false;

// ---- Init ----
document.addEventListener('DOMContentLoaded', async () => {
  const params = new URLSearchParams(window.location.search);
  roomCode = params.get('room');
  const inviteName = params.get('invite');

  if (!roomCode && !inviteName) {
    // No params — show error
    showError('Parameter room tidak ditemukan. Pastikan kamu membuka link yang benar.');
    return;
  }

  // If only invite param (creating a new room)
  if (!roomCode && inviteName) {
    await createRoom(decodeURIComponent(inviteName));
    return;
  }

  // Has roomCode — join room
  await joinRoom(roomCode);
});

// ---- Create Room (Inviter) ----
async function createRoom(inviteName) {
  try {
    const res = await fetch(`${CHAT_API}?action=get_or_create&invite_name=${encodeURIComponent(inviteName)}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Gagal membuat room');

    roomCode = data.room_code;
    isCreator = true;
    myPersonaName = data.persona_name;
    myPersonaAvatar = data.persona_avatar;

    // Redirect to the actual room URL (so link is shareable)
    const chatUrl = `${window.location.origin}${window.location.pathname}?room=${roomCode}`;
    history.replaceState({}, '', `?room=${roomCode}`);

    setupChatUI(data.creator_name, data.creator_avatar, inviteName);
    showInviteBanner(inviteName, chatUrl);
    startPolling();
    showApp();
  } catch (err) {
    showError(err.message);
  }
}

// ---- Join Room ----
async function joinRoom(code) {
  try {
    const res = await fetch(`${CHAT_API}?action=room_info&room_code=${code}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Room tidak ditemukan');

    isCreator = data.is_creator;
    myPersonaName = data.persona_name;
    myPersonaAvatar = data.persona_avatar;

    const displayName = isCreator ? data.creator_name : data.invite_name || 'Chat Privat';
    setupChatUI(displayName, isCreator ? data.creator_avatar : '💬', data.invite_name);

    if (isCreator) {
      const chatUrl = `${window.location.origin}${window.location.pathname}?room=${roomCode}`;
      showInviteBanner(data.invite_name, chatUrl);
    }

    // Load existing messages
    await pollMessages(true);
    startPolling();
    showApp();
  } catch (err) {
    showError(err.message);
  }
}

// ---- Setup Chat UI ----
function setupChatUI(roomName, roomAvatar, inviteName) {
  document.getElementById('chatRoomAvatar').textContent = roomAvatar || '💬';
  document.getElementById('chatRoomName').textContent = isCreator
    ? `Chat dengan ${inviteName || 'Teman Anonim'}`
    : 'Chat Privat Anonim';
  document.getElementById('myPersonaAvatar').textContent = myPersonaAvatar;
  document.getElementById('myPersonaName').textContent = myPersonaName;

  // Button handlers
  document.getElementById('chatSendBtn').addEventListener('click', sendMessage);
  document.getElementById('closeChatBtn').addEventListener('click', confirmCloseChat);
  document.getElementById('copyLinkBtn').addEventListener('click', copyRoomLink);
  document.getElementById('personaInfoBtn').addEventListener('click', () => {
    document.getElementById('privacyModal').style.display = 'flex';
  });
  document.getElementById('closePrivacyModal').addEventListener('click', () => {
    document.getElementById('privacyModal').style.display = 'none';
  });
  document.getElementById('closeInviteBtn')?.addEventListener('click', () => {
    document.getElementById('inviteBanner').style.display = 'none';
  });
  document.getElementById('copyInviteBtn')?.addEventListener('click', () => {
    const link = document.getElementById('inviteLinkText').textContent;
    navigator.clipboard.writeText(link).then(() => showToast('🔗 Link disalin!'));
  });
  document.getElementById('copyLinkBtn').addEventListener('click', copyRoomLink);

  // Chat input
  const input = document.getElementById('chatInput');
  input.addEventListener('input', () => {
    autoResizeTextarea(input);
    updateCharCount(input.value.length);
  });
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  // Click outside modal
  document.getElementById('privacyModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('privacyModal')) {
      document.getElementById('privacyModal').style.display = 'none';
    }
  });
}

// ---- Show Invite Banner ----
function showInviteBanner(inviteName, chatUrl) {
  const banner = document.getElementById('inviteBanner');
  const targetName = document.getElementById('inviteTargetName');
  const linkText = document.getElementById('inviteLinkText');
  if (banner) {
    banner.style.display = 'flex';
    if (targetName) targetName.textContent = inviteName || 'teman anonim';
    if (linkText) linkText.textContent = chatUrl;
  }
}

// ---- Poll Messages ----
async function pollMessages(initial = false) {
  if (chatClosed) return;
  try {
    const res = await fetch(`${CHAT_API}?action=messages&room_code=${roomCode}&since=${lastMessageId}`);
    const data = await res.json();
    if (!res.ok) {
      if (res.status === 404) {
        chatClosed = true;
        stopPolling();
        showRoomClosed();
        return;
      }
      return;
    }

    if (data.messages && data.messages.length > 0) {
      data.messages.forEach(msg => renderMessage(msg));
      lastMessageId = data.messages[data.messages.length - 1].id;
      scrollToBottom(initial ? 'instant' : 'smooth');
    }

    // Update status
    document.getElementById('chatRoomStatus').innerHTML = '<span class="status-dot"></span> Aktif';
  } catch (err) {
    // Network error — show offline indicator
    document.getElementById('chatRoomStatus').innerHTML = '<span class="status-dot offline"></span> Koneksi terputus';
  }
}

// ---- Start/Stop Polling ----
function startPolling() {
  pollTimer = setInterval(() => pollMessages(), POLL_INTERVAL_MS);
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

// ---- Send Message ----
async function sendMessage() {
  const input = document.getElementById('chatInput');
  const message = input.value.trim();
  if (!message || chatClosed) return;

  const sendBtn = document.getElementById('chatSendBtn');
  sendBtn.disabled = true;
  input.value = '';
  autoResizeTextarea(input);
  updateCharCount(0);

  // Optimistic render
  const tempId = 'temp_' + Date.now();
  renderMessage({
    id: tempId,
    sender_name: myPersonaName,
    sender_avatar: myPersonaAvatar,
    message: message,
    time_ago: 'Baru saja',
    is_own: true,
    pending: true
  });
  scrollToBottom('smooth');

  try {
    const res = await fetch(CHAT_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'send', room_code: roomCode, message })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Gagal mengirim');

    // Update temp message with real ID
    const tempEl = document.querySelector(`[data-temp-id="${tempId}"]`);
    if (tempEl && data.message) {
      tempEl.classList.remove('pending');
      tempEl.dataset.tempId = '';
      lastMessageId = Math.max(lastMessageId, data.message.id);
    }
  } catch (err) {
    showToast('❌ ' + err.message);
    // Remove failed temp message
    const tempEl = document.querySelector(`[data-temp-id="${tempId}"]`);
    if (tempEl) tempEl.remove();
    input.value = message;
    autoResizeTextarea(input);
  } finally {
    sendBtn.disabled = false;
    input.focus();
  }
}

// ---- Render Message ----
function renderMessage(msg) {
  // Skip if already rendered (by ID)
  if (msg.id && !String(msg.id).startsWith('temp_')) {
    if (document.querySelector(`[data-msg-id="${msg.id}"]`)) return;
  }

  const messages = document.getElementById('chatMessages');
  const isOwn = msg.is_own;
  const tempAttr = msg.pending ? `data-temp-id="${msg.id}"` : `data-msg-id="${msg.id}"`;

  const el = document.createElement('div');
  el.className = `chat-message ${isOwn ? 'own' : 'other'} ${msg.pending ? 'pending' : ''}`;
  el.setAttribute('data-temp-id', msg.pending ? msg.id : '');
  el.setAttribute('data-msg-id', !msg.pending ? msg.id : '');

  el.innerHTML = `
    ${!isOwn ? `<div class="msg-avatar">${msg.sender_avatar}</div>` : ''}
    <div class="msg-content">
      ${!isOwn ? `<div class="msg-sender">${msg.sender_name}</div>` : ''}
      <div class="msg-bubble">${escapeHTML(msg.message)}</div>
      <div class="msg-time">${msg.time_ago} ${isOwn ? (msg.pending ? '⏳' : '✓') : ''}</div>
    </div>
    ${isOwn ? `<div class="msg-avatar own-avatar">${msg.sender_avatar}</div>` : ''}
  `;

  messages.appendChild(el);
}

// ---- Show Room Closed ----
function showRoomClosed() {
  const messages = document.getElementById('chatMessages');
  const el = document.createElement('div');
  el.className = 'chat-system-msg';
  el.innerHTML = '🔒 Chat room ini telah ditutup.';
  messages.appendChild(el);
  document.getElementById('chatInput').disabled = true;
  document.getElementById('chatSendBtn').disabled = true;
}

// ---- Close Chat ----
function confirmCloseChat() {
  if (!isCreator) {
    showToast('ℹ️ Hanya pembuat room yang bisa menutup chat.');
    return;
  }
  if (!confirm('Tutup chat room ini? Semua pesan akan terhapus setelah 24 jam dan tidak ada yang bisa join lagi.')) return;

  fetch(`${CHAT_API}?action=close&room_code=${roomCode}`)
    .then(() => {
      stopPolling();
      chatClosed = true;
      showToast('✅ Chat room ditutup.');
      setTimeout(() => window.location.href = 'index.html', 1500);
    })
    .catch(() => showToast('❌ Gagal menutup room.'));
}

// ---- Copy Room Link ----
function copyRoomLink() {
  const link = window.location.href;
  navigator.clipboard.writeText(link)
    .then(() => showToast('🔗 Link chat disalin!'))
    .catch(() => {
      prompt('Salin link ini:', link);
    });
}

// ---- Helpers ----
function showApp() {
  document.getElementById('chatLoading').style.display = 'none';
  document.getElementById('chatApp').style.display = 'flex';
}

function showError(msg) {
  document.getElementById('chatLoading').style.display = 'none';
  document.getElementById('chatError').style.display = 'flex';
  document.getElementById('chatErrorMsg').textContent = msg;
}

function scrollToBottom(behavior = 'smooth') {
  const messages = document.getElementById('chatMessages');
  messages.scrollTo({ top: messages.scrollHeight, behavior });
}

function autoResizeTextarea(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function updateCharCount(len) {
  const el = document.getElementById('chatCharCount');
  if (el) el.textContent = `${len}/1000`;
}

function escapeHTML(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(str));
  return div.innerHTML.replace(/\n/g, '<br>');
}

function showToast(message) {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// Page unload cleanup
window.addEventListener('beforeunload', () => stopPolling());
