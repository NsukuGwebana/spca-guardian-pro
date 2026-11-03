<?php
// Administrators: review and approve the hours volunteers log, and see who's most active.
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$secs = "MOD(TIME_TO_SEC(end_time) - TIME_TO_SEC(start_time) + 86400, 86400)";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    switch ($_POST['do'] ?? '') {
        case 'approve':
            q("UPDATE community_engagement SET status = 'Approved' WHERE volunteer_id = ?", [$id]);
            log_activity('Approved volunteer hours', "Entry #$id");
            $_SESSION['flash'] = ['ok', 'Hours approved.'];
            break;
        case 'approve_all':
            $n = q("SELECT COUNT(*) n FROM community_engagement WHERE status = 'Pending'")->fetch_assoc()['n'];
            q("UPDATE community_engagement SET status = 'Approved' WHERE status = 'Pending'");
            log_activity('Approved volunteer hours', "All pending ($n entries)");
            $_SESSION['flash'] = ['ok', "$n entries approved."];
            break;
        case 'remove':
            q("DELETE FROM community_engagement WHERE volunteer_id = ?", [$id]);
            log_activity('Removed volunteer hours', "Entry #$id");
            $_SESSION['flash'] = ['ok', 'Entry removed.'];
            break;
    }
    header('Location: Admin_Community.php?' . http_build_query(['status' => $_POST['tab'] ?? 'Pending']));
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$tab = in_array($_GET['status'] ?? '', ['Pending', 'Approved', 'All'], true) ? $_GET['status'] : 'Pending';
$search = trim($_GET['q'] ?? '');

$where = ['1=1']; $params = [];
if ($tab !== 'All') { $where[] = 'status = ?'; $params[] = $tab; }
if ($search !== '') {
    $where[] = "(CONCAT(volunteer_Fname, ' ', volunteer_Lname) LIKE ? OR email LIKE ? OR activities_name LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}
$base = 'FROM community_engagement WHERE ' . implode(' AND ', $where);

if (($_GET['export'] ?? '') === 'csv') {
    $rows = q("SELECT date, CONCAT(volunteer_Fname, ' ', volunteer_Lname), email, contact_number, activities_name, activity_description,
                      TIME_FORMAT(start_time, '%H:%i'), TIME_FORMAT(end_time, '%H:%i'), ROUND($secs / 3600, 2), status
               $base ORDER BY date DESC", $params)->fetch_all(MYSQLI_NUM);
    log_activity('Exported volunteer hours CSV', count($rows) . ' rows');
    send_csv('volunteer_hours_' . date('Y-m-d') . '.csv', ['Date', 'Volunteer', 'Email', 'Phone', 'Activity', 'Notes', 'Start', 'End', 'Hours', 'Status'], $rows);
}

$counts = ['Pending' => 0, 'Approved' => 0];
$r = q("SELECT status, COUNT(*) n FROM community_engagement GROUP BY status");
while ($c = $r->fetch_assoc()) $counts[$c['status']] = (int)$c['n'];
$counts['All'] = array_sum($counts);

$total = (int)q("SELECT COUNT(*) n $base", $params)->fetch_assoc()['n'];
[$page, $pages, $offset] = paginate($total, (int)($_GET['page'] ?? 1), 20);
$rows = q("SELECT *, $secs / 3600 AS hours $base ORDER BY date DESC, volunteer_id DESC LIMIT 20 OFFSET $offset", $params)->fetch_all(MYSQLI_ASSOC);

$month = date('Y-m-01');
$top = q("SELECT CONCAT(volunteer_Fname, ' ', volunteer_Lname) AS name, SUM($secs) / 3600 AS hours, COUNT(*) AS n
          FROM community_engagement WHERE status = 'Approved'
          GROUP BY volunteer_Fname, volunteer_Lname ORDER BY hours DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$stats = q("SELECT COUNT(DISTINCT CONCAT(volunteer_Fname, ' ', volunteer_Lname)) people,
                   COALESCE(SUM(IF(status = 'Approved', $secs, 0)), 0) / 3600 approved,
                   COALESCE(SUM(IF(date >= ?, $secs, 0)), 0) / 3600 month
            FROM community_engagement", [$month])->fetch_assoc();
$h = fn($x) => rtrim(rtrim(number_format((float)$x, 1, '.', ' '), '0'), '.');
$keep = array_filter(['status' => $tab, 'q' => $search], fn($v) => $v !== '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Volunteer Hours | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  td form { display: inline; margin: 0; }
  .num { text-align: right; font-variant-numeric: tabular-nums; }
  .muted { color: var(--spca-muted); font-size: .85rem; }
  .pill.Approved { background: #dff3e7; color: var(--spca-good); }
  .top { background: var(--spca-card); border: 1px solid var(--spca-line); border-radius: var(--spca-radius); padding: 16px; margin: 0 0 16px; }
  .top ol { margin: 6px 0 0; padding-left: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 4px 20px; }
  .top li { white-space: nowrap; }
</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="page-head">
    <div><h1>Volunteer hours</h1><p>Approve the hours volunteers log. Approved hours count towards their totals.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="tool-btn light" href="viewEvents.php">Events &amp; sign-ups</a>
      <a class="tool-btn light" href="?<?= e(http_build_query($keep + ['export' => 'csv'])) ?>">Download CSV</a>
    </div>
  </div>
  <?php if ($flash): ?><div class="flash <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>

  <section class="kpis">
    <div class="kpi"><div class="k-label">Waiting for approval</div><div class="k-value"><?= $counts['Pending'] ?></div><div class="k-sub">entries</div></div>
    <div class="kpi"><div class="k-label">Approved hours</div><div class="k-value"><?= $h($stats['approved']) ?></div><div class="k-sub">all time</div></div>
    <div class="kpi"><div class="k-label">Hours this month</div><div class="k-value"><?= $h($stats['month']) ?></div><div class="k-sub"><?= e(date('F Y')) ?></div></div>
    <div class="kpi"><div class="k-label">Volunteers</div><div class="k-value"><?= (int)$stats['people'] ?></div><div class="k-sub">who have logged hours</div></div>
  </section>

  <?php if ($top): ?>
  <div class="top"><b>Most active volunteers (approved hours)</b>
    <ol><?php foreach ($top as $t): ?><li><?= e($t['name']) ?>: <?= $h($t['hours']) ?> h <span class="muted">(<?= (int)$t['n'] ?> visit<?= (int)$t['n'] === 1 ? '' : 's' ?>)</span></li><?php endforeach; ?></ol>
  </div>
  <?php endif; ?>

  <nav class="tabs">
    <?php foreach (['Pending' => 'Waiting for approval', 'Approved' => 'Approved', 'All' => 'All'] as $k => $label): ?>
      <a class="<?= $k === $tab ? 'on' : '' ?>" href="?status=<?= $k ?>"><?= $label ?><b><?= $counts[$k] ?></b></a>
    <?php endforeach; ?>
  </nav>

  <form class="filter-bar" method="get">
    <input type="hidden" name="status" value="<?= e($tab) ?>">
    <label class="grow">Volunteer, email or activity<input type="search" name="q" value="<?= e($search) ?>"></label>
    <button type="submit">Search</button>
    <?php if ($search !== ''): ?><a class="tool-btn light" href="?status=<?= e($tab) ?>">Clear</a><?php endif; ?>
  </form>

  <div class="list-meta">
    <span><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?></span>
    <?php if ($tab === 'Pending' && $counts['Pending'] > 0): ?>
      <form method="post" style="margin:0"><input type="hidden" name="do" value="approve_all"><input type="hidden" name="tab" value="Pending"><button type="submit" class="tool-btn">Approve all <?= $counts['Pending'] ?></button></form>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Date</th><th>Volunteer</th><th>Activity</th><th>Time</th><th class="num">Hours</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7">Nothing here.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e(date('j M Y', strtotime($row['date']))) ?></td>
        <td><?= e($row['volunteer_Fname'] . ' ' . $row['volunteer_Lname']) ?><br><span class="muted"><?= e($row['email'] ?: $row['contact_number']) ?></span></td>
        <td><?= e($row['activities_name']) ?><?php if ($row['activity_description']): ?><br><span class="muted"><?= e($row['activity_description']) ?></span><?php endif; ?></td>
        <td><?= e(substr($row['start_time'], 0, 5) . '–' . substr($row['end_time'], 0, 5)) ?></td>
        <td class="num"><?= $h($row['hours']) ?></td>
        <td><span class="pill <?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
        <td>
          <?php if ($row['status'] !== 'Approved'): ?>
            <form method="post"><input type="hidden" name="do" value="approve"><input type="hidden" name="id" value="<?= (int)$row['volunteer_id'] ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>"><button type="submit" class="btn approve">Approve</button></form>
          <?php endif; ?>
          <form method="post" onsubmit="return confirmTwice(this)"><input type="hidden" name="do" value="remove"><input type="hidden" name="id" value="<?= (int)$row['volunteer_id'] ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>"><button type="submit" class="btn delete">Remove</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?= pager_html($page, $pages, $keep) ?>
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
