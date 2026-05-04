<?php
// c:/xampp/htdocs/parking/api_get_unread_chat.php
include "config.php";
include "auth.php";
header("Content-Type: application/json; charset=utf-8");

$response = ["success" => false, "unread_count" => 0];

if (isLoggedIn()) {
    // Admin is logged in. Get all unread messages from valid clients
    $sql = "SELECT COUNT(*) as c FROM chat_messages WHERE sender_type = 'client' AND client_id > 0 AND is_read = 0 AND deleted_by_admin = 0";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $response["unread_count"] = (int)$row["c"];
        $response["success"] = true;
    }
} else if (isClientLoggedIn()) {
    // Client is logged in. Get unread messages from admin to this client
    $client_id = (int)$_SESSION["client_id"];
    $sql = "SELECT COUNT(*) as c FROM chat_messages WHERE client_id = $client_id AND sender_type = 'admin' AND is_read = 0 AND deleted_by_client = 0";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $response["unread_count"] = (int)$row["c"];
        $response["success"] = true;
    }
} else {
    $response["message"] = "Not logged in";
}

echo json_encode($response);
?>
