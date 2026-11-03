<?php
// The volunteer's own dashboard: their hours, logging new hours, and signing up for events.
// Managing volunteers (approving hours, contact details, creating events) is for Administrators.
$ALLOWED_ROLES = ['Volunteer'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$me = q("SELECT idnumber, firstname, surname, email, phonenumber FROM users WHERE username = ?", [$_SESSION['username']])->fetch_assoc();
if (!$me) {
    show_error_page(404, 'Account not found', 'Your account could not be found. Please sign in again.');
}
$myId = (int)$me['idnumber'];

const ACTIVITIES = [
    'Walking' => 'Walking dogs',
    'Feeding' => 'Feeding',
    'Hygiene Assistance' => 'Cleaning and hygiene',
    'Visiting' => 'Visiting and socialising',
    'Fostering' => 'Fostering',
    'Fundraising' => 'Fundraising',
    'Adoption Support' => 'Helping at adoption events',
    'Other' => 'Something else',
];

// Entries that belong to this volunteer: linked to the account, or older ones logged with the same email.
$mine = "(user_id = ? OR (user_id IS NULL AND email <> '' AND email = ?))";
$mineParams = [$myId, (string)$me['email']];
$secs = "MOD(TIME_TO_SEC(end_time) - TIME_TO_SEC(start_time) + 86400, 86400)"; // handles shifts past midnight

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$old = $_SESSION['log_old'] ?? [];
unset($_SESSION['log_old']);

/* ---------- Actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = $_POST['do'] ?? '';

    if ($do === 'log') {
        $date  = $_POST['date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end   = $_POST['end_time'] ?? '';
        $act   = $_POST['activity'] ?? '';
        $notes = mb_substr(trim($_POST['notes'] ?? ''), 0, 500);
        $problems = [];
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date)            $problems[] = 'Choose the date you volunteered.';
        elseif ($date > date('Y-m-d'))                        $problems[] = "You can't log hours for a future date.";
        elseif ($date < date('Y-m-d', strtotime('-1 year')))  $problems[] = 'Hours older than a year must be added by the office.';
        if (!isset(ACTIVITIES[$act]))                         $problems[] = 'Choose what you did.';
        if ($act === 'Other' && $notes === '')                $problems[] = 'Tell us what you did.';
        $mins = 0;
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
            $problems[] = 'Enter your start and end times.';
        } else {
            $mins = (strtotime($end) - strtotime($start)) / 60;
            if ($mins <= 0)        $problems[] = 'The end time must be after the start time.';
            elseif ($mins > 720)   $problems[] = 'A single entry can be at most 12 hours. Log long days as two entries.';
        }

        if ($problems) {
            $_SESSION['flash'] = ['bad', implode(' ', $problems)];
            $_SESSION['log_old'] = compact('date', 'start', 'end', 'act', 'notes');
        } else {
            q("INSERT INTO community_engagement
                 (volunteer_Fname, volunteer_Lname, contact_number, email, date, activities_name, activity_description,
                  start_time, end_time, lapse_time, supervisor_name, supervisor_surname, user_id, status)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', ?, 'Pending')",
              [$me['firstname'], $me['surname'], (string)$me['phonenumber'], (string)$me['email'], $date, $act, $notes,
               $start, $end, intdiv((int)$mins, 60) . ' hours ' . ((int)$mins % 60) . ' minutes', $myId]);
            log_activity('Logged volunteer hours', ACTIVITIES[$act] . " on {$date} ({$start}–{$end})");
            $_SESSION['flash'] = ['ok', 'Thanks! Your ' . round($mins / 60, 1) . ' hours were logged and are waiting for approval.'];
        }
    } elseif ($do === 'signup' || $do === 'cancel') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $ev = q("SELECT id, event_name, event_date, max_volunteers,
                        (SELECT COUNT(*) FROM event_signups s WHERE s.event_id = events.id) AS taken
                 FROM events WHERE id = ?", [$eventId])->fetch_assoc();
        $already = $ev ? q("SELECT 1 FROM event_signups WHERE event_id = ? AND volunteer_id = ?", [$eventId, $myId])->num_rows > 0 : false;

        if (!$ev || $ev['event_date'] < date('Y-m-d')) {
            $_SESSION['flash'] = ['bad', 'That event has already happened or no longer exists.'];
        } elseif ($do === 'signup') {
            if ($already) {
                $_SESSION['flash'] = ['ok', "You're already signed up for {$ev['event_name']}."];
            } elseif ((int)$ev['taken'] >= (int)$ev['max_volunteers']) {
                $_SESSION['flash'] = ['bad', "Sorry, {$ev['event_name']} is full."];
            } else {
                q("INSERT INTO event_signups (event_id, volunteer_id) VALUES (?, ?)", [$eventId, $myId]);
                log_activity('Signed up for event', $ev['event_name'] . ' on ' . $ev['event_date']);
                $_SESSION['flash'] = ['ok', "You're signed up for {$ev['event_name']} on " . date('j F', strtotime($ev['event_date'])) . '. See you there!'];
            }
        } else {
            q("DELETE FROM event_signups WHERE event_id = ? AND volunteer_id = ?", [$eventId, $myId]);
            log_activity('Cancelled event sign-up', $ev['event_name']);
            $_SESSION['flash'] = ['ok', "You're no longer signed up for {$ev['event_name']}."];
        }
    }
    header('Location: volunteerDashboard.php');
    exit;
}

/* ---------- CSV of my hours (proof of hours for school, work or bursaries) ---------- */
if (($_GET['export'] ?? '') === 'hours') {
    $rows = q("SELECT date, activities_name, activity_description, TIME_FORMAT(start_time, '%H:%i'), TIME_FORMAT(end_time, '%H:%i'),
                      ROUND($secs / 3600, 2), status
               FROM community_engagement WHERE $mine ORDER BY date DESC, start_time DESC", $mineParams)->fetch_all(MYSQLI_NUM);
    send_csv('my_volunteer_hours_' . date('Y-m-d') . '.csv', ['Date', 'Activity', 'Notes', 'Start', 'End', 'Hours', 'Status'], $rows);
}

/* ---------- Numbers ---------- */
$tot = q("SELECT COUNT(*) n,
                 COALESCE(SUM($secs), 0) / 3600 AS hours,
                 COALESCE(SUM(IF(status = 'Approved', $secs, 0)), 0) / 3600 AS approved,
                 COALESCE(SUM(IF(date >= ?, $secs, 0)), 0) / 3600 AS month
          FROM community_engagement WHERE $mine", array_merge([date('Y-m-01')], $mineParams))->fetch_assoc();

$recent = q("SELECT date, activities_name, activity_description, start_time, end_time, status, $secs / 3600 AS hours
             FROM community_engagement WHERE $mine ORDER BY date DESC, start_time DESC LIMIT 8", $mineParams)->fetch_all(MYSQLI_ASSOC);

$events = q("SELECT e.id, e.event_name, e.event_date, e.event_time, e.location, e.description, e.max_volunteers,
                    (SELECT COUNT(*) FROM event_signups s WHERE s.event_id = e.id) AS taken,
                    (SELECT COUNT(*) FROM event_signups s WHERE s.event_id = e.id AND s.volunteer_id = ?) AS going
             FROM events e WHERE e.event_date >= CURDATE()
             ORDER BY e.event_date, e.event_time LIMIT 6", [$myId])->fetch_all(MYSQLI_ASSOC);
$goingCount = count(array_filter($events, fn($e) => (int)$e['going'] > 0));

$h = fn($x) => rtrim(rtrim(number_format((float)$x, 1, '.', ' '), '0'), '.');
$v = fn($k, $default = '') => e($old[$k] ?? $default);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Volunteer Dashboard | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  .hello { display: flex; justify-content: space-between; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
  .hello h1 { margin: 0; }
  .hello p { margin: 4px 0 0; color: var(--spca-muted); }
  .grid2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr); gap: 16px; align-items: start; }
  @media (max-width: 900px) { .grid2 { grid-template-columns: 1fr; } }
  .card { background: var(--spca-card); border: 1px solid var(--spca-line); border-radius: var(--spca-radius); padding: 20px; color: var(--spca-ink); min-width: 0; }
  .card h2 { font-size: 1.15rem; margin: 0 0 4px; }
  .card .sub { margin: 0 0 14px; color: var(--spca-muted); font-size: .88rem; }
  body.spca .card form label { margin: 10px 0 4px; font-size: .85rem; }
  .row3 { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 10px; }
  @media (max-width: 480px) { .row3 { grid-template-columns: 1fr 1fr; } .row3 > :first-child { grid-column: 1 / -1; } }
  .event { border: 1px solid var(--spca-line); border-radius: 10px; padding: 14px; display: grid; grid-template-columns: 58px 1fr auto; gap: 14px; align-items: center; margin-bottom: 10px; }
  .event .date { text-align: center; background: var(--spca-teal-soft); color: var(--spca-teal); border-radius: 10px; padding: 6px 0; line-height: 1.1; }
  .event .date b { display: block; font-size: 1.35rem; }
  .event .date span { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
  .event h3 { margin: 0; font-size: 1rem; }
  .event p { margin: 2px 0 0; font-size: .84rem; color: var(--spca-muted); }
  .event form { margin: 0; }
  .event button { padding: 8px 14px; border-radius: 8px; border: 0; font-weight: 600; cursor: pointer; background: var(--spca-teal); color: #fff; white-space: nowrap; }
  .event button.cancel { background: var(--spca-teal-soft); color: var(--spca-teal); }
  .event button[disabled] { background: #e3e8e9; color: var(--spca-muted); cursor: not-allowed; }
  .going { color: var(--spca-good); font-weight: 600; font-size: .8rem; display: block; margin-bottom: 4px; text-align: right; }
  @media (max-width: 480px) { .event { grid-template-columns: 50px 1fr; } .event form { grid-column: 1 / -1; } .event button { width: 100%; } .going { text-align: left; } }
  .links { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
  .empty { color: var(--spca-muted); font-size: .92rem; padding: 10px 0; }
  .pill.Approved { background: #dff3e7; color: var(--spca-good); }
  .num { text-align: right; font-variant-numeric: tabular-nums; }
</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="hello">
    <div>
      <h1>Hi, <?= e($me['firstname']) ?>!</h1>
      <p>Thank you for helping the animals. Here's your volunteering at a glance.</p>
    </div>
    <a class="tool-btn" href="#log">+ Log hours</a>
  </div>

  <?php if ($flash): ?><div class="flash <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>

  <section class="kpis" aria-label="My numbers">
    <div class="kpi"><div class="k-label">Hours this month</div><div class="k-value"><?= $h($tot['month']) ?></div><div class="k-sub"><?= e(date('F Y')) ?></div></div>
    <div class="kpi"><div class="k-label">Total hours</div><div class="k-value"><?= $h($tot['hours']) ?></div><div class="k-sub"><?= $h($tot['approved']) ?> approved by the office</div></div>
    <div class="kpi"><div class="k-label">Times volunteered</div><div class="k-value"><?= (int)$tot['n'] ?></div><div class="k-sub">entries logged</div></div>
    <div class="kpi"><div class="k-label">Upcoming events</div><div class="k-value"><?= $goingCount ?></div><div class="k-sub">you're signed up for</div></div>
  </section>

  <div class="grid2">
    <section class="card" id="log">
      <h2>Log my hours</h2>
      <p class="sub">Logged as <?= e($me['firstname'] . ' ' . $me['surname']) ?>. The office approves hours each week.</p>
      <form method="post">
        <input type="hidden" name="do" value="log">
        <div class="row3">
          <div><label for="date">Date</label><input type="date" id="date" name="date" max="<?= date('Y-m-d') ?>" value="<?= $v('date', date('Y-m-d')) ?>" required></div>
          <div><label for="start_time">From</label><input type="time" id="start_time" name="start_time" value="<?= $v('start') ?>" required></div>
          <div><label for="end_time">To</label><input type="time" id="end_time" name="end_time" value="<?= $v('end') ?>" required></div>
        </div>
        <label for="activity">What did you do?</label>
        <select id="activity" name="activity" required>
          <option value="">Choose an activity</option>
          <?php foreach (ACTIVITIES as $val => $label): ?><option value="<?= e($val) ?>" <?= ($old['act'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <label for="notes">Notes <span style="font-weight:400;color:var(--spca-muted)">(optional, or required for "Something else")</span></label>
        <input type="text" id="notes" name="notes" maxlength="500" value="<?= $v('notes') ?>" placeholder="e.g. Walked Bella and Max">
        <p class="sub" id="dur" style="margin:8px 0 0" aria-live="polite"></p>
        <div class="btn-container"><button type="submit">Log hours</button></div>
      </form>
    </section>

    <section class="card">
      <h2>Upcoming events</h2>
      <p class="sub">Sign up so the team knows how many helpers to expect.</p>
      <?php if (!$events): ?>
        <p class="empty">No events are planned right now. Check back soon.</p>
      <?php endif; ?>
      <?php foreach ($events as $ev):
          $left = max(0, (int)$ev['max_volunteers'] - (int)$ev['taken']);
          $going = (int)$ev['going'] > 0; ?>
        <article class="event">
          <div class="date"><b><?= e(date('j', strtotime($ev['event_date']))) ?></b><span><?= e(date('M', strtotime($ev['event_date']))) ?></span></div>
          <div>
            <h3><?= e($ev['event_name']) ?></h3>
            <p><?= e(date('D', strtotime($ev['event_date']))) ?> · <?= e(substr($ev['event_time'], 0, 5)) ?> · <?= e($ev['location']) ?></p>
            <?php if ($ev['description']): ?><p><?= e($ev['description']) ?></p><?php endif; ?>
            <p><?= $left ?> of <?= (int)$ev['max_volunteers'] ?> spots left</p>
          </div>
          <form method="post">
            <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
            <?php if ($going): ?>
              <span class="going">✓ You're going</span>
              <button class="cancel" type="submit" name="do" value="cancel">Can't make it</button>
            <?php elseif ($left === 0): ?>
              <button type="button" disabled>Full</button>
            <?php else: ?>
              <button type="submit" name="do" value="signup">Sign me up</button>
            <?php endif; ?>
          </form>
        </article>
      <?php endforeach; ?>
    </section>
  </div>

  <section class="card" style="margin-top:16px">
    <div class="page-head" style="align-items:center">
      <div><h2>My recent volunteering</h2><p class="sub" style="margin:0">Need proof of your hours for school, work or a bursary? Download them.</p></div>
      <a class="tool-btn light" href="?export=hours">Download my hours (CSV)</a>
    </div>
    <?php if (!$recent): ?>
      <p class="empty">Nothing logged yet. Your hours will appear here once you log them.</p>
    <?php else: ?>
    <div class="table-wrap" style="margin-top:12px">
      <table>
        <thead><tr><th>Date</th><th>Activity</th><th>Time</th><th class="num">Hours</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= e(date('j M Y', strtotime($r['date']))) ?></td>
            <td><?= e(ACTIVITIES[$r['activities_name']] ?? $r['activities_name']) ?><?php if ($r['activity_description']): ?><br><span style="color:var(--spca-muted);font-size:.85rem"><?= e($r['activity_description']) ?></span><?php endif; ?></td>
            <td><?= e(substr($r['start_time'], 0, 5) . '–' . substr($r['end_time'], 0, 5)) ?></td>
            <td class="num"><?= $h($r['hours']) ?></td>
            <td><span class="pill <?= e($r['status']) ?>"><?= $r['status'] === 'Approved' ? 'Approved' : 'Waiting for approval' ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <div class="links">
    <a class="tool-btn light" href="Volunteerguide.pdf" target="_blank" rel="noopener">Volunteer guide (PDF)</a>
    <a class="tool-btn light" href="cruelty_report_form.php">Report animal cruelty</a>
    <a class="tool-btn light" href="adoption_form.php">Animals looking for homes</a>
    <a class="tool-btn light" href="ForgotPasswordform.html">Change my password</a>
  </div>
</main>
<script>
// Show the length of the shift as the volunteer fills in the times.
(function () {
  var s = document.getElementById('start_time'), e = document.getElementById('end_time'), out = document.getElementById('dur');
  function upd() {
    if (!s.value || !e.value) { out.textContent = ''; return; }
    var m = (e.value.split(':')[0] * 60 + +e.value.split(':')[1]) - (s.value.split(':')[0] * 60 + +s.value.split(':')[1]);
    out.textContent = m > 0 ? 'That\'s ' + Math.floor(m / 60) + ' h ' + (m % 60) + ' min.' : 'The end time must be after the start time.';
  }
  s.addEventListener('input', upd); e.addEventListener('input', upd); upd();
})();
</script>
</body>
</html>
