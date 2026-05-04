<?php
/**
 * Shared helpers for chat APIs.
 */

function ensure_chats_table($conn)
{
    $createTableSql = "CREATE TABLE IF NOT EXISTS chats (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_type ENUM('customer', 'admin') NOT NULL,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!secure_query($conn, $createTableSql, "", [], false)) {
        return false;
    }

    // Backward-compatible migration for existing chats table.
    $checkOrderId = secure_query($conn, "SHOW COLUMNS FROM chats LIKE 'order_id'", "", []);
    if ($checkOrderId && $checkOrderId->num_rows === 0) {
        if (!secure_query($conn, "ALTER TABLE chats ADD COLUMN order_id INT NULL AFTER id", "", [], false)) {
            return false;
        }
    }

    // Indexes speed up chat list and unread lookups.
    if (!ensure_chats_index($conn, 'idx_chats_sender_type_sender_id', 'sender_type, sender_id')) {
        return false;
    }
    if (!ensure_chats_index($conn, 'idx_chats_sender_type_receiver_id', 'sender_type, receiver_id')) {
        return false;
    }
    if (!ensure_chats_index($conn, 'idx_chats_unread', 'is_read, sender_type')) {
        return false;
    }
    if (!ensure_chats_index($conn, 'idx_chats_order_id', 'order_id')) {
        return false;
    }
    if (!ensure_chats_index($conn, 'idx_chats_order_unread', 'order_id, is_read')) {
        return false;
    }

    return true;
}

function ensure_chats_index($conn, $indexName, $columnSql)
{
    // Key_name is safe here as it's from a trusted source or controlled index name
    $check = secure_query($conn, "SHOW INDEX FROM chats WHERE Key_name = ?", "s", [$indexName]);

    if ($check && $check->num_rows > 0) {
        return true;
    }

    // Index creation SQL usually doesn't support parameterized index/column names
    // so we use standard mysqli_query after ensuring input is safe (though these are hardcoded in the app)
    return mysqli_query($conn, "CREATE INDEX $indexName ON chats ($columnSql)") !== false;
}

function chat_json_error($message, $statusCode = 400)
{
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function fetch_order_chat_context($conn, $orderId)
{
    return fetch_one(secure_query($conn, "SELECT id, order_code, customer_id, status FROM orders WHERE id = ? LIMIT 1", "i", [$orderId]));
}

function is_order_chat_allowed_for_customer($status)
{
    $status = strtolower((string) $status);
    return in_array($status, ['process', 'diterima', 'preparing', 'diracik', 'ready', 'siap', 'done', 'selesai'], true);
}

function is_order_chat_send_allowed($status)
{
    $status = strtolower((string) $status);
    return in_array($status, ['process', 'diterima', 'preparing', 'diracik', 'ready', 'siap'], true);
}
