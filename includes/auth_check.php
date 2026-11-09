<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf_field() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Form expired. Please go back, refresh, and try again.');
    }
}
function flash($msg, $type = 'ok') { $_SESSION['flash'] = [$msg, $type]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert ' . $t . '">' . h($m) . '</div>';
    }
}
function checkAccess($role) {
    if (empty($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        exit('<h2>403 – Access denied</h2><p>Your role cannot open this page. <a href="logout.php">Logout</a></p>');
    }
}
function page_head($title, $logged = false) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . h($title) . '</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="top"><a class="brand" href="index.php">🏛️ Barangay Incident System</a>';
    if ($logged && $_SESSION['role'] === 'Admin') echo '<nav class="nav"><a href="admin_dashboard.php">Incidents</a><a href="history.php">History Log</a><a href="access_control.php">Access Control</a></nav>';
    if ($logged) echo '<div class="who">' . h($_SESSION['officer_name']) . ' <span class="pill">' . h($_SESSION['role']) . '</span> <a class="btn ghost" href="logout.php">Logout</a></div>';
    echo '</header><main>';
}
function page_foot() { echo '</main></body></html>'; }
function log_activity($pdo, $action, $details = '', $ref = null, $uid = null) {
    $pdo->prepare("INSERT INTO ACTIVITY_LOG (User_ID,Action,Incident_Ref,Details,IP_Address) VALUES (?,?,?,?,?)")
        ->execute([$uid ?? ($_SESSION['user_id'] ?? null), $action, $ref, mb_substr($details, 0, 255), $_SERVER['REMOTE_ADDR'] ?? null]);
}
function can($pdo, $code) {
    static $c = [];
    $u = $_SESSION['user_id'] ?? 0; $k = $u . $code;
    if (!isset($c[$k])) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM USER_PERMISSION up JOIN PERMISSION p ON up.Permission_ID=p.Permission_ID WHERE up.User_ID=? AND p.Permission_Code=?");
        $s->execute([$u, $code]); $c[$k] = (bool)$s->fetchColumn();
    }
    return $c[$k];
}
function opts($rows, $idk, $nmk, $sel = null, $blank = null) {
    $o = $blank !== null ? '<option value="">' . h($blank) . '</option>' : '';
    foreach ($rows as $r) $o .= '<option value="' . h($r[$idk]) . '"' . ((string)$r[$idk] === (string)$sel ? ' selected' : '') . '>' . h($r[$nmk]) . '</option>';
    return $o;
}
