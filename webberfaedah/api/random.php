<?php
/**
 * API: Random Story
 * GET — Return a random story ID
 */
require_once __DIR__ . '/config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$stmt = $db->query("SELECT id FROM stories ORDER BY RAND() LIMIT 1");
$row = $stmt->fetch();

if (!$row) {
    jsonError('Belum ada cerita.', 404);
}

jsonResponse(['id' => (int)$row['id']]);
?>
