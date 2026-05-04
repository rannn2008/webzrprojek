<?php
/**
 * API: Comments
 * GET  ?story_id=X — List comments for a story
 * POST — Add comment to a story
 */
require_once __DIR__ . '/config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $storyId = intval($_GET['story_id'] ?? 0);
    if ($storyId <= 0) {
        jsonError('story_id tidak valid.');
    }

    $stmt = $db->prepare("SELECT * FROM comments WHERE story_id = ? ORDER BY created_at ASC");
    $stmt->execute([$storyId]);
    $comments = $stmt->fetchAll();

    foreach ($comments as &$c) {
        $c['time_ago'] = timeAgo($c['created_at']);
    }

    jsonResponse(['comments' => $comments, 'total' => count($comments)]);

} elseif ($method === 'POST') {
    if (!rateLimitCheck('post_comment', 10)) {
        jsonError('Terlalu banyak komentar. Coba lagi nanti.', 429);
    }

    $data = getJsonBody();
    $storyId = intval($data['story_id'] ?? 0);
    $text = trim($data['text'] ?? '');

    if ($storyId <= 0) {
        jsonError('story_id tidak valid.');
    }
    if (!$text) {
        jsonError('Komentar tidak boleh kosong.');
    }
    if (mb_strlen($text) > 2000) {
        jsonError('Komentar terlalu panjang (max 2000 karakter).');
    }

    // Check story exists
    $check = $db->prepare("SELECT id FROM stories WHERE id = ?");
    $check->execute([$storyId]);
    if (!$check->fetch()) {
        jsonError('Cerita tidak ditemukan.', 404);
    }

    // Moderate
    $modCheck = moderateContent($text);
    if (!$modCheck['safe']) {
        jsonError('🚫 ' . $modCheck['reason']);
    }

    $identity = getRandomIdentity();
    $stmt = $db->prepare("INSERT INTO comments (story_id, anon_name, anon_avatar, text) VALUES (?, ?, ?, ?)");
    $stmt->execute([$storyId, $identity['name'], $identity['avatar'], sanitize($text)]);

    $newComment = [
        'id' => (int)$db->lastInsertId(),
        'story_id' => $storyId,
        'anon_name' => $identity['name'],
        'anon_avatar' => $identity['avatar'],
        'text' => sanitize($text),
        'time_ago' => 'Baru saja'
    ];

    jsonResponse(['success' => true, 'comment' => $newComment], 201);

} else {
    jsonError('Method not allowed.', 405);
}
?>
