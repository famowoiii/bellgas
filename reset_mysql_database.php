<?php

echo "=== Reset MySQL Database for Fresh Migration ===\n";

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=bellgas_laravel', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Connected to MySQL\n";

    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found " . count($tables) . " tables to drop\n";

    // Drop all tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    foreach ($tables as $table) {
        echo "Dropping table: $table... ";
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "✓\n";
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n✓ Database reset completed!\n";
    echo "Now you can run: php artisan migrate\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}