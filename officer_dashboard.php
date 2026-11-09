<?php
require_once 'includes/auth_check.php';
checkAccess('Officer');
require_once 'config/db.php';
$me = $_SESSION['officer_id'];
$statuses = $pdo->query("SELECT * FROM INCIDENT_STATUS ORDER BY Status_ID")->fetchAll();
$sn = array_column($statuses, 'Status_Name', 'Status_ID');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ref = $_POST['ref'] ?? ''; $act = $_POST['action'] ?? '';
    $own = $pdo->prepare("SELECT Status_ID FROM INCIDENT WHERE Incident_Ref=? AND Officer_ID=?");
    $own->execute([$ref, $me]);
    $cur = $own->fetch();
    try {
        if (!$cur || !can($pdo, 'VIEW_ASSIGNED')) flash('That incident is not assigned to you.', 'err');
        elseif ($act === 'status') {
            $new = (int)($_POST['status_id'] ?? 0);
            if (!can($pdo, 'UPDATE_STATUS')) flash('You do not have access to change case status.', 'err');
            elseif (!isset($sn[$new])) flash('Invalid status.', 'err');
            else {
                $pdo->prepare("UPDATE INCIDENT SET Status_ID=? WHERE Incident_Ref=? AND Officer_ID=?")->execute([$new, $ref, $me]);
                log_activity($pdo, 'STATUS_CHANGED', $sn[$cur['Status_ID']] . ' -> ' . $sn[$new], $ref);
                flash("Status of $ref updated.");
            }
        } elseif ($act === 'followup') {
            $note = trim($_POST['note'] ?? '');
            if (!can($pdo, 'ADD_FOLLOWUP')) flash('You do not have access to add follow-ups.', 'err');
            elseif ($note === '' || mb_strlen($note) > 255) flash('Enter a note (max 255 characters).', 'err');
            else {
                $pdo->prepare("INSERT INTO INCIDENT_FOLLOWUP (Incident_Ref,Follow_up_Date,Follow_up_Note) VALUES (?,CURDATE(),?)")->execute([$ref, $note]);
                log_activity($pdo, 'FOLLOWUP_ADDED', $note, $ref);
                flash("Follow-up added to $ref.");
            }
        }
    } catch (PDOException $e) { flash('Update failed.', 'err'); }
    header('Location: officer_dashboard.php'); exit();
}
$cases = []; $notes = [];
if (can($pdo, 'VIEW_ASSIGNED')) {
    $st = $pdo->prepare("SELECT i.Incident_Ref, i.Incident_Date, i.Incident_Time, i.Description, i.Other_Specify, i.Location, i.Status_ID, r.Resident_Name, r.Contact, t.Type_Name
        FROM INCIDENT i JOIN RESIDENT r ON i.Resident_ID=r.Resident_ID JOIN INCIDENT_TYPE t ON i.Type_ID=t.Type_ID
        WHERE i.Officer_ID=? ORDER BY i.Incident_Date DESC");
    $st->execute([$me]); $cases = $st->fetchAll();
    $fu = $pdo->prepare("SELECT f.* FROM INCIDENT_FOLLOWUP f JOIN INCIDENT i ON f.Incident_Ref=i.Incident_Ref WHERE i.Officer_ID=? ORDER BY f.Follow_up_Date, f.Followup_ID");
    $fu->execute([$me]);
    foreach ($fu->fetchAll() as $f) $notes[$f['Incident_Ref']][] = $f;
}
$closed = count(array_filter($cases, fn($c) => $sn[$c['Status_ID']] === 'Closed'));
$all = can($pdo, 'VIEW_ALL') ? $pdo->query("SELECT i.Incident_Ref, i.Incident_Date, i.Location, t.Type_Name, s.Status_Name, o.Officer_Name FROM INCIDENT i
    JOIN INCIDENT_TYPE t ON i.Type_ID=t.Type_ID JOIN INCIDENT_STATUS s ON i.Status_ID=s.Status_ID LEFT JOIN OFFICER o ON i.Officer_ID=o.Officer_ID ORDER BY i.Incident_Date DESC")->fetchAll() : null;
$ma = $pdo->prepare("SELECT p.Permission_Name, p.Description FROM USER_PERMISSION up JOIN PERMISSION p ON up.Permission_ID=p.Permission_ID WHERE up.User_ID=? ORDER BY p.Permission_ID");
$ma->execute([$_SESSION['user_id']]); $myAccess = $ma->fetchAll();
$rl = $pdo->prepare("SELECT Action, Incident_Ref, Details, Logged_At FROM ACTIVITY_LOG WHERE User_ID=? ORDER BY Log_ID DESC LIMIT 8");
$rl->execute([$_SESSION['user_id']]); $recent = $rl->fetchAll();
$canS = can($pdo, 'UPDATE_STATUS'); $canF = can($pdo, 'ADD_FOLLOWUP');
page_head('Officer Dashboard', true);
show_flash();
?>
<h1>My Assigned Cases</h1>
<div class="stats">
  <div class="stat"><b><?= count($cases) ?></b>Assigned to me</div>
  <div class="stat"><b><?= count($cases) - $closed ?></b>Still in progress</div>
  <div class="stat"><b><?= $closed ?></b>Closed</div>
</div>
<?php if (!can($pdo, 'VIEW_ASSIGNED')): ?><div class="alert err">You do not currently have access to view assigned cases. Please ask the admin.</div><?php else: ?>
<div class="card"><div class="tbl"><table>
  <tr><th>Ref</th><th>Date</th><th>Type</th><th>Location</th><th>Complainant</th><th>Status</th><th>Follow-ups</th></tr>
  <?php foreach ($cases as $c): ?>
  <tr>
    <td><b><?= h($c['Incident_Ref']) ?></b></td><td><?= h($c['Incident_Date']) ?><br><span class="muted"><?= h(substr($c['Incident_Time'] ?? '', 0, 5)) ?></span></td><td><b><?= h($c['Type_Name']) ?></b><?= $c['Other_Specify'] ? ': ' . h($c['Other_Specify']) : '' ?><?php if ($c['Description']): ?><details><summary>Details</summary><?= h($c['Description']) ?></details><?php endif; ?></td>
    <td><?= h($c['Location']) ?></td><td><?= h($c['Resident_Name']) ?><br><span class="muted"><?= h($c['Contact']) ?></span></td>
    <td><?php if ($canS): ?><form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="ref" value="<?= h($c['Incident_Ref']) ?>">
      <select name="status_id"><?= opts($statuses, 'Status_ID', 'Status_Name', $c['Status_ID']) ?></select><button class="btn sm">Save</button></form>
      <?php else: echo h($sn[$c['Status_ID']]); endif; ?></td>
    <td><details><summary><?= count($notes[$c['Incident_Ref']] ?? []) ?> logged</summary>
      <?php foreach ($notes[$c['Incident_Ref']] ?? [] as $n) echo '<div class="muted">' . h($n['Follow_up_Date']) . ' – ' . h($n['Follow_up_Note']) . '</div>'; ?>
      <?php if ($canF): ?><form method="POST" class="inline" style="margin-top:8px"><?= csrf_field() ?><input type="hidden" name="action" value="followup"><input type="hidden" name="ref" value="<?= h($c['Incident_Ref']) ?>">
        <input name="note" maxlength="255" placeholder="New follow-up note" required><button class="btn sm">Add</button></form><?php endif; ?></details></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$cases): ?><tr><td colspan="7">No incidents currently assigned to you.</td></tr><?php endif; ?>
</table></div></div>
<?php endif; ?>
<?php if ($all !== null): ?>
<div class="card"><h2>All incidents (read-only)</h2><div class="tbl"><table>
  <tr><th>Ref</th><th>Date</th><th>Type</th><th>Location</th><th>Status</th><th>Officer</th></tr>
  <?php foreach ($all as $a) echo '<tr><td><b>' . h($a['Incident_Ref']) . '</b></td><td>' . h($a['Incident_Date']) . '</td><td>' . h($a['Type_Name']) . '</td><td>' . h($a['Location']) . '</td><td>' . h($a['Status_Name']) . '</td><td>' . h($a['Officer_Name'] ?? 'Unassigned') . '</td></tr>'; ?>
</table></div></div>
<?php endif; ?>
<div class="grid">
  <div class="card"><h2>My access</h2>
    <?php foreach ($myAccess as $m) echo '<p><b>' . h($m['Permission_Name']) . '</b><br><span class="muted">' . h($m['Description']) . '</span></p>'; ?>
    <?php if (!$myAccess) echo '<p class="muted">No access granted yet.</p>'; ?></div>
  <div class="card"><h2>My recent activity</h2>
    <?php foreach ($recent as $r) echo '<p class="muted">' . h($r['Logged_At']) . ' – <b>' . h($r['Action']) . '</b> ' . h($r['Incident_Ref'] ?? '') . ' ' . h($r['Details']) . '</p>'; ?></div>
</div>
<?php page_foot(); ?>
