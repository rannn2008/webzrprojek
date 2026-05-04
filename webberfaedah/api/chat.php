<?php
/**
 * API: Private Anonymous Chat
 * GET  ?action=get_or_create&invite_name=X    — Get/create a chat room
 * GET  ?action=messages&room_code=X&since=Y   — Fetch new messages (polling)
 * GET  ?action=room_info&room_code=X          — Get room info
 * POST {action:"send", room_code, message}    — Send a message
 */
require_once __DIR__ . '/config.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$sessionId = getSessionId();

// Generate/get session persona (consistent per session)
function getOrCreatePersona(string $sessionId): array {
    $names = ['Anonim Beruang','Anonim Kucing','Anonim Kelinci','Anonim Burung','Anonim Panda',
              'Anonim Rusa','Anonim Lumba-lumba','Anonim Penguin','Anonim Koala','Anonim Rubah',
              'Anonim Kuda','Anonim Harimau','Anonim Gajah','Anonim Singa','Anonim Elang','Anonim Ikan Hiu'];
    $avatars = ['🐻','🐱','🐰','🐦','🐼','🦌','🐬','🐧','🐨','🦊','🐴','🐯','🐘','🦁','🦅','🦈'];

    // Deterministic based on session ID hash
    $hash = crc32($sessionId);
    $idx = abs($hash) % count($names);
    return ['name' => $names[$idx], 'avatar' => $avatars[$idx]];
}

function generateRoomCode(): string {
    return bin2hex(random_bytes(6)); // 12-char hex code
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get_or_create') {
        // Create chat room (inviter perspective)
        $inviteName = trim($_GET['invite_name'] ?? '');
        $persona = getOrCreatePersona($sessionId);

        // Check if this session already has an active room created recently (within 10 mins)
        $stmt = $db->prepare("SELECT * FROM private_chats WHERE creator_session = ? AND created_at > NOW() - INTERVAL 10 MINUTE AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $existing = $stmt->fetch();

        if ($existing) {
            jsonResponse([
                'room_code'     => $existing['room_code'],
                'invite_name'   => $existing['invite_name'],
                'persona_name'  => $persona['name'],
                'persona_avatar'=> $persona['avatar'],
                'is_new'        => false
            ]);
        }

        $roomCode = generateRoomCode();
        $stmt = $db->prepare("INSERT INTO private_chats (room_code, creator_session, invite_name, creator_name, creator_avatar) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$roomCode, $sessionId, $inviteName, $persona['name'], $persona['avatar']]);

        jsonResponse([
            'room_code'      => $roomCode,
            'invite_name'    => $inviteName,
            'persona_name'   => $persona['name'],
            'persona_avatar' => $persona['avatar'],
            'is_new'         => true
        ]);

    } elseif ($action === 'room_info') {
        $roomCode = trim($_GET['room_code'] ?? '');
        if (!$roomCode) jsonError('room_code diperlukan.');

        $stmt = $db->prepare("SELECT * FROM private_chats WHERE room_code = ? AND is_active = 1");
        $stmt->execute([$roomCode]);
        $room = $stmt->fetch();
        if (!$room) jsonError('Room tidak ditemukan atau sudah ditutup.', 404);

        $persona = getOrCreatePersona($sessionId);

        // Count participants (unique sessions that sent a message)
        $pStmt = $db->prepare("SELECT COUNT(DISTINCT sender_session) as cnt FROM chat_messages WHERE room_code = ?");
        $pStmt->execute([$roomCode]);
        $pCount = (int)$pStmt->fetchColumn();

        jsonResponse([
            'room_code'       => $room['room_code'],
            'creator_name'    => $room['creator_name'],
            'creator_avatar'  => $room['creator_avatar'],
            'invite_name'     => $room['invite_name'],
            'created_at'      => $room['created_at'],
            'persona_name'    => $persona['name'],
            'persona_avatar'  => $persona['avatar'],
            'is_creator'      => ($room['creator_session'] === $sessionId),
            'participant_count' => $pCount
        ]);

    } elseif ($action === 'messages') {
        $roomCode = trim($_GET['room_code'] ?? '');
        $since    = intval($_GET['since'] ?? 0); // message ID offset

        if (!$roomCode) jsonError('room_code diperlukan.');

        // Verify room exists and active
        $stmt = $db->prepare("SELECT id FROM private_chats WHERE room_code = ? AND is_active = 1");
        $stmt->execute([$roomCode]);
        if (!$stmt->fetch()) jsonError('Room tidak ditemukan.', 404);

        $msgStmt = $db->prepare("SELECT * FROM chat_messages WHERE room_code = ? AND id > ? ORDER BY id ASC LIMIT 50");
        $msgStmt->execute([$roomCode, $since]);
        $messages = $msgStmt->fetchAll();

        foreach ($messages as &$m) {
            $m['time_ago'] = timeAgo($m['created_at']);
            $m['is_own']   = ($m['sender_session'] === $sessionId);
            unset($m['sender_session']); // never expose session id
        }

        jsonResponse(['messages' => $messages]);

    } elseif ($action === 'close') {
        $roomCode = trim($_GET['room_code'] ?? '');
        if (!$roomCode) jsonError('room_code diperlukan.');

        $stmt = $db->prepare("UPDATE private_chats SET is_active = 0 WHERE room_code = ? AND creator_session = ?");
        $stmt->execute([$roomCode, $sessionId]);

        jsonResponse(['success' => true, 'message' => 'Chat room ditutup.']);

    } else {
        jsonError('Action tidak valid.');
    }

} elseif ($method === 'POST') {
    if (!rateLimitCheck('chat_send', 30)) {
        jsonError('Terlalu cepat mengirim pesan. Tunggu sebentar.', 429);
    }

    $data    = getJsonBody();
    $action  = $data['action'] ?? '';
    $roomCode = trim($data['room_code'] ?? '');
    $message  = trim($data['message'] ?? '');

    if ($action !== 'send') jsonError('Action tidak valid.');
    if (!$roomCode) jsonError('room_code diperlukan.');
    if (!$message)  jsonError('Pesan tidak boleh kosong.');
    if (mb_strlen($message) > 1000) jsonError('Pesan terlalu panjang (max 1000 karakter).');

    // Verify room
    $stmt = $db->prepare("SELECT id FROM private_chats WHERE room_code = ? AND is_active = 1");
    $stmt->execute([$roomCode]);
    if (!$stmt->fetch()) jsonError('Room tidak ditemukan atau sudah ditutup.', 404);

    // Moderate
    $mod = moderateContent($message);
    if (!$mod['safe']) jsonError('🚫 ' . $mod['reason']);

    $persona = getOrCreatePersona($sessionId);

    $ins = $db->prepare("INSERT INTO chat_messages (room_code, sender_session, sender_name, sender_avatar, message) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$roomCode, $sessionId, $persona['name'], $persona['avatar'], sanitize($message)]);

    $newId = (int)$db->lastInsertId();

    jsonResponse([
        'success'  => true,
        'message'  => [
            'id'             => $newId,
            'room_code'      => $roomCode,
            'sender_name'    => $persona['name'],
            'sender_avatar'  => $persona['avatar'],
            'message'        => sanitize($message),
            'time_ago'       => 'Baru saja',
            'is_own'         => true
        ]
    ], 201);

} else {
    jsonError('Method not allowed.', 405);
}
?>
