<?php
require_once 'includes/auth_check.php';
checkAccess('Admin');
require_once 'config/db.php';
$perms = $pdo->query("SELECT * FROM PERMISSION ORDER BY Permission_ID")->fetchAll();
$codes = array_column($perms, 'Permission_Code', 'Permission_ID');
function grants($pdo, $uid) {
    $s = $pdo->prepare("SELECT Permission_ID FROM USER_PERMISSION WHERE User_ID=?"); $s->execute([$uid]);
    return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = (int)($_POST['user_id'] ?? 0); $role = $_POST['role'] ?? '';
    $chosen = array_values(array_intersect(array_map('intval', (array)($_POST['perm'] ?? [])), array_keys($codes)));
    $u = $pdo->prepare("SELECT Username, Role FROM USERS WHERE User_ID=?"); $u->execute([$uid]); $target = $u->fetch();
    if (!$target || !in_array($role, ['Admin', 'Officer'], true)) flash('Invalid request.', 'err');
    elseif ($uid === (int)$_SESSION['user_id']) flash('You cannot change your own access.', 'err');
    else {
        try {
            $before = grants($pdo, $uid);
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM USER_PERMISSION WHERE User_ID=?")->execute([$uid]);
            $ins = $pdo->prepare("INSERT INTO USER_PERMISSION (User_ID,Permission_ID,Granted_By) VALUES (?,?,?)");
            foreach ($chosen as $pid) $ins->execute([$uid, $pid, $_SESSION['user_id']]);
            $pdo->prepare("UPDATE USERS SET Role=? WHERE User_ID=?")->execute([$role, $uid]);
            $names = fn($ids) => $ids ? implode(', ', array_map(fn($i) => $codes[$i], $ids)) : 'none';
            log_activity($pdo, 'ACCESS_CHANGED', "Target {$target['Username']} (ID $uid). Role {$target['Role']} -> $role. Access: " . $names($before) . ' -> ' . $names($chosen));
            $pdo->commit();
            flash("Confirmed: access for {$target['Username']} was updated. Role: $role. Access now: " . $names($chosen) . '. This change is recorded in the history log.');
        } catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('Could not save access changes.', 'err'); }
    }
    header('Location: access_control.php'); exit();
}
$users = $pdo->query("SELECT u.User_ID, u.Username, u.Role, o.Officer_ID, o.Officer_Name FROM USERS u JOIN OFFICER o ON u.Officer_ID=o.Officer_ID ORDER BY u.User_ID")->fetchAll();
page_head('Access Control', true);
show_flash();
?>
<h1>Access Control</h1>
<p><a class="btn sm" href="register.php">+ Register New Account</a></p>
<div class="card">
  <h2>What each access allows</h2>
  <?php foreach ($perms as $p): ?><p><b><?= h($p['Permission_Name']) ?></b> <span class="muted">(<?= h($p['Permission_Code']) ?>)</span><br><?= h($p['Description']) ?></p><?php endforeach; ?>
  <p class="muted">Admins always have oversight access (track all incidents, assign officers, history log, access control). Officers and other public officials only get the accesses ticked below.</p>
</div>
<?php foreach ($users as $u): $mine = grants($pdo, $u['User_ID']); $self = (int)$u['User_ID'] === (int)$_SESSION['user_id']; ?>
<div class="ucard">
  <form method="POST" data-user="<?= h($u['Username']) ?>" onsubmit="return conf(this)"><?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= (int)$u['User_ID'] ?>">
    <h3><?= h($u['Username']) ?> <span class="muted">– <?= h($u['Officer_ID'] . ' ' . $u['Officer_Name']) ?> (User ID <?= (int)$u['User_ID'] ?>)</span><?= $self ? ' <span class="pill">you</span>' : '' ?></h3>
    <label>Role</label>
    <select name="role" style="max-width:200px" <?= $self ? 'disabled' : '' ?>><?= opts([['r' => 'Officer'], ['r' => 'Admin']], 'r', 'r', $u['Role']) ?></select>
    <label>Access</label>
    <?php foreach ($perms as $p): ?>
      <label class="perm"><input type="checkbox" name="perm[]" value="<?= (int)$p['Permission_ID'] ?>" <?= in_array((int)$p['Permission_ID'], $mine, true) ? 'checked' : '' ?> <?= $self ? 'disabled' : '' ?>>
      <span><b><?= h($p['Permission_Name']) ?></b><?= h($p['Description']) ?></span></label>
    <?php endforeach; ?>
    <?php if (!$self): ?><button class="btn sm" style="margin-top:8px">Save access</button><?php endif; ?>
  </form>
</div>
<?php endforeach; ?>
<script>
function conf(f) {
  return confirm('Confirm access change for "' + f.dataset.user + '"?\n\nThe new role and access take effect immediately and will be recorded in the history log under your admin ID.');
}
</script>
<?php page_foot(); ?>
