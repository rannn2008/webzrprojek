/* ============================================
   RUANG CURHAT ANONIM — Application Logic
   Full Feature Version: Dark Mode, Search, Bookmark,
   Share, Report, Crisis Detection, Virtual Hug,
   AI Badge, Mood Widget, Private Chat Invite
   ============================================ */

const API_BASE = 'api';

// ---- Constants ----
const CATEGORIES = [
  { id: 'all', label: 'Semua', emoji: '✨' },
  { id: 'school', label: 'Sekolah & Pendidikan', emoji: '📚' },
  { id: 'friendship', label: 'Persahabatan', emoji: '🤝' },
  { id: 'family', label: 'Keluarga', emoji: '👨‍👩‍👧‍👦' },
  { id: 'life', label: 'Masalah Hidup', emoji: '🌊' },
  { id: 'motivation', label: 'Motivasi', emoji: '🔥' },
  { id: 'funny', label: 'Cerita Lucu', emoji: '😂' }
];

const MOODS = [
  { id: 'sad', label: 'Sedih', emoji: '😢' },
  { id: 'confused', label: 'Bingung', emoji: '😕' },
  { id: 'happy', label: 'Senang', emoji: '😊' },
  { id: 'angry', label: 'Marah', emoji: '😠' },
  { id: 'stressed', label: 'Stres', emoji: '😰' }
];

const CRISIS_KEYWORDS = [
  'bunuh diri', 'mau mati', 'ingin mati', 'tidak mau hidup', 'nggak mau hidup',
  'mengakhiri hidup', 'akhiri hidup', 'tidak ada gunanya hidup', 'lebih baik mati',
  'pengen mati', 'pingin mati', 'udah nggak kuat', 'nggak kuat lagi', 'menyakiti diri'
];

// ---- API Helpers ----
async function apiGet(endpoint) {
  const res = await fetch(`${API_BASE}/${endpoint}`);
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error || 'Gagal memuat data.');
  }
  return res.json();
}

async function apiPost(endpoint, data) {
  const res = await fetch(`${API_BASE}/${endpoint}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || 'Terjadi kesalahan.');
  return json;
}

// ---- Utility ----
function truncateText(text, maxLength) {
  if (text.length <= maxLength) return text;
  return text.substr(0, maxLength).trim() + '...';
}
function getMoodInfo(moodId) { return MOODS.find(m => m.id === moodId) || MOODS[0]; }
function getCategoryInfo(catId) { return CATEGORIES.find(c => c.id === catId) || CATEGORIES[0]; }
function formatNumber(num) {
  num = parseInt(num) || 0;
  if (num >= 1000) return (num / 1000).toFixed(1) + 'rb';
  return num.toString();
}

// ---- Dark Mode ----
function initDarkMode() {
  const saved = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);

  // Insert toggle button in header
  const headerRight = document.querySelector('.header-inner');
  if (!headerRight) return;

  const btn = document.createElement('button');
  btn.className = 'dark-mode-btn';
  btn.id = 'darkModeBtn';
  btn.setAttribute('aria-label', 'Toggle dark mode');
  btn.textContent = saved === 'dark' ? '☀️' : '🌙';

  const writeBtn = headerRight.querySelector('.btn-write');
  if (writeBtn) {
    headerRight.insertBefore(btn, writeBtn);
  } else {
    const mobileBtn = headerRight.querySelector('.mobile-menu-btn');
    if (mobileBtn) headerRight.insertBefore(btn, mobileBtn);
    else headerRight.appendChild(btn);
  }

  btn.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    btn.textContent = next === 'dark' ? '☀️' : '🌙';
  });
}

// ---- Bookmarks ----
function getBookmarks() {
  try { return JSON.parse(localStorage.getItem('bookmarks') || '[]'); } catch { return []; }
}
function toggleBookmark(storyId, title) {
  let bookmarks = getBookmarks();
  const idx = bookmarks.findIndex(b => b.id === storyId);
  if (idx >= 0) {
    bookmarks.splice(idx, 1);
    showToast('🔖 Bookmark dihapus.');
  } else {
    bookmarks.push({ id: storyId, title, savedAt: Date.now() });
    showToast('🔖 Cerita disimpan ke bookmark!');
  }
  localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
  return idx < 0; // returns true if newly bookmarked
}
function isBookmarked(storyId) {
  return getBookmarks().some(b => b.id === storyId);
}

// ---- AI Story Trigger ----
async function triggerAIStoryIfNeeded() {
  try {
    const res = await fetch(`${API_BASE}/ai_story_generator.php?trigger=1`);
  } catch (e) { /* silent fail */ }
}

// ---- Rendering ----
function renderStoryCard(s) {
  const mood = getMoodInfo(s.mood);
  const cat = getCategoryInfo(s.category);
  const relate = parseInt(s.relate_count) || 0;
  const support = parseInt(s.support_count) || 0;
  const helpful = parseInt(s.helpful_count) || 0;
  const commentCount = parseInt(s.comment_count) || 0;
  const readMin = parseInt(s.read_minutes) || 1;
  const bookmarked = isBookmarked(s.id);
  const isAI = parseInt(s.is_ai_generated) === 1;

  return `
    <article class="story-card" onclick="navigateToStory(${s.id})">
      <button class="bookmark-btn ${bookmarked ? 'bookmarked' : ''}" onclick="handleBookmark(event, ${s.id}, '${s.title.replace(/'/g, "\\'")}')" title="${bookmarked ? 'Hapus bookmark' : 'Simpan cerita'}">
        ${bookmarked ? '🔖' : '🔗'}
      </button>
      <div class="story-card-header">
        <div class="story-meta">
          <div class="avatar-anon">${s.anon_avatar}</div>
          <div>
            <div class="story-author">${s.anon_name}${isAI ? '<span class="ai-badge">🤖 AI</span>' : ''}</div>
            <div class="story-time">${s.time_ago}</div>
          </div>
        </div>
        <span class="mood-badge mood-${s.mood}">${mood.emoji} ${mood.label}</span>
      </div>
      <span class="story-category cat-${s.category}">${cat.emoji} ${cat.label}</span>
      <h3 class="story-title">${truncateText(s.title, 80)}</h3>
      <p class="story-excerpt">${truncateText(s.content, 150)}</p>
      <div class="story-footer">
        <div class="reactions-mini">
          <span class="reaction-mini">🤝 ${formatNumber(relate)}</span>
          <span class="reaction-mini">💕 ${formatNumber(support)}</span>
          <span class="reaction-mini">💡 ${formatNumber(helpful)}</span>
        </div>
        <span class="read-time-badge">🕐 ${readMin} mnt</span>
        <span class="comment-count">💬 ${commentCount}</span>
      </div>
    </article>
  `;
}

function handleBookmark(event, storyId, title) {
  event.stopPropagation();
  const btn = event.currentTarget;
  const nowBookmarked = toggleBookmark(storyId, title);
  btn.textContent = nowBookmarked ? '🔖' : '🔗';
  btn.classList.toggle('bookmarked', nowBookmarked);
}

function renderTrendingItem(s, rank) {
  const relate = parseInt(s.relate_count) || 0;
  const support = parseInt(s.support_count) || 0;
  const commentCount = parseInt(s.comment_count) || 0;
  return `
    <a href="story.html?id=${s.id}" class="trending-item">
      <span class="trending-rank">${String(rank).padStart(2, '0')}</span>
      <div class="trending-content">
        <h4>${truncateText(s.title, 60)}</h4>
        <div class="trending-stats">
          <span>🤝 ${formatNumber(relate)}</span>
          <span>💕 ${formatNumber(support)}</span>
          <span>💬 ${commentCount} komentar</span>
        </div>
      </div>
    </a>
  `;
}

// ---- Mood Community Widget ----
async function initMoodWidget() {
  try {
    const data = await apiGet('stories.php?sort=recent&limit=50');
    const counts = { sad: 0, confused: 0, happy: 0, angry: 0, stressed: 0 };
    data.stories.forEach(s => { if (counts[s.mood] !== undefined) counts[s.mood]++; });

    const total = Object.values(counts).reduce((a, b) => a + b, 0) || 1;
    const container = document.getElementById('moodWidget');
    if (!container) return;

    const moodLabels = { sad: '😢 Sedih', confused: '😕 Bingung', happy: '😊 Senang', angry: '😠 Marah', stressed: '😰 Stres' };
    container.innerHTML = `
      <div class="mood-community-widget">
        <div class="mood-widget-title">📊 Mood Komunitas Hari Ini</div>
        <div class="mood-bars">
          ${Object.entries(counts).map(([mood, cnt]) => {
            const pct = Math.round((cnt / total) * 100);
            return `
              <div class="mood-bar-row ${mood}">
                <span class="mood-bar-label">${moodLabels[mood]}</span>
                <div class="mood-bar-track">
                  <div class="mood-bar-fill" style="width:${pct}%"></div>
                </div>
                <span class="mood-bar-pct">${pct}%</span>
              </div>`;
          }).join('')}
        </div>
      </div>`;
  } catch (e) { /* silent */ }
}

// ---- Page: Homepage ----
async function initHomepage() {
  let activeCategory = 'all';
  let searchQuery = '';

  // Trigger AI story generation in background
  triggerAIStoryIfNeeded();

  // Mood widget
  initMoodWidget();

  // Search bar
  const storiesSection = document.getElementById('stories');
  if (storiesSection) {
    const sectionHeader = storiesSection.querySelector('.section-header');
    if (sectionHeader) {
      const searchWrapper = document.createElement('div');
      searchWrapper.className = 'search-bar-wrapper';
      searchWrapper.innerHTML = `
        <span class="search-bar-icon">🔍</span>
        <input type="text" class="search-bar-input" id="searchInput" placeholder="Cari cerita...">
        <button class="search-bar-clear" id="searchClear">✕</button>
      `;
      sectionHeader.after(searchWrapper);

      const searchInput = document.getElementById('searchInput');
      const searchClear = document.getElementById('searchClear');
      searchInput?.addEventListener('input', () => {
        searchQuery = searchInput.value.trim();
        searchClear?.classList.toggle('visible', searchQuery.length > 0);
        renderFeed();
      });
      searchClear?.addEventListener('click', () => {
        searchInput.value = '';
        searchQuery = '';
        searchClear.classList.remove('visible');
        renderFeed();
      });
    }
  }

  // Mood widget placeholder
  const storiesEl = document.querySelector('.section#stories .container');
  if (storiesEl) {
    const widgetDiv = document.createElement('div');
    widgetDiv.id = 'moodWidget';
    storiesEl.prepend(widgetDiv);
  }

  // Category pills
  const catBar = document.getElementById('categoryBar');
  if (catBar) {
    catBar.innerHTML = CATEGORIES.map(c =>
      `<button class="cat-pill ${c.id === 'all' ? 'active' : ''}" data-cat="${c.id}">
        <span class="emoji">${c.emoji}</span> ${c.label}
      </button>`
    ).join('');

    catBar.addEventListener('click', (e) => {
      const pill = e.target.closest('.cat-pill');
      if (!pill) return;
      activeCategory = pill.dataset.cat;
      catBar.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      renderFeed();
    });
  }

  // Load trending
  const trendingList = document.getElementById('trendingList');
  if (trendingList) {
    try {
      const data = await apiGet('stories.php?sort=trending&limit=5');
      trendingList.innerHTML = data.stories.map((s, i) => renderTrendingItem(s, i + 1)).join('');
    } catch (e) {
      trendingList.innerHTML = '<p style="padding:1rem;color:var(--text-muted);">Gagal memuat trending.</p>';
    }
  }

  // Load stats
  try {
    const stats = await apiGet('stats.php');
    const statEls = document.querySelectorAll('.stat-number');
    if (statEls.length >= 3) {
      animateCounter(statEls[0], stats.stories);
      animateCounter(statEls[1], stats.reactions);
      animateCounter(statEls[2], stats.comments);
    }
  } catch (e) { /* ignore */ }

  // Feed
  async function renderFeed() {
    const grid = document.getElementById('storiesGrid');
    if (!grid) return;
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;"><div class="skeleton skeleton-title" style="margin:0 auto;"></div></div>';

    try {
      const catParam = activeCategory !== 'all' ? `&category=${activeCategory}` : '';
      const searchParam = searchQuery ? `&search=${encodeURIComponent(searchQuery)}` : '';
      const data = await apiGet(`stories.php?sort=recent&limit=12${catParam}${searchParam}`);

      let stories = data.stories;

      // Client-side search filter fallback
      if (searchQuery && stories.length > 0) {
        const q = searchQuery.toLowerCase();
        stories = stories.filter(s =>
          s.title.toLowerCase().includes(q) || s.content.toLowerCase().includes(q)
        );
      }

      if (stories.length === 0) {
        grid.innerHTML = `
          <div class="empty-state" style="grid-column:1/-1;">
            <div class="icon">${searchQuery ? '🔍' : '📭'}</div>
            <h3>${searchQuery ? `Tidak ada cerita untuk "${searchQuery}"` : 'Belum ada cerita di kategori ini'}</h3>
            <p>${searchQuery ? 'Coba kata kunci lain.' : 'Jadilah yang pertama berbagi cerita!'}</p>
            <a href="post.html" class="btn btn-primary">✍️ Tulis Cerita</a>
          </div>`;
      } else {
        grid.innerHTML = stories.map(s => renderStoryCard(s)).join('');
      }
    } catch (e) {
      grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><p>Gagal memuat cerita. Pastikan database sudah di-setup.</p></div>';
    }
  }
  renderFeed();

  // Random story FAB
  const fabRandom = document.getElementById('fabRandom');
  if (fabRandom) {
    fabRandom.addEventListener('click', async () => {
      try {
        const data = await apiGet('random.php');
        window.location.href = `story.html?id=${data.id}`;
      } catch (e) { showToast('⚠️ Gagal memuat cerita random.'); }
    });
  }
}

// ---- Page: Post Story ----
function initPostPage() {
  const form = document.getElementById('postForm');
  const successEl = document.getElementById('postSuccess');
  const formCard = document.getElementById('postFormCard');
  if (!form) return;

  let selectedMood = null;

  const moodSelector = document.getElementById('moodSelector');
  if (moodSelector) {
    moodSelector.innerHTML = MOODS.map(m =>
      `<button type="button" class="mood-option" data-mood="${m.id}">
        <span class="mood-emoji">${m.emoji}</span> ${m.label}
      </button>`
    ).join('');

    moodSelector.addEventListener('click', (e) => {
      const opt = e.target.closest('.mood-option');
      if (!opt) return;
      selectedMood = opt.dataset.mood;
      moodSelector.querySelectorAll('.mood-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
    });
  }

  const catSelect = document.getElementById('categorySelect');
  if (catSelect) {
    catSelect.innerHTML = '<option value="">— Pilih Kategori —</option>' +
      CATEGORIES.filter(c => c.id !== 'all').map(c =>
        `<option value="${c.id}">${c.emoji} ${c.label}</option>`
      ).join('');
  }

  const contentArea = document.getElementById('storyContent');
  const charCount = document.getElementById('charCount');
  const MAX_CHARS = 5000;
  if (contentArea && charCount) {
    contentArea.addEventListener('input', () => {
      const len = contentArea.value.length;
      charCount.textContent = `${len} / ${MAX_CHARS}`;
      charCount.className = 'char-count';
      if (len > MAX_CHARS * 0.9) charCount.classList.add('danger');
      else if (len > MAX_CHARS * 0.75) charCount.classList.add('warning');
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const title = document.getElementById('storyTitle').value.trim();
    const content = contentArea.value.trim();
    const category = catSelect.value;

    if (!title || !content || !category) { showToast('⚠️ Mohon lengkapi semua field yang wajib diisi.'); return; }
    if (!selectedMood) { showToast('⚠️ Pilih mood kamu dulu ya!'); return; }
    if (content.length > MAX_CHARS) { showToast('⚠️ Cerita terlalu panjang.'); return; }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Mengirim...';

    try {
      const result = await apiPost('stories.php', { title, content, category, mood: selectedMood });
      if (formCard) formCard.style.display = 'none';
      if (successEl) {
        successEl.classList.add('show');
        const link = successEl.querySelector('.new-story-link');
        if (link) link.setAttribute('href', `story.html?id=${result.id}`);
      }
      showToast('🎉 Ceritamu berhasil dipublikasikan!');
    } catch (err) {
      showToast('❌ ' + err.message);
      submitBtn.disabled = false;
      submitBtn.textContent = '🚀 Publikasikan Cerita';
    }
  });
}

// ---- Crisis Detection ----
function detectCrisis(text) {
  const lower = text.toLowerCase();
  return CRISIS_KEYWORDS.some(kw => lower.includes(kw));
}

function showCrisisBanner(container) {
  const existing = document.getElementById('crisisBanner');
  if (existing) return;

  const banner = document.createElement('div');
  banner.className = 'crisis-banner';
  banner.id = 'crisisBanner';
  banner.innerHTML = `
    <div class="crisis-banner-icon">🆘</div>
    <div class="crisis-banner-content">
      <h4>Kamu Tidak Sendirian</h4>
      <p>Jika kamu atau seseorang membutuhkan bantuan darurat, hubungi:
        <a href="tel:119">119 ext. 8</a> (Into The Light) atau
        <a href="tel:021-500-454">021-500-454</a> (RSJ Grhasia).
        Bantuan selalu tersedia 💜
      </p>
    </div>
    <button class="crisis-banner-close" aria-label="Tutup">×</button>
  `;
  banner.querySelector('.crisis-banner-close').addEventListener('click', () => banner.remove());
  container.prepend(banner);
}

// ---- Virtual Hug (Confetti) ----
function launchConfetti() {
  const container = document.createElement('div');
  container.className = 'confetti-container';
  document.body.appendChild(container);

  const colors = ['#7C5CFC','#A78BFA','#F472B6','#FB923C','#34D399','#FBBF24','#60A5FA'];
  for (let i = 0; i < 80; i++) {
    const piece = document.createElement('div');
    piece.className = 'confetti-piece';
    piece.style.cssText = `
      left: ${Math.random() * 100}vw;
      background: ${colors[Math.floor(Math.random() * colors.length)]};
      width: ${Math.random() * 10 + 6}px;
      height: ${Math.random() * 10 + 6}px;
      border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
      animation-duration: ${Math.random() * 2 + 1.5}s;
      animation-delay: ${Math.random() * 0.5}s;
    `;
    container.appendChild(piece);
  }

  setTimeout(() => container.remove(), 3500);
  showToast('🤗 Kamu mengirim pelukan virtual!');
}

// ---- Share Story ----
function shareStory(storyId, title) {
  const url = `${window.location.origin}${window.location.pathname.replace('story.html', '')}story.html?id=${storyId}`;
  const text = `"${title}" — Baca cerita ini di Ruang Curhat Anonim`;

  if (navigator.share) {
    navigator.share({ title, text, url }).catch(() => {});
    return;
  }

  // Fallback modal
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay share-overlay';
  overlay.innerHTML = `
    <div class="modal-box">
      <button class="modal-close-btn" id="shareModalClose">✕</button>
      <h3>📤 Bagikan Cerita</h3>
      <p style="color:var(--text-muted);font-size:0.875rem;">Cerita ini bersifat anonim — identitas penulis tetap terlindungi.</p>
      <div class="share-options">
        <button class="share-option-btn" onclick="window.open('https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}','_blank')">
          <span class="share-icon">💬</span>WhatsApp
        </button>
        <button class="share-option-btn" onclick="window.open('https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}','_blank')">
          <span class="share-icon">🐦</span>Twitter/X
        </button>
      </div>
      <div class="share-link-box">
        <input class="share-link-input" value="${url}" readonly>
        <button class="share-copy-btn" id="shareCopyBtn">Salin</button>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);

  overlay.querySelector('#shareModalClose').addEventListener('click', () => overlay.remove());
  overlay.querySelector('#shareCopyBtn').addEventListener('click', () => {
    navigator.clipboard.writeText(url).then(() => {
      showToast('🔗 Link disalin!');
      overlay.remove();
    });
  });
  overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

// ---- Report Story ----
function openReportModal(storyId) {
  const reasons = [
    { id: 'bullying', label: '🚫 Bullying atau perundungan' },
    { id: 'hate_speech', label: '💢 Ujaran kebencian (SARA)' },
    { id: 'spam', label: '📩 Spam atau iklan' },
    { id: 'inappropriate', label: '⚠️ Konten tidak pantas' },
    { id: 'misinformation', label: '❌ Informasi palsu/menyesatkan' },
    { id: 'other', label: '📌 Alasan lainnya' },
  ];

  let selectedReason = null;

  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `
    <div class="modal-box">
      <button class="modal-close-btn" id="reportClose">✕</button>
      <h3>🚨 Laporkan Cerita</h3>
      <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:1rem;">Pilih alasan yang paling tepat:</p>
      <div class="report-reasons">
        ${reasons.map(r => `<button class="report-reason-btn" data-reason="${r.id}">${r.label}</button>`).join('')}
      </div>
      <button class="btn btn-primary" id="reportSubmit" style="width:100%" disabled>Kirim Laporan</button>
    </div>
  `;

  document.body.appendChild(overlay);

  const submitBtn = overlay.querySelector('#reportSubmit');
  overlay.querySelectorAll('.report-reason-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      overlay.querySelectorAll('.report-reason-btn').forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      selectedReason = btn.dataset.reason;
      submitBtn.disabled = false;
    });
  });

  overlay.querySelector('#reportClose').addEventListener('click', () => overlay.remove());
  overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

  submitBtn.addEventListener('click', async () => {
    if (!selectedReason) return;
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Mengirim...';
    try {
      await apiPost('report.php', { story_id: storyId, reason: selectedReason });
      showToast('✅ Laporan terkirim. Terima kasih telah menjaga komunitas!');
      overlay.remove();
    } catch (err) {
      showToast('❌ ' + err.message);
      submitBtn.disabled = false;
      submitBtn.textContent = 'Kirim Laporan';
    }
  });
}

// ---- Private Chat Invite ----
function inviteToChat(anonName) {
  const encodedName = encodeURIComponent(anonName);
  const chatUrl = `chat.html?invite=${encodedName}`;
  window.open(chatUrl, '_blank');
}

// ---- Page: Story Detail ----
async function initStoryDetail() {
  const params = new URLSearchParams(window.location.search);
  const storyId = params.get('id');
  if (!storyId) { window.location.href = 'index.html'; return; }

  const detailEl = document.getElementById('storyDetail');
  if (!detailEl) return;

  try {
    const data = await apiGet(`story.php?id=${storyId}`);
    const story = data.story;
    const mood = getMoodInfo(story.mood);
    const cat = getCategoryInfo(story.category);
    const isAI = parseInt(story.is_ai_generated) === 1;

    document.title = `${story.title} — Ruang Curhat Anonim`;

    const contentParagraphs = story.content.split('\n').filter(p => p.trim()).map(p => `<p>${p}</p>`).join('');

    detailEl.innerHTML = `
      <div class="story-detail-header">
        <div class="story-detail-meta">
          <div class="avatar-anon-lg">${story.anon_avatar}</div>
          <div class="story-detail-info">
            <div class="story-author">${story.anon_name}${isAI ? '<span class="ai-badge">🤖 AI</span>' : ''}</div>
            <div class="story-time">${story.time_ago} · 👁️ ${formatNumber(story.views)} views</div>
          </div>
        </div>
        <div class="story-badges">
          <span class="mood-badge mood-${story.mood}">${mood.emoji} ${mood.label}</span>
          <span class="story-category cat-${story.category}">${cat.emoji} ${cat.label}</span>
        </div>
      </div>
      <h1 class="story-detail-title">${story.title}</h1>
      <div class="story-detail-content">${contentParagraphs}</div>
      <div class="reactions-bar" id="reactionsBar">
        <button class="reaction-btn ${story.user_reactions.includes('relate') ? 'active' : ''}" data-type="relate">
          🤝 Relate <span class="count">${story.reactions.relate}</span>
        </button>
        <button class="reaction-btn ${story.user_reactions.includes('support') ? 'active' : ''}" data-type="support">
          💕 Support <span class="count">${story.reactions.support}</span>
        </button>
        <button class="reaction-btn ${story.user_reactions.includes('helpful') ? 'active' : ''}" data-type="helpful">
          💡 Helpful <span class="count">${story.reactions.helpful}</span>
        </button>
        <button class="hug-btn" id="hugBtn">🤗 Kirim Pelukan <span class="hug-count" id="hugCount">0</span></button>
      </div>
      <div class="story-actions-bar">
        <button class="story-action-btn" onclick="shareStory(${story.id}, '${story.title.replace(/'/g, "\\'")}')">📤 Bagikan</button>
        <button class="story-action-btn ${isBookmarked(story.id) ? 'bookmarked' : ''}" id="detailBookmarkBtn" onclick="handleDetailBookmark(${story.id}, '${story.title.replace(/'/g, "\\'")}')">
          ${isBookmarked(story.id) ? '🔖 Tersimpan' : '🔖 Simpan'}
        </button>
        <button class="story-action-btn danger" onclick="openReportModal(${story.id})">🚨 Laporkan</button>
      </div>
    `;

    // Crisis detection
    if (detectCrisis(story.title + ' ' + story.content)) {
      showCrisisBanner(detailEl.parentElement || detailEl);
    }

    // Virtual hug
    let hugCount = parseInt(localStorage.getItem(`hugs_${storyId}`) || '0');
    document.getElementById('hugCount').textContent = hugCount;
    document.getElementById('hugBtn').addEventListener('click', () => {
      hugCount++;
      localStorage.setItem(`hugs_${storyId}`, hugCount);
      document.getElementById('hugCount').textContent = hugCount;
      launchConfetti();
    });

    // AI response
    const aiEl = document.getElementById('aiResponse');
    if (aiEl) { aiEl.querySelector('.ai-text').textContent = story.ai_response; }

    // Reactions
    const reactionsBar = document.getElementById('reactionsBar');
    reactionsBar?.addEventListener('click', async (e) => {
      const btn = e.target.closest('.reaction-btn');
      if (!btn) return;
      const type = btn.dataset.type;
      try {
        const result = await apiPost('reactions.php', { story_id: parseInt(storyId), type });
        btn.classList.toggle('active', result.action === 'added');
        reactionsBar.querySelectorAll('.reaction-btn').forEach(b => {
          const t = b.dataset.type;
          b.querySelector('.count').textContent = result.reactions[t];
        });
      } catch (err) { showToast('⚠️ ' + err.message); }
    });

    await loadComments(storyId);

    // Comment form
    const commentForm = document.getElementById('commentForm');
    commentForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const textarea = document.getElementById('commentText');
      const text = textarea.value.trim();
      if (!text) return;

      const submitBtn = commentForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;

      try {
        await apiPost('comments.php', { story_id: parseInt(storyId), text });
        textarea.value = '';
        showToast('💬 Komentar berhasil ditambahkan!');
        await loadComments(storyId);
      } catch (err) { showToast('❌ ' + err.message); }
      finally { submitBtn.disabled = false; }
    });

  } catch (err) {
    detailEl.innerHTML = `<div class="empty-state"><div class="icon">😕</div><h3>Cerita tidak ditemukan</h3><p>${err.message}</p><a href="index.html" class="btn btn-primary mt-md">Kembali ke Beranda</a></div>`;
  }
}

function handleDetailBookmark(storyId, title) {
  const nowBookmarked = toggleBookmark(storyId, title);
  const btn = document.getElementById('detailBookmarkBtn');
  if (btn) {
    btn.textContent = nowBookmarked ? '🔖 Tersimpan' : '🔖 Simpan';
    btn.classList.toggle('bookmarked', nowBookmarked);
  }
}

async function loadComments(storyId) {
  const list = document.getElementById('commentList');
  const countEl = document.getElementById('commentCount');
  if (!list) return;

  try {
    const data = await apiGet(`comments.php?story_id=${storyId}`);
    if (countEl) countEl.textContent = data.total;

    if (data.comments.length === 0) {
      list.innerHTML = `<div class="empty-state" style="padding:var(--space-xl) 0;"><div class="icon">💬</div><h3>Belum ada komentar</h3><p>Jadilah yang pertama memberikan dukungan!</p></div>`;
    } else {
      list.innerHTML = data.comments.map(c => `
        <div class="comment-item">
          <div class="comment-avatar">${c.anon_avatar}</div>
          <div class="comment-body">
            <div class="comment-author">${c.anon_name}</div>
            <div class="comment-time">${c.time_ago}</div>
            <p class="comment-text">${c.text}</p>
            <button class="comment-chat-invite" onclick="inviteToChat('${c.anon_name.replace(/'/g, "\\'")}')">
              💬 Ajak Chat Privat
            </button>
          </div>
        </div>
      `).join('');
    }
  } catch (e) {
    list.innerHTML = '<p style="color:var(--text-muted);">Gagal memuat komentar.</p>';
  }
}

// ---- Navigation ----
function navigateToStory(id) { window.location.href = `story.html?id=${id}`; }

// ---- Toast ----
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

// ---- Counter Animation ----
function animateCounter(el, target) {
  let current = 0;
  const step = Math.max(1, Math.ceil(target / 60));
  const interval = setInterval(() => {
    current += step;
    if (current >= target) { current = target; clearInterval(interval); }
    el.textContent = formatNumber(current);
  }, 30);
}

// ---- Header ----
function initHeader() {
  const header = document.querySelector('.header');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 10);
    });
  }

  const menuBtn = document.querySelector('.mobile-menu-btn');
  const mobileNav = document.querySelector('.mobile-nav');
  if (menuBtn && mobileNav) {
    menuBtn.addEventListener('click', () => {
      menuBtn.classList.toggle('open');
      mobileNav.classList.toggle('open');
      document.body.style.overflow = mobileNav.classList.contains('open') ? 'hidden' : '';
    });
    mobileNav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        menuBtn.classList.remove('open');
        mobileNav.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }
}

// ---- Init ----
document.addEventListener('DOMContentLoaded', () => {
  // Apply saved theme immediately
  const savedTheme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);

  const page = document.body.dataset.page;
  if (page !== 'chat') {
    initHeader();
    initDarkMode();
  }

  switch (page) {
    case 'home':  initHomepage(); break;
    case 'post':  initPostPage(); break;
    case 'story': initStoryDetail(); break;
  }
});
