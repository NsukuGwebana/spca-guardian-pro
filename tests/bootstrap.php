<?php
// Test bootstrap: loads the pure libraries. Integration tests load the app bootstrap themselves.
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/../lib/validation.php';
require_once __DIR__ . '/../lib/workflow.php';
require_once __DIR__ . '/../lib/listing.php';
