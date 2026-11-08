<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAccess($allowed_role) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    if ($_SESSION['role'] !== $allowed_role) {
        echo "<h2 style='color:red;'>403 Access Denied: You do not have permission to view this page.</h2>";
        echo "<a href='logout.php'>Logout</a>";
        exit();
    }
}
?>