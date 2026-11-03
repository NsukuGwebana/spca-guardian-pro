<?php
// Management dashboard: the key numbers an SPCA manager looks at each month.
// Every chart can be downloaded as CSV (opens in Excel) and read as a table.
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

/* ---------- Time window ---------- */
$months = in_array((int)($_GET['months'] ?? 6), [6, 12, 24], true) ? (int)($_GET['months'] ?? 6) : 6;

// End at the latest month that has data (never in the future), so an old
// sample database still shows a useful picture.
$latest = '1970-01-01';
foreach (["SELECT MAX(created_at) d FROM adoptions", "SELECT MAX(ddate) d FROM donations", "SELECT MAX(`date`) d FROM cruelty_report"] as $sql) {
    $latest = max($latest, (string)(q($sql)->fetch_assoc()['d'] ?? ''));
}
$latest = $latest === '1970-01-01' ? date('Y-m-d') : min($latest, date('Y-m-d'));
$end   = new DateTime(substr($latest, 0, 7) . '-01');
$start = (clone $end)->modify('-' . ($months - 1) . ' months');
$from  = $start->format('Y-m-d');
$to    = (clone $end)->modify('+1 month')->format('Y-m-d');

$monthKeys = [];
for ($d = clone $start; $d <= $end; $d->modify('+1 month')) $monthKeys[$d->format('Y-m')] = $d->format('M y');

/** Turns "month => value" rows into a full series with zeros for empty months. */
function by_month(string $sql, array $params, array $monthKeys): array
{
    $vals = array_fill_keys(array_keys($monthKeys), 0);
    $r = q($sql, $params);
    while ($row = $r->fetch_assoc()) if (isset($vals[$row['m']])) $vals[$row['m']] = (float)$row['v'];
    return array_values($vals);
}

/* ---------- KPIs ---------- */
$avgDays = q("SELECT ROUND(AVG(DATEDIFF(status_updated_at, created_at))) d, COUNT(*) n
              FROM adoptions WHERE status = 'Adopted' AND status_updated_at IS NOT NULL")->fetch_assoc();
$stay    = q("SELECT ROUND(AVG(DATEDIFF(NOW(), Intake_date))) d, COUNT(*) n
              FROM animals WHERE is_deleted = 0 AND status <> 'Adopted' AND Intake_date IS NOT NULL")->fetch_assoc();
$kennel  = q("SELECT SUM(status = 'Occupied') occ, SUM(LOWER(status) <> 'maintenance') usable
              FROM kennels WHERE is_deleted = 0")->fetch_assoc();
$open    = q("SELECT COUNT(*) n, SUM(DATEDIFF(NOW(), created_at) > 5) late
              FROM adoptions WHERE status IN ('Pending','Home visit','Approved')")->fetch_assoc();
$decided = q("SELECT SUM(status = 'Adopted') a, SUM(status IN ('Adopted','Rejected')) d FROM adoptions")->fetch_assoc();
$donTot  = q("SELECT COALESCE(SUM(damount),0) s, COUNT(*) n FROM donations WHERE ddate >= ? AND ddate < ?", [$from, $to])->fetch_assoc();

$occPct  = $kennel['usable'] ? round(100 * $kennel['occ'] / $kennel['usable']) : 0;
$success = $decided['d'] ? round(100 * $decided['a'] / $decided['d']) : null;

/* ---------- Chart series ---------- */
$applied = by_month("SELECT DATE_FORMAT(created_at, '%Y-%m') m, COUNT(*) v FROM adoptions
                     WHERE created_at >= ? AND created_at < ? GROUP BY m", [$from, $to], $monthKeys);
$adopted = by_month("SELECT DATE_FORMAT(status_updated_at, '%Y-%m') m, COUNT(*) v FROM adoptions
                     WHERE status = 'Adopted' AND status_updated_at >= ? AND status_updated_at < ? GROUP BY m", [$from, $to], $monthKeys);
$donated = by_month("SELECT DATE_FORMAT(ddate, '%Y-%m') m, SUM(damount) v FROM donations
                     WHERE ddate >= ? AND ddate < ? GROUP BY m", [$from, $to], $monthKeys);

$areas = q("SELECT TRIM(SUBSTRING_INDEX(location, ',', 1)) area, COUNT(*) n FROM cruelty_report
            WHERE `date` >= ? AND `date` < ? GROUP BY area ORDER BY n DESC, area LIMIT 8", [$from, $to])->fetch_all(MYSQLI_ASSOC);
$species = q("SELECT COALESCE(NULLIF(TRIM(Species), ''), 'Unknown') s, COUNT(*) n FROM animals
              WHERE is_deleted = 0 AND status <> 'Adopted' GROUP BY s ORDER BY n DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

/* ---------- CSV downloads ---------- */
$export = $_GET['export'] ?? '';
if ($export !== '') {
    $labels = array_keys($monthKeys);
    $sets = [
        'adoptions' => [['Month', 'Applications', 'Adoptions completed'], array_map(null, $labels, $applied, $adopted)],
        'donations' => [['Month', 'Donations (R)'], array_map(null, $labels, $donated)],
        'cruelty'   => [['Area', 'Cruelty reports'], array_map(fn($r) => [$r['area'], $r['n']], $areas)],
        'species'   => [['Species', 'Animals in care'], array_map(fn($r) => [$r['s'], $r['n']], $species)],
    ];
    if (!isset($sets[$export])) show_error_page(404, 'Unknown download', 'That report does not exist.');
    log_activity('Exported analytics CSV', $export);
    send_csv("spca_{$export}_{$from}_to_" . $end->format('Y-m') . '.csv', $sets[$export][0], $sets[$export][1]);
}

$period = $start->format('M Y') . ' – ' . $end->format('M Y');
$charts = [
    'adoptions' => ['labels' => array_values($monthKeys), 'series' => [['Applications', $applied], ['Adoptions completed', $adopted]]],
    'donations' => ['labels' => array_values($monthKeys), 'series' => [['Donations (R)', $donated]]],
    'cruelty'   => ['labels' => array_column($areas, 'area'), 'series' => [['Reports', array_map('intval', array_column($areas, 'n'))]]],
    'species'   => ['labels' => array_column($species, 's'), 'series' => [['Animals', array_map('intval', array_column($species, 'n'))]]],
];
$fmtR = fn($v) => 'R ' . number_format((float)$v, 0, '.', ' ');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Management Dashboard | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  .chart-head { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
  .chart-head a { font-size: .8rem; font-weight: 600; color: var(--spca-teal); white-space: nowrap; }
  details { margin-top: 10px; font-size: .85rem; }
  details summary { cursor: pointer; color: var(--spca-teal); font-weight: 600; }
  details table { margin-top: 8px; font-size: .85rem; }
  .legend { display: flex; gap: 14px; font-size: .8rem; color: var(--spca-muted); margin-bottom: 6px; flex-wrap: wrap; }
  .legend i { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 5px; vertical-align: -1px; }
  .empty { color: var(--spca-muted); font-size: .9rem; padding: 40px 0; text-align: center; }
  .kpi.warn .k-value { color: var(--spca-bad); }
</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="page-head">
    <div>
      <h1>Management dashboard</h1>
      <p><?= e($period) ?><?= $end->format('Y-m') !== date('Y-m') ? ' (latest month with data)' : '' ?></p>
    </div>
    <form class="filter-bar" method="get" style="margin:0">
      <label>Period
        <select name="months" onchange="this.form.submit()">
          <?php foreach ([6, 12, 24] as $m): ?><option value="<?= $m ?>" <?= $m === $months ? 'selected' : '' ?>>Last <?= $m ?> months</option><?php endforeach; ?>
        </select>
      </label>
      <noscript><button type="submit">Show</button></noscript>
    </form>
  </div>

  <section class="kpis" aria-label="Key numbers">
    <div class="kpi"><div class="k-label">Avg days to adoption</div>
      <div class="k-value"><?= $avgDays['n'] ? (int)$avgDays['d'] : '—' ?></div>
      <div class="k-sub">application to handover, <?= (int)$avgDays['n'] ?> adoption<?= $avgDays['n'] == 1 ? '' : 's' ?></div></div>
    <div class="kpi"><div class="k-label">Kennel occupancy</div>
      <div class="k-value"><?= $occPct ?>%</div>
      <div class="k-sub"><?= (int)$kennel['occ'] ?> of <?= (int)$kennel['usable'] ?> usable kennels</div></div>
    <div class="kpi <?= $open['late'] ? 'warn' : '' ?>"><div class="k-label">Open applications</div>
      <div class="k-value"><?= (int)$open['n'] ?></div>
      <div class="k-sub"><?= (int)$open['late'] ?> waiting more than 5 days</div></div>
    <div class="kpi"><div class="k-label">Approval rate</div>
      <div class="k-value"><?= $success === null ? '—' : $success . '%' ?></div>
      <div class="k-sub">of decided applications</div></div>
    <div class="kpi"><div class="k-label">Avg length of stay</div>
      <div class="k-value"><?= $stay['n'] ? (int)$stay['d'] : '—' ?></div>
      <div class="k-sub">days, <?= (int)$stay['n'] ?> animals in care</div></div>
    <div class="kpi"><div class="k-label">Donations</div>
      <div class="k-value"><?= e($fmtR($donTot['s'])) ?></div>
      <div class="k-sub"><?= (int)$donTot['n'] ?> gifts in this period</div></div>
  </section>

  <section class="charts">
    <?php
    $cards = [
      'adoptions' => ['Adoptions per month', 'Applications received and adoptions completed.'],
      'donations' => ['Donations by month', 'Total rand value received.'],
      'cruelty'   => ['Cruelty reports by area', 'Top areas in this period: where to focus inspections and outreach.'],
      'species'   => ['Animals in care by species', 'Current shelter population.'],
    ];
    foreach ($cards as $key => [$title, $sub]):
      $c = $charts[$key]; $hasData = array_sum(array_merge(...array_map(fn($s) => $s[1], $c['series']))) > 0; ?>
    <article class="chart-card">
      <div class="chart-head">
        <div><h3><?= e($title) ?></h3><p><?= e($sub) ?></p></div>
        <a href="?months=<?= $months ?>&amp;export=<?= $key ?>">CSV</a>
      </div>
      <?php if (count($c['series']) > 1): ?>
        <div class="legend"><span><i style="background:#008ba3"></i><?= e($c['series'][0][0]) ?></span><span><i style="background:#b86a00"></i><?= e($c['series'][1][0]) ?></span></div>
      <?php endif; ?>
      <?php if ($hasData): ?>
        <div class="chart-box"><canvas id="c-<?= $key ?>" role="img" aria-label="<?= e($title) ?> chart"></canvas></div>
      <?php else: ?>
        <p class="empty">No data in this period.</p>
      <?php endif; ?>
      <details>
        <summary>Show as table</summary>
        <div class="table-wrap"><table>
          <thead><tr><th></th><?php foreach ($c['series'] as $s): ?><th><?= e($s[0]) ?></th><?php endforeach; ?></tr></thead>
          <tbody>
          <?php foreach ($c['labels'] as $i => $label): ?>
            <tr><td><?= e($label) ?></td><?php foreach ($c['series'] as $s): ?><td><?= $key === 'donations' ? e($fmtR($s[1][$i])) : (int)$s[1][$i] ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      </details>
    </article>
    <?php endforeach; ?>
  </section>

  <div class="back-row"><a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a></div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const DATA = <?= json_encode($charts, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const COLORS = ['#008ba3', '#b86a00'];   // checked for colour-blind separation and contrast
if (window.Chart) {
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.color = '#5b6e72';
  const grid = { color: '#e3ebed', drawTicks: false };
  const rand = v => 'R ' + Math.round(v).toLocaleString('en-ZA');

  function draw(key, opts = {}) {
    const el = document.getElementById('c-' + key);
    if (!el) return;
    const d = DATA[key];
    new Chart(el, {
      type: opts.type || 'bar',
      data: {
        labels: d.labels,
        datasets: d.series.map(([label, values], i) => ({
          label, data: values,
          backgroundColor: COLORS[i], borderColor: COLORS[i],
          borderRadius: 4, borderSkipped: 'start', maxBarThickness: 26,
          borderWidth: opts.type === 'line' ? 2 : 0, pointRadius: 4, pointHoverRadius: 6, tension: 0,
        })),
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        indexAxis: opts.horizontal ? 'y' : 'x',
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: opts.money ? { label: c => c.dataset.label + ': ' + rand(c.parsed.y) } : {} },
        },
        scales: {
          x: { grid: opts.horizontal ? grid : { display: false }, border: { display: false },
               ticks: opts.horizontal ? { precision: 0 } : { maxRotation: 0, autoSkip: true } },
          y: { grid: opts.horizontal ? { display: false } : grid, border: { display: false }, beginAtZero: true,
               ticks: opts.money ? { callback: rand } : { precision: 0 } },
        },
      },
    });
  }
  draw('adoptions', { type: 'line' });
  draw('donations', { money: true });
  draw('cruelty', { horizontal: true });
  draw('species', { horizontal: true });
}
</script>
</body>
</html>
