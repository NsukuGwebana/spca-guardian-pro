<?php
// Copy this file to config.php and fill in your own values.
// config.php is listed in .gitignore so real passwords never reach GitHub.

// Database (on InfinityFree: Control Panel > MySQL Databases)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');          // e.g. sql123.infinityfree.com
define('DB_USER', getenv('DB_USER') ?: 'your_db_user');       // e.g. if0_12345678
define('DB_PASS', getenv('DB_PASS') ?: 'your_db_password');   // your hosting account password
define('DB_NAME', getenv('DB_NAME') ?: 'sysmasters');         // e.g. if0_12345678_sysmasters
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Email sending through Brevo (free: 300 emails a day). See INFINITYFREE.md.
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp-relay.brevo.com');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
define('MAIL_USER', getenv('MAIL_USER') !== false ? getenv('MAIL_USER') : 'your_brevo_smtp_login');      // Brevo > SMTP & API > SMTP tab, e.g. 9a1b2c001@smtp-brevo.com
define('MAIL_PASS', getenv('MAIL_PASS') ?: 'your_brevo_smtp_key');        // an SMTP key from the same page
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'your_verified_sender@example.com'); // a sender address verified in Brevo
define('MAIL_SECURE', getenv('MAIL_SECURE') ?: 'tls');   // 'none' only for a local mail catcher
