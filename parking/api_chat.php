<?php
// c:/xampp/htdocs/parking/api_chat.php
include "config.php";
include "auth.php";
header("Content-Type: application/json; charset=utf-8");

// Base table + backward-compatible upgrades
$conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    sender_type ENUM('admin','client') NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text','voice') NOT NULL DEFAULT 'text',
    media_path VARCHAR(255) DEFAULT NULL,
    media_mime VARCHAR(100) DEFAULT NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("ALTER TABLE chat_messages ADD COLUMN client_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id");
$conn->query("ALTER TABLE chat_messages ADD COLUMN message_type ENUM('text','voice') NOT NULL DEFAULT 'text' AFTER message");
$conn->query("ALTER TABLE chat_messages ADD COLUMN media_path VARCHAR(255) DEFAULT NULL AFTER message_type");
$conn->query("ALTER TABLE chat_messages ADD COLUMN media_mime VARCHAR(100) DEFAULT NULL AFTER media_path");
$conn->query("ALTER TABLE chat_messages ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER media_mime");
$conn->query("ALTER TABLE chat_messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER is_deleted");
$conn->query("ALTER TABLE chat_messages ADD COLUMN deleted_by_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER is_read");
$conn->query("ALTER TABLE chat_messages ADD COLUMN deleted_by_client TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_by_admin");
$conn->query("ALTER TABLE chat_messages ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER deleted_by_client");
$conn->query("CREATE INDEX idx_chat_client_id ON chat_messages (client_id)");
$conn->query("CREATE INDEX idx_chat_created_at ON chat_messages (created_at)");

function failJson($message, $extra = [])
{
    echo json_encode(array_merge(["success" => false, "message" => $message], $extra));
    exit;
}

function getSenderContext()
{
    if (isLoggedIn()) {
        return [
            "role" => "admin",
            "name" => $_SESSION["admin_name"] ?? "Admin",
            "client_id" => 0
        ];
    }
    if (isClientLoggedIn()) {
        return [
            "role" => "client",
            "name" => $_SESSION["client_name"] ?? "Client",
            "client_id" => (int)($_SESSION["client_id"] ?? 0)
        ];
    }
    return null;
}

function resolveClientChannelId($senderCtx)
{
    if (!$senderCtx) {
        return 0;
    }
    if ($senderCtx["role"] === "client") {
        return (int)$senderCtx["client_id"];
    }
    $cid = (int)($_POST["client_id"] ?? 0);
    return $cid > 0 ? $cid : 0;
}

function sanitizeMessageText($message)
{
    $msg = trim((string)$message);
    if ($msg === "") {
        return "";
    }
    if (function_exists("mb_strlen") && function_exists("mb_substr")) {
        if (mb_strlen($msg) > 4000) {
            $msg = mb_substr($msg, 0, 4000);
        }
    } else if (strlen($msg) > 4000) {
        $msg = substr($msg, 0, 4000);
    }
    return $msg;
}

function normalizeUploadedAudioExt($mime, $originalName)
{
    $map = [
        "audio/webm" => "webm",
        "audio/ogg" => "ogg",
        "audio/mpeg" => "mp3",
        "audio/mp3" => "mp3",
        "audio/wav" => "wav",
        "audio/x-wav" => "wav",
        "audio/mp4" => "m4a",
        "audio/aac" => "aac"
    ];
    if (isset($map[$mime])) {
        return $map[$mime];
    }
    $ext = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));
    $allowed = ["webm", "ogg", "mp3", "wav", "m4a", "aac"];
    return in_array($ext, $allowed, true) ? $ext : "webm";
}

function safeDeleteMediaFile($relativePath)
{
    if (!$relativePath) {
        return;
    }
    $baseDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "chat_audio");
    if (!$baseDir) {
        return;
    }
    $target = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $relativePath));
    if (!$target) {
        return;
    }
    if (strpos($target, $baseDir) !== 0) {
        return;
    }
    if (is_file($target)) {
        @unlink($target);
    }
}

// ===== POST actions =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim((string)($_POST["action"] ?? ""));
    if ($action === "" && isset($_POST["message"])) {
        $action = "send_text"; // backward compatibility
    }

    $senderCtx = getSenderContext();
    if (!$senderCtx) {
        failJson("Not logged in");
    }

    if ($action === "send_text") {
        $msg = sanitizeMessageText($_POST["message"] ?? "");
        if ($msg === "") {
            failJson("Empty message");
        }

        $clientId = resolveClientChannelId($senderCtx);
        if ($clientId <= 0) {
            failJson("No client selected");
        }

        $stmt = $conn->prepare("INSERT INTO chat_messages (client_id, sender_type, sender_name, message, message_type) VALUES (?, ?, ?, ?, 'text')");
        $stmt->bind_param("isss", $clientId, $senderCtx["role"], $senderCtx["name"], $msg);
        $ok = $stmt->execute();
        echo json_encode(["success" => $ok, "id" => (int)$conn->insert_id]);
        exit;
    }

    if ($action === "send_voice") {
        $clientId = resolveClientChannelId($senderCtx);
        if ($clientId <= 0) {
            failJson("No client selected");
        }
        if (!isset($_FILES["voice_note"])) {
            failJson("Voice file not found");
        }

        $file = $_FILES["voice_note"];
        if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            failJson("Upload failed");
        }
        if (($file["size"] ?? 0) <= 0 || ($file["size"] ?? 0) > 10 * 1024 * 1024) {
            failJson("Invalid file size");
        }

        $mime = (string)($file["type"] ?? "application/octet-stream");
        $ext = normalizeUploadedAudioExt($mime, $file["name"] ?? "");

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "chat_audio";
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        if (!is_dir($uploadDir)) {
            failJson("Upload directory unavailable");
        }

        $filename = "voice_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
            failJson("Unable to save voice file");
        }

        $relativePath = "uploads/chat_audio/" . $filename;
        $messageText = "[Voice message]";
        $stmt = $conn->prepare("INSERT INTO chat_messages (client_id, sender_type, sender_name, message, message_type, media_path, media_mime) VALUES (?, ?, ?, ?, 'voice', ?, ?)");
        $stmt->bind_param("isssss", $clientId, $senderCtx["role"], $senderCtx["name"], $messageText, $relativePath, $mime);
        $ok = $stmt->execute();
        echo json_encode([
            "success" => $ok,
            "id" => (int)$conn->insert_id,
            "media_path" => $relativePath
        ]);
        exit;
    }

    if ($action === "delete_message") {
        $messageId = (int)($_POST["message_id"] ?? 0);
        $deleteType = (string)($_POST["delete_type"] ?? "for_everyone"); // for_me, for_everyone
        if ($messageId <= 0) {
            failJson("Invalid message id");
        }

        $stmtGet = $conn->prepare("SELECT id, client_id, sender_type, media_path FROM chat_messages WHERE id = ? LIMIT 1");
        $stmtGet->bind_param("i", $messageId);
        $stmtGet->execute();
        $msgRow = $stmtGet->get_result()->fetch_assoc();
        if (!$msgRow) {
            failJson("Message not found");
        }

        if ($deleteType === "for_me") {
            if ($senderCtx["role"] === "admin") {
                $stmtDel = $conn->prepare("UPDATE chat_messages SET deleted_by_admin = 1 WHERE id = ?");
            } else {
                if ((int)$msgRow["client_id"] !== (int)$senderCtx["client_id"]) failJson("Not allowed");
                $stmtDel = $conn->prepare("UPDATE chat_messages SET deleted_by_client = 1 WHERE id = ?");
            }
            $stmtDel->bind_param("i", $messageId);
            $ok = $stmtDel->execute();
            echo json_encode(["success" => $ok]);
            exit;
        }

        $canDelete = false;
        if ($senderCtx["role"] === "admin") {
            $canDelete = true;
        } else if ($senderCtx["role"] === "client") {
            $canDelete = ((int)$msgRow["client_id"] === (int)$senderCtx["client_id"] && $msgRow["sender_type"] === "client");
        }
        if (!$canDelete) {
            failJson("Not allowed");
        }

        $placeholder = "[Pesan dihapus]";
        $stmtDel = $conn->prepare("UPDATE chat_messages SET is_deleted = 1, deleted_at = NOW(), message = ?, media_path = NULL, media_mime = NULL, message_type = 'text' WHERE id = ?");
        $stmtDel->bind_param("si", $placeholder, $messageId);
        $ok = $stmtDel->execute();
        safeDeleteMediaFile($msgRow["media_path"] ?? "");
        echo json_encode(["success" => $ok]);
        exit;
    }

    if ($action === "delete_conversation") {
        if ($senderCtx["role"] !== "admin") {
            failJson("Admin only");
        }
        $clientId = (int)($_POST["client_id"] ?? 0);
        if ($clientId <= 0) {
            failJson("Invalid client id");
        }

        $resMedia = $conn->prepare("SELECT media_path FROM chat_messages WHERE client_id = ? AND media_path IS NOT NULL");
        $resMedia->bind_param("i", $clientId);
        $resMedia->execute();
        $mediaRows = $resMedia->get_result();
        while ($mr = $mediaRows->fetch_assoc()) {
            safeDeleteMediaFile($mr["media_path"] ?? "");
        }

        $stmt = $conn->prepare("DELETE FROM chat_messages WHERE client_id = ?");
        $stmt->bind_param("i", $clientId);
        $ok = $stmt->execute();
        echo json_encode(["success" => $ok]);
        exit;
    }

    failJson("Invalid action");
}

// ===== GET actions =====
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $senderCtx = getSenderContext();
    if (!$senderCtx) {
        echo json_encode(["messages" => [], "latest_id" => 0]);
        exit;
    }

    if (isset($_GET["conversations"])) {
        if ($senderCtx["role"] !== "admin") {
            failJson("Admin only");
        }
        $sql = "SELECT c.client_id, u.name, u.plate_number,
                    (SELECT COUNT(*) FROM chat_messages WHERE client_id = c.client_id) AS msg_count,
                    (SELECT message FROM chat_messages WHERE client_id = c.client_id ORDER BY id DESC LIMIT 1) AS last_msg,
                    (SELECT message_type FROM chat_messages WHERE client_id = c.client_id ORDER BY id DESC LIMIT 1) AS last_type,
                    (SELECT is_deleted FROM chat_messages WHERE client_id = c.client_id ORDER BY id DESC LIMIT 1) AS last_deleted,
                    (SELECT created_at FROM chat_messages WHERE client_id = c.client_id ORDER BY id DESC LIMIT 1) AS last_time,
                    (SELECT COUNT(*) FROM chat_messages WHERE client_id = c.client_id AND sender_type = 'client' AND is_read = 0 AND deleted_by_admin = 0) AS unread_count
                FROM (SELECT DISTINCT client_id FROM chat_messages WHERE client_id > 0) c
                LEFT JOIN users u ON c.client_id = u.id
                ORDER BY last_time DESC";
        $res = $conn->query($sql);
        $convos = [];
        while ($row = $res->fetch_assoc()) {
            $preview = (string)($row["last_msg"] ?? "");
            if (($row["last_deleted"] ?? "0") === "1") {
                $preview = "[Pesan dihapus]";
            } else if (($row["last_type"] ?? "text") === "voice") {
                $preview = "[Voice message]";
            }
            $convos[] = [
                "client_id" => (int)$row["client_id"],
                "name" => $row["name"],
                "plate_number" => $row["plate_number"],
                "msg_count" => (int)$row["msg_count"],
                "unread_count" => (int)$row["unread_count"],
                "last_msg" => $preview,
                "last_time" => $row["last_time"]
            ];
        }
        echo json_encode(["conversations" => $convos]);
        exit;
    }

    $clientId = 0;
    if ($senderCtx["role"] === "client") {
        $clientId = (int)$senderCtx["client_id"];
    } else if ($senderCtx["role"] === "admin") {
        $clientId = (int)($_GET["client_id"] ?? 0);
    }
    if ($clientId <= 0) {
        echo json_encode(["messages" => [], "latest_id" => 0]);
        exit;
    }

    // Mark as read whenever fetched
    if ($senderCtx["role"] === "admin") {
        $stmtUp = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE client_id = ? AND sender_type = 'client' AND is_read = 0");
        $stmtUp->bind_param("i", $clientId);
        $stmtUp->execute();
    } else if ($senderCtx["role"] === "client") {
        $stmtUp = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE client_id = ? AND sender_type = 'admin' AND is_read = 0");
        $stmtUp->bind_param("i", $clientId);
        $stmtUp->execute();
    }

    $sinceId = (int)($_GET["since_id"] ?? 0);
    $condition = ($senderCtx["role"] === "admin") ? "AND deleted_by_admin = 0" : "AND deleted_by_client = 0";

    if ($sinceId > 0) {
        $stmt = $conn->prepare("SELECT id, client_id, sender_type, sender_name, message, message_type, media_path, media_mime, is_deleted, created_at, is_read
                                FROM chat_messages WHERE client_id = ? AND id > ? $condition ORDER BY id ASC LIMIT 80");
        $stmt->bind_param("ii", $clientId, $sinceId);
    } else {
        $stmt = $conn->prepare("SELECT id, client_id, sender_type, sender_name, message, message_type, media_path, media_mime, is_deleted, created_at, is_read
                                FROM chat_messages WHERE client_id = ? $condition ORDER BY id DESC LIMIT 60");
        $stmt->bind_param("i", $clientId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $row["id"] = (int)$row["id"];
        $row["client_id"] = (int)$row["client_id"];
        $row["is_deleted"] = (int)$row["is_deleted"];
        $row["is_read"] = (int)$row["is_read"];
        if ($row["is_deleted"] === 1) {
            $row["message"] = "[Pesan dihapus]";
            $row["message_type"] = "text";
            $row["media_path"] = null;
            $row["media_mime"] = null;
        }
        $messages[] = $row;
    }
    if ($sinceId === 0) {
        $messages = array_reverse($messages);
    }

    $latestId = $sinceId;
    foreach ($messages as $m) {
        if ($m["id"] > $latestId) {
            $latestId = $m["id"];
        }
    }

    echo json_encode(["messages" => $messages, "latest_id" => (int)$latestId]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
?>
