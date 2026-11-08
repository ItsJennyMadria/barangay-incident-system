<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAccess($allowed_role) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
    if ($_SESSION['role'] !== $allowed_role) {
        echo "<h2 style='color:red;'>403 Access Denied</h2>";
        echo "<a href='logout.php'>Logout</a>";
        exit();
    }
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_field() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function csrf_check() {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die("Invalid request token (CSRF mismatch).");
    }
}

function flash($msg, $type = 'ok') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert ' . ($f['type'] === 'err' ? 'err' : 'ok') . '">' . h($f['msg']) . '</div>';
    }
}

function opts($items, $val_key, $lbl_key, $selected = null, $placeholder = null) {
    $html = '';
    if ($placeholder !== null) {
        $html .= '<option value="">' . h($placeholder) . '</option>';
    }
    foreach ($items as $item) {
        $v = $item[$val_key];
        $l = $item[$lbl_key];
        $sel = ((string)$v === (string)$selected) ? ' selected' : '';
        $html .= '<option value="' . h($v) . '"' . $sel . '>' . h($l) . '</option>';
    }
    return $html;
}

function page_head($title, $nav = false) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= h($title) ?> - Barangay Incident Monitoring</title>
    <style>
        * { box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #f1f5f9; color: #0f172a; margin: 0; padding: 0; }
        .nav { background: #1e293b; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #cbd5e1; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .muted { color: #64748b; font-size: 0.9rem; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        label { display: block; font-weight: 600; margin: 0.5rem 0 0.2rem; font-size: 0.85rem; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; }
        .btn { background: #2563eb; color: white; border: none; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn.full { width: 100%; margin-top: 1rem; }
        .btn.sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
        .tbl { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f8fafc; }
        .alert { padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert.ok { background: #dcfce7; color: #166534; }
        .alert.err { background: #fee2e2; color: #991b1b; }
        .pill { background: #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: white; padding: 1rem; border-radius: 8px; text-align: center; }
        .stat b { display: block; font-size: 1.5rem; color: #2563eb; }
        form.inline { display: inline-flex; gap: 0.4rem; }
    </style>
</head>
<body>
<?php if ($nav && !empty($_SESSION['user_id'])): ?>
<div class="nav">
    <div><strong>Barangay Monitoring</strong></div>
    <div>
        <span>Logged in as <strong><?= h($_SESSION['officer_name']) ?></strong> (<?= h($_SESSION['role']) ?>)</span>
        <a href="<?= $_SESSION['role'] === 'Admin' ? 'admin_dashboard.php' : 'officer_dashboard.php' ?>">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<?php endif; ?>
<div class="container">
<?php
}

function page_foot() {
    echo '</div></body></html>';
}
?>