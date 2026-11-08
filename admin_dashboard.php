<?php
require_once 'includes/auth_check.php';
checkAccess('Admin');
require_once 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (($_POST['action'] ?? '') === 'incident') {
            $off = ($_POST['officer_id'] ?? '') === '' ? null : $_POST['officer_id'];
            $pdo->prepare("UPDATE INCIDENT SET Officer_ID=?, Status_ID=? WHERE Incident_Ref=?")
                ->execute([$off, (int)$_POST['status_id'], $_POST['ref']]);
            flash('Incident ' . $_POST['ref'] . ' updated.');
        } elseif (($_POST['action'] ?? '') === 'role') {
            $role = $_POST['role'] ?? '';
            if (!in_array($role, ['Admin', 'Officer'], true) || (int)$_POST['user_id'] === (int)$_SESSION['user_id']) {
                flash('You cannot change your own role, and the role must be Admin or Officer.', 'err');
            } else {
                $pdo->prepare("UPDATE USERS SET Role=? WHERE User_ID=?")->execute([$role, (int)$_POST['user_id']]);
                flash('Role updated.');
            }
        }
    } catch (PDOException $e) { flash('Update failed. Please check your input.', 'err'); }
    header('Location: admin_dashboard.php'); exit();
}
$stat = fn($sql) => $pdo->query($sql)->fetchColumn();
$statuses = $pdo->query("SELECT * FROM INCIDENT_STATUS ORDER BY Status_ID")->fetchAll();
$officers = $pdo->query("SELECT o.Officer_ID, o.Officer_Name FROM OFFICER o LEFT JOIN USERS u ON u.Officer_ID=o.Officer_ID
    WHERE u.Role IS NULL OR u.Role<>'Admin' ORDER BY o.Officer_ID")->fetchAll();
$incidents = $pdo->query("SELECT i.*, r.Resident_Name, r.Contact, t.Type_Name FROM INCIDENT i
    JOIN RESIDENT r ON i.Resident_ID=r.Resident_ID JOIN INCIDENT_TYPE t ON i.Type_ID=t.Type_ID
    ORDER BY (i.Officer_ID IS NULL) DESC, i.Incident_Date DESC, i.Incident_Ref DESC")->fetchAll();
$users = $pdo->query("SELECT u.User_ID, u.Username, u.Role, o.Officer_ID, o.Officer_Name FROM USERS u
    JOIN OFFICER o ON u.Officer_ID=o.Officer_ID ORDER BY u.User_ID")->fetchAll();
$roles = [['r' => 'Admin'], ['r' => 'Officer']];
page_head('Admin Dashboard', true);
show_flash();
?>
<h1>System Administration</h1>
<div class="stats">
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM INCIDENT") ?></b>Total incidents</div>
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM INCIDENT WHERE Officer_ID IS NULL") ?></b>Awaiting assignment</div>
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM INCIDENT i JOIN INCIDENT_STATUS s ON i.Status_ID=s.Status_ID WHERE s.Status_Name<>'Closed'") ?></b>Not yet closed</div>
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM USERS") ?></b>System accounts</div>
</div>
<div class="card">
  <h2>Incidents – assign officers &amp; update status</h2>
  <div class="tbl"><table>
    <tr><th>Ref</th><th>Date</th><th>Type</th><th>Location</th><th>Complainant</th><th>Assign / Status</th></tr>
    <?php foreach ($incidents as $i): ?>
    <tr>
      <td><b><?= h($i['Incident_Ref']) ?></b></td><td><?= h($i['Incident_Date']) ?></td><td><?= h($i['Type_Name']) ?></td>
      <td><?= h($i['Location']) ?></td><td><?= h($i['Resident_Name']) ?><br><span class="muted"><?= h($i['Contact']) ?></span></td>
      <td><form method="POST" class="inline"><?= csrf_field() ?>
        <input type="hidden" name="action" value="incident"><input type="hidden" name="ref" value="<?= h($i['Incident_Ref']) ?>">
        <select name="officer_id"><?= opts($officers, 'Officer_ID', 'Officer_Name', $i['Officer_ID'], '-- Unassigned --') ?></select>
        <select name="status_id"><?= opts($statuses, 'Status_ID', 'Status_Name', $i['Status_ID']) ?></select>
        <button class="btn sm">Save</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<div class="card">
  <h2>User accounts &amp; roles</h2>
  <p><a class="btn sm" href="register.php">+ Register New Account</a></p>
  <div class="tbl"><table>
    <tr><th>Username</th><th>Officer profile</th><th>Role</th></tr>
    <?php foreach ($users as $u): ?>
    <tr><td><b><?= h($u['Username']) ?></b></td><td><?= h($u['Officer_ID'] . ' – ' . $u['Officer_Name']) ?></td>
      <td><?php if ((int)$u['User_ID'] === (int)$_SESSION['user_id']): ?><span class="pill"><?= h($u['Role']) ?></span> (you)
        <?php else: ?><form method="POST" class="inline"><?= csrf_field() ?>
          <input type="hidden" name="action" value="role"><input type="hidden" name="user_id" value="<?= (int)$u['User_ID'] ?>">
          <select name="role"><?= opts($roles, 'r', 'r', $u['Role']) ?></select><button class="btn sm">Change</button></form>
        <?php endif; ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php page_foot(); ?>
