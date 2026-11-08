<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT u.*, o.Officer_Name 
        FROM USERS u
        JOIN OFFICER o ON u.Officer_ID = o.Officer_ID
        WHERE u.Username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password_Hash'])) {
        $_SESSION['user_id'] = $user['User_ID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['officer_id'] = $user['Officer_ID'];
        $_SESSION['officer_name'] = $user['Officer_Name'];

        if ($user['Role'] === 'Admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: officer_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>System Login</title></head>
<body>
    <h2>Barangay System Login</h2>
    <?php if($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
    <form method="POST" action="login.php">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Log In</button>
    </form>
    <p>Need an account? <a href="register.php">Register</a></p>
</body>
</html>