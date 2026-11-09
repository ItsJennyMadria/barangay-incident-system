<?php
require_once 'includes/auth_check.php';
checkAccess('Admin');
require_once 'config/db.php';
const WARN = "Saving changes to this incident may interfere with the officer on duty. Please coordinate with the officer first, since this case has not been resolved.";
$views = ['all', 'unassigned', 'pending', 'closed'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $ref = $_POST['ref'] ?? '';
        $new = ($_POST['officer_id'] ?? '') === '' ? null : $_POST['officer_id'];
        $st = $pdo->prepare("SELECT i.Officer_ID, s.Status_Name FROM INCIDENT i JOIN INCIDENT_STATUS s ON i.Status_ID=s.Status_ID WHERE i.Incident_Ref=?");
        $st->execute([$ref]);
        $cur = $st->fetch();
        if (!$cur) flash('Incident not found.', 'err');
        elseif ($cur['Officer_ID'] === $new) flash('Nothing changed.', 'err');
        else {
            $onDuty = $cur['Officer_ID'] !== null && $cur['Status_Name'] !== 'Closed';
            if ($onDuty && ($_POST['ack'] ?? '') !== '1') flash('Not saved. ' . WARN, 'err');
            else {
                $pdo->prepare("UPDATE INCIDENT SET Officer_ID=? WHERE Incident_Ref=?")->execute([$new, $ref]);
                log_activity($pdo, 'INCIDENT_ASSIGNED', ($cur['Officer_ID'] ?? 'unassigned') . ' -> ' . ($new ?? 'unassigned') . ($onDuty ? ' (admin acknowledged on-duty warning)' : ''), $ref);
                flash("$ref officer assignment saved and logged.");
            }
        }
    } catch (PDOException $e) { flash('Update failed. Please check your input.', 'err'); }
    $v = $_POST['view'] ?? 'all';
    header('Location: admin_dashboard.php?view=' . (in_array($v, $views, true) ? $v : 'all')); exit();
}
$view = in_array($_GET['view'] ?? '', $views, true) ? $_GET['view'] : 'all';
$where = ['unassigned' => 'WHERE i.Officer_ID IS NULL', 'pending' => "WHERE s.Status_Name<>'Closed'", 'closed' => "WHERE s.Status_Name='Closed'"][$view] ?? '';
$stat = fn($sql) => $pdo->query($sql)->fetchColumn();
$officers = $pdo->query("SELECT o.Officer_ID, o.Officer_Name FROM OFFICER o LEFT JOIN USERS u ON u.Officer_ID=o.Officer_ID
    WHERE u.Role IS NULL OR u.Role<>'Admin' ORDER BY o.Officer_ID")->fetchAll();
$incidents = $pdo->query("SELECT i.*, r.Resident_Name, r.Contact, t.Type_Name, s.Status_Name, o.Officer_Name FROM INCIDENT i
    JOIN RESIDENT r ON i.Resident_ID=r.Resident_ID JOIN INCIDENT_TYPE t ON i.Type_ID=t.Type_ID
    JOIN INCIDENT_STATUS s ON i.Status_ID=s.Status_ID LEFT JOIN OFFICER o ON i.Officer_ID=o.Officer_ID $where
    ORDER BY (i.Officer_ID IS NULL) DESC, i.Incident_Date DESC, i.Incident_Ref DESC")->fetchAll();
page_head('Admin Dashboard', true);
show_flash();
?>
<h1>Incident Tracking &amp; Oversight</h1>
<p class="muted">Admins monitor all incidents, dispatch officers, review the history log and manage access. Case status and follow-ups are handled by officers only.</p>
<div class="stats">
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM INCIDENT") ?></b>Total incidents</div>
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM INCIDENT WHERE Officer_ID IS NULL") ?></b>Unassigned complaints</div>
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM INCIDENT i JOIN INCIDENT_STATUS s ON i.Status_ID=s.Status_ID WHERE s.Status_Name<>'Closed'") ?></b>Pending cases</div>
  <div class="stat"><b><?= $stat("SELECT COUNT(*) FROM USERS") ?></b>Active user accounts</div>
</div>
<div class="card">
  <div class="tabs"><?php foreach (['all' => 'All', 'unassigned' => 'Unassigned', 'pending' => 'Pending', 'closed' => 'Closed'] as $k => $l) echo '<a class="' . ($view === $k ? 'on' : '') . '" href="?view=' . $k . '">' . $l . '</a>'; ?></div>
  <div class="tbl"><table>
    <tr><th>Ref</th><th>Date</th><th>Type</th><th>Location</th><th>Complainant</th><th>Status</th><th>Assigned officer</th><th>History</th></tr>
    <?php foreach ($incidents as $i): $onDuty = $i['Officer_ID'] !== null && $i['Status_Name'] !== 'Closed'; ?>
    <tr class="<?= $i['Officer_ID'] === null ? 'new' : '' ?>">
      <td><b><?= h($i['Incident_Ref']) ?></b><?= $i['Officer_ID'] === null ? ' <span class="pill">NEW</span>' : '' ?></td>
      <td><?= h($i['Incident_Date']) ?></td><td><?= h($i['Type_Name']) ?></td><td><?= h($i['Location']) ?></td>
      <td><?= h($i['Resident_Name']) ?><br><span class="muted"><?= h($i['Contact']) ?></span></td>
      <td><?= h($i['Status_Name']) ?></td>
      <td><form method="POST" class="inline" data-warn="<?= $onDuty ? 1 : 0 ?>" onsubmit="return chk(this)"><?= csrf_field() ?>
        <input type="hidden" name="ref" value="<?= h($i['Incident_Ref']) ?>"><input type="hidden" name="view" value="<?= h($view) ?>"><input type="hidden" name="ack" value="">
        <select name="officer_id" data-orig="<?= h($i['Officer_ID']) ?>"><?= opts($officers, 'Officer_ID', 'Officer_Name', $i['Officer_ID'], '-- Unassigned --') ?></select>
        <button class="btn sm">Save</button></form></td>
      <td><a href="history.php?incident=<?= h($i['Incident_Ref']) ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$incidents): ?><tr><td colspan="8">No incidents in this view.</td></tr><?php endif; ?>
  </table></div>
</div>
<script>
const WARN = <?= json_encode(WARN) ?>;
function chk(f) {
  var s = f.officer_id;
  if (f.dataset.warn === '1' && s.value !== s.dataset.orig) {
    if (!confirm('⚠️ WARNING\n\n' + WARN + '\n\nPress OK only if you have coordinated with the officer.')) return false;
    f.ack.value = '1';
  }
  return true;
}
</script>
<?php page_foot(); ?>
