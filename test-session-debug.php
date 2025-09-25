<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Start the session
session_start();

echo "Session Debug\n";
echo "=============\n";

// Check various session variables
$sessionKeys = ['jwt_token', 'frontend_token', 'authenticated', 'user_data'];

foreach ($sessionKeys as $key) {
    if (isset($_SESSION[$key])) {
        echo "$key: " . (is_array($_SESSION[$key]) ? json_encode($_SESSION[$key]) : $_SESSION[$key]) . "\n";
    } else {
        echo "$key: NOT SET\n";
    }
}

echo "\nAll session keys:\n";
print_r(array_keys($_SESSION ?? []));