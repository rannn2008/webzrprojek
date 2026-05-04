<?php
/**
 * Database Setup Script for Ruang Curhat Anonim
 * Run this ONCE: http://localhost/webberfaedah/setup_database.php
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `ruang_curhat` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `ruang_curhat`");

    // --- Stories table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `stories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(200) NOT NULL,
        `content` TEXT NOT NULL,
        `category` VARCHAR(50) NOT NULL,
        `mood` VARCHAR(30) NOT NULL,
        `anon_name` VARCHAR(100) NOT NULL,
        `anon_avatar` VARCHAR(10) NOT NULL,
        `views` INT DEFAULT 0,
        `is_ai_generated` TINYINT(1) DEFAULT 0,
        `ai_source` VARCHAR(30) DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_category` (`category`),
        INDEX `idx_created` (`created_at`),
        INDEX `idx_ai` (`is_ai_generated`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Migrate: add AI columns if missing (for existing DBs)
    try {
        $pdo->exec("ALTER TABLE `stories` ADD COLUMN `is_ai_generated` TINYINT(1) DEFAULT 0");
    } catch (Exception $e) { /* column already exists */ }
    try {
        $pdo->exec("ALTER TABLE `stories` ADD COLUMN `ai_source` VARCHAR(30) DEFAULT NULL");
    } catch (Exception $e) { /* column already exists */ }

    // --- Comments table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `story_id` INT NOT NULL,
        `anon_name` VARCHAR(100) NOT NULL,
        `anon_avatar` VARCHAR(10) NOT NULL,
        `text` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
        INDEX `idx_story` (`story_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // --- Reactions table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `story_id` INT NOT NULL,
        `type` ENUM('relate','support','helpful') NOT NULL,
        `session_id` VARCHAR(100) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_reaction` (`story_id`, `type`, `session_id`),
        INDEX `idx_story_type` (`story_id`, `type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // --- Story Reports table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `story_reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `story_id` INT NOT NULL,
        `reason` VARCHAR(50) NOT NULL,
        `session_id` VARCHAR(100) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_report` (`story_id`, `session_id`),
        INDEX `idx_story_report` (`story_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // --- Private Chat Rooms table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `private_chats` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `room_code` VARCHAR(20) NOT NULL UNIQUE,
        `creator_session` VARCHAR(100) NOT NULL,
        `creator_name` VARCHAR(100) NOT NULL,
        `creator_avatar` VARCHAR(10) NOT NULL,
        `invite_name` VARCHAR(100) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_room_code` (`room_code`),
        INDEX `idx_creator` (`creator_session`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // --- Chat Messages table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `room_code` VARCHAR(20) NOT NULL,
        `sender_session` VARCHAR(100) NOT NULL,
        `sender_name` VARCHAR(100) NOT NULL,
        `sender_avatar` VARCHAR(10) NOT NULL,
        `message` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_room_msg` (`room_code`),
        INDEX `idx_msg_id` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // --- Seed data ---
    $count = $pdo->query("SELECT COUNT(*) FROM stories")->fetchColumn();
    if ($count == 0) {
        $stories = [
            [
                'title' => 'Sahabatku Ternyata Ngomongin Aku di Belakang',
                'content' => "Jadi ceritanya, aku punya sahabat dari SD. Kita udah kayak saudara sendiri, kemana-mana bareng, curhat segala hal. Tapi kemarin aku nggak sengaja baca chat dia di grup yang ternyata ngomongin aku. Mereka bilang aku \"lebay\" dan \"attention seeker\". Sakit banget rasanya. Orang yang paling aku percaya ternyata kayak gitu di belakangku.\n\nAku sekarang bingung harus gimana. Mau konfrontasi tapi takut malah ribut. Mau diemin aja tapi hati ini nggak bisa bohong. Ada yang pernah ngalamin hal kayak gini? Gimana cara kalian move on dari pengkhianatan sahabat?",
                'category' => 'friendship', 'mood' => 'sad',
                'anon_name' => 'Anonim Kucing', 'anon_avatar' => '🐱', 'views' => 1240
            ],
            [
                'title' => 'Nilai Ujianku Jeblok, Malu Sama Orangtua',
                'content' => "Hasil ujian semester kemarin keluar dan nilaiku turun drastis. Padahal orangtuaku udah kerja keras banting tulang buat bayar sekolah. Aku merasa gagal banget jadi anak. Tiap malam aku nangis sendiri karena nggak tau harus gimana.\n\nAku udah belajar tapi kayaknya emang kemampuanku terbatas. Temen-temenku pada dapet nilai bagus semua. Aku merasa jadi yang paling bodoh di kelas. Ada yang pernah ngerasain kayak gini? Gimana cara bangkit dari kegagalan?",
                'category' => 'school', 'mood' => 'stressed',
                'anon_name' => 'Anonim Penguin', 'anon_avatar' => '🐧', 'views' => 2150
            ],
            [
                'title' => 'Orangtuaku Mau Cerai, Aku Hancur',
                'content' => "Aku baru tau kalo orangtuaku ternyata udah lama bermasalah. Kemarin malam mereka ribut hebat dan aku dengar kata \"cerai\" disebut berkali-kali. Aku cuma bisa nangis di kamar sambil nutup telinga.\n\nAku anak tunggal jadi nggak ada yang bisa aku ajak ngomong soal ini. Di sekolah aku pura-pura baik-baik aja padahal dalem hati rasanya remuk. Aku takut banget keluarga ini pecah. Apa yang harus aku lakuin?",
                'category' => 'family', 'mood' => 'sad',
                'anon_name' => 'Anonim Rusa', 'anon_avatar' => '🦌', 'views' => 3420
            ],
            [
                'title' => 'Akhirnya Aku Berani Presentasi di Depan Kelas! 🎉',
                'content' => "Selama ini aku orangnya super pemalu. Presentasi di depan kelas itu kayak mimpi buruk buat aku. Tangan gemetaran, suara pecah-pecah, mau pingsan rasanya. Tapi hari ini AKU BERHASIL!\n\nAku presentasi tentang project sains dan ternyata temen-temen suka! Guruku juga bilang presentasiku bagus. Aku nggak nyangka aku bisa! Ternyata kuncinya cuma satu: PERSIAPAN. Aku latihan depan cermin berkali-kali sampai hafal.\n\nBuat kalian yang takut presentasi, percaya deh, kalau aku yang super pemalu ini bisa, kalian pasti lebih bisa lagi! 💪",
                'category' => 'motivation', 'mood' => 'happy',
                'anon_name' => 'Anonim Burung', 'anon_avatar' => '🐦', 'views' => 1876
            ],
            [
                'title' => 'Guru Les Privat Ngajarin Sesuatu yang Mengubah Hidupku',
                'content' => "Jadi ceritanya, aku les privat matematika karena nilaiku jelek banget. Awalnya mau protes ke orangtua, males les. Tapi guru lesku ternyata beda dari guru-guru lain.\n\nDia nggak pernah marah kalau aku salah. Dia bilang, \"Salah itu bagus, artinya kamu lagi belajar.\" Dia selalu sabar dan ngajarin dengan cara yang fun. Sekarang aku malah suka matematika.\n\nTapi yang paling mengubah hidupku bukan matematikanya. Dia bilang: \"Mencintai proses lebih penting dari mengejar hasil.\" Kata-kata itu mengubah cara pandangku tentang segalanya. Sekarang aku nggak takut gagal lagi.",
                'category' => 'school', 'mood' => 'happy',
                'anon_name' => 'Anonim Kuda', 'anon_avatar' => '🐴', 'views' => 2890
            ],
            [
                'title' => 'Dikerjain Pas Ospek, Malah Dapat Sahabat Seumur Hidup',
                'content' => "Waktu ospek kampus, aku dikerjain habis-habisan sama senior. Disuruh push up, jalan jongkok, teriak-teriak nggak jelas. Aku hampir nangis dan mau pulang.\n\nTapi ada satu temen sekelompok yang terus nyemangatin aku. Dia bilang, \"Tenang, kita lewatin bareng-bareng.\" Dari situ kita jadi deket banget.\n\nSekarang udah 3 tahun kita sahabatan. Dia yang nemenin aku pas susah, dan aku yang ada buat dia. Kadang hidup itu lucu ya, hal buruk bisa jadi awal dari sesuatu yang indah. 😊",
                'category' => 'friendship', 'mood' => 'happy',
                'anon_name' => 'Anonim Lumba-lumba', 'anon_avatar' => '🐬', 'views' => 1645
            ],
            [
                'title' => 'Auto Reply Chat Dosen Jam 2 Malam, Besoknya Dipanggil 😂',
                'content' => "Jadi kemarin malam aku lagi scroll HP sambil setengah tidur. Tiba-tiba ada notif masuk dari dosen pembimbing yang minta revisi skripsi. Dalam keadaan setengah sadar, aku reply:\n\n\"Iya pak, nanti saya kerjain. Sekarang lagi males 😴\"\n\nBesoknya aku baru sadar dan PANIK SETENGAH MATI. Chat udah dibaca! Akhirnya aku dipanggil ke ruangannya. Aku udah pasrah siap kena marah.\n\nEh ternyata dosenku ketawa dan bilang, \"Saya juga kadang males kok, tapi tetep harus dikerjain ya.\" HAHAHAHA ternyata dosenku chill banget. Tapi tetep ya, revisinya harus selesai minggu depan. 💀",
                'category' => 'funny', 'mood' => 'happy',
                'anon_name' => 'Anonim Koala', 'anon_avatar' => '🐨', 'views' => 5230
            ],
            [
                'title' => 'Aku Kehilangan Motivasi untuk Kuliah',
                'content' => "Semester ini terasa berat banget. Aku nggak tau lagi kenapa aku kuliah. Tugas numpuk, organisasi bikin capek, dan aku mulai ngerasa semua ini sia-sia.\n\nTemen-temenku kelihatan semangat dan punya tujuan yang jelas. Tapi aku? Aku bahkan nggak tau mau jadi apa setelah lulus. Rasanya kayak jalan di kegelapan tanpa arah.\n\nApa ada yang pernah ngerasain hal yang sama? Gimana cara kalian menemukan motivasi lagi?",
                'category' => 'life', 'mood' => 'confused',
                'anon_name' => 'Anonim Beruang', 'anon_avatar' => '🐻', 'views' => 4120
            ],
            [
                'title' => 'Mama Sering Bandingin Aku Sama Kakak',
                'content' => "Dari kecil mama selalu bilang \"Lihat kakakmu, dia rajin belajar\" atau \"Kakakmu nggak pernah bikin mama repot.\" Rasanya kayak aku ini selalu kurang di mata mama.\n\nAku udah berusaha sebaik mungkin tapi kayaknya nggak pernah cukup. Nilai bagus pun tetep dibandingin. Aku mulai ngerasa nggak dihargai di keluargaku sendiri.\n\nKadang aku mikir, apa mama bakal lebih bahagia kalo nggak punya aku? Aku capek terus-terusan jadi nomor dua.",
                'category' => 'family', 'mood' => 'angry',
                'anon_name' => 'Anonim Harimau', 'anon_avatar' => '🐯', 'views' => 3780
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO stories (title, content, category, mood, anon_name, anon_avatar, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? HOUR)");
        $hours = [5, 8, 12, 2, 18, 24, 30, 36, 48];

        foreach ($stories as $i => $s) {
            $stmt->execute([$s['title'], $s['content'], $s['category'], $s['mood'], $s['anon_name'], $s['anon_avatar'], $s['views'], $hours[$i]]);
        }

        // Seed comments
        $commentsData = [
            [1, 'Anonim Panda', '🐼', 'Aku pernah ngalamin hal yang sama persis! Yang aku lakuin adalah konfrontasi baik-baik. Bilang aja kamu udah tau dan kamu kecewa. Kalau dia beneran sahabat, dia bakal minta maaf.'],
            [1, 'Anonim Kelinci', '🐰', 'Sabar ya... semoga kamu bisa menemukan teman yang benar-benar tulus. Kamu nggak salah kok, yang salah itu mereka.'],
            [1, 'Anonim Rubah', '🦊', 'Lebih baik tau sekarang daripada nanti. Setidaknya sekarang kamu tau siapa yang beneran sayang sama kamu. Stay strong! 💪'],
            [2, 'Anonim Beruang', '🐻', 'Nilai bukan segalanya! Aku dulu juga pernah nilai jeblok, tapi sekarang alhamdulillah udah kerja di tempat yang bagus. Yang penting jangan nyerah belajar!'],
            [2, 'Anonim Elang', '🦅', 'Coba cari metode belajar yang beda. Kadang masalahnya bukan kita bodoh, tapi cara belajarnya yang belum cocok. Semangat ya! 🔥'],
            [3, 'Anonim Lumba-lumba', '🐬', 'Kamu nggak sendirian. Aku juga anak dari keluarga broken home dan sekarang udah bisa terima keadaan. Yang penting, ini BUKAN salahmu. Jangan pernah menyalahkan dirimu. ❤️'],
            [3, 'Anonim Koala', '🐨', 'Peluk virtual untukmu 🤗 Apapun yang terjadi, kamu tetap dicintai. Coba ngomong ke guru BK atau orang dewasa yang kamu percaya.'],
            [4, 'Anonim Harimau', '🐯', 'KEREN BANGEETTT! Aku juga lagi belajar buat berani ngomong di depan umum. Makasih udah berbagi, jadi termotivasi! 🔥'],
            [4, 'Anonim Gajah', '🐘', 'Proud of you!! Langkah kecil kayak gini yang bikin perbedaan besar. Keep going! ⭐'],
            [5, 'Anonim Singa', '🦁', 'Guru yang kayak gini tuh langka banget. Beruntung banget kamu punya mentor yang baik! 🙏'],
            [6, 'Anonim Rubah', '🦊', 'Cerita ini bikin aku senyum sendiri 😭💕 Semoga persahabatan kalian langgeng terus!'],
            [6, 'Anonim Panda', '🐼', 'True friendship goals! Makasih udah sharing, bikin percaya kalo sahabat sejati itu ada.'],
            [7, 'Anonim Ikan Hiu', '🦈', 'HAHAHA ini legendary banget sih! Beruntung dosennya baik, kalau dosenku bisa kena DO kali 😂😂😂'],
            [7, 'Anonim Beruang', '🐻', 'Pelajaran hidup: jangan pegang HP pas ngantuk 😂'],
            [7, 'Anonim Kucing', '🐱', 'Ini cerita yang bakal kamu ceritain ke anak cucu nanti wkwk'],
            [8, 'Anonim Elang', '🦅', 'Aku juga pernah di titik itu. Yang membantu aku adalah: berhenti membandingkan diri dengan orang lain. Setiap orang punya timeline sendiri. 🌱'],
            [8, 'Anonim Penguin', '🐧', 'Coba istirahat dulu. Burnout itu nyata. Kadang kita butuh mundur sebentar supaya bisa maju lagi lebih kuat.'],
            [9, 'Anonim Rusa', '🦌', 'Kamu berharga apa adanya. Jangan ukur nilaimu dari perbandingan orang lain. Sometimes parents dont realize they hurt us. Coba ngomong baik-baik sama mama kamu tentang perasaanmu. ❤️']
        ];

        $cStmt = $pdo->prepare("INSERT INTO comments (story_id, anon_name, anon_avatar, text, created_at) VALUES (?, ?, ?, ?, NOW() - INTERVAL ? MINUTE)");
        foreach ($commentsData as $i => $c) {
            $cStmt->execute([$c[0], $c[1], $c[2], $c[3], ($i + 1) * 30]);
        }

        // Seed reactions
        $reactionsData = [
            [1, 'relate', 142], [1, 'support', 89], [1, 'helpful', 34],
            [2, 'relate', 287], [2, 'support', 156], [2, 'helpful', 92],
            [3, 'relate', 198], [3, 'support', 245], [3, 'helpful', 67],
            [4, 'relate', 89],  [4, 'support', 312], [4, 'helpful', 178],
            [5, 'relate', 156], [5, 'support', 201], [5, 'helpful', 267],
            [6, 'relate', 234], [6, 'support', 189], [6, 'helpful', 45],
            [7, 'relate', 456], [7, 'support', 23],  [7, 'helpful', 12],
            [8, 'relate', 367], [8, 'support', 198], [8, 'helpful', 145],
            [9, 'relate', 412], [9, 'support', 287], [9, 'helpful', 98]
        ];

        $rStmt = $pdo->prepare("INSERT INTO reactions (story_id, type, session_id) VALUES (?, ?, ?)");
        foreach ($reactionsData as $r) {
            for ($j = 0; $j < $r[2]; $j++) {
                $rStmt->execute([$r[0], $r[1], 'seed_' . $r[0] . '_' . $r[1] . '_' . $j]);
            }
        }
    }

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup</title>
    <style>body{font-family:Inter,sans-serif;max-width:600px;margin:60px auto;text-align:center;color:#1E1B4B;}
    .ok{background:#D1FAE5;color:#047857;padding:20px;border-radius:12px;margin:20px 0;}
    a{color:#7C5CFC;font-weight:600;}</style></head><body>
    <h1>💜 Setup Berhasil!</h1>
    <div class='ok'>✅ Database <strong>ruang_curhat</strong> berhasil dibuat!<br>
    ✅ Tabel stories, comments, reactions berhasil dibuat!<br>
    ✅ Data demo berhasil di-seed!</div>
    <p><a href='index.html'>→ Buka Ruang Curhat Anonim</a></p>
    <p style='color:#9CA3AF;font-size:0.85rem;'>Hapus file ini setelah setup selesai untuk keamanan.</p>
    </body></html>";

} catch (PDOException $e) {
    echo "<h1>❌ Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
