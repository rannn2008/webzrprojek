<?php
/**
 * API: Reactions
 * GET  ?story_id=X — Get reaction counts + user's reactions
 * POST — Toggle reaction (relate/support/helpful) per session
 */
require_once __DIR__ . '/config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $storyId = intval($_GET['story_id'] ?? 0);
    if ($storyId <= 0) jsonError('story_id tidak valid.');

    $stmt = $db->prepare("SELECT type, COUNT(*) AS cnt FROM reactions WHERE story_id = ? GROUP BY type");
    $stmt->execute([$storyId]);
    $counts = ['relate' => 0, 'support' => 0, 'helpful' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $counts[$r['type']] = (int)$r['cnt'];
    }

    $sessionId = getSessionId();
    $uStmt = $db->prepare("SELECT type FROM reactions WHERE story_id = ? AND session_id = ?");
    $uStmt->execute([$storyId, $sessionId]);
    $userReactions = array_column($uStmt->fetchAll(), 'type');

    jsonResponse(['reactions' => $counts, 'user_reactions' => $userReactions]);

} elseif ($method === 'POST') {
    if (!rateLimitCheck('reaction', 30)) {
        jsonError('Terlalu banyak permintaan.', 429);
    }

    $data = getJsonBody();
    $storyId = intval($data['story_id'] ?? 0);
    $type = trim($data['type'] ?? '');
    $sessionId = getSessionId();

    if ($storyId <= 0) jsonError('story_id tidak valid.');
    if (!in_array($type, ['relate', 'support', 'helpful'])) jsonError('Tipe reaksi tidak valid.');

    // Check if already reacted
    $check = $db->prepare("SELECT id FROM reactions WHERE story_id = ? AND type = ? AND session_id = ?");
    $check->execute([$storyId, $type, $sessionId]);

    if ($check->fetch()) {
        // Remove reaction (toggle off)
        $db->prepare("DELETE FROM reactions WHERE story_id = ? AND type = ? AND session_id = ?")->execute([$storyId, $type, $sessionId]);
        $action = 'removed';
    } else {
        // Add reaction (toggle on)
        $db->prepare("INSERT INTO reactions (story_id, type, session_id) VALUES (?, ?, ?)")->execute([$storyId, $type, $sessionId]);
        $action = 'added';
    }

    // Return updated counts
    $stmt = $db->prepare("SELECT type, COUNT(*) AS cnt FROM reactions WHERE story_id = ? GROUP BY type");
    $stmt->execute([$storyId]);
    $counts = ['relate' => 0, 'support' => 0, 'helpful' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $counts[$r['type']] = (int)$r['cnt'];
    }

    jsonResponse(['success' => true, 'action' => $action, 'reactions' => $counts]);

} else {
    jsonError('Method not allowed.', 405);
}
?>
