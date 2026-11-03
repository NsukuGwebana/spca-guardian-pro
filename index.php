<?php
require_once __DIR__ . '/bootstrap.php';
// Send visitors who open the site root to the homepage
header("Location: Homepage.html");
exit;
