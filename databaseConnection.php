<?php
// Shared database connection. Every page that includes this gets $conn,
// plus the error handling and security set up in bootstrap.php.
require_once __DIR__ . '/bootstrap.php';

$serverName = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$database   = DB_NAME;
$conn       = db();
