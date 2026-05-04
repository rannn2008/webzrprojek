<?php
/**
 * API: Report Story
 * POST — Submit a report for a story
 * GET  ?story_id=X — Check if current session already reported
 */
require_once __DIR__ . '/config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $storyId = intval($_GET['story_id'] ?? 0);
    if ($storyId <= 0) jsonError('story_id tidak valid.');

    $sessionId = getSessionId();
    $stmt = $db->prepare("SELECT id FROM story_reports WHERE story_id = ? AND session_id = ?");
    $stmt->execute([$storyId, $sessionId]);
    $existing = $stmt->fetch();

    jsonResponse(['already_reported' => (bool)$existing]);

} elseif ($method === 'POST') {
    if (!rateLimitCheck('report', 5)) {
        jsonError('Terlalu banyak laporan. Coba lagi nanti.', 429);
    }

    $data = getJsonBody();
    $storyId = intval($data['story_id'] ?? 0);
    $reason  = trim($data['reason'] ?? '');

    if ($storyId <= 0) jsonError('story_id tidak valid.');
    if (!$reason) jsonError('Alasan laporan wajib diisi.');

    $validReasons = ['bullying', 'hate_speech', 'spam', 'inappropriate', 'misinformation', 'other'];
    if (!in_array($reason, $validReasons)) jsonError('Alasan tidak valid.');

    // Check story exists
    $chk = $db->prepare("SELECT id FROM stories WHERE id = ?");
    $chk->execute([$storyId]);
    if (!$chk->fetch()) jsonError('Cerita tidak ditemukan.', 404);

    $sessionId = getSessionId();

    // Prevent duplicate reports from same session
    $dup = $db->prepare("SELECT id FROM story_reports WHERE story_id = ? AND session_id = ?");
    $dup->execute([$storyId, $sessionId]);
    if ($dup->fetch()) {
        jsonError('Kamu sudah melaporkan cerita ini sebelumnya.', 409);
    }

    $stmt = $db->prepare("INSERT INTO story_reports (story_id, reason, session_id) VALUES (?, ?, ?)");
    $stmt->execute([$storyId, $reason, $sessionId]);

    jsonResponse(['success' => true, 'message' => 'Laporan berhasil dikirim. Terima kasih sudah menjaga komunitas! 🛡️'], 201);

} else {
    jsonError('Method not allowed.', 405);
}
?>
