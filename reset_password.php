<?php
// Step 2 of a password reset: check the emailed link, then save the new password.
require_once __DIR__ . '/databaseConnection.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $reset = q("SELECT id, user_id FROM password_resets
                WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
               [hash('sha256', $token)])->fetch_assoc();
}

$errors = [];
$done   = false;

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($new) < 8)                                  $errors[] = 'Use at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) $errors[] = 'Use at least one letter and one number.';
    if ($new !== $confirm)                                 $errors[] = "The two passwords don't match.";

    if (!$errors) {
        q("UPDATE users SET userpassword = ? WHERE idnumber = ?", [password_hash($new, PASSWORD_DEFAULT), (int)$reset['user_id']]);
        q("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL", [(int)$reset['user_id']]);
        q("DELETE FROM login_attempts WHERE username = (SELECT username FROM users WHERE idnumber = ?)", [(int)$reset['user_id']]);
        log_activity('Reset password', 'User #' . (int)$reset['user_id']);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Choose a new password | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>body{min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:16px;box-sizing:border-box}
.card{background:#fff;border-radius:16px;padding:28px;width:100%;max-width:440px;box-shadow:0 10px 30px rgba(12,40,46,.12)}.card p{color:#5b6e72;line-height:1.55}</style>
</head>
<body class="spca">
<div class="card">
<?php if ($done): ?>
  <h2>Password changed</h2>
  <p class="success">Your new password is saved. You can sign in now.</p>
  <a href="Newlogin.html" class="back-btn">&larr; Go to login</a>
<?php elseif (!$reset): ?>
  <h2>This link doesn't work</h2>
  <p>Reset links work once and expire after 1 hour. Please ask for a new one.</p>
  <a href="ForgotPasswordform.html" class="back-btn">Get a new link</a>
<?php else: ?>
  <h2>Choose a new password</h2>
  <?php if ($errors): ?><div class="error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label for="new_password">New password *</label>
    <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
    <label for="confirm_password">Type it again *</label>
    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
    <p class="form-intro">At least 8 characters, with a letter and a number.</p>
    <div class="btn-container">
      <button type="submit">Save new password</button>
      <a href="Newlogin.html" class="back-btn">&larr; Back to login</a>
    </div>
  </form>
<?php endif; ?>
</div>
</body>
</html>
