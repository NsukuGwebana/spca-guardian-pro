<?php
require_once __DIR__ . '/bootstrap.php';
// Protects staff pages.
// 1. Sends visitors who aren't signed in to the login page.
// 2. Signs users out after 15 minutes without activity.
// 3. If the page sets $ALLOWED_ROLES before including this file, only those roles
//    (plus Administrators) may open it. Everyone else sees an "access denied" page.
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

$timeout = 900; // 15 minutes

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: Newlogin.html");
    exit();
}

$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['access']) || $_SESSION['access'] !== "yes") {
    header("Location: Newlogin.html");
    exit();
}

// Signed in: forms and action links on staff pages must carry the session's token (CSRF protection).
require_csrf_token();

if (isset($ALLOWED_ROLES) && is_array($ALLOWED_ROLES)) {
    $role = $_SESSION['userrole'] ?? '';
    if ($role !== 'Administrator' && !in_array($role, $ALLOWED_ROLES, true)) {
        http_response_code(403);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Access denied | SPCA Guardian Pro</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#eef3f4;font-family:Poppins,system-ui,-apple-system,"Segoe UI",sans-serif;color:#0c282e;padding:16px;box-sizing:border-box}
  .card{background:#fff;border-radius:16px;padding:28px;max-width:420px;box-shadow:0 10px 30px rgba(12,40,46,.12)}
  h1{margin:0 0 8px;font-size:1.3rem}
  p{margin:0 0 20px;color:#4f6468}
  a{display:inline-block;background:#134f5c;color:#fff;text-decoration:none;padding:11px 18px;border-radius:10px;font-weight:600}
</style>
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
  <div class="card">
    <h1>You don't have access to this page</h1>
    <p>This page is for <?= htmlspecialchars(implode(' and ', $ALLOWED_ROLES)) ?> staff. You're signed in as <?= htmlspecialchars($role) ?>.</p>
    <a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a>
  </div>
</body>
</html>
<?php
        exit();
    }
}
?>
