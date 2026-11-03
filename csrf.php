<?php
// Gives the signed-in user's form token to csrf.js (used by the static .html staff pages).
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['token' => (($_SESSION['access'] ?? '') === 'yes') ? csrf_token() : null]);
