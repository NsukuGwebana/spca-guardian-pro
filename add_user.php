<?php
// Administrators add staff accounts here (Administrator, Vet or Volunteer).
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$back = $_SESSION['add_user'] ?? ['problems' => [], 'values' => []];
unset($_SESSION['add_user']);
$v = fn($k) => e($back['values'][$k] ?? '');
$role = $back['values']['userrole'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add User | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  .form-card{background:var(--spca-card);border:1px solid var(--spca-line);border-radius:var(--spca-radius);padding:24px;max-width:640px;margin:0 auto}
  .errors{margin:0 0 10px;padding:12px 16px 12px 34px;border-radius:8px;background:#fbe4e1;color:var(--spca-bad)}
  .hint{font-size:.82rem;color:var(--spca-muted);margin:4px 0 0}
</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="form-card">
    <h2>Add a user</h2>
    <p class="form-intro">Create a sign-in for a staff member. Volunteers can also sign themselves up on the login page.</p>
    <?php if ($back['problems']): ?>
      <ul class="errors" role="alert"><?php foreach ($back['problems'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form action="createSystemUserSysDev.php" method="post">
      <div class="two-col">
        <div><label for="firstname">First name *</label><input type="text" id="firstname" name="firstname" maxlength="35" value="<?= $v('firstname') ?>" required></div>
        <div><label for="surname">Surname *</label><input type="text" id="surname" name="surname" maxlength="35" value="<?= $v('surname') ?>" required></div>
      </div>
      <label for="userrole">Role *</label>
      <select id="userrole" name="userrole" required>
        <option value="">Choose a role</option>
        <?php foreach (['Administrator' => 'Administrator: full access', 'Vet' => 'Vet: animals and medical care', 'Volunteer' => 'Volunteer: activities and events'] as $r => $label): ?>
          <option value="<?= $r ?>" <?= $r === $role ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <div class="two-col">
        <div><label for="email">Email *</label><input type="email" id="email" name="email" maxlength="150" value="<?= $v('email') ?>" required><p class="hint">Used for password resets.</p></div>
        <div><label for="phonenumber">Phone number *</label><input type="tel" id="phonenumber" name="phonenumber" maxlength="15" value="<?= $v('phonenumber') ?>" placeholder="082 123 4567" required></div>
      </div>
      <div class="two-col">
        <div><label for="username">Username *</label><input type="text" id="username" name="username" maxlength="35" value="<?= $v('username') ?>" autocomplete="off" required></div>
        <div><label for="pword">Password *</label><input type="password" id="pword" name="pword" minlength="8" maxlength="72" autocomplete="new-password" required><p class="hint">At least 8 characters, with a letter and a number.</p></div>
      </div>
      <div class="btn-container">
        <button type="submit">Create user</button>
        <a href="displaySystemUser.php" class="back-btn">&larr; Back to users</a>
      </div>
    </form>
  </div>
</main>
</body>
</html>
