<?php
require_once 'includes/auth_check.php';
checkAccess('Officer');
require_once 'config/db.php';
$me = $_SESSION['officer_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ref = $_POST['ref'] ?? '';
    $own = $pdo->prepare("SELECT COUNT(*) FROM INCIDENT WHERE Incident_Ref=? AND Officer_ID=?");
    $own->execute([$ref, $me]);
    if (!$own->fetchColumn()) { flash('That incident is not assigned to you.', 'err'); }
    else {
        try {
            if (($_POST['action'] ?? '') === 'status') {
                $pdo->prepare("UPDATE INCIDENT SET Status_ID=? WHERE Incident_Ref=? AND Officer_ID=?")->execute([(int)$_POST['status_id'], $ref, $me]);
                flash("Status of $ref updated.");
            } elseif (($_POST['action'] ?? '') === 'followup') {
                $note = trim($_POST['note'] ?? '');
                if ($note === '' || mb_strlen($note) > 255) flash('Enter a note (max 255 characters).', 'err');
                else { $pdo->prepare("INSERT INTO INCIDENT_FOLLOWUP (Incident_Ref,Follow_up_Date,Follow_up_Note) VALUES (?,CURDATE(),?)")->execute([$ref, $note]); flash("Follow-up added to $ref."); }
            }
        } catch (PDOException $e) { flash('Update failed.', 'err'); }
    }
    header('Location: officer_dashboard.php'); exit();
}
$statuses = $pdo->query("SELECT * FROM INCIDENT_STATUS ORDER BY Status_ID")->fetchAll();
$st = $pdo->prepare("SELECT i.Incident_Ref, i.Incident_Date, i.Location, i.Status_ID, r.Resident_Name, r.Contact, t.Type_Name
    FROM INCIDENT i JOIN RESIDENT r ON i.Resident_ID=r.Resident_ID JOIN INCIDENT_TYPE t ON i.Type_ID=t.Type_ID
    WHERE i.Officer_ID=? ORDER BY i.Incident_Date DESC");
$st->execute([$me]);
$cases = $st->fetchAll();
$fu = $pdo->prepare("SELECT f.* FROM INCIDENT_FOLLOWUP f JOIN INCIDENT i ON f.Incident_Ref=i.Incident_Ref WHERE i.Officer_ID=? ORDER BY f.Follow_up_Date, f.Followup_ID");
$fu->execute([$me]);
$notes = [];
foreach ($fu->fetchAll() as $f) $notes[$f['Incident_Ref']][] = $f;
page_head('Officer Dashboard', true);
show_flash();
?>
<h1>My Assigned Cases</h1>
<div class="card"><div class="tbl"><table>
  <tr><th>Ref</th><th>Date</th><th>Type</th><th>Location</th><th>Complainant</th><th>Status</th><th>Follow-ups</th></tr>
  <?php foreach ($cases as $c): ?>
  <tr>
    <td><b><?= h($c['Incident_Ref']) ?></b></td><td><?= h($c['Incident_Date']) ?></td><td><?= h($c['Type_Name']) ?></td>
    <td><?= h($c['Location']) ?></td><td><?= h($c['Resident_Name']) ?><br><span class="muted"><?= h($c['Contact']) ?></span></td>
    <td><form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="ref" value="<?= h($c['Incident_Ref']) ?>">
      <select name="status_id"><?= opts($statuses, 'Status_ID', 'Status_Name', $c['Status_ID']) ?></select><button class="btn sm">Save</button></form></td>
    <td><details><summary><?= count($notes[$c['Incident_Ref']] ?? []) ?> logged</summary>
      <?php foreach ($notes[$c['Incident_Ref']] ?? [] as $n) echo '<div class="muted">' . h($n['Follow_up_Date']) . ' – ' . h($n['Follow_up_Note']) . '</div>'; ?>
      <form method="POST" class="inline" style="margin-top:8px"><?= csrf_field() ?><input type="hidden" name="action" value="followup"><input type="hidden" name="ref" value="<?= h($c['Incident_Ref']) ?>">
        <input name="note" maxlength="255" placeholder="New follow-up note" required><button class="btn sm">Add</button></form></details></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$cases): ?><tr><td colspan="7">No incidents currently assigned to you.</td></tr><?php endif; ?>
</table></div></div>
<?php page_foot(); ?>
