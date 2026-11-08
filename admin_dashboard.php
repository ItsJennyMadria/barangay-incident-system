<?php
require_once 'includes/auth_check.php';
checkAccess('Admin');
require_once 'config/db.php';

$totalIncidents = $pdo->query("SELECT COUNT(*) FROM INCIDENT")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM USERS")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head><title>Admin Dashboard</title></head>
<body>
    <h1>System Administration Dashboard</h1>
    <p>Welcome, <strong><?= $_SESSION['officer_name'] ?></strong> (Role: <?= $_SESSION['role'] ?> | ID: <?= $_SESSION['officer_id'] ?>)</p>
    <hr>
    <h3>System Overview</h3>
    <ul>
        <li>Total Incidents Logged: <strong><?= $totalIncidents ?></strong></li>
        <li>Registered System Accounts: <strong><?= $totalUsers ?></strong></li>
    </ul>
    <p><a href="register.php">Register New User Account</a> | <a href="logout.php">Logout</a></p>
</body>
</html>