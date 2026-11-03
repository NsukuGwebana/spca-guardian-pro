<?php
// Staff accounts: search, filter by role, pagination, CSV export and removal.
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$search = trim($_GET['q'] ?? '');
$role   = in_array($_GET['role'] ?? '', ['Administrator', 'Vet', 'Volunteer'], true) ? $_GET['role'] : '';

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = "(username LIKE ? OR firstname LIKE ? OR surname LIKE ? OR email LIKE ? OR CAST(idnumber AS CHAR) LIKE ?)";
    array_push($params, ...array_fill(0, 5, "%$search%"));
}
if ($role !== '') { $where[] = 'userrole = ?'; $params[] = $role; }
$base = 'FROM users WHERE ' . implode(' AND ', $where);

if (($_GET['export'] ?? '') === 'csv') {
    $rows = q("SELECT idnumber, firstname, surname, username, userrole, email, phonenumber, registration_date $base ORDER BY surname, firstname", $params)->fetch_all(MYSQLI_NUM);
    log_activity('Exported users CSV', count($rows) . ' rows');
    send_csv('staff_users_' . date('Y-m-d') . '.csv', ['ID', 'First name', 'Surname', 'Username', 'Role', 'Email', 'Phone', 'Registered'], $rows);
}

$total = (int)q("SELECT COUNT(*) n $base", $params)->fetch_assoc()['n'];
[$page, $pages, $offset] = paginate($total, (int)($_GET['page'] ?? 1), 20);
$users = q("SELECT idnumber, firstname, surname, username, userrole, email, registration_date,
                   (SELECT MAX(attempted_at) FROM login_attempts la WHERE la.username = users.username AND la.success = 1) AS last_login
            $base ORDER BY surname, firstname LIMIT 20 OFFSET $offset", $params)->fetch_all(MYSQLI_ASSOC);
$filtered = array_filter(['q' => $search, 'role' => $role], fn($v) => $v !== '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Users | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>td form { display: inline; margin: 0; } .muted { color: var(--spca-muted); font-size: .85rem; }</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="page-head">
    <div><h1>Staff users</h1><p>Everyone who can sign in to the system.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="tool-btn" href="add_user.php">Add user</a>
      <a class="tool-btn light" href="?<?= e(http_build_query($filtered + ['export' => 'csv'])) ?>">Download CSV</a>
    </div>
  </div>

  <?php if ($flash): ?><div class="flash <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>

  <form class="filter-bar" method="get">
    <label class="grow">Name, username, email or ID<input type="search" name="q" value="<?= e($search) ?>"></label>
    <label>Role
      <select name="role"><option value="">All roles</option>
        <?php foreach (['Administrator', 'Vet', 'Volunteer'] as $r): ?><option <?= $r === $role ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
      </select></label>
    <button type="submit">Search</button>
    <?php if ($filtered): ?><a class="tool-btn light" href="displaySystemUser.php">Clear</a><?php endif; ?>
  </form>

  <div class="list-meta"><span><?= $total ?> user<?= $total === 1 ? '' : 's' ?></span><span>A–Z by surname</span></div>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Email</th><th>Last sign-in</th><th></th></tr></thead>
    <tbody>
    <?php if (!$users): ?><tr><td colspan="6">No users match.</td></tr><?php endif; ?>
    <?php foreach ($users as $u): $self = $u['username'] === ($_SESSION['username'] ?? ''); ?>
      <tr>
        <td><?= e($u['firstname'] . ' ' . $u['surname']) ?><br><span class="muted">ID <?= e($u['idnumber']) ?></span></td>
        <td><?= e($u['username']) ?></td>
        <td><span class="pill"><?= e($u['userrole']) ?></span></td>
        <td><?= $u['email'] ? e($u['email']) : '<span class="muted" title="No email: this user can\'t reset their password by email">—</span>' ?></td>
        <td><?= $u['last_login'] ? e(date('j M Y H:i', strtotime($u['last_login']))) : '<span class="muted">never</span>' ?></td>
        <td>
          <?php if ($self): ?><span class="muted">you</span>
          <?php else: ?>
            <form method="post" action="DeleteUser.php" onsubmit="return confirmTwice(this)">
              <input type="hidden" name="idnumber" value="<?= e($u['idnumber']) ?>">
              <button type="submit" class="btn delete">Remove</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?= pager_html($page, $pages, $filtered) ?>
  <div class="back-row"><a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a></div>
</main>
<script>
function confirmTwice(form) {
  const b = form.querySelector('button');
  if (b.dataset.armed) return true;
  b.dataset.armed = 1; b.textContent = 'Click again to remove';
  setTimeout(() => { delete b.dataset.armed; b.textContent = 'Remove'; }, 4000);
  return false;
}
</script>
</body>
</html>
