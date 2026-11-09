<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$home = fn($r) => $r === 'Admin' ? 'admin_dashboard.php' : 'officer_dashboard.php';
if (!empty($_SESSION['user_id'])) { header('Location: ' . $home($_SESSION['role'])); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        log_activity($pdo, 'LOGIN', 'Logged in as ' . $user['Role']);
        header('Location: ' . $home($user['Role'])); exit();
    }
    log_activity($pdo, 'LOGIN_FAILED', 'Attempted username: ' . trim($_POST['username'] ?? ''));
    $error = 'Invalid username or password.';
}
page_head('Staff Login');
?>
<div class="card login">
  <h1>🔐 Staff Portal</h1>
  <p class="muted">For barangay administrators and officers only.</p>
  <?php if ($error): ?><div class="alert err"><?= h($error) ?></div><?php endif; ?>
  <?php if (isset($_GET['out']) && !$error): ?><div class="alert ok">You have been logged out.</div><?php endif; ?>
  <form method="POST" action="login.php">
    <?= csrf_field() ?>
    <label>Username</label><input name="username" required autocomplete="username" autofocus>
    <label>Password</label><input type="password" name="password" required autocomplete="current-password">
    <button class="btn full" type="submit">Log In</button>
  </form>
  <p style="margin-top:18px"><a href="index.php">← Back to the public reporting page</a></p>
</div>
<?php page_foot(); ?>
