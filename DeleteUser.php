<?php
// Removes a staff account. POST only, from the users list; you can't remove yourself or the last administrator.
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$id = (int)($_POST['idnumber'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) { header('Location: displaySystemUser.php'); exit; }

$u = q("SELECT idnumber, username, userrole, firstname, surname FROM users WHERE idnumber = ?", [$id])->fetch_assoc();
if (!$u) {
    $_SESSION['flash'] = ['bad', 'That user no longer exists.'];
} elseif ($u['username'] === ($_SESSION['username'] ?? '')) {
    $_SESSION['flash'] = ['bad', "You can't remove your own account."];
} elseif ($u['userrole'] === 'Administrator' && (int)q("SELECT COUNT(*) n FROM users WHERE userrole = 'Administrator'")->fetch_assoc()['n'] <= 1) {
    $_SESSION['flash'] = ['bad', "You can't remove the last administrator."];
} else {
    q("DELETE FROM users WHERE idnumber = ?", [$id]);
    q("DELETE FROM password_resets WHERE user_id = ?", [$id]);
    log_activity('Removed user', "{$u['username']} ({$u['userrole']})");
    $_SESSION['flash'] = ['ok', "{$u['firstname']} {$u['surname']} ({$u['username']}) was removed."];
}
header('Location: displaySystemUser.php');
exit;
