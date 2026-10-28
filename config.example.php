<?php
/**
 * Configuration template.
 *
 * Copy this file to config.php and fill in your own values.
 * config.php is listed in .gitignore, so your real passwords never reach GitHub.
 */

// MySQL database
define('DB_HOST', 'localhost');        // e.g. localhost when running on XAMPP
define('DB_USER', 'root');             // your MySQL username
define('DB_PASS', '');                 // your MySQL password
define('DB_NAME', 'sysmasters');       // database name (import database/sysmasters.sql)

// Gmail SMTP for system emails (adoption acknowledgements, receipts, messages)
// Use a Gmail App Password, never your normal password:
// https://support.google.com/accounts/answer/185833
define('SMTP_USER', 'your.address@gmail.com');
define('SMTP_PASS', 'your-16-character-app-password');
