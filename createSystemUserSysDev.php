<?php
// Creates a user account. Two ways in:
//  - the public "Create Account" form on Newlogin.html: always a Volunteer account
//  - add_user.php, used by a signed-in Administrator: Administrator, Vet or Volunteer
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Newlogin.html');
    exit;
}

$isAdmin = ($_SESSION['access'] ?? '') === 'yes' && ($_SESSION['userrole'] ?? '') === 'Administrator';

$in = [
    'firstname'   => trim($_POST['firstname'] ?? ''),
    'surname'     => trim($_POST['surname'] ?? ''),
    'username'    => trim($_POST['username'] ?? ''),
    'email'       => trim($_POST['email'] ?? ''),
    'phonenumber' => trim($_POST['phonenumber'] ?? ''),
    'userrole'    => $isAdmin ? (string)($_POST['userrole'] ?? '') : 'Volunteer',
];
$pword = (string)($_POST['pword'] ?? '');

$problems = [];
if ($in['firstname'] === '' || $in['surname'] === '')                  $problems[] = 'Please enter a first name and surname.';
if (!preg_match('/^[A-Za-z0-9._-]{3,35}$/', $in['username']))         $problems[] = 'Usernames need 3 to 35 letters or numbers (dots, dashes and underscores are fine, no spaces).';
if (!valid_email($in['email']))                                        $problems[] = 'Please enter a valid email address. It is used for password resets.';
if (!valid_phone($in['phonenumber']))                                  $problems[] = 'Please enter a South African phone number, e.g. 082 123 4567.';
if (!in_array($in['userrole'], ['Administrator', 'Vet', 'Volunteer'], true)) $problems[] = 'Please choose a role.';
$problems = array_merge($problems, password_problems($pword));
if (!$problems && q("SELECT 1 FROM users WHERE username = ? LIMIT 1", [$in['username']])->num_rows) {
    $problems[] = "The username '{$in['username']}' is already taken. Please choose another.";
}

if (!$problems) {
    q("INSERT INTO users (firstname, surname, username, userpassword, userrole, phonenumber, email) VALUES (?, ?, ?, ?, ?, ?, ?)",
      [$in['firstname'], $in['surname'], $in['username'], password_hash($pword, PASSWORD_DEFAULT),
       $in['userrole'], $in['phonenumber'], $in['email']]);
    log_activity($isAdmin ? 'Created user' : 'Volunteer signed up', "{$in['username']} ({$in['userrole']})");
}

// Administrators go back to the users list (or the form, if something needs fixing)
if ($isAdmin) {
    if ($problems) {
        $_SESSION['add_user'] = ['problems' => $problems, 'values' => $in];
        header('Location: add_user.php');
    } else {
        $_SESSION['flash'] = ['ok', "{$in['firstname']} {$in['surname']} can now sign in as {$in['userrole']} with the username '{$in['username']}'."];
        header('Location: displaySystemUser.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $problems ? 'Please check your details' : 'Account created' ?> | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  body{min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:16px;box-sizing:border-box}
  .card{background:#fff;border-radius:16px;padding:28px;max-width:460px;width:100%;box-shadow:0 10px 30px rgba(12,40,46,.12)}
  .card p{color:#5b6e72;line-height:1.55}
  .card ul{margin:0 0 18px;padding-left:20px;color:#c0392b;line-height:1.6}
  .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
</style>
</head>
<body class="spca">
<div class="card">
<?php if ($problems): ?>
  <h2>Please check your details</h2>
  <ul><?php foreach ($problems as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
  <div class="row">
    <a href="javascript:history.back()" class="back-btn">&larr; Go back and fix them</a>
  </div>
<?php else: ?>
  <h2>Welcome, <?= e($in['firstname']) ?>!</h2>
  <p class="success">Your volunteer account is ready.</p>
  <p>Sign in with your username <strong><?= e($in['username']) ?></strong> and the password you chose.</p>
  <div class="row"><a href="Newlogin.html" class="back-btn">Go to sign in &rarr;</a></div>
<?php endif; ?>
</div>
</body>
</html>
