<?php

echo "=== Testing MySQL/MariaDB Connections ===\n";

$hosts = ['127.0.0.1', 'localhost', '::1'];
$ports = [3306, 3307];

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        echo "Testing $host:$port... ";

        try {
            $pdo = new PDO("mysql:host=$host;port=$port", 'root', '', [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            echo "✓ SUCCESS!\n";
            echo "Connected successfully to $host:$port\n";

            // Test database creation
            echo "Testing database operations... ";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS bellgas_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ Database ready\n";

            // Update .env if needed
            if ($host !== '127.0.0.1' || $port !== 3306) {
                echo "UPDATE your .env file:\n";
                echo "DB_HOST=$host\n";
                echo "DB_PORT=$port\n";
            }

            exit(0); // Exit on first success

        } catch (Exception $e) {
            echo "✗ Failed: " . substr($e->getMessage(), 0, 50) . "...\n";
        }
    }
}

echo "\n❌ No working MySQL connection found.\n";
echo "\nTroubleshooting steps:\n";
echo "1. Open XAMPP Control Panel\n";
echo "2. Make sure MySQL shows 'Running' status\n";
echo "3. Click 'Admin' button for MySQL to open phpMyAdmin\n";
echo "4. If phpMyAdmin works, MySQL is running on different settings\n";
echo "5. Check XAMPP\\mysql\\bin\\my.ini for port configuration\n";