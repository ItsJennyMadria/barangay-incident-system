<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$types = $pdo->query("SELECT * FROM INCIDENT_TYPE ORDER BY Type_ID")->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $contact = trim($_POST['contact'] ?? '');
    $loc = trim($_POST['location'] ?? ''); $type = (int)($_POST['type_id'] ?? 0); $date = $_POST['date'] ?? '';
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Please enter your full name.';
    if (!preg_match('/^[0-9+\- ]{7,20}$/', $contact)) $errors[] = 'Please enter a valid contact number.';
    if ($loc === '' || mb_strlen($loc) > 100) $errors[] = 'Please tell us where it happened.';
    if (!in_array($type, array_column($types, 'Type_ID'))) $errors[] = 'Please choose an incident type.';
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date || $date > date('Y-m-d')) $errors[] = 'Please enter a valid date (not in the future).';
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("SELECT Resident_ID FROM RESIDENT WHERE Resident_Name = ? AND Contact = ?");
            $st->execute([$name, $contact]);
            $rid = $st->fetchColumn();
            if (!$rid) {
                $n = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(Resident_ID,3) AS UNSIGNED)),0) FROM RESIDENT")->fetchColumn() + 1;
                $rid = sprintf('R-%03d', $n);
                $pdo->prepare("INSERT INTO RESIDENT VALUES (?,?,?)")->execute([$rid, $name, $contact]);
            }
            $n = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(Incident_Ref,5) AS UNSIGNED)),0) FROM INCIDENT")->fetchColumn() + 1;
            $ref = sprintf('INC-%03d', $n);
            $pdo->prepare("INSERT INTO INCIDENT (Incident_Ref,Incident_Date,Location,Type_ID,Resident_ID,Officer_ID,Status_ID)
                VALUES (?,?,?,?,?,NULL,(SELECT Status_ID FROM INCIDENT_STATUS WHERE Status_Name='Open'))")
                ->execute([$ref, $date, $loc, $type, $rid]);
            $pdo->commit();
            flash("Report submitted! Your reference number is $ref. Keep it to track your report.");
            header('Location: index.php'); exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'We could not save your report. Please try again.';
        }
    }
}
$track = null;
if (!empty($_GET['track'])) {
    $st = $pdo->prepare("SELECT i.Incident_Ref, i.Incident_Date, t.Type_Name, s.Status_Name, o.Officer_Name
        FROM INCIDENT i JOIN INCIDENT_TYPE t ON i.Type_ID=t.Type_ID JOIN INCIDENT_STATUS s ON i.Status_ID=s.Status_ID
        LEFT JOIN OFFICER o ON i.Officer_ID=o.Officer_ID WHERE i.Incident_Ref = ?");
    $st->execute([strtoupper(trim($_GET['track']))]);
    $track = $st->fetch() ?: false;
}
page_head('Report an Incident');
show_flash();
?>
<div class="grid">
  <section class="card">
    <h1>Report an Incident</h1>
    <p class="muted">See something wrong in your barangay? Fill in the form and our officers will follow up. No account needed.</p>
    <?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div><?php endif; ?>
    <form method="POST" action="index.php">
      <?= csrf_field() ?>
      <div class="row">
        <div><label>Your full name</label><input name="name" maxlength="100" required value="<?= h($_POST['name'] ?? '') ?>"></div>
        <div><label>Contact number</label><input name="contact" maxlength="20" required value="<?= h($_POST['contact'] ?? '') ?>"></div>
      </div>
      <div class="row">
        <div><label>Incident type</label><select name="type_id" required><?= opts($types, 'Type_ID', 'Type_Name', $_POST['type_id'] ?? null, '-- Choose type --') ?></select></div>
        <div><label>Date it happened</label><input type="date" name="date" max="<?= date('Y-m-d') ?>" required value="<?= h($_POST['date'] ?? date('Y-m-d')) ?>"></div>
      </div>
      <label>Location (e.g. Purok 3, near the chapel)</label>
      <input name="location" maxlength="100" required value="<?= h($_POST['location'] ?? '') ?>">
      <button class="btn full" type="submit">Submit Report</button>
    </form>
  </section>
  <aside>
    <div class="card">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <h2>Welcome back</h2><p><?= h($_SESSION['officer_name']) ?> (<?= h($_SESSION['role']) ?>)</p>
        <a class="btn full" href="<?= $_SESSION['role'] === 'Admin' ? 'admin_dashboard.php' : 'officer_dashboard.php' ?>">Open my dashboard</a>
      <?php else: ?>
        <h2>🔐 Staff Login</h2><p class="muted">For barangay admins and officers.</p>
        <form method="POST" action="login.php">
          <?= csrf_field() ?>
          <label>Username</label><input name="username" required autocomplete="username">
          <label>Password</label><input type="password" name="password" required autocomplete="current-password">
          <button class="btn full" type="submit">Log In</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="card">
      <h2>Track a Report</h2>
      <form method="GET" class="inline"><input name="track" placeholder="INC-001" required value="<?= h($_GET['track'] ?? '') ?>"><button class="btn sm">Check</button></form>
      <?php if ($track): ?>
        <p><b><?= h($track['Incident_Ref']) ?></b> · <?= h($track['Type_Name']) ?> · <?= h($track['Incident_Date']) ?><br>
        Status: <span class="pill"><?= h($track['Status_Name']) ?></span><br>
        Handled by: <?= h($track['Officer_Name'] ?? 'Awaiting assignment') ?></p>
      <?php elseif ($track === false): ?><p class="muted">No report found with that reference.</p><?php endif; ?>
    </div>
  </aside>
</div>
<?php page_foot(); ?>
