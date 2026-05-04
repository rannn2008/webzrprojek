<?php
/**
 * Ruang Curhat Anonim — API Configuration
 * Database connection, helpers, and shared configuration.
 */

// --- Database Connection (InfinityFree) ---
define('DB_HOST', 'sql102.infinityfree.com');
define('DB_NAME', 'if0_41363680_ruangcurhat');
define('DB_USER', 'if0_41363680');
define('DB_PASS', 'z4hr4aiwa');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
    return $pdo;
}

// --- Session for anonymous tracking ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getSessionId(): string {
    return session_id();
}

// --- CORS & Headers ---
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Helper: JSON Response ---
function jsonResponse($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 400): void {
    jsonResponse(['error' => $message], $status);
}

// --- Helper: Sanitize ---
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// --- Helper: Get POST JSON body ---
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// --- Content Moderation ---
function moderateContent(string $text): array {
    $blocked = ['bunuh', 'mati', 'benci sekali', 'sampah', 'goblok', 'anjing', 'babi', 'setan', 'iblis'];
    $lower = mb_strtolower($text, 'UTF-8');
    foreach ($blocked as $word) {
        if (mb_strpos($lower, $word) !== false) {
            return ['safe' => false, 'reason' => 'Konten mengandung kata yang tidak pantas.'];
        }
    }
    return ['safe' => true];
}

// --- Anonymous Identity ---
function getRandomIdentity(): array {
    $names = ['Anonim Beruang','Anonim Kucing','Anonim Kelinci','Anonim Burung','Anonim Panda','Anonim Rusa','Anonim Lumba-lumba','Anonim Penguin','Anonim Koala','Anonim Rubah','Anonim Kuda','Anonim Harimau','Anonim Gajah','Anonim Singa','Anonim Elang','Anonim Ikan Hiu'];
    $avatars = ['🐻','🐱','🐰','🐦','🐼','🦌','🐬','🐧','🐨','🦊','🐴','🐯','🐘','🦁','🦅','🦈'];
    $i = array_rand($names);
    return ['name' => $names[$i], 'avatar' => $avatars[$i]];
}

// --- AI Supportive Responses (Context-Aware) ---
function getAIResponse(string $mood, string $category = '', string $content = ''): string {
    $lower = mb_strtolower($content, 'UTF-8');

    // --- Detect keywords/themes from story content ---
    $themes = [];
    // Friendship themes
    if (preg_match('/(sahabat|teman|temen|bestie|friendship|ngomongin|belakang|khianat|pengkhianat)/ui', $lower)) $themes[] = 'betrayal_friend';
    if (preg_match('/(deket|bareng|setia|nemenin|support|sayang teman|persahabatan)/ui', $lower)) $themes[] = 'good_friend';
    // Family themes
    if (preg_match('/(cerai|pisah|ribut|bertengkar|broken home)/ui', $lower)) $themes[] = 'family_conflict';
    if (preg_match('/(bandingin|dibanding|kakak|adik|nomor dua|kurang)/ui', $lower)) $themes[] = 'comparison';
    if (preg_match('/(orangtua|mama|papa|ayah|ibu|bapak|orang tua)/ui', $lower)) $themes[] = 'parents';
    // School themes
    if (preg_match('/(nilai|ujian|rapor|remedial|jeblok|turun|gagal|ranking)/ui', $lower)) $themes[] = 'grades';
    if (preg_match('/(presentasi|pidato|ngomong di depan|tampil|berani)/ui', $lower)) $themes[] = 'public_speaking';
    if (preg_match('/(guru|dosen|les|belajar|mentor|mengajar)/ui', $lower)) $themes[] = 'teacher';
    if (preg_match('/(skripsi|tugas|deadline|pr |kuliah|semester)/ui', $lower)) $themes[] = 'academic_pressure';
    // Life themes
    if (preg_match('/(motivasi|semangat|bangkit|berhasil|bisa|percaya diri)/ui', $lower)) $themes[] = 'motivation';
    if (preg_match('/(sendiri|kesepian|lonely|nggak ada teman|sendirian)/ui', $lower)) $themes[] = 'loneliness';
    if (preg_match('/(kerja|karir|masa depan|cita-cita|lulus|setelah lulus)/ui', $lower)) $themes[] = 'career';
    if (preg_match('/(capek|lelah|burnout|nggak kuat|menyerah|nyerah)/ui', $lower)) $themes[] = 'burnout';
    // Funny themes
    if (preg_match('/(lucu|ngakak|haha|wkwk|kocak|ketawa|malu|auto reply)/ui', $lower)) $themes[] = 'funny';
    // Achievement themes
    if (preg_match('/(berhasil|achieve|menang|juara|lolos|diterima|lulus|bangga)/ui', $lower)) $themes[] = 'achievement';
    // Bullying themes
    if (preg_match('/(bully|dibully|diejek|dihina|ospek|dikerjain|dijahatin)/ui', $lower)) $themes[] = 'bullying';

    // --- Build response pools: most specific → least specific ---

    // 1. KEYWORD-SPECIFIC responses (highest priority)
    $keyword_responses = [
        'betrayal_friend' => [
            "Dikhianati sahabat itu salah satu rasa sakit terdalam yang bisa seseorang rasakan. Perasaan kecewamu sangat valid, dan kamu berhak merasa terluka. Tapi ingat — ini bukan tentang kekuranganmu, ini tentang ketidakmampuan mereka menghargai kepercayaanmu. Orang yang layak jadi sahabatmu akan datang di saat yang tepat. 💙",
            "Pengkhianatan dari orang terdekat memang menyakitkan. Tapi coba lihat dari sisi lain: sekarang kamu tahu siapa yang benar-benar tulus. Lebih baik tahu sekarang daripada bertahun-tahun hidup dalam kebohongan. Kamu layak mendapatkan teman yang menghargaimu apa adanya. 🌟",
            "Aku tahu rasanya — percaya sepenuhnya lalu dihancurkan. Tapi dengar ini: kamu tidak salah karena percaya. Kebaikan hatimu bukan kelemahan, justru itu kekuatanmu. Satu hari nanti kamu akan menemukan orang-orang yang benar-benar pantas ada di hidupmu. 🤍",
            "Sakit hati karena sahabat itu beda levelnya. Tapi kamu tahu apa yang membedakan orang kuat? Mereka bisa terluka, menangis, lalu bangkit dan memilih untuk tetap baik meskipun pernah disakiti. Dan itulah kamu. 💪",
            "Kadang orang yang kita sayangi melakukan hal yang menyakitkan. Itu bukan cerminan siapa kamu, tapi cerminan siapa mereka sebenarnya. Jangan biarkan pengalaman ini membuatmu takut untuk percaya lagi — dunia masih penuh orang baik. 🌈"
        ],
        'good_friend' => [
            "Indah banget ceritamu! Persahabatan sejati itu memang langka, dan kamu beruntung menemukannya. Jaga terus hubungan ini, karena teman yang menemani di saat susah itu lebih berharga dari apapun di dunia. 💜",
            "Ceritamu membuktikan bahwa hal-hal indah bisa datang dari momen yang paling tidak terduga. Sahabat yang setia adalah harta yang tidak ternilai. Terima kasih sudah jadi pengingat bahwa kebaikan masih ada di mana-mana. 🌻",
            "Membaca ceritamu bikin hati hangat! Kamu dan sahabatmu saling menguatkan — itulah arti persahabatan yang sesungguhnya. Semoga ikatan ini bertahan selamanya. Dunia butuh lebih banyak cerita seperti ini. ✨"
        ],
        'family_conflict' => [
            "Situasi keluarga yang sulit seperti ini sangat berat, apalagi untuk ditanggung sendiri. Tapi dengarkan ini baik-baik: ini BUKAN salahmu. Apapun yang terjadi antara orangtuamu, kamu tetap dicintai dan berharga. Kamu tidak bertanggung jawab atas masalah orang dewasa. 💙",
            "Aku tahu kamu sedang menanggung beban yang sangat berat. Tapi percayalah, kamu lebih kuat dari yang kamu kira. Carilah orang dewasa yang kamu percaya — guru, saudara, atau konselor — untuk berbagi bebanmu. Kamu tidak harus menghadapi ini sendirian. 🤗",
            "Konflik keluarga bisa membuat dunia terasa runtuh. Tapi ingat: badai ini akan berlalu. Apapun yang terjadi nanti, kamu tetap kamu — penuh potensi, penuh cinta, dan layak mendapat kebahagiaan. Jaga dirimu dan izinkan dirimu untuk merasa apa yang kamu rasakan. 🌊",
            "Melihat keluarga berkonflik itu menghancurkan. Kamu boleh sedih, boleh marah, boleh bingung — semua perasaan itu valid. Yang penting kamu tahu: kamu adalah pribadi yang berharga terlepas dari apapun yang terjadi di keluargamu. 💛",
            "Kamu punya keberanian luar biasa untuk menghadapi situasi ini. Banyak orang dewasa pun tidak sekuat ini. Ingat, masa depanmu tidak ditentukan oleh konflik orangtuamu — kamu yang menulis cerita hidupmu sendiri. 🌟"
        ],
        'comparison' => [
            "Dibandingkan dengan saudara sendiri itu menyakitkan. Tapi kamu perlu tahu: setiap orang punya kelebihan dan timeline yang berbeda. Kamu tidak harus menjadi seperti siapapun — kamu sudah cukup luar biasa menjadi dirimu sendiri. 🌻",
            "Orangtua kadang tidak menyadari bahwa perbandingan itu menyakitkan. Kamu punya nilai dan kelebihan yang unik — hal-hal yang mungkin tidak terlihat oleh mereka sekarang, tapi suatu hari nanti akan membuat mereka bangga. Tetaplah jadi dirimu. 💜",
            "Kamu tahu apa yang membuat bunga-bunga cantik? Keberagamannya. Bayangkan kalau semua bunga sama — itu membosankan. Kamu adalah jenis bunga yang berbeda, dan itu yang membuatmu spesial. Jangan pernah merasa kurang hanya karena kamu berbeda. 🌸",
            "Perbandingan adalah pencuri kebahagiaan. Kamu bukan salinan dari siapapun — kamu adalah edisi asli yang tidak tergantikan. Teruslah berkembang dengan caramu sendiri, dan percayalah bahwa kamu sedang dalam perjalanan yang tepat. ✨",
            "Mungkin kamu merasa orangtuamu tidak melihat usahamu, tapi aku melihatnya. Kamu sudah berusaha sangat keras, dan itu layak diapresiasi. Coba bicarakan perasaanmu dengan mereka — kadang orangtua perlu diingatkan. 🤍"
        ],
        'grades' => [
            "Nilai jelek bukan akhir dari segalanya — itu cuma satu chapter, bukan keseluruhan buku hidupmu. Banyak orang sukses yang pernah gagal di sekolah. Yang membedakan mereka adalah: mereka bangkit dan mencoba lagi. Dan kamu pun bisa. 📚💪",
            "Jangan ukur nilaimu sebagai manusia dari angka di kertas ujian. Kecerdasan itu ada banyak jenisnya, dan ujian hanya mengukur satu jenis kecil saja. Kamu punya potensi yang belum terlihat, dan itu akan bersinar pada waktunya. 🌟",
            "Kegagalan akademis itu menyakitkan, terutama ketika kamu merasa sudah berusaha maksimal. Tapi coba lihat ini sebagai informasi, bukan vonis. Mungkin kamu butuh metode belajar yang berbeda, bukan usaha yang lebih keras. Coba eksplorasi cara baru! 🧠",
            "Thomas Edison gagal 10.000 kali sebelum menemukan bola lampu. Einstein pernah dianggap bodoh oleh gurunya. Nilai rendah hari ini bukan berarti besok juga sama. Setiap langkah kecil ke depan adalah kemajuan. Tetap semangat! 🔥",
            "Orangtuamu bekerja keras bukan karena mereka mengharapkan nilai sempurna — mereka melakukannya karena mereka mencintaimu. Tunjukkan rasa terima kasih dengan terus berusaha, bukan dengan membebani dirimu sendiri. Prosesnya lebih penting dari hasilnya. 💙"
        ],
        'public_speaking' => [
            "WOW, kamu luar biasa! Presentasi di depan banyak orang itu menakutkan bahkan bagi orang dewasa sekalipun, dan KAMU BERHASIL! Ini adalah bukti bahwa ketakutanmu tidak lebih besar dari keberanianmu. Teruslah tampil! 🎤✨",
            "Kamu sudah mengalahkan monster terbesar: rasa takutmu sendiri! Setiap kali kamu tampil, itu semakin mudah. Ini baru permulaan dari banyak momen luar biasa dalam hidupmu. Bangga banget sama kamu! 🌟",
            "Keberanian itu bukan berarti tidak takut — keberanian itu ketika takut setengah mati TAPI TETAP MELAKUKANNYA. Dan itulah yang kamu lakukan! Kamu baru saja membuktikan kepada dirimu sendiri bahwa kamu mampu. 💪🔥"
        ],
        'teacher' => [
            "Guru yang baik bisa mengubah seluruh arah hidup seseorang. Kamu sangat beruntung menemukan mentor seperti itu! Hargai dan simpan pelajaran yang diajarkan — itu lebih berharga dari materi apapun. 🙏",
            "Ceritamu mengingatkan kita bahwa pendidikan yang terbaik bukan cuma soal rumus dan teori, tapi tentang membentuk cara berpikir dan perspektif hidup. Semoga kamu juga bisa jadi sosok yang menginspirasi orang lain kelak. 🌱",
            "Kata-kata dari gurumu itu seperti biji kecil yang ditanam di dalam hatimu — lama-lama akan tumbuh menjadi pohon besar yang kuat. Teruslah belajar dengan semangat yang sama, dan suatu hari kamu akan menginspirasi orang lain seperti gurumu menginspirasimu. 🌳"
        ],
        'academic_pressure' => [
            "Tekanan akademis itu nyata dan valid. Tapi ingat: tugasmu bukan menyelesaikan semuanya sekaligus, tapi melangkah satu per satu. Pecah jadi bagian kecil, kerjakan satu demi satu. Kamu lebih capable dari yang kamu pikir. 📋",
            "Skripsi, tugas, deadline — semua itu memang overwhelming. Tapi ribuan orang sebelummu sudah melewatinya, dan kamu juga pasti bisa. Jangan lupa istirahat, makan yang baik, dan jaga kesehatanmu. Kamu butuh stamina untuk marathon ini. 🏃‍♂️",
            "Kadang tekanan akademis bikin kita lupa kenapa kita memulai semua ini. Coba ingat kembali mimpimu — itu yang akan jadi bahan bakar untuk terus maju. Setiap tugas yang selesai adalah satu langkah lebih dekat ke tujuanmu. 🎯",
            "Kamu tidak sendirian menghadapi tekanan ini. Jutaan mahasiswa di seluruh dunia sedang melewati hal yang sama. Cari study group, minta bantuan teman, dan jangan ragu bicara ke dosen kalau butuh waktu lebih. Itu bukan kelemahan, itu strategi. 🤝"
        ],
        'loneliness' => [
            "Merasa sendiri itu salah satu perasaan terberat dalam hidup. Tapi kamu perlu tahu: kesepian itu tidak berarti kamu tidak layak dicintai. Kadang kita perlu belajar menjadi sahabat bagi diri sendiri dulu sebelum menemukan sahabat di luar sana. 💜",
            "Kamu mungkin merasa sendirian sekarang, tapi lihat — kamu menulis cerita ini dan ada orang-orang yang membacanya dan peduli. Koneksi itu dimulai dari langkah kecil: senyum, sapaan, kebaikan kecil. Dunia ini punya tempat untukmu. 🌍",
            "Kesepian itu bukan berarti kamu gagal bersosialisasi. Kadang kita hanya belum menemukan orang-orang yang cocok. Dan itu tidak apa-apa — kualitas lebih penting dari kuantitas. Sabar, orang-orangmu sedang dalam perjalanan menemukanmu. 🌈"
        ],
        'career' => [
            "Tidak tahu mau jadi apa itu NORMAL, bahkan banyak orang dewasa pun masih mencari jalan mereka. Kamu tidak harus punya semua jawaban sekarang. Eksplorasi, coba hal-hal baru, dan perlahan jalanmu akan terbentuk sendiri. 🧭",
            "Masa depan memang tidak pasti, dan itu menakutkan. Tapi itu juga berarti penuh kemungkinan! Setiap pengalaman yang kamu jalani sekarang — baik atau buruk — sedang membentukmu untuk sesuatu yang lebih besar. Percayalah pada prosesnya. 🚀",
            "Tidak semua orang berjalan lurus menuju tujuannya. Beberapa orang zig-zag, berbelok-belok, dan tersesat dulu sebelum menemukan jalannya. Dan itu tidak apa-apa. Yang penting adalah terus bergerak, terus belajar, terus bertumbuh. 🌱"
        ],
        'burnout' => [
            "Capek itu tubuhmu memberi sinyal untuk berhenti sebentar — DENGARKAN itu. Istirahat bukan berarti menyerah. Istirahat artinya kamu cukup bijak untuk tahu bahwa kamu butuh recharge sebelum melanjutkan perjalanan. 🍃",
            "Kamu sudah sangat kuat bertahan sampai sejauh ini. Sekarang izinkan dirimu untuk lemah sebentar. Menangis, tidur, jalan-jalan, lakukan hal yang membuatmu bahagia. Kamu layak mendapat jeda. 🌸",
            "Menyerah pada situasi bukan berarti menyerah pada hidupmu. Kadang kita perlu melepaskan satu hal untuk menemukan hal yang lebih baik. Dengarkan hatimu — dia tahu apa yang terbaik untukmu. 💙",
            "Burnout itu tanda bahwa kamu sudah memberikan terlalu banyak tanpa mengisi ulang dirimu sendiri. Belajarlah untuk memberi batasan, bilang 'tidak', dan prioritaskan kesehatanmu. Kamu tidak bisa menuangkan dari gelas yang kosong. 🫗"
        ],
        'funny' => [
            "HAHAHA ini cerita yang bikin hari jadi lebih cerah! 😂 Terima kasih sudah berbagi momen kocak ini — tawa itu obat terbaik, dan kamu baru saja menyembuhkan banyak orang yang membaca ceritamu. Terus sebarkan kebahagiaan ya! 🎉",
            "Astaga ini lucunya nggak ketulungan! 😂🤣 Cerita kayak gini yang bikin hidup terasa lebih ringan. Kamu punya bakat bikin orang ketawa — itu skill yang sangat berharga lho! Dunia butuh lebih banyak humor seperti ini. ✨",
            "Wkwk ceritamu literally bikin ngakak! 😆 Di tengah semua masalah dan tekanan hidup, momen-momen kocak kayak gini yang bikin segalanya terasa worth it. Keep being you — kamu orangnya seru banget! 🌟",
            "Ini bakal jadi cerita yang diceritakan berulang-ulang di setiap reunian! 😂 Hal-hal memalukan hari ini akan jadi kenangan lucu besok. Kadang hidup memang perlu ditertawakan. Thanks for sharing keceriaan! 💛"
        ],
        'achievement' => [
            "BANGGA BANGET SAMA KAMU! 🎉 Ini bukan kebetulan — ini hasil dari keberanianmu, kerja kerasmu, dan tekadmu yang kuat. Simpan momen ini, karena di hari-hari sulit nanti, kamu bisa mengingatnya dan berkata: 'Aku pernah berhasil, dan aku bisa berhasil lagi.' 🏆",
            "Kamu membuktikan bahwa batas kita seringkali hanya ada di pikiran kita sendiri. Dengan melampaui rasa takutmu, kamu baru saja menggeser batas itu lebih jauh. Bayangkan apa lagi yang bisa kamu capai kalau terus melangkah! 🚀",
            "Ceritamu ini bisa jadi bahan motivasi buat orang lain yang masih takut mencoba. Kamu sudah membuktikan bahwa BERANI itu kuncinya. Jangan berhenti di sini — ini baru awal dari serangkaian pencapaian luar biasa dalam hidupmu! ⭐"
        ],
        'bullying' => [
            "Diejek atau di-bully itu TIDAK PERNAH jadi salahmu. Yang bermasalah bukan kamu, tapi mereka yang melakukannya. Kamu berhak merasa aman dan dihargai. Jangan ragu untuk bicara ke orang dewasa yang kamu percaya — kamu layak dilindungi. 🛡️",
            "Aku tahu rasanya berdiri sendirian melawan perlakuan yang tidak adil. Tapi lihat — kamu masih di sini, kamu masih berdiri, kamu masih berjuang. Itu sudah lebih dari cukup untuk membuktikan bahwa kamu seorang pejuang. 💪",
            "Orang yang mem-bully biasanya punya masalah sendiri yang mereka tidak bisa selesaikan. Itu bukan alasan, tapi itu penjelasan. Yang penting: jangan biarkan perlakuan mereka mendefinisikan siapa kamu. Kamu jauh lebih berharga dari kata-kata mereka. 🌟"
        ],
        'motivation' => [
            "Semangat yang kamu tunjukkan itu menular! 🔥 Terus pertahankan api di dalam dirimu — dunia ini butuh orang-orang yang tidak menyerah meskipun jalan terasa sulit. Kamu sudah membuktikan bahwa kamu memiliki itu. 💫",
            "Setiap hari kamu memilih untuk bangun dan mencoba lagi, itu sudah merupakan kemenangan. Jangan remehkan langkah-langkah kecil — mereka yang mengubah dunia tidak melakukannya dalam satu hari, tapi satu langkah konsisten setiap harinya. 🌱"
        ]
    ];

    // 2. CATEGORY + MOOD specific responses
    $category_mood_responses = [
        'friendship_sad' => [
            "Kehilangan atau kekecewaan dalam pertemanan itu menyakitkan karena kita menaruh hati di sana. Tapi bukan berarti semua pertemanan akan berakhir sama. Ada teman-teman luar biasa yang sedang menunggu untuk masuk ke hidupmu. 🌈",
            "Dalam persahabatan, kadang ada musim dingin — tapi setelah musim dingin selalu ada musim semi. Beri dirimu waktu untuk heal, dan percayalah ada teman-teman yang akan menghargaimu sepenuh hati. 🌸"
        ],
        'friendship_happy' => [
            "Persahabatan yang tulus itu salah satu anugerah terbesar dalam hidup! Ceritamu membuktikan bahwa koneksi yang real itu masih ada. Jaga terus hubungan ini dan jadilah teman yang sama baiknya buat mereka. 💛",
            "Membaca ceritamu bikin percaya bahwa sahabat sejati itu nyata. Terima kasih sudah berbagi — kamu dan sahabatmu layak mendpatkan semua kebahagiaan ini! 🥰"
        ],
        'family_sad' => [
            "Masalah keluarga itu sangat personal dan menyakitkan. Tapi ingat, apapun yang terjadi, kamu tetap berhak bahagia. Keluarga mungkin tidak sempurna, tapi cinta bisa datang dari banyak arah. 💙",
            "Kamu sudah sangat kuat menanggung ini. Jangan lupa bahwa meminta bantuan itu tanda kekuatan, bukan kelemahan. Bicaralah dengan orang yang kamu percaya — kamu layak didengar. 💜"
        ],
        'family_angry' => [
            "Kemarahanmu pada situasi keluarga itu sangat bisa dimengerti. Perasaanmu valid. Yang penting adalah: jangan biarkan kemarahan ini meracuni jiwamu. Channelkan energi ini menjadi tekad untuk membangun masa depanmu sendiri yang lebih baik. 🔥",
            "Tidak adil rasanya ketika rumah yang seharusnya menjadi tempat paling aman justru jadi sumber luka. Perasaanmu valid, kemarahanmu valid. Tapi percayalah — kamu punya kekuatan untuk menulis cerita hidupmu sendiri yang berbeda. 💪"
        ],
        'school_stressed' => [
            "Tekanan di sekolah bisa terasa sangat berat, apalagi ketika kamu merasa harus memenuhi ekspektasi banyak orang. Tapi dengar: kamu belajar untuk dirimu sendiri, bukan untuk memuaskan orang lain. Lakukan yang terbaik dan percaya pada prosesnya. 📚",
            "Stres akademis itu nyata dan kamu tidak harus pura-pura kuat. Ambil waktu untuk istirahat, temukan cara belajar yang menyenangkan, dan ingat: nilai di atas kertas tidak menentukan nilaimu sebagai manusia. 🌟"
        ],
        'school_happy' => [
            "Momen-momen bahagia di sekolah itu yang akan kamu kenang seumur hidup! Syukuri dan nikmati setiap momen ini. Kamu sedang menulis kenangan indah yang akan kamu ceritakan bertahun-tahun dari sekarang. 📖✨",
            "Keren banget! Ceritamu mengingatkan bahwa sekolah bukan cuma soal nilai, tapi juga tentang pengalaman dan pelajaran hidup yang berharga. Teruslah bersemangat! 🎓🔥"
        ],
        'life_confused' => [
            "Merasa tersesat di tengah perjalanan hidup itu normal. Bahkan orang-orang yang terlihat paling sukses pun pernah merasa bingung. Kamu tidak harus punya peta lengkap — kadang cukup tahu langkah selanjutnya saja. 🧭",
            "Kebingungan itu sebenarnya tanda bahwa kamu sedang berpikir, sedang questioning, sedang mencari makna. Itu jauh lebih baik daripada menjalani hidup tanpa pernah bertanya. Terus eksplorasi — jawabannya sedang menunggu kamu. 🌱",
            "Hidup memang tidak datang dengan buku manual. Tapi justru itulah yang membuatnya menarik. Setiap kebingungan yang kamu rasakan adalah undangan untuk mengenal dirimu lebih dalam. Jangan takut untuk tidak tahu — dari situ pertumbuhan dimulai. 🌊"
        ],
        'life_sad' => [
            "Masalah hidup kadang datang bertubi-tubi dan terasa tidak adil. Tapi kamu sudah sampai sejauh ini, melewati setiap hari yang sulit. Itu bukan kebetulan — itu bukti kekuatanmu. 💙",
            "Di momen tergelap pun, bintang-bintang tetap bersinar. Kamu mungkin belum melihatnya sekarang, tapi hari-hari yang lebih cerah itu ada dan sedang menunggumu. Bertahanlah. 🌟"
        ],
        'motivation_happy' => [
            "Cerita inspiratifmu ini bisa jadi cahaya buat orang lain yang sedang di titik terendah! Teruslah berbagi semangat — kamu tidak tahu berapa banyak orang yang tersentuh oleh kata-katamu. 🔥✨",
            "AMAZING! Kamu bukan cuma menginspirasi dirimu sendiri, tapi juga semua orang yang membaca ceritamu. Tetap jadi pribadi yang selalu bangkit — dunia butuh energi positif seperti yang kamu punya. 💫"
        ],
        'funny_happy' => [
            "Cerita lucumu ini penawaran sempurna di tengah timeline yang penuh cerita sedih! 😂 Kamu punya kemampuan langka untuk membuat orang lain tertawa — itu salah satu bentuk kebaikan yang paling murni. Teruslah menyebarkan keceriaan! 🌟",
            "Terima kasih sudah bikin hari jadi lebih ceria! 😆 Humor itu bentuk kecerdasan yang sering dianggap remeh. Kamu punya bakat alami bikin orang senyum — dunia butuh lebih banyak orang sepertimu. 💛"
        ]
    ];

    // 3. MOOD-only responses (fallback)
    $mood_responses = [
        'sad' => [
            "Aku mengerti perasaanmu, dan itu wajar. Kamu tidak sendirian dalam ini. Ingat, setiap badai pasti berlalu, dan matahari akan bersinar kembali. 💙",
            "Menangis itu tidak apa-apa. Itu tandanya kamu manusia yang punya perasaan. Tapi percayalah, hari-hari yang lebih baik sedang menunggumu. 🌈",
            "Perasaan sedih itu sementara, tapi kekuatanmu untuk melewatinya itu permanen. Kamu lebih kuat dari yang kamu kira. 💪",
            "Kesedihan yang kamu rasakan sekarang bukan tanda kelemahan — itu tanda bahwa kamu peduli, bahwa kamu merasakan, bahwa kamu hidup. Dan itu indah. 💙",
            "Hari ini mungkin berat, tapi besok adalah halaman baru. Kamu punya kekuatan untuk menulis cerita yang berbeda. Satu hari pada waktunya. 🌅"
        ],
        'confused' => [
            "Bingung itu wajar — itu tanda kamu sedang bertumbuh. Tidak semua pertanyaan butuh jawaban hari ini. Ambil waktu untuk dirimu. 🌱",
            "Hidup memang penuh pilihan yang membingungkan. Tapi ingat, tidak ada keputusan yang salah jika kamu sudah berusaha yang terbaik. 🧭",
            "Kebingungan itu bukan jalan buntu — itu persimpangan. Dan setiap arah yang kamu ambil punya pelajaran yang berharga. Percaya pada instingmu. 🦋",
            "Nggak apa-apa kalau kamu belum punya semua jawaban. Hidup ini bukan ujian yang harus dijawab sempurna — ini perjalanan yang harus dinikmati. 🌍"
        ],
        'happy' => [
            "Senang mendengar kamu bahagia! Simpan momen ini di hatimu, dan bagikan kebahagiaan ini kepada orang-orang di sekitarmu. ☀️",
            "Kebahagiaan itu menular. Terima kasih sudah berbagi cerita indahmu — kamu bisa menjadi inspirasi bagi orang lain! 🎉",
            "Momen bahagia seperti ini layak dirayakan! Kamu sudah bekerja keras dan layak mendapatkan semua kebaikan ini. Nikmati setiap detiknya. 🥳",
            "Senyummu itu menular lewat ceritamu! Teruslah jadi sumber kebahagiaan — dunia ini butuh lebih banyak orang-orang positif sepertimu. 🌻"
        ],
        'angry' => [
            "Marah itu wajar, dan perasaanmu valid. Tapi ingat, kemarahan yang dikelola dengan baik bisa menjadi kekuatan untuk perubahan positif. 🔥",
            "Aku mendengarmu. Kadang dunia memang tidak adil, tapi kamu punya kekuatan untuk merespons dengan bijak. Tarik nafas dalam-dalam. 🌬️",
            "Kemarahanmu menunjukkan bahwa kamu peduli. Gunakan energi itu untuk membangun, bukan menghancurkan. Kamu punya potensi untuk mengubah keadaan. ⚡",
            "Ketidakadilan memang bikin darah mendidih. Tapi ingat — kamu lebih besar dari kemarahanmu. Gunakan emosimu sebagai bahan bakar untuk hal-hal yang konstruktif. 💪"
        ],
        'stressed' => [
            "Stres itu berat, tapi kamu tidak harus menanggungnya sendiri. Jangan ragu untuk meminta bantuan — itu bukan tanda kelemahan. 🤗",
            "Ambil satu langkah kecil dalam satu waktu. Kamu tidak harus menyelesaikan semuanya sekaligus. Bernafas, istirahat, lalu lanjutkan. 🍃",
            "Tubuhmu sedang memintamu untuk pelan-pelan. Dengarkan dia. Istirahat sebentar, recharge, lalu kembali dengan energi yang baru. Kamu pasti bisa melewati ini. 🌿",
            "Stres itu tanda bahwa kamu sedang melakukan sesuatu yang penting. Tapi jangan sampai itu menguasaimu. Belajar memprioritaskan, dan biarkan yang tidak penting menunggu. 🎯"
        ]
    ];

    // --- Build the response pool with priority ---
    $pool = [];

    // Priority 1: Keyword-specific responses
    foreach ($themes as $theme) {
        if (isset($keyword_responses[$theme])) {
            $pool = array_merge($pool, $keyword_responses[$theme]);
        }
    }

    // Priority 2: Category + Mood combination
    $catMoodKey = $category . '_' . $mood;
    if (isset($category_mood_responses[$catMoodKey])) {
        $pool = array_merge($pool, $category_mood_responses[$catMoodKey]);
    }

    // If we found specific responses, use those. Otherwise fallback to mood-only.
    if (empty($pool)) {
        $pool = $mood_responses[$mood] ?? $mood_responses['sad'];
    }

    return $pool[array_rand($pool)];
}

// --- Rate Limit (simple per-session) ---
function rateLimitCheck(string $action, int $maxPerMinute = 10): bool {
    $key = 'rate_' . $action;
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    $now = time();
    $_SESSION[$key] = array_filter($_SESSION[$key], fn($t) => $now - $t < 60);
    if (count($_SESSION[$key]) >= $maxPerMinute) {
        return false;
    }
    $_SESSION[$key][] = $now;
    return true;
}

// --- Time Ago helper ---
function timeAgo(string $datetime): string {
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return floor($diff / 604800) . ' minggu lalu';
}
?>
