<?php

echo "Creating MySQL database for BellGas Laravel...\n";

try {
    // Connect to MySQL without specifying database
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Connected to MySQL server\n";

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS bellgas_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);

    echo "✓ Database 'bellgas_laravel' created successfully\n";

    // Verify database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'bellgas_laravel'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database verified - ready for Laravel migrations\n";
    }

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nPossible solutions:\n";
    echo "1. Make sure XAMPP MySQL is running\n";
    echo "2. Check if port 3306 is open\n";
    echo "3. Try accessing http://localhost/phpmyadmin\n";
}