<?php
session_start();

$timeout = 900; // 15 minutes

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: loginFormSysdev.html");
    exit();
}

$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['access']) || $_SESSION['access'] !== "yes") {
    header("Location: loginFormSysdev.html");
    exit();
}
?>
