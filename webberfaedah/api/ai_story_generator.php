<?php
/**
 * AI Story Generator for Ruang Curhat Anonim
 * 
 * Generates 1-3 stories per day automatically using:
 * 1. Google Gemini API (primary)
 * 2. OpenAI (fallback)
 * 3. Local curated templates (failsafe - no API needed)
 * 
 * Usage:
 *   - Auto: called via beranda load (checks if needed)
 *   - Manual: GET api/ai_story_generator.php?trigger=1
 *   - Cron: php ai_story_generator.php (run from CLI)
 */

// Allow CLI execution
if (php_sapi_name() !== 'cli') {
    define('WEB_ACCESS', true);
}

require_once __DIR__ . '/config.php';

// ---- Config ----
define('AI_MAX_PER_DAY', 3);           // max AI stories per day
define('GEMINI_API_KEY', AI_GEMINI_KEY);
define('OPENAI_API_KEY', AI_OPENAI_KEY);
define('GEMINI_MODEL', 'gemini-1.5-flash');
define('OPENAI_MODEL', 'gpt-3.5-turbo');

// ---- Check if should run ----
function shouldGenerateToday(PDO $db): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE is_ai_generated = 1 AND DATE(created_at) = CURDATE()");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    return $count < AI_MAX_PER_DAY;
}

// ---- Build Prompt ----
function buildStoryPrompt(string $category, string $mood, string $theme): string {
    $categoryNames = [
        'school'     => 'Sekolah & Pendidikan',
        'friendship' => 'Persahabatan',
        'family'     => 'Keluarga',
        'life'       => 'Masalah Hidup',
        'motivation' => 'Motivasi & Inspirasi',
        'funny'      => 'Cerita Lucu',
    ];
    $moodNames = [
        'sad'      => 'sedih dan butuh dukungan',
        'confused' => 'bingung dan galau',
        'happy'    => 'senang dan ingin berbagi',
        'angry'    => 'frustrasi dan marah',
        'stressed' => 'stres dan tertekan',
    ];

    $catName  = $categoryNames[$category] ?? 'Masalah Hidup';
    $moodName = $moodNames[$mood] ?? 'sedih';

    return "Kamu adalah seorang remaja atau dewasa muda Indonesia yang sedang $moodName. Tulis sebuah curhat anonim yang jujur, relatable, dan emosional di platform curhat anonim bernama 'Ruang Curhat Anonim'.

Topik: $catName
Tema spesifik: $theme
Mood penulis: $moodName

Panduan menulis:
- Tulis dari sudut pandang orang pertama (menggunakan 'aku')
- Gunakan bahasa Indonesia informal/gaul yang natural (boleh pakai kata: nggak, gue/aku, banget, dll)
- Panjang cerita 150-300 kata
- Cerita harus terasa autentik, bukan seperti tulisan AI
- Akhiri dengan pertanyaan atau ajakan berbagi pengalaman serupa
- JANGAN ucapkan ini adalah tulisan AI
- Format output:
  JUDUL: [judul menarik, max 80 karakter]
  CERITA: [isi cerita]

Tulis sekarang:";
}

// ---- Gemini API ----
function callGeminiAPI(string $prompt): ?string {
    if (!GEMINI_API_KEY || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY') return null;

    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

    $body = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature'     => 0.9,
            'maxOutputTokens' => 600,
            'topP'            => 0.95,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

// ---- OpenAI API ----
function callOpenAIAPI(string $prompt): ?string {
    if (!OPENAI_API_KEY || OPENAI_API_KEY === 'YOUR_OPENAI_API_KEY') return null;

    $url  = "https://api.openai.com/v1/chat/completions";
    $body = json_encode([
        'model'       => OPENAI_MODEL,
        'messages'    => [
            ['role' => 'system', 'content' => 'Kamu adalah remaja Indonesia yang sedang curhat di platform anonim. Tulis dengan bahasa yang natural dan emosional.'],
            ['role' => 'user',   'content' => $prompt]
        ],
        'max_tokens'  => 600,
        'temperature' => 0.9,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

// ---- Parse AI Output ----
function parseAIOutput(string $text): ?array {
    // Try JUDUL: / CERITA: format
    if (preg_match('/JUDUL:\s*(.+?)(?:\n|$)/s', $text, $titleM) &&
        preg_match('/CERITA:\s*(.+)/s', $text, $contentM)) {
        $title   = trim($titleM[1]);
        $content = trim($contentM[1]);
        if ($title && strlen($content) > 50) {
            return ['title' => $title, 'content' => $content];
        }
    }

    // Fallback: first line = title, rest = content
    $lines = explode("\n", trim($text));
    $title = trim($lines[0]);
    $content = trim(implode("\n", array_slice($lines, 1)));
    if ($title && strlen($content) > 50) {
        return ['title' => $title, 'content' => $content];
    }

    return null;
}

// ---- Local Template Fallback ----
function getLocalTemplate(): array {
    $templates = [
        [
            'title'    => 'Kadang Aku Ngerasa Paling Kesepian di Antara Semua Orang',
            'content'  => "Aneh ya, bisa ngerasa kesepian padahal dikelilingin banyak orang. Di kelas, di rumah, di mana aja — aku selalu ada di tengah-tengah orang. Tapi entah kenapa, rasa sepi itu nggak pernah pergi.\n\nAku bisa ketawa bareng temen-temen, tapi begitu pulang dan sendirian di kamar, ada kehampaan yang besar banget. Kayak ada lubang di dada yang nggak tau harus diisi sama apa.\n\nAku udah coba cerita ke beberapa orang, tapi kayaknya mereka nggak beneran ngerti. Mereka bilang 'ah lebay deh' atau 'kamu terlalu overthinking'. Itu yang bikin aku makin ngerasa sendirian.\n\nAda nggak yang pernah ngerasain hal yang sama? Gimana cara kalian ngurangin rasa sepi itu?",
            'category' => 'life', 'mood' => 'sad',
        ],
        [
            'title'    => 'Sempet Nyerah Kuliah, Tapi Akhirnya Nemuin Jawaban yang Nggak Terduga',
            'content'  => "Semester lalu aku hampir DO. Bukan karena nilai jelek — well nilai juga jelek sih — tapi lebih karena aku udah nggak tau lagi buat apa aku kuliah.\n\nSetiap pagi bangun susah banget. Ngerjain tugas sambil nahan nangis. Masuk kelas kayak zombie. Sampai suatu hari aku iseng ikut kegiatan volunteering di panti jompo.\n\nDan di sana, ada seorang nenek yang cerita tentang penyesalannya karena dulu nggak punya kesempatan sekolah. Dia bilang: 'Kalau saja saya bisa bersekolah dulu, hidup saya pasti beda.'\n\nNggak tau kenapa, kata-kata itu langsung ngehantam aku. Aku nangis di toilet panti itu. Ternyata aku lupa betapa berharganya kesempatan yang aku anggap beban.\n\nSekarang masih susah, tapi aku inget wajah nenek itu tiap kali mau menyerah.",
            'category' => 'motivation', 'mood' => 'happy',
        ],
        [
            'title'    => 'Diputusin Lewat Chat, Setelah 3 Tahun Jaga Perasaan',
            'content'  => "Tiga tahun lebih aku udah suka sama dia. Bukan pacaran, kita emang nggak pernah resmi. Tapi kita udah kayak pasangan — chat tiap hari, ketemuan, saling support.\n\nKemarin dia bilang, 'Aku lagi deket sama orang lain. Maaf ya.' Segitu aja. Via chat. Setelah tiga tahun.\n\nAku nggak marah soal dia milih orang lain — itu haknya. Tapi caranya itu yang nyakitin. Kayak tiga tahun itu nggak berarti apa-apa. Kayak aku ini bisa dibuang gitu aja tanpa penjelasan yang layak.\n\nSekarang aku masih sering buka chatnya, ngehapus, ngetik, hapus lagi. Nggak tau mau bilang apa. Mungkin memang nggak ada yang perlu dikatain.\n\nPernah nggak kalian ngerasa kehilangan seseorang yang sebenernya nggak pernah benar-benar jadi milik kalian?",
            'category' => 'life', 'mood' => 'sad',
        ],
        [
            'title'    => 'Mama Nggak Percaya Aku Stres, Katanya Aku Cuma Manja',
            'content'  => "Aku coba cerita ke mama kalau aku lagi overwhelmed banget sama sekolah, temen-temen, dan semua hal. Aku udah nangis sambil cerita. Dan responnya?\n\n'Kamu tuh cuma lebay. Mama dulu juga susah tapi nggak kayak gini. Kamu kurang bersyukur.'\n\nNGGAK tau gimana rasa sakit waktu itu. Bukannya dapat pelukan, aku malah dapat ceramah. Aku langsung masuk kamar dan mengunci diri.\n\nAku tau mama capek kerja buat aku. Aku tau dia sayang aku dengan caranya sendiri. Tapi kenapa susah banget buat dia sekadar bilang 'Iya, kamu pasti capek ya.'\n\nKalian yang punya orang tua yang susah diajak ngobrol soal perasaan — gimana cara kalian cope? Aku beneran butuh saran.",
            'category' => 'family', 'mood' => 'angry',
        ],
        [
            'title'    => 'Skripsi Akhirnya Acc, Tapi Aku Nangis Bukan Karena Senang',
            'content'  => "Tadi siang dosen pembimbing akhirnya bilang 'Oke, skripsinya sudah bisa lanjut ke sidang.'\n\nAku nyangka bakal langsung melompat kegirangan. Tapi yang terjadi: aku duduk diam, terus tiba-tiba nangis.\n\nBukan nangis bahagia. Aku nangis karena baru sadar — selama dua tahun ngerjain ini, aku udah kehilangan banyak hal. Temen-temen yang jauhin aku karena aku selalu 'sibuk'. Pacar yang pergi karena aku 'nggak ada waktu'. Kesehatan yang menurun karena begadang tiap malam.\n\nAku berhasil. Tapi dengan harga yang mahal banget.\n\nApakah itu worth it? Aku masih nggak tau. Yang aku tau, aku sangat, sangat lelah. Dan sendirian merayakan hal ini terasa menyedihkan juga.\n\nBuat yang lagi berjuang sama skripsi atau tugas akhir — kalian luar biasa. Bertahanlah.",
            'category' => 'school', 'mood' => 'confused',
        ],
        [
            'title'    => 'Ketika Nggak Sengaja Jadi Teman Curhat Orang yang Aku Benci',
            'content'  => "Jadi ceritanya ada satu anak di kelompok belajar aku yang dari dulu aku nggak suka. Orangnya nyebelin, suka pamer, dan sering bikin situasi awkward.\n\nTapi kemarin, dia tiba-tiba nge-chat aku jam 12 malem. Bilang dia lagi nangis dan nggak tau harus cerita ke siapa. Aku mau ignore, tapi nggak tega.\n\nJadi aku dengerin dia curhat. Aku baru tau ternyata di balik sikap pamernya, dia lagi berjuang banget sama masalah keluarganya. Orang tuanya mau cerai, dan dia cuma pura-pura baik-baik aja di depan orang.\n\nAku nggak tiba-tiba jadi sayang sama dia. Tapi entah kenapa, setelah itu aku nggak bisa benci dia seperti sebelumnya.\n\nKadang orang yang paling menyebalkan adalah orang yang paling butuh didengar. Pelajaran hari ini buat aku.",
            'category' => 'friendship', 'mood' => 'confused',
        ],
        [
            'title'    => 'Lolos CPNS Setelah Gagal 4 Kali — Ini yang Pengen Aku Sampaikan',
            'content'  => "Empat tahun, empat kali gagal CPNS. Tiap tahun belajar mati-matian, tiap tahun hasilnya sama — tidak lolos.\n\nTahun kedua, aku hampir menyerah. Tahun ketiga, bapakku sakit dan aku tetap ikut ujian sambil bolak-balik RS. Tahun keempat, udah nggak ngarep — aku ikut untuk terakhir kalinya.\n\nDan kemarin, namaku ada di pengumuman kelulusan.\n\nAku menangis di kamar mandi kantor selama 15 menit. Nggak ada yang tau. Karena momen itu terlalu pribadi, terlalu berat, untuk dirayakan di luar.\n\nBuat kalian yang lagi di titik menyerah — aku nggak akan bilang 'semangat ya' yang klise itu. Aku cuma mau bilang: perjalananmu valid, rasa lelahmu valid, dan kamu boleh istirahat sebelum mencoba lagi.\n\nTapi jangan berhenti.",
            'category' => 'motivation', 'mood' => 'happy',
        ],
        [
            'title'    => 'Ketauan Skip Kelas Sama Guru, Tapi Yang Terjadi Selanjutnya...',
            'content'  => "Jadi kemarin aku dan dua temen skip pelajaran Fisika buat ngadem di kantin. Santai banget, makan bakso, ngobrol ngalor ngidul.\n\nEh nggak taunya, pak guru lewat kantin. Mata kita ketemu. Dia liat kita. Kita liat dia.\n\nHENINGGG SEJAGAT RAYA.\n\nKita langsung pura-pura sibuk sama HP masing-masing. Pak guru cuma senyum, terus jalan lagi.\n\nSepanjang hari aku nunggu dipanggil ke ruang guru. Deg-degan parah. Tapi sampai pulang sekolah, nggak ada panggilan.\n\nKeesokan harinya di kelas, pak guru buka pelajaran dengan: 'Kemarin saya lihat beberapa orang di kantin. Sepertinya butuh istirahat. Tapi kalian sudah mengerjakan soal halaman 47 kan?'\n\nSemua pada gelagapan. Ternyata soal itu ada di bab yang kami skip. HAHAHA karma memang nyata bestie. 💀",
            'category' => 'funny', 'mood' => 'happy',
        ],
    ];

    return $templates[array_rand($templates)];
}

// ---- Main Generator ----
function generateAIStory(PDO $db): array {
    $categories = ['school', 'friendship', 'family', 'life', 'motivation', 'funny'];
    $moods      = ['sad', 'confused', 'happy', 'angry', 'stressed'];
    $themes = [
        'school'     => ['tekanan ujian dan nilai', 'hubungan dengan guru', 'perundungan di sekolah', 'skripsi dan tugas akhir', 'ospek dan masa orientasi'],
        'friendship' => ['pengkhianatan sahabat', 'persahabatan beda latar belakang', 'kehilangan teman', 'konflik dalam grup pertemanan', 'menemukan sahabat baru'],
        'family'     => ['orangtua yang tidak mengerti', 'perbandingan dengan saudara', 'masalah ekonomi keluarga', 'broken home', 'ekspektasi orangtua'],
        'life'       => ['galau pilihan hidup', 'rasa kesepian', 'quarter life crisis', 'patah hati', 'kehilangan orang tersayang'],
        'motivation' => ['bangkit setelah kegagalan', 'menemukan tujuan hidup', 'sukses setelah berjuang panjang', 'mengubah kebiasaan buruk', 'belajar dari pengalaman pahit'],
        'funny'      => ['momen memalukan yang jadi kenangan', 'kejadian tidak terduga di sekolah', 'salah paham yang lucu', 'pengalaman kocak sehari-hari', 'situasi awkward dengan crush'],
    ];

    $category = $categories[array_rand($categories)];
    $mood     = $moods[array_rand($moods)];
    $themeList= $themes[$category];
    $theme    = $themeList[array_rand($themeList)];
    $prompt   = buildStoryPrompt($category, $mood, $theme);

    $rawText  = null;
    $source   = 'local';

    // 1. Try Gemini
    $rawText = callGeminiAPI($prompt);
    if ($rawText) {
        $source = 'gemini';
    }

    // 2. Fallback: OpenAI
    if (!$rawText) {
        $rawText = callOpenAIAPI($prompt);
        if ($rawText) $source = 'openai';
    }

    // 3. Fallback: Local template
    if (!$rawText) {
        $template = getLocalTemplate();
        $identityData = getRandomIdentity();
        $identity = $identityData;

        $stmt = $db->prepare("INSERT INTO stories (title, content, category, mood, anon_name, anon_avatar, is_ai_generated, ai_source) VALUES (?, ?, ?, ?, ?, ?, 1, 'local')");
        $stmt->execute([
            $template['title'],
            $template['content'],
            $template['category'],
            $template['mood'],
            $identity['name'],
            $identity['avatar']
        ]);

        return [
            'success' => true,
            'id'      => (int)$db->lastInsertId(),
            'source'  => 'local',
            'title'   => $template['title'],
        ];
    }

    // Parse AI output
    $parsed = parseAIOutput($rawText);
    if (!$parsed) {
        // If parse fails, use local fallback
        $template = getLocalTemplate();
        $identity = getRandomIdentity();
        $stmt = $db->prepare("INSERT INTO stories (title, content, category, mood, anon_name, anon_avatar, is_ai_generated, ai_source) VALUES (?, ?, ?, ?, ?, ?, 1, 'local')");
        $stmt->execute([$template['title'], $template['content'], $template['category'], $template['mood'], $identity['name'], $identity['avatar']]);
        return ['success' => true, 'id' => (int)$db->lastInsertId(), 'source' => 'local (parse_fail)', 'title' => $template['title']];
    }

    // Insert AI-generated story
    $identity = getRandomIdentity();
    $stmt = $db->prepare("INSERT INTO stories (title, content, category, mood, anon_name, anon_avatar, is_ai_generated, ai_source) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
    $stmt->execute([
        sanitize(mb_substr($parsed['title'], 0, 200)),
        sanitize($parsed['content']),
        $category,
        $mood,
        $identity['name'],
        $identity['avatar'],
        $source
    ]);

    return [
        'success' => true,
        'id'      => (int)$db->lastInsertId(),
        'source'  => $source,
        'title'   => mb_substr($parsed['title'], 0, 80),
    ];
}

// ---- Entry Point ----
$isCLI     = (php_sapi_name() === 'cli');
$db        = getDB();
$trigger   = isset($_GET['trigger']) || $isCLI;

function cliLog(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

if (!$trigger) {
    $needed = shouldGenerateToday($db);
    jsonResponse(['generation_needed' => $needed]);
}

if (!shouldGenerateToday($db)) {
    $msg = 'Sudah cukup cerita AI untuk hari ini (max ' . AI_MAX_PER_DAY . ' per hari).';
    if ($isCLI) { cliLog('SKIP: ' . $msg); exit(0); }
    jsonResponse(['success' => false, 'message' => $msg]);
}

if ($isCLI) cliLog('Memulai generate cerita AI...');

$result = generateAIStory($db);

if ($isCLI) {
    cliLog('Berhasil! Source: ' . $result['source']);
    cliLog('Story ID  : ' . $result['id']);
    cliLog('Judul     : ' . $result['title']);
    exit(0);
}

jsonResponse($result);
?>
