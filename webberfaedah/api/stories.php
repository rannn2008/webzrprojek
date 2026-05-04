<?php
/**
 * API: Stories
 * GET  — List stories (optional: ?category=X&sort=trending|recent&page=1&limit=12)
 * POST — Create new story
 */
require_once __DIR__ . '/config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = $_GET['category'] ?? 'all';
    $sort = $_GET['sort'] ?? 'recent';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;

    $where = '';
    $params = [];
    if ($category !== 'all') {
        $where = 'WHERE s.category = ?';
        $params[] = $category;
    }

    if ($sort === 'trending') {
        $orderBy = 'ORDER BY total_reactions DESC, s.created_at DESC';
    } else {
        $orderBy = 'ORDER BY s.created_at DESC';
    }

    // Count total
    $countSql = "SELECT COUNT(*) FROM stories s $where";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch stories with reaction counts
    $sql = "SELECT s.*,
            COALESCE(rc.relate_count, 0) AS relate_count,
            COALESCE(rc.support_count, 0) AS support_count,
            COALESCE(rc.helpful_count, 0) AS helpful_count,
            COALESCE(rc.relate_count, 0) + COALESCE(rc.support_count, 0) + COALESCE(rc.helpful_count, 0) AS total_reactions,
            COALESCE(cc.comment_count, 0) AS comment_count
        FROM stories s
        LEFT JOIN (
            SELECT story_id,
                SUM(type = 'relate') AS relate_count,
                SUM(type = 'support') AS support_count,
                SUM(type = 'helpful') AS helpful_count
            FROM reactions GROUP BY story_id
        ) rc ON rc.story_id = s.id
        LEFT JOIN (
            SELECT story_id, COUNT(*) AS comment_count
            FROM comments GROUP BY story_id
        ) cc ON cc.story_id = s.id
        $where $orderBy LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $stories = $stmt->fetchAll();

    // Add time_ago and reading time
    foreach ($stories as &$s) {
        $s['time_ago'] = timeAgo($s['created_at']);
        $wordCount = str_word_count(strip_tags($s['content']));
        $s['read_minutes'] = max(1, (int)ceil($wordCount / 200));
    }

    jsonResponse([
        'stories' => $stories,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);

} elseif ($method === 'POST') {
    if (!rateLimitCheck('post_story', 5)) {
        jsonError('Terlalu banyak permintaan. Coba lagi nanti.', 429);
    }

    $data = getJsonBody();
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');
    $category = trim($data['category'] ?? '');
    $mood = trim($data['mood'] ?? '');

    // Validation
    if (!$title || !$content || !$category || !$mood) {
        jsonError('Semua field wajib diisi.');
    }

    if (mb_strlen($title) > 200) {
        jsonError('Judul terlalu panjang (max 200 karakter).');
    }

    if (mb_strlen($content) > 5000) {
        jsonError('Cerita terlalu panjang (max 5000 karakter).');
    }

    $validCategories = ['school','friendship','family','life','motivation','funny'];
    if (!in_array($category, $validCategories)) {
        jsonError('Kategori tidak valid.');
    }

    $validMoods = ['sad','confused','happy','angry','stressed'];
    if (!in_array($mood, $validMoods)) {
        jsonError('Mood tidak valid.');
    }

    // Content moderation
    $modCheck = moderateContent($title . ' ' . $content);
    if (!$modCheck['safe']) {
        jsonError('🚫 ' . $modCheck['reason'] . ' Mohon gunakan bahasa yang sopan.');
    }

    // Create story
    $identity = getRandomIdentity();
    $stmt = $db->prepare("INSERT INTO stories (title, content, category, mood, anon_name, anon_avatar, is_ai_generated, ai_source) VALUES (?, ?, ?, ?, ?, ?, 0, NULL)");
    $stmt->execute([
        sanitize($title),
        sanitize($content),
        $category,
        $mood,
        $identity['name'],
        $identity['avatar']
    ]);

    $newId = (int)$db->lastInsertId();

    jsonResponse([
        'success' => true,
        'id' => $newId,
        'anon_name' => $identity['name'],
        'anon_avatar' => $identity['avatar'],
        'message' => 'Ceritamu berhasil dipublikasikan!'
    ], 201);

} else {
    jsonError('Method not allowed.', 405);
}
?>
