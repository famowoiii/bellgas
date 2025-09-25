<?php

echo "=== XAMPP MySQL Configuration Check ===\n";

// Common XAMPP MySQL config locations
$configPaths = [
    'C:\xampp\mysql\bin\my.ini',
    'C:\xampp\mysql\data\my.ini',
    'C:\xampp\mysql\my.cnf',
    'C:\xampp\mysql\bin\my.cnf'
];

echo "Checking MySQL configuration files...\n";

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        echo "✓ Found config: $path\n";

        $content = file_get_contents($path);

        // Extract port info
        if (preg_match('/port\s*=\s*(\d+)/', $content, $matches)) {
            echo "  Port configured: {$matches[1]}\n";
        }

        // Extract bind-address
        if (preg_match('/bind-address\s*=\s*([^\s\n]+)/', $content, $matches)) {
            echo "  Bind address: {$matches[1]}\n";
        } else {
            echo "  Bind address: not specified (should bind to all)\n";
        }

        break;
    }
}

echo "\nChecking if MySQL process is running...\n";

// Check if MySQL is actually running
$output = [];
exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul', $output);

if (count($output) > 1) {
    echo "✓ MySQL process found:\n";
    foreach ($output as $line) {
        if (strpos($line, 'mysqld') !== false) {
            echo "  $line\n";
        }
    }
} else {
    echo "✗ MySQL process not found\n";
    echo "Try starting MySQL from XAMPP Control Panel\n";
}

echo "\nChecking network connections...\n";

// Check listening ports
$output = [];
exec('netstat -an | findstr :3306', $output);

if (!empty($output)) {
    echo "✓ Port 3306 connections:\n";
    foreach ($output as $line) {
        echo "  $line\n";
    }
} else {
    echo "✗ No connections on port 3306\n";

    // Check other common MySQL ports
    $ports = [3307, 3308, 3309];
    foreach ($ports as $port) {
        exec("netstat -an | findstr :$port", $portOutput);
        if (!empty($portOutput)) {
            echo "✓ Found MySQL on port $port:\n";
            foreach ($portOutput as $line) {
                echo "  $line\n";
            }
        }
    }
}

echo "\n=== Recommended Actions ===\n";
echo "1. Open XAMPP Control Panel as Administrator\n";
echo "2. Stop MySQL service\n";
echo "3. Click 'Config' button next to MySQL\n";
echo "4. Select 'my.ini' to check configuration\n";
echo "5. Start MySQL service again\n";
echo "6. Click 'Admin' to test phpMyAdmin access\n";