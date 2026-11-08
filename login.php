<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit(); }
csrf_check();
$stmt = $pdo->prepare("SELECT u.*, o.Officer_Name FROM USERS u JOIN OFFICER o ON u.Officer_ID = o.Officer_ID WHERE u.Username = ?");
$stmt->execute([trim($_POST['username'] ?? '')]);
$user = $stmt->fetch();
if ($user && password_verify($_POST['password'] ?? '', $user['Password_Hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['User_ID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['officer_id'] = $user['Officer_ID'];
    $_SESSION['officer_name'] = $user['Officer_Name'];
    header('Location: ' . ($user['Role'] === 'Admin' ? 'admin_dashboard.php' : 'officer_dashboard.php'));
    exit();
}
flash('Invalid username or password.', 'err');
header('Location: index.php');
exit();
