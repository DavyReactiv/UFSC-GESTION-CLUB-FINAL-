<?php
// Simple WP-CLI check to ensure frontend-pro.js exists.
$file = dirname(__DIR__, 2) . '/assets/js/frontend-pro.js';
if (!file_exists($file)) {
    fwrite(STDERR, "Missing asset: {$file}\n");
    exit(1);
}
// Output confirmation for CI visibility.
fwrite(STDOUT, "Found asset: {$file}\n");

