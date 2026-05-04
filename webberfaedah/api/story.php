<?php
/**
 * API: Single Story
 * GET ?id=X — Fetch single story with full details, increment views
 */
require_once __DIR__ . '/config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    jsonError('ID cerita tidak valid.');
}

// Fetch story
$stmt = $db->prepare("SELECT * FROM stories WHERE id = ?");
$stmt->execute([$id]);
$story = $stmt->fetch();

if (!$story) {
    jsonError('Cerita tidak ditemukan.', 404);
}

// Increment views
$db->prepare("UPDATE stories SET views = views + 1 WHERE id = ?")->execute([$id]);
$story['views']++;

// Get reaction counts
$rStmt = $db->prepare("SELECT type, COUNT(*) AS cnt FROM reactions WHERE story_id = ? GROUP BY type");
$rStmt->execute([$id]);
$reactions = ['relate' => 0, 'support' => 0, 'helpful' => 0];
foreach ($rStmt->fetchAll() as $r) {
    $reactions[$r['type']] = (int)$r['cnt'];
}
$story['reactions'] = $reactions;

// Get current user's reactions
$sessionId = getSessionId();
$urStmt = $db->prepare("SELECT type FROM reactions WHERE story_id = ? AND session_id = ?");
$urStmt->execute([$id, $sessionId]);
$story['user_reactions'] = array_column($urStmt->fetchAll(), 'type');

// Get AI response
$story['ai_response'] = getAIResponse($story['mood'], $story['category'], $story['content']);

// Time ago
$story['time_ago'] = timeAgo($story['created_at']);

jsonResponse(['story' => $story]);
?>
