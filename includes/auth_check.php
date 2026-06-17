<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    $_SESSION['flash_message'] = 'Silakan login terlebih dahulu.';
    $_SESSION['flash_type']    = 'warning';
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}
?>
