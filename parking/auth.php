<?php
// c:/xampp/htdocs/parking/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION["admin_id"]);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function isClientLoggedIn()
{
    return isset($_SESSION["client_id"]);
}

function requireClientLogin()
{
    if (!isClientLoggedIn()) {
        header("Location: client_login.php");
        exit();
    }
}

// Security Guard: Prevent Role Confusion
function restrictToAdmin() {
    requireLogin();
    if (isset($_SESSION["client_id"])) {
        unset($_SESSION["client_id"]);
        unset($_SESSION["client_name"]);
        header("Location: login.php?error=conflict");
        exit();
    }
}

function restrictToClient() {
    requireClientLogin();
    if (isset($_SESSION["admin_id"])) {
        unset($_SESSION["admin_id"]);
        unset($_SESSION["admin_name"]);
        header("Location: client_login.php?error=conflict");
        exit();
    }
}
?>
