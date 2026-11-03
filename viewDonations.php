<?php
// Donation records with search, filters, totals, pagination and CSV export.
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$f = [
    'q'    => trim($_GET['q'] ?? ''),
    'type' => trim($_GET['type'] ?? ''),
    'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '',
    'to'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '',
    'receipt' => in_array($_GET['receipt'] ?? '', ['sent', 'unsent'], true) ? $_GET['receipt'] : '',
];

$where = ['1=1'];
$params = [];
if ($f['q'] !== '') {
    $where[] = "(CONCAT(r.F_name, ' ', r.L_name) LIKE ? OR r.E_address LIKE ? OR d.dlocation LIKE ?)";
    array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
}
if ($f['type'] !== '')    { $where[] = 'd.dtype = ?';  $params[] = $f['type']; }
if ($f['from'] !== '')    { $where[] = 'd.ddate >= ?'; $params[] = $f['from']; }
if ($f['to'] !== '')      { $where[] = 'd.ddate <= ?'; $params[] = $f['to']; }
if ($f['receipt'] !== '') { $where[] = 'd.seen = ' . ($f['receipt'] === 'sent' ? 1 : 0); }
$base = "FROM donations d JOIN donor r ON d.donor_id = r.donor_id WHERE " . implode(' AND ', $where);

if (($_GET['export'] ?? '') === 'csv') {
    $rows = q("SELECT d.ddate, CONCAT(r.F_name, ' ', r.L_name), r.E_address, r.Phone_num, d.damount, d.dtype, d.dlocation,
                      IF(d.seen, 'Yes', 'No') $base ORDER BY d.ddate DESC", $params)->fetch_all(MYSQLI_NUM);
    log_activity('Exported donations CSV', count($rows) . ' rows');
    send_csv('donations_' . date('Y-m-d') . '.csv', ['Date', 'Donor', 'Email', 'Phone', 'Amount (R)', 'Type', 'Location', 'Receipt sent'], $rows);
}

$sum = q("SELECT COUNT(*) n, COALESCE(SUM(d.damount), 0) total $base", $params)->fetch_assoc();
[$page, $pages, $offset] = paginate((int)$sum['n'], (int)($_GET['page'] ?? 1), 20);
$rows = q("SELECT d.dtype, d.dlocation, d.damount, d.donor_id, d.ddate, d.seen, r.F_name, r.L_name, r.E_address
           $base ORDER BY d.ddate DESC, d.donation_id DESC LIMIT 20 OFFSET $offset", $params)->fetch_all(MYSQLI_ASSOC);
$types = q("SELECT DISTINCT dtype FROM donations WHERE dtype <> '' ORDER BY dtype")->fetch_all(MYSQLI_NUM);
$filtered = array_filter($f, fn($v) => $v !== '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Donations | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  td form { display: inline; margin: 0; }
  .flag { color: var(--spca-bad); font-weight: 600; font-size: .8rem; }
  .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="page-head">
    <div><h1>Donations</h1><p>Send receipts and thank donors. Gifts over R500 are flagged for a personal acknowledgement.</p></div>
    <a class="tool-btn light" href="?<?= e(http_build_query($filtered + ['export' => 'csv'])) ?>">Download CSV</a>
  </div>

  <?php if ($flash): ?><div class="flash <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>

  <form class="filter-bar" method="get">
    <label class="grow">Donor, email or location<input type="search" name="q" value="<?= e($f['q']) ?>"></label>
    <label>Type
      <select name="type"><option value="">All</option>
        <?php foreach ($types as [$t]): ?><option <?= $t === $f['type'] ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
      </select></label>
    <label>From<input type="date" name="from" value="<?= e($f['from']) ?>"></label>
    <label>To<input type="date" name="to" value="<?= e($f['to']) ?>"></label>
    <label>Receipt
      <select name="receipt"><option value="">All</option>
        <option value="unsent" <?= $f['receipt'] === 'unsent' ? 'selected' : '' ?>>Not sent</option>
        <option value="sent" <?= $f['receipt'] === 'sent' ? 'selected' : '' ?>>Sent</option>
      </select></label>
    <button type="submit">Filter</button>
    <?php if ($filtered): ?><a class="tool-btn light" href="viewDonations.php">Clear</a><?php endif; ?>
  </form>

  <div class="list-meta">
    <span><?= (int)$sum['n'] ?> donation<?= $sum['n'] == 1 ? '' : 's' ?> · total <strong>R <?= number_format((float)$sum['total'], 2, '.', ' ') ?></strong></span>
    <span>Newest first</span>
  </div>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Date</th><th>Donor</th><th class="num">Amount</th><th>Type</th><th>Location</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6">No donations match these filters.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row):
        $key = ['donor_id' => $row['donor_id'], 'date' => $row['ddate']]; ?>
      <tr>
        <td><?= $row['ddate'] ? e(date('j M Y', strtotime($row['ddate']))) : '—' ?></td>
        <td><?= e($row['F_name'] . ' ' . $row['L_name']) ?></td>
        <td class="num">R <?= number_format((float)$row['damount'], 2, '.', ' ') ?><?php if ($row['damount'] > 500): ?> <span class="flag" title="Large gift">● large</span><?php endif; ?></td>
        <td><?= e(ucfirst((string)$row['dtype'])) ?></td>
        <td><?= e($row['dlocation'] ?? '') ?></td>
        <td>
          <a class="btn" href="receipt.php?<?= e(http_build_query($key)) ?>">View</a>
          <?php if (!$row['seen']): ?>
            <form method="post" action="send_receipt.php"><input type="hidden" name="donor_id" value="<?= (int)$row['donor_id'] ?>"><input type="hidden" name="date" value="<?= e($row['ddate']) ?>"><button type="submit" class="btn">Send receipt</button></form>
            <?php if ($row['damount'] > 500): ?>
              <form method="post" action="acknowledge_email.php"><input type="hidden" name="donor_id" value="<?= (int)$row['donor_id'] ?>"><input type="hidden" name="date" value="<?= e($row['ddate']) ?>"><button type="submit" class="btn">Thank donor</button></form>
            <?php endif; ?>
          <?php else: ?><span class="pill">Receipt sent</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <?= pager_html($page, $pages, $filtered) ?>
  <div class="back-row"><a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a></div>
</main>
</body>
</html>
