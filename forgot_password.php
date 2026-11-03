<?php
// Step 1 of a password reset: email the user a one-time link (valid for 1 hour).
require_once __DIR__ . '/databaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ForgotPasswordform.html');
    exit;
}

$login = trim($_POST['login'] ?? '');
$user  = $login === '' ? null
       : q("SELECT idnumber, firstname, email FROM users WHERE username = ? OR email = ? LIMIT 1", [$login, $login])->fetch_assoc();

if ($user && filter_var($user['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    $token = bin2hex(random_bytes(32));                 // goes in the email link
    $hash  = hash('sha256', $token);                    // only the hash is stored
    q("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL", [(int)$user['idnumber']]);
    q("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)",
      [(int)$user['idnumber'], $hash]);

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $base  = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $link  = $base . '/reset_password.php?token=' . $token;

    send_mail($user['email'], 'Reset your SPCA Guardian Pro password',
        '<p>Hi ' . e($user['firstname']) . ',</p>'
      . '<p>Someone (hopefully you) asked to reset your SPCA Guardian Pro password. Choose a new one here:</p>'
      . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>'
      . '<p>The link works once and expires in 1 hour. If you didn\'t ask for this, you can ignore this email.</p>');
    log_activity('Requested password reset', 'User #' . (int)$user['idnumber']);
}
// The same message either way, so nobody can use this form to find out which accounts exist.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Check your email | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>body{min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:16px;box-sizing:border-box}
.card{background:#fff;border-radius:16px;padding:28px;max-width:440px;box-shadow:0 10px 30px rgba(12,40,46,.12)}.card p{color:#5b6e72;line-height:1.55}</style>
</head>
<body class="spca">
<div class="card">
  <h2>Check your email</h2>
  <p>If an account matches what you entered and has an email address, we've sent it a link to choose a new password. The link expires in 1 hour.</p>
  <p>No email after a few minutes? Check your spam folder, or ask an administrator to reset your password.</p>
  <a href="Newlogin.html" class="back-btn">&larr; Back to login</a>
</div>
</body>
</html>
