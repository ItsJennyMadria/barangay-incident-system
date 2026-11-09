<?php
require_once 'includes/auth_check.php';
checkAccess('Admin');
require_once 'config/db.php';
$uid = (int)($_GET['user'] ?? 0); $act = $_GET['action'] ?? ''; $ref = strtoupper(trim($_GET['incident'] ?? ''));
$sql = "SELECT l.*, u.Username, u.Role FROM ACTIVITY_LOG l LEFT JOIN USERS u ON l.User_ID=u.User_ID WHERE 1=1";
$p = [];
if ($uid) { $sql .= " AND l.User_ID=?"; $p[] = $uid; }
if ($act !== '') { $sql .= " AND l.Action=?"; $p[] = $act; }
if ($ref !== '') { $sql .= " AND l.Incident_Ref=?"; $p[] = $ref; }
$st = $pdo->prepare($sql . " ORDER BY l.Log_ID DESC LIMIT 300");
$st->execute($p);
$logs = $st->fetchAll();
$users = $pdo->query("SELECT User_ID, Username FROM USERS ORDER BY Username")->fetchAll();
$actions = $pdo->query("SELECT DISTINCT Action FROM ACTIVITY_LOG ORDER BY Action")->fetchAll();
page_head('History Log', true);
?>
<h1>History Log</h1>
<p class="muted">Every login, logout and change is recorded with who did it (name, ID and role), what they did and when. Admin actions are included.</p>
<div class="card">
  <form method="GET" class="inline" style="margin-bottom:14px">
    <select name="user"><?= opts($users, 'User_ID', 'Username', $uid, 'All users') ?></select>
    <select name="action"><?= opts($actions, 'Action', 'Action', $act, 'All actions') ?></select>
    <input name="incident" placeholder="Incident ref (INC-001)" value="<?= h($ref) ?>">
    <button class="btn sm">Filter</button> <a class="btn sm ghost" style="color:#12355b;border-color:#12355b" href="history.php">Reset</a>
  </form>
  <div class="tbl"><table>
    <tr><th>When</th><th>Who</th><th>Action</th><th>Incident</th><th>Details</th><th>IP</th></tr>
    <?php foreach ($logs as $l): ?>
    <tr><td><?= h($l['Logged_At']) ?></td>
      <td><?= $l['Username'] ? '<b>' . h($l['Username']) . '</b> <span class="muted">ID ' . (int)$l['User_ID'] . '</span> <span class="pill">' . h($l['Role']) . '</span>' : '<span class="muted">Public / not logged in</span>' ?></td>
      <td><?= h($l['Action']) ?></td><td><?= h($l['Incident_Ref'] ?? '—') ?></td><td><?= h($l['Details']) ?></td><td><?= h($l['IP_Address']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="6">No history found.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php page_foot(); ?>
