<?php
// Adoption pipeline: Pending -> Home visit -> Approved -> Adopted (or Rejected).
// Every move is checked against the rules in lib/workflow.php, emails the applicant,
// keeps the animal's status in step and is written to the activity log.
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ---------- Move an application to its next step ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $to   = (string)($_POST['to'] ?? '');
    $note = mb_substr(trim($_POST['note'] ?? ''), 0, 255);
    $visit = trim($_POST['visit_at'] ?? '');
    $back = 'admin_adoptions.php?status=' . urlencode($_POST['tab'] ?? 'Pending');

    $app = q("SELECT a.id, a.status, a.full_name, a.email, a.animal_id, ani.Aname
              FROM adoptions a JOIN animals ani ON ani.id = a.animal_id WHERE a.id = ?", [$id])->fetch_assoc();

    $problem = null;
    if (!$app)                                        $problem = "That application no longer exists.";
    elseif (!adoption_can_move($app['status'], $to))  $problem = "An application that is '{$app['status']}' can't move to '$to'.";
    elseif ($to === 'Home visit' && (strtotime($visit) === false || strtotime($visit) < time() - 3600))
                                                      $problem = "Please choose a date and time for the home visit (not in the past).";
    elseif ($to === 'Adopted' && empty($_POST['id_checked']))
                                                      $problem = "Tick the box to confirm you checked the adopter's ID document before handover.";

    if ($problem) {
        $_SESSION['flash'] = ['bad', $problem];
        header("Location: $back");
        exit;
    }

    $visitAt = $to === 'Home visit' ? date('Y-m-d H:i:s', strtotime($visit)) : null;
    $conn = db();
    $conn->begin_transaction();
    try {
        q("UPDATE adoptions SET status = ?, status_updated_at = NOW(), staff_note = ?,
                  home_visit_at = COALESCE(?, home_visit_at)
           WHERE id = ?", [$to, $note, $visitAt, $id]);
        q("UPDATE animals SET status = ? WHERE id = ?", [animal_status_for($to), (int)$app['animal_id']]);
        if ($to === 'Adopted') {
            // Free the kennel the animal was in.
            q("UPDATE kennels k JOIN animal_kennel ak ON ak.kennel_id = k.kennel_id
               SET k.status = 'Available' WHERE ak.animal_id = ? AND ak.release_date IS NULL", [(int)$app['animal_id']]);
            q("UPDATE animal_kennel SET release_date = NOW() WHERE animal_id = ? AND release_date IS NULL", [(int)$app['animal_id']]);
        }
        $conn->commit();
    } catch (Throwable $ex) {
        $conn->rollback();
        throw $ex; // bootstrap shows the friendly error page and logs it
    }

    [$subject, $body] = adoption_email($to, $app['full_name'], $app['Aname'], $visitAt, $note);
    $sent = send_mail((string)$app['email'], $subject, $body);
    log_activity("Adoption moved to $to", "Application #$id ({$app['Aname']}, {$app['full_name']})" . ($sent ? ', applicant emailed' : ''));

    $msg = "{$app['full_name']}'s application for {$app['Aname']} is now '$to'.";
    $msg .= $sent ? ' The applicant has been emailed.'
                  : ($app['email'] ? ' The email could not be sent; please phone the applicant.' : ' No email on file; please phone the applicant.');
    $_SESSION['flash'] = [$sent || !$app['email'] ? 'ok' : 'bad', $msg];
    header("Location: $back");
    exit;
}

/* ---------- List ---------- */
$statuses = array_keys(ADOPTION_FLOW);
$tab = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : 'Pending';
$search = trim($_GET['q'] ?? '');

$counts = array_fill_keys($statuses, 0);
$r = q("SELECT status, COUNT(*) n FROM adoptions GROUP BY status");
while ($c = $r->fetch_assoc()) if (isset($counts[$c['status']])) $counts[$c['status']] = (int)$c['n'];

$where = "a.status = ?";
$params = [$tab];
if ($search !== '') {
    $where .= " AND (a.full_name LIKE ? OR a.id_number LIKE ? OR a.email LIKE ? OR ani.Aname LIKE ?)";
    array_push($params, ...array_fill(0, 4, "%$search%"));
}
$base = "FROM adoptions a JOIN animals ani ON ani.id = a.animal_id WHERE $where";

if (($_GET['export'] ?? '') === 'csv') {
    $rows = q("SELECT a.id, ani.Aname, ani.Species, a.full_name, a.id_number, a.phone, a.email,
                      CONCAT_WS(', ', a.address_street, a.address_city, a.address_province) AS address,
                      a.housing_type, a.status, a.created_at, a.home_visit_at, a.status_updated_at, a.staff_note
               $base ORDER BY a.created_at DESC", $params)->fetch_all(MYSQLI_ASSOC);
    log_activity('Exported adoptions CSV', "$tab, " . count($rows) . ' rows');
    send_csv("adoptions_" . strtolower(str_replace(' ', '_', $tab)) . '_' . date('Y-m-d') . '.csv',
        ['Application', 'Animal', 'Species', 'Applicant', 'ID number', 'Phone', 'Email', 'Address',
         'Housing', 'Status', 'Applied', 'Home visit', 'Last update', 'Staff note'], $rows);
}

$total = (int)q("SELECT COUNT(*) n $base", $params)->fetch_assoc()['n'];
[$page, $pages, $offset] = paginate($total, (int)($_GET['page'] ?? 1), 15);
$apps = q("SELECT a.*, ani.Aname, ani.Species, ani.Animal_breed, ani.animal_picture,
                  DATEDIFF(NOW(), COALESCE(a.status_updated_at, a.created_at)) AS days_open
           $base ORDER BY a.created_at ASC LIMIT 15 OFFSET $offset", $params)->fetch_all(MYSQLI_ASSOC);

$next = ADOPTION_FLOW[$tab];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Adoption Pipeline | SPCA Guardian Pro</title>
<link rel="stylesheet" href="spca-theme.css">
<style>
  .app-card { background: var(--spca-card); border: 1px solid var(--spca-line); border-radius: var(--spca-radius); padding: 16px; display: grid; grid-template-columns: 84px 1fr; gap: 14px; margin-bottom: 12px; }
  .app-card img { width: 84px; height: 84px; object-fit: cover; border-radius: 10px; background: var(--spca-teal-soft); }
  .app-card h3 { margin: 0; font-size: 1.05rem; color: var(--spca-ink); }
  .app-card .meta { color: var(--spca-muted); font-size: .85rem; margin: 2px 0 8px; }
  .facts span { overflow-wrap: anywhere; }
  .facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 4px 16px; font-size: .88rem; }
  .facts b { color: var(--spca-muted); font-weight: 600; }
  .reason { font-size: .88rem; margin: 8px 0 0; color: var(--spca-ink); }
  .moves { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--spca-line); align-items: flex-end; }
  .moves form { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; margin: 0; }
  body.spca .moves input[type=text], body.spca .moves input[type=datetime-local] { width: auto; margin: 0; padding: 7px 9px; border: 1px solid var(--spca-line); border-radius: 8px; font-size: .85rem; }
  body.spca .moves label { font-size: .78rem; color: var(--spca-muted); display: flex; flex-direction: column; gap: 3px; margin: 0; font-weight: 600; }
  body.spca .moves label.check { flex-direction: row; align-items: center; gap: 6px; color: var(--spca-ink); }
  body.spca .moves .go { padding: 8px 14px; border: 0; border-radius: 8px; font-weight: 600; cursor: pointer; color: #fff; background: var(--spca-teal); }
  body.spca .moves .go.reject { background: var(--spca-bad); }
  body.spca .moves .go.adopt { background: var(--spca-good); }
  .late { color: var(--spca-bad); font-weight: 600; }
  .flowline { font-size: .85rem; color: var(--spca-muted); }
  @media (max-width: 520px) { .app-card { grid-template-columns: 1fr; } }
</style>
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<main class="page-wrap">
  <div class="page-head">
    <div>
      <h1>Adoption pipeline</h1>
      <p class="flowline">Pending &rarr; Home visit &rarr; Approved &rarr; Adopted. The applicant is emailed at every step.</p>
    </div>
    <a class="tool-btn light" href="?<?= e(http_build_query(['status' => $tab, 'q' => $search, 'export' => 'csv'])) ?>">Download CSV</a>
  </div>

  <?php if ($flash): ?><div class="flash <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>

  <nav class="tabs" aria-label="Application status">
    <?php foreach ($statuses as $s): ?>
      <a class="<?= $s === $tab ? 'on' : '' ?>" href="?status=<?= urlencode($s) ?>"><?= e($s) ?><b><?= $counts[$s] ?></b></a>
    <?php endforeach; ?>
  </nav>

  <form class="filter-bar" method="get">
    <input type="hidden" name="status" value="<?= e($tab) ?>">
    <label class="grow">Search applicant, ID number, email or animal
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="e.g. Thandi or 9001015009087">
    </label>
    <button type="submit">Search</button>
    <?php if ($search !== ''): ?><a class="tool-btn light" href="?status=<?= urlencode($tab) ?>">Clear</a><?php endif; ?>
  </form>

  <div class="list-meta"><span><?= $total ?> application<?= $total === 1 ? '' : 's' ?> <?= $search !== '' ? 'match' : 'in' ?> “<?= e($tab) ?>”</span><span>Oldest first</span></div>

  <?php if (!$apps): ?>
    <p class="kpi">Nothing here right now.</p>
  <?php endif; ?>

  <?php foreach ($apps as $a):
      $img = $a['animal_picture'] ?: 'Images/no-photo.svg'; ?>
    <article class="app-card">
      <img src="<?= e($img) ?>" alt="" onerror="this.src='Images/no-photo.svg'">
      <div>
        <h3><?= e($a['full_name']) ?> &rarr; <?= e($a['Aname']) ?> <span class="pill <?= e(str_replace(' ', '-', $a['status'])) ?>"><?= e($a['status']) ?></span></h3>
        <div class="meta">
          <?= e($a['Species']) ?> · <?= e($a['Animal_breed']) ?> · applied <?= e(date('j M Y', strtotime($a['created_at']))) ?>
          <?php if (in_array($a['status'], ['Pending', 'Home visit', 'Approved'], true)): ?>
            · <span class="<?= $a['days_open'] > 5 ? 'late' : '' ?>"><?= (int)$a['days_open'] ?> days at this step</span>
          <?php endif; ?>
        </div>
        <div class="facts">
          <span><b>ID number:</b> <?= e($a['id_number']) ?></span>
          <span><b>Phone:</b> <?= e($a['phone']) ?></span>
          <span><b>Email:</b> <?= e($a['email'] ?: '—') ?></span>
          <span><b>Home:</b> <?= e($a['housing_type']) ?><?= $a['has_fenced_yard'] ? ', fenced yard' : '' ?></span>
          <span><b>Address:</b> <?= e(implode(', ', array_filter([$a['address_street'], $a['address_city']]))) ?></span>
          <span><b>Other pets:</b> <?= $a['has_other_pets'] ? e($a['other_pets_details'] ?: 'Yes') : 'No' ?></span>
          <?php if ($a['home_visit_at']): ?><span><b>Home visit:</b> <?= e(date('j M Y H:i', strtotime($a['home_visit_at']))) ?></span><?php endif; ?>
          <?php if ($a['id_copy']): ?><span><b>ID copy:</b> <a href="<?= e($a['id_copy']) ?>" target="_blank" rel="noopener">Open</a></span><?php endif; ?>
        </div>
        <?php if ($a['reason']): ?><p class="reason"><b>Why they want to adopt:</b> <?= e($a['reason']) ?></p><?php endif; ?>
        <?php if ($a['staff_note']): ?><p class="reason"><b>Last note:</b> <?= e($a['staff_note']) ?></p><?php endif; ?>

        <?php if ($next): ?>
        <div class="moves">
          <?php foreach ($next as $to): if ($to === 'Rejected') continue; ?>
            <form method="post">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <input type="hidden" name="to" value="<?= e($to) ?>">
              <input type="hidden" name="tab" value="<?= e($tab) ?>">
              <?php if ($to === 'Home visit'): ?>
                <label>Visit date and time<input type="datetime-local" name="visit_at" required min="<?= date('Y-m-d\TH:i') ?>"></label>
              <?php endif; ?>
              <?php if ($to === 'Adopted'): ?>
                <label class="check"><input type="checkbox" name="id_checked" value="1" required> I checked their ID document (<?= e($a['id_number']) ?>)</label>
              <?php endif; ?>
              <label>Note for the applicant (optional)<input type="text" name="note" maxlength="255"></label>
              <button class="go <?= $to === 'Adopted' ? 'adopt' : '' ?>" type="submit">
                <?= ['Home visit' => 'Book home visit', 'Approved' => 'Approve', 'Adopted' => 'Hand over: mark adopted'][$to] ?>
              </button>
            </form>
          <?php endforeach; ?>
          <form method="post" onsubmit="return confirmReject(this)">
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <input type="hidden" name="to" value="Rejected">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <label>Reason (sent to applicant)<input type="text" name="note" maxlength="255"></label>
            <button class="go reject" type="submit">Reject</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>

  <?= pager_html($page, $pages, ['status' => $tab, 'q' => $search]) ?>

  <div class="back-row"><a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a></div>
</main>
<script>
// Ask once before rejecting, using a second click instead of a browser dialog.
function confirmReject(form) {
  const b = form.querySelector('button');
  if (b.dataset.armed) return true;
  b.dataset.armed = 1; b.textContent = 'Click again to reject';
  setTimeout(() => { delete b.dataset.armed; b.textContent = 'Reject'; }, 4000);
  return false;
}
</script>
</body>
</html>
