<?php
session_start();
session_unset();
session_destroy();
session_start();
$_SESSION['flash_message'] = 'Anda telah berhasil logout.';
$_SESSION['flash_type']    = 'info';

require_once '../includes/config.php';
header('Location: ' . BASE_URL . 'pages/login.php');
exit;
?>
