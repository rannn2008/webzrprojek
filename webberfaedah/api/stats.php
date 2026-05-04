<?php
/**
 * API: Stats
 * GET — Return global platform statistics
 */
require_once __DIR__ . '/config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$stories = (int)$db->query("SELECT COUNT(*) FROM stories")->fetchColumn();
$reactions = (int)$db->query("SELECT COUNT(*) FROM reactions")->fetchColumn();
$comments = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();

jsonResponse([
    'stories' => $stories,
    'reactions' => $reactions,
    'comments' => $comments
]);
?>
