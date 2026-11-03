<?php
// Who did what, and when: sign-ins, adoption decisions, exports, deletions, password resets.
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$f = [
    'user'   => trim($_GET['user'] ?? ''),
    'action' => trim($_GET['action_like'] ?? ''),
    'from'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '',
    'to'     => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '',
];
$where = ['1=1'];
$params = [];
if ($f['user'] !== '')   { $where[] = 'username = ?';        $params[] = $f['user']; }
if ($f['action'] !== '') { $where[] = '(action LIKE ? OR details LIKE ?)'; array_push($params, "%{$f['action']}%", "%{$f['action']}%"); }
if ($f['from'] !== '')   { $where[] = 'created_at >= ?';     $params[] = $f['from'] . ' 00:00:00'; }
if ($f['to'] !== '')     { $where[] = 'created_at <= ?';     $params[] = $f['to'] . ' 23:59:59'; }
$base = 'FROM activity_log WHERE ' . implode(' AND ', $where);

if (($_GET['export'] ?? '') === 'csv') {
    $rows = q("SELECT created_at, username, user_role, action, details, ip_address $base ORDER BY id DESC", $params)->fetch_all(MYSQLI_NUM);
    send_csv('activity_log_' . date('Y-m-d') . '.csv', ['When', 'User', 'Role', 'Action', 'Details', 'IP address'], $rows);
}

$total = (int)q("SELECT COUNT(*) n $base", $params)->fetch_assoc()['n'];
[$page, $pages, $offset] = paginate($total, (int)($_GET['page'] ?? 1), 30);
$rows  = q("SELECT * $base ORDER BY id DESC LIMIT 30 OFFSET $offset", $params)->fetch_all(MYSQLI_ASSOC);
$users = q("SELECT DISTINCT username FROM activity_log ORDER BY username")->fetch_all(MYSQLI_NUM);
$fails = q("SELECT COUNT(*) n FROM login_attempts WHERE success = 0 AND attempted_at > NOW() - INTERVAL 1 DAY")->fetch_assoc()['n'];
$keep  = array_filter(['user' => $f['user'], 'action_like' => $f['action'], 'from' => $f['from'], 'to' => $f['to']], fn($v) => $v !== '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Activity Log | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>.when { white-space: nowrap; font-variant-numeric: tabular-nums; } .muted { color: var(--spca-muted); font-size: .85rem; }</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="page-head">
    <div><h1>Activity log</h1><p>Every important action in the system, newest first. <?= (int)$fails ?> failed sign-in<?= $fails == 1 ? '' : 's' ?> in the last 24 hours.</p></div>
    <a class="tool-btn light" href="?<?= e(http_build_query($keep + ['export' => 'csv'])) ?>">Download CSV</a>
  </div>

  <form class="filter-bar" method="get">
    <label>User<select name="user"><option value="">Everyone</option>
      <?php foreach ($users as [$u]): ?><option <?= $u === $f['user'] ? 'selected' : '' ?>><?= e($u) ?></option><?php endforeach; ?>
    </select></label>
    <label class="grow">Action or detail contains<input type="search" name="action_like" value="<?= e($f['action']) ?>" placeholder="e.g. Adoption, Exported, Removed"></label>
    <label>From<input type="date" name="from" value="<?= e($f['from']) ?>"></label>
    <label>To<input type="date" name="to" value="<?= e($f['to']) ?>"></label>
    <button type="submit">Filter</button>
    <?php if ($keep): ?><a class="tool-btn light" href="activity_log.php">Clear</a><?php endif; ?>
  </form>

  <div class="list-meta"><span><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?></span></div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP address</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="5">Nothing logged yet for these filters.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="when"><?= e(date('j M Y H:i', strtotime($r['created_at']))) ?></td>
        <td><?= e($r['username']) ?><?php if ($r['user_role']): ?><br><span class="muted"><?= e($r['user_role']) ?></span><?php endif; ?></td>
        <td><?= e($r['action']) ?></td>
        <td><?= e($r['details']) ?></td>
        <td class="muted"><?= e($r['ip_address']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?= pager_html($page, $pages, $keep) ?>
  <div class="back-row"><a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a></div>
</main>
</body>
</html>
