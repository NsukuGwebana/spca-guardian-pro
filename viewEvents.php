<?php
// Events and who has signed up. Administrators see volunteers' names; volunteers see the list and spots left.
$ALLOWED_ROLES = ['Administrator', 'Volunteer'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$isAdmin = ($_SESSION['userrole'] ?? '') === 'Administrator';
$when = ($_GET['when'] ?? '') === 'past' ? 'past' : 'upcoming';

$events = q("SELECT e.*, (SELECT COUNT(*) FROM event_signups s WHERE s.event_id = e.id) AS taken
             FROM events e WHERE " . ($when === 'past' ? 'e.event_date < CURDATE() ORDER BY e.event_date DESC' : 'e.event_date >= CURDATE() ORDER BY e.event_date, e.event_time')
           )->fetch_all(MYSQLI_ASSOC);

$names = [];
if ($isAdmin && $events) {
    $r = q("SELECT s.event_id, u.firstname, u.surname, u.phonenumber, u.email
            FROM event_signups s JOIN users u ON u.idnumber = s.volunteer_id
            ORDER BY s.signup_date");
    while ($x = $r->fetch_assoc()) $names[$x['event_id']][] = $x;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Events | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  .ev { background: var(--spca-card); border: 1px solid var(--spca-line); border-radius: var(--spca-radius); padding: 16px 18px; margin-bottom: 12px; color: var(--spca-ink); }
  .ev h3 { margin: 0; font-size: 1.05rem; }
  .ev .meta { color: var(--spca-muted); font-size: .88rem; margin: 2px 0 6px; }
  .ev details { margin-top: 8px; font-size: .9rem; }
  .ev summary { cursor: pointer; color: var(--spca-teal); font-weight: 600; }
  .ev ul { margin: 8px 0 0; padding-left: 20px; }
  .full { color: var(--spca-bad); font-weight: 600; }
</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="page-head">
    <div><h1>Events</h1><p><?= $isAdmin ? 'Volunteers sign up from their dashboard. Open an event to see who is coming.' : 'Sign up for events from your dashboard.' ?></p></div>
    <?php if ($isAdmin): ?><a class="tool-btn" href="Event.html">+ Create event</a><?php else: ?><a class="tool-btn" href="volunteerDashboard.php">Go to my dashboard</a><?php endif; ?>
  </div>
  <nav class="tabs">
    <a class="<?= $when === 'upcoming' ? 'on' : '' ?>" href="?when=upcoming">Upcoming</a>
    <a class="<?= $when === 'past' ? 'on' : '' ?>" href="?when=past">Past</a>
  </nav>
  <?php if (!$events): ?><p class="kpi">No <?= $when ?> events.</p><?php endif; ?>
  <?php foreach ($events as $ev): $left = (int)$ev['max_volunteers'] - (int)$ev['taken']; ?>
    <article class="ev">
      <h3><?= e($ev['event_name']) ?></h3>
      <div class="meta"><?= e(date('l j F Y', strtotime($ev['event_date']))) ?> · <?= e(substr($ev['event_time'], 0, 5)) ?> · <?= e($ev['location']) ?></div>
      <?php if ($ev['description']): ?><div><?= e($ev['description']) ?></div><?php endif; ?>
      <div class="meta" style="margin-top:6px"><?= (int)$ev['taken'] ?> of <?= (int)$ev['max_volunteers'] ?> volunteers signed up<?= $left <= 0 ? ' · <span class="full">Full</span>' : '' ?></div>
      <?php if ($isAdmin && !empty($names[$ev['id']])): ?>
        <details><summary>Who's coming (<?= count($names[$ev['id']]) ?>)</summary>
          <ul><?php foreach ($names[$ev['id']] as $p): ?><li><?= e($p['firstname'] . ' ' . $p['surname']) ?> · <?= e($p['phonenumber']) ?><?= $p['email'] ? ' · ' . e($p['email']) : '' ?></li><?php endforeach; ?></ul>
        </details>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  <div class="back-row"><a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a></div>
</main>
</body>
</html>
