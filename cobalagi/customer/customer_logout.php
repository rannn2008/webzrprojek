<?php
require_once '../config/config.php';

// Clear customer session
unset($_SESSION['customer_logged_in']);
unset($_SESSION['customer_id']);
unset($_SESSION['customer_nama']);
unset($_SESSION['customer_whatsapp']);

// Redirect to home
header("Location: ../index.php");
exit();
?>
