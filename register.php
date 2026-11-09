<?php
require_once 'includes/auth_check.php';
checkAccess('Admin');
require_once 'config/db.php';
$errors = [];
function unlinked($pdo) {
    return $pdo->query("SELECT o.Officer_ID, CONCAT(o.Officer_ID,' - ',o.Officer_Name) AS Label FROM OFFICER o
        LEFT JOIN USERS u ON u.Officer_ID=o.Officer_ID WHERE u.User_ID IS NULL ORDER BY o.Officer_ID")->fetchAll();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oid = $_POST['officer_id'] ?? ''; $uname = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ''; $oname = trim($_POST['officer_name'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $uname)) $errors[] = 'Username: 3-30 letters, numbers, dot or underscore.';
    if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!in_array($role, ['Admin', 'Officer'], true)) $errors[] = 'Choose a valid role.';
    if ($oid === 'new' && (mb_strlen($oname) < 2 || mb_strlen($oname) > 100)) $errors[] = 'Enter the new officer\'s full name.';
    if ($oid !== 'new' && !in_array($oid, array_column(unlinked($pdo), 'Officer_ID'), true)) $errors[] = 'Choose an officer profile without an account.';
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            if ($oid === 'new') {
                $n = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(Officer_ID,3) AS UNSIGNED)),0) FROM OFFICER")->fetchColumn() + 1;
                $oid = sprintf('O-%02d', $n);
                $pdo->prepare("INSERT INTO OFFICER VALUES (?,?)")->execute([$oid, $oname]);
            }
            $pdo->prepare("INSERT INTO USERS (Username,Password_Hash,Role,Officer_ID) VALUES (?,?,?,?)")
                ->execute([$uname, password_hash($pw, PASSWORD_BCRYPT), $role, $oid]);
            $newUid = (int)$pdo->lastInsertId();
            if ($role === 'Officer') $pdo->prepare("INSERT INTO USER_PERMISSION (User_ID,Permission_ID,Granted_By) SELECT ?,Permission_ID,? FROM PERMISSION WHERE Permission_Code IN ('VIEW_ASSIGNED','UPDATE_STATUS','ADD_FOLLOWUP')")->execute([$newUid, $_SESSION['user_id']]);
            log_activity($pdo, 'ACCOUNT_CREATED', "Account '$uname' ($role) for officer $oid; default access granted");
            $pdo->commit();
            flash("Account '$uname' created as $role.");
            header('Location: admin_dashboard.php'); exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getCode() === '23000' ? 'That username is already taken.' : 'Could not create the account.';
        }
    }
}
page_head('Register Account', true);
?>
<div class="card" style="max-width:520px;margin:auto">
  <h1>Register New Account</h1>
  <p class="muted">Create a login and assign its role. Only admins can do this.</p>
  <?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div><?php endif; ?>
  <form method="POST">
    <?= csrf_field() ?>
    <label>Officer profile</label>
    <select name="officer_id" required>
      <option value="new">+ New officer (type name below)</option>
      <?= opts(unlinked($pdo), 'Officer_ID', 'Label', $_POST['officer_id'] ?? null) ?>
    </select>
    <label>New officer's full name (only if "New officer")</label>
    <input name="officer_name" maxlength="100" value="<?= h($_POST['officer_name'] ?? '') ?>">
    <label>Username</label><input name="username" required value="<?= h($_POST['username'] ?? '') ?>">
    <label>Password (min. 8 characters)</label><input type="password" name="password" required autocomplete="new-password">
    <label>Role</label>
    <select name="role"><option>Officer</option><option>Admin</option></select>
    <button class="btn full">Create Account</button>
  </form>
  <p><a href="admin_dashboard.php">← Back to dashboard</a></p>
</div>
<?php page_foot(); ?>
