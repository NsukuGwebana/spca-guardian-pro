<?php
// Login handler. All PHP logic runs before any HTML is sent,
// so the role-based redirects (header("Location: ...")) always work.
require_once __DIR__ . '/databaseConnection.php';

const MAX_FAILS_PER_USER = 5;   // wrong passwords allowed per username...
const MAX_FAILS_PER_IP   = 20;  // ...and per computer
const LOCK_MINUTES       = 15;  // ...within this many minutes

$message = "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Newlogin.html");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['userpassword'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? '';

if ($username === '' || $password === '') {
    $message = "Please enter your username and password.";
} else {
    // Slow down password guessing: too many recent failures locks the login for a while
    $fails = q("SELECT
                  COALESCE(SUM(username = ?), 0) AS user_fails,
                  COUNT(*)                       AS ip_fails
                FROM login_attempts
                WHERE success = 0
                  AND attempted_at > (NOW() - INTERVAL " . LOCK_MINUTES . " MINUTE)
                  AND (username = ? OR ip_address = ?)",
               [$username, $username, $ip])->fetch_assoc();

    if ((int)$fails['user_fails'] >= MAX_FAILS_PER_USER || (int)$fails['ip_fails'] >= MAX_FAILS_PER_IP) {
        $message = "Too many wrong attempts. For your security, please wait " . LOCK_MINUTES . " minutes and try again, or reset your password.";
        log_error("Login locked for '$username' from $ip");
    } else {
        $row = q("SELECT * FROM users WHERE username = ?", [$username])->fetch_assoc();
        $ok  = $row && password_verify($password, $row['userpassword']);

        q("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)", [$username, $ip, $ok ? 1 : 0]);

        if ($ok) {
            session_regenerate_id(true); // prevents session fixation

            $_SESSION['access']        = "yes";
            $_SESSION['username']      = $row['username'];
            $_SESSION['userrole']      = $row['userrole'];
            $_SESSION['firstname']     = $row['firstname'];
            $_SESSION['surname']       = $row['surname'];
            $_SESSION['idnumber']      = $row['idnumber'];
            $_SESSION['LAST_ACTIVITY'] = time();
            unset($_SESSION['csrf_token']); // a fresh form token for the new session

            log_activity('Signed in');

            switch ($row['userrole']) {
                case 'Administrator': header("Location: adminDashboard.php"); exit();
                case 'Vet':           header("Location: vetDashboard.php");    exit();
                case 'Volunteer':     header("Location: volunteerDashboard.php");               exit();
                default:
                    $message = "Your account doesn't have a role yet. Please contact the administrator.";
            }
        } else {
            // Same message whether the username or the password was wrong, so usernames can't be guessed
            $message = "Login failed. Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | SPCA Guardian Pro</title>
    <link rel="stylesheet" href="spca-theme.css">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 16px; box-sizing: border-box; }
        .card { background: #fff; border-radius: 16px; padding: 28px; max-width: 420px; box-shadow: 0 10px 30px rgba(12,40,46,.12); }
        .row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
    </style>
</head>
<body class="spca">
    <div class="card">
        <h2>Sign in</h2>
        <p class="error"><?= e($message) ?></p>
        <div class="row">
            <a href="Newlogin.html" class="back-btn">&larr; Back to login</a>
            <a href="ForgotPasswordform.html" class="back-btn">Forgot password?</a>
        </div>
    </div>
</body>
</html>
