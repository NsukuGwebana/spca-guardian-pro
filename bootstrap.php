<?php
/**
 * bootstrap.php
 * Loaded first by every PHP page (through config.php users, databaseConnection.php and Session_handler.php).
 * It gives the whole system one consistent way to:
 *   - read settings (config.php)
 *   - handle errors: log the details, show visitors a friendly message, never show SQL errors
 *   - start a secure session
 *   - protect forms against cross-site request forgery (CSRF)
 *   - connect to the database (db())
 *   - escape output (e()), record staff activity (log_activity()) and send email (send_mail())
 */

if (defined('SPCA_BOOTSTRAPPED')) {
    return;
}
define('SPCA_BOOTSTRAPPED', true);

// config.php holds the real settings; without it (Docker, CI) the example file reads them from environment variables.
require_once is_file(__DIR__ . '/config.php') ? __DIR__ . '/config.php' : __DIR__ . '/config.example.php';
require_once __DIR__ . '/lib/validation.php';
require_once __DIR__ . '/lib/workflow.php';
require_once __DIR__ . '/lib/listing.php';

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false); // set to true in config.php on your own computer to see full errors
}
date_default_timezone_set('Africa/Johannesburg');

/* ------------------------------------------------------------------ *
 * 1. Error handling
 * ------------------------------------------------------------------ */

define('SPCA_LOG_DIR', __DIR__ . '/logs');

/** Write one line to logs/error.log (the folder is blocked from the web by logs/.htaccess). */
function log_error(string $message): void
{
    if (!is_dir(SPCA_LOG_DIR)) {
        @mkdir(SPCA_LOG_DIR, 0750, true);
        @file_put_contents(SPCA_LOG_DIR . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    $line = sprintf("[%s] %s %s | %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        $_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''),
        $message
    );
    @error_log($line, 3, SPCA_LOG_DIR . '/error.log');
}

/** True when the browser asked for JSON (charts, notifications) rather than a page. */
function wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return stripos($accept, 'application/json') !== false
        || strcasecmp($xhr, 'XMLHttpRequest') === 0
        || str_starts_with($script, 'get_') || str_starts_with($script, 'get');
}

/** Show a friendly error page (or JSON) and stop. The real reason is in the log, never on screen. */
function show_error_page(int $status, string $title, string $message): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
    }
    if (wants_json()) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['error' => $message]);
        exit;
    }
    $t = htmlspecialchars($title, ENT_QUOTES);
    $m = htmlspecialchars($message, ENT_QUOTES);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t} | SPCA Guardian Pro</title><link rel="stylesheet" href="spca-theme.css">
<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box}
.card{background:#fff;border-radius:16px;padding:28px;max-width:440px;box-shadow:0 10px 30px rgba(12,40,46,.12)}
.card p{color:#5b6e72;margin:6px 0 20px;line-height:1.5}.row{display:flex;gap:10px;flex-wrap:wrap}</style></head>
<body class="spca"><div class="card"><h2>{$t}</h2><p>{$m}</p>
<div class="row"><a class="back-btn" href="javascript:history.back()">&larr; Go back</a><a class="back-btn" href="Homepage.html">Home</a></div></div></body></html>
HTML;
    exit;
}

ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '0'); // we log ourselves
error_reporting(E_ALL);

// Notices and warnings: log them and carry on (deprecations only in debug mode).
set_error_handler(function (int $no, string $msg, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    if (($no & (E_DEPRECATED | E_USER_DEPRECATED)) && !APP_DEBUG) {
        return true;
    }
    log_error("PHP warning [$no] $msg in " . basename($file) . ":$line");
    return !APP_DEBUG; // in debug mode PHP still prints it
});

// Anything thrown and not caught (database errors included) ends here.
set_exception_handler(function (Throwable $e): void {
    log_error(get_class($e) . ': ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    if (APP_DEBUG) {
        echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
        exit;
    }
    if ($e instanceof mysqli_sql_exception) {
        show_error_page(500, "We couldn't reach the database",
            "Something went wrong while loading or saving this information. Please try again in a moment. If it keeps happening, contact the SPCA office.");
    }
    show_error_page(500, 'Something went wrong',
        "Sorry, this page ran into a problem. Please try again. If it keeps happening, contact the SPCA office.");
});

// Fatal errors that skip the exception handler.
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        log_error("Fatal: {$err['message']} in " . basename($err['file']) . ":{$err['line']}");
        if (!APP_DEBUG) {
            show_error_page(500, 'Something went wrong', 'Sorry, this page ran into a problem. Please try again.');
        }
    }
});

// Database errors throw exceptions, so they can't fail silently.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ------------------------------------------------------------------ *
 * 2. Session (secure cookie settings)
 * ------------------------------------------------------------------ */

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,   // JavaScript can't read the session cookie
        'samesite' => 'Lax',  // other sites can't send it with their forms
    ]);
    session_start();
}

/* ------------------------------------------------------------------ *
 * 3. Small helpers used everywhere
 * ------------------------------------------------------------------ */

/** Escape text for HTML output. Use for anything that came from a user or the database. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** The shared database connection. */
function db(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

/** Run a prepared query: q("SELECT * FROM animals WHERE id = ?", [5]). Returns mysqli_result or true. */
function q(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    return $res === false ? true : $res;
}

/** Record who did what, for the activity log page. Never stops the page if logging fails. */
function log_activity(string $action, string $details = ''): void
{
    try {
        $user = $_SESSION['username'] ?? 'public';
        $role = $_SESSION['userrole'] ?? '';
        q("INSERT INTO activity_log (username, user_role, action, details, ip_address) VALUES (?, ?, ?, ?, ?)",
          [$user, $role, $action, mb_substr($details, 0, 500), $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Throwable $e) {
        log_error('Activity log failed: ' . $e->getMessage());
    }
}

/** Send an email through the SMTP service in config.php (Brevo). Returns true when it was sent. */
function send_mail(string $to, string $subject, string $html): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !defined('MAIL_HOST')) {
        return false;
    }
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->Port       = (int)MAIL_PORT;
        $mail->SMTPAuth   = MAIL_USER !== '';
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        if (defined('MAIL_SECURE') && MAIL_SECURE === 'none') {   // local mail catcher (Docker)
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, 'SPCA Guardian Pro');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '</p>'], "\n", $html)));
        $mail->send();
        return true;
    } catch (Throwable $e) {
        log_error('Email to ' . $to . ' failed: ' . $e->getMessage());
        return false;
    }
}

/* ------------------------------------------------------------------ *
 * 4. CSRF protection for every form
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** Does the request come from this website? (checks the Origin or Referer the browser sends) */
function same_origin_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $src  = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    if ($src === '' || $src === 'null') {
        return true; // some privacy settings remove both headers; the token check still applies to staff
    }
    return strcasecmp((string)parse_url($src, PHP_URL_HOST) . (parse_url($src, PHP_URL_PORT) ? ':' . parse_url($src, PHP_URL_PORT) : ''), $host) === 0
        || strcasecmp((string)parse_url($src, PHP_URL_HOST), explode(':', $host)[0]) === 0;
}

if (PHP_SAPI !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Every form must come from this website.
    if (!same_origin_request()) {
        log_error('Blocked cross-site form submission from ' . ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '?'));
        show_error_page(403, 'Form blocked', 'For your safety, this form must be sent from the SPCA Guardian Pro website.');
    }
}

/**
 * Staff pages only (called by Session_handler.php after the login check):
 * forms, AJAX calls and ?action= links must carry this session's secret token.
 * Public forms and the login form don't need it, so a leftover signed-in
 * session can never block someone from logging in or contacting the SPCA.
 */
function require_csrf_token(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'POST') {
        $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            log_error('Missing or wrong form token for ' . ($_SESSION['username'] ?? '?'));
            show_error_page(419, 'Your form expired', 'Please go back, refresh the page and try again.');
        }
    } elseif ($method === 'GET' && isset($_GET['action'])) {
        // Links that change data (approve, reject, archive...) use ?action=.
        $sent = $_GET['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            log_error('Action link without a valid token for ' . ($_SESSION['username'] ?? '?'));
            show_error_page(419, 'That link expired', 'Please go back, refresh the page and try again.');
        }
    }
}

// Create the token now, so it is saved with the session before the page is sent.
if (PHP_SAPI !== 'cli') {
    csrf_token();
}

// Add the token to every <form method="post"> on PHP pages automatically,
// and load csrf.js so forms and scripts on staff pages send it too.
if (PHP_SAPI !== 'cli' && !wants_json()) {
    ob_start(function (string $html): string {
        if (($_SESSION['access'] ?? '') === 'yes' && stripos($html, '</body>') !== false && stripos($html, 'csrf.js') === false) {
            $html = preg_replace('~</body>~i', '<script src="csrf.js"></script></body>', $html, 1);
        }
        // Add the token to action links (?action=approve&id=5).
        $tok = csrf_token();
        $html = preg_replace_callback('/(["\'])((?:(?!\1)[^<>\s])*?[?&](?:amp;)?action=(?:(?!\1)[^<>\s])*)\1/i', function ($m) use ($tok) {
            if (strpos($m[2], 'csrf_token=') !== false) { return $m[0]; }
            return $m[1] . $m[2] . '&amp;csrf_token=' . $tok . $m[1];
        }, $html);
        if (stripos($html, '<form') === false) {
            return $html;
        }
        $field = csrf_field();
        return preg_replace_callback('/<form\b[^>]*>/i', function ($m) use ($field) {
            return preg_match('/method\s*=\s*["\']?post/i', $m[0]) ? $m[0] . $field : $m[0];
        }, $html);
    });
}
