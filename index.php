<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$hotlines = require 'config/hotlines.php';
$types = $pdo->query("SELECT t.*, s.Service_Name FROM INCIDENT_TYPE t JOIN SERVICE s ON t.Service_ID=s.Service_ID
    ORDER BY (t.Type_Name='Other'), s.Service_ID, t.Type_Name")->fetchAll();
$byId = array_column($types, null, 'Type_ID');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $contact = trim($_POST['contact'] ?? ''); $loc = trim($_POST['location'] ?? '');
    $type = (int)($_POST['type_id'] ?? 0); $date = $_POST['date'] ?? ''; $time = $_POST['time'] ?? '';
    $desc = trim($_POST['description'] ?? ''); $spec = trim($_POST['other_specify'] ?? '');
    $t = $byId[$type] ?? null; $isOther = $t && $t['Type_Name'] === 'Other';
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Please enter your full name.';
    if (!preg_match('/^[0-9+\- ]{7,20}$/', $contact)) $errors[] = 'Please enter a valid contact number.';
    if ($loc === '' || mb_strlen($loc) > 100) $errors[] = 'Please tell us where it happened.';
    if (!$t) $errors[] = 'Please choose an incident type.';
    if ($isOther && (mb_strlen($spec) < 3 || mb_strlen($spec) > 100)) $errors[] = 'You chose "Other": please say briefly what kind of problem it is (for example "stray dogs").';
    if (mb_strlen($desc) < ($isOther ? 20 : 10) || mb_strlen($desc) > 500) $errors[] = 'Please describe what happened (' . ($isOther ? '20' : '10') . ' to 500 characters).';
    $dt = DateTime::createFromFormat('Y-m-d H:i', "$date $time");
    if (!$dt || $dt->format('Y-m-d H:i') !== "$date $time") $errors[] = 'Please enter a valid date and time.';
    elseif ($dt > new DateTime()) $errors[] = 'That date and time has not happened yet. Please enter when the incident actually occurred (an estimate is fine).';
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
            $pdo->prepare("INSERT INTO INCIDENT (Incident_Ref,Incident_Date,Incident_Time,Location,Description,Other_Specify,Type_ID,Resident_ID,Officer_ID,Status_ID)
                VALUES (?,?,?,?,?,?,?,?,NULL,(SELECT Status_ID FROM INCIDENT_STATUS WHERE Status_Name='Open'))")
                ->execute([$ref, $dt->format('Y-m-d'), $dt->format('H:i:s'), $loc, $desc, $isOther ? $spec : null, $type, $rid]);
            $pdo->commit();
            log_activity($pdo, 'REPORT_SUBMITTED', "Public report from resident $rid; routed to {$t['Service_Name']}", $ref, null);
            flash("Report submitted! Reference number: $ref. It was routed to {$t['Service_Name']}. Keep the number to track your report." . ($t['Is_Urgent'] ? ' If anyone is in danger, call 911 now.' : ''));
            header('Location: index.php?track=' . $ref); exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'We could not save your report. Please try again.';
        }
    }
}
$track = null;
if (!empty($_GET['track'])) {
    $st = $pdo->prepare("SELECT i.Incident_Ref, i.Incident_Date, i.Incident_Time, t.Type_Name, sv.Service_Name, s.Status_Name, o.Officer_Name,
        (SELECT MAX(Follow_up_Date) FROM INCIDENT_FOLLOWUP f WHERE f.Incident_Ref=i.Incident_Ref) AS Last_Update
        FROM INCIDENT i JOIN INCIDENT_TYPE t ON i.Type_ID=t.Type_ID JOIN SERVICE sv ON t.Service_ID=sv.Service_ID
        JOIN INCIDENT_STATUS s ON i.Status_ID=s.Status_ID LEFT JOIN OFFICER o ON i.Officer_ID=o.Officer_ID WHERE i.Incident_Ref = ?");
    $st->execute([strtoupper(trim($_GET['track']))]);
    $track = $st->fetch() ?: false;
}
$groups = [];
foreach ($types as $t) $groups[$t['Type_Name'] === 'Other' ? 'Not listed above' : $t['Service_Name']][] = $t;
$tel = fn($n) => 'tel:' . preg_replace('/\D/', '', $n);
page_head('Report an Incident');
show_flash();
?>
<div class="sos">
  <div><h2>🚨 Emergency? Call first, report later.</h2><p>If someone is in danger, fire, or needs medical help, call right away.</p></div>
  <div><a class="qbtn" href="tel:911">📞 911</a><a class="qbtn" href="tel:09163137327">🚓 Police</a><a class="qbtn" href="tel:09352307289">🔥 Fire (BFP)</a><a class="qbtn" href="tel:09158139494">🚑 Rescue Team</a></div>
</div>
<div class="grid">
  <section class="card">
    <h1>Report an Incident</h1>
    <p class="muted">Fill in the details below. Your report goes to the barangay office, which assigns the right officer. No account needed.</p>
    <?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div><?php endif; ?>
    <form method="POST" action="index.php" id="rf">
      <?= csrf_field() ?>
      <div class="row">
        <div><label>Your full name</label><input name="name" maxlength="100" required value="<?= h($_POST['name'] ?? '') ?>"></div>
        <div><label>Contact number</label><input name="contact" maxlength="20" required placeholder="09XX-XXX-XXXX" value="<?= h($_POST['contact'] ?? '') ?>"></div>
      </div>
      <label>What kind of incident is it?</label>
      <select name="type_id" id="type" required>
        <option value="">-- Choose the closest match --</option>
        <?php foreach ($groups as $g => $ts): ?><optgroup label="<?= h($g) ?>"><?php foreach ($ts as $t): ?>
          <option value="<?= (int)$t['Type_ID'] ?>" <?= (int)($_POST['type_id'] ?? 0) === (int)$t['Type_ID'] ? 'selected' : '' ?>><?= h($t['Type_Name']) ?></option>
        <?php endforeach; ?></optgroup><?php endforeach; ?>
      </select>
      <div id="hint" class="hint" hidden></div>
      <div id="urgent" class="alert err" style="margin-top:10px" hidden>🚨 <b>This may be an emergency.</b> If anyone is in danger right now, call <a href="tel:911"><b>911</b></a> first, then submit this report.</div>
      <div id="otherBox" hidden><label>What kind of problem is this?</label><input name="other_specify" id="spec" maxlength="100" placeholder="e.g. stray dogs, broken street light" value="<?= h($_POST['other_specify'] ?? '') ?>"></div>
      <div class="row">
        <div><label>Date it happened</label><input type="date" id="date" name="date" max="<?= date('Y-m-d') ?>" required value="<?= h($_POST['date'] ?? date('Y-m-d')) ?>"></div>
        <div><label>Estimated time</label><input type="time" id="time" name="time" required value="<?= h($_POST['time'] ?? date('H:i')) ?>"></div>
      </div>
      <div id="dtwarn" class="alert err" style="margin-top:10px" hidden>⚠️ That date and time has not happened yet. Please enter when the incident actually occurred. An estimate is fine.</div>
      <label>Where did it happen?</label>
      <input name="location" maxlength="100" required placeholder="e.g. Purok 3, near the chapel" value="<?= h($_POST['location'] ?? '') ?>">
      <label>What happened?</label>
      <textarea name="description" rows="4" maxlength="500" required placeholder="Who or what was involved, what you saw or heard, any landmarks..."><?= h($_POST['description'] ?? '') ?></textarea>
      <button class="btn full" type="submit">Submit Report</button>
    </form>
  </section>
  <aside>
    <div class="card">
      <h2>🔎 Track a Report</h2>
      <p class="muted">Enter the reference number you received.</p>
      <form method="GET" class="inline"><input name="track" placeholder="INC-001" required value="<?= h($_GET['track'] ?? '') ?>"><button class="btn sm">Check</button></form>
      <?php if ($track): ?>
        <p><b><?= h($track['Incident_Ref']) ?></b> · <?= h($track['Type_Name']) ?><br>
        Happened: <?= h($track['Incident_Date'] . ' ' . substr($track['Incident_Time'] ?? '', 0, 5)) ?><br>
        Service: <?= h($track['Service_Name']) ?><br>
        Status: <span class="pill"><?= h($track['Status_Name']) ?></span><br>
        Handled by: <?= h($track['Officer_Name'] ?? 'Awaiting assignment') ?><br>
        Last update: <?= h($track['Last_Update'] ?? 'No updates yet') ?></p>
      <?php elseif ($track === false): ?><p class="muted">No report found with that reference.</p><?php endif; ?>
    </div>
    <div class="card"><h2>How it works</h2>
      <ol class="steps"><li>Describe what happened.</li><li>We send it to the right service (peace and order, fire, health, and so on).</li><li>The barangay assigns an officer.</li><li>Track progress with your reference number.</li></ol></div>
  </aside>
</div>
<div class="card">
  <h2>📞 Emergency Hotlines</h2>
  <p class="muted">Tap a number on your phone to call. Please confirm numbers with the barangay office if a call does not connect.</p>
  <div class="hot-grid">
    <?php foreach ($hotlines as $grp => $items): foreach ($items as [$nm, $nums]): ?>
      <div class="hot"><small><?= h($grp) ?></small><div class="nm"><?= h($nm) ?></div>
        <?php foreach ($nums as $n) echo '<a class="tel" href="' . $tel($n) . '">' . h($n) . '</a>'; ?></div>
    <?php endforeach; endforeach; ?>
  </div>
</div>
<p class="muted center"><a href="login.php">Staff portal (admins and officers)</a></p>
<script>
const T = <?= json_encode(array_map(fn($t) => ['d' => $t['Description'], 's' => $t['Service_Name'], 'u' => (int)$t['Is_Urgent'], 'o' => $t['Type_Name'] === 'Other'], $byId)) ?>;
const $ = id => document.getElementById(id);
function upd() {
  var t = T[$('type').value];
  $('hint').hidden = !t; $('urgent').hidden = !(t && t.u); $('otherBox').hidden = !(t && t.o); $('spec').required = !!(t && t.o);
  if (t) $('hint').innerHTML = '<b>' + t.s + '</b> will handle this. ' + t.d;
}
function chkT() {
  var n = new Date(), p = x => String(x).padStart(2, '0'),
      today = n.getFullYear() + '-' + p(n.getMonth() + 1) + '-' + p(n.getDate()), now = p(n.getHours()) + ':' + p(n.getMinutes());
  var bad = $('date').value && $('time').value && ($('date').value > today || ($('date').value === today && $('time').value > now));
  $('dtwarn').hidden = !bad; $('time').max = $('date').value === today ? now : '';
  return !bad;
}
$('type').onchange = upd; $('date').onchange = $('time').onchange = $('time').oninput = chkT;
$('rf').onsubmit = function (e) { if (!chkT()) { e.preventDefault(); $('dtwarn').scrollIntoView({behavior: 'smooth', block: 'center'}); } };
upd(); chkT();
</script>
<?php page_foot(); ?>
