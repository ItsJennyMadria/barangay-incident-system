<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
if (!empty($_SESSION['user_id'])) log_activity($pdo, 'LOGOUT', 'Logged out');
$_SESSION = [];
session_destroy();
header("Location: login.php?out=1");
exit();
