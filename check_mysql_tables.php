<?php

echo "Checking MySQL database status...\n";

try {
    // Connect to MySQL database
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=bellgas_laravel', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Connected to bellgas_laravel database\n\n";

    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables found: " . count($tables) . "\n";

    if (count($tables) > 0) {
        echo "Existing tables:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    } else {
        echo "No tables found - migrations need to be run\n";
    }

    // Check if migrations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
    if ($stmt->rowCount() > 0) {
        echo "\n✓ Migrations table exists\n";

        // Check migration status
        $stmt = $pdo->query("SELECT migration, batch FROM migrations ORDER BY batch, migration");
        $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Migrations run: " . count($migrations) . "\n";
        if (count($migrations) > 0) {
            echo "Last migration: " . end($migrations)['migration'] . "\n";
        }
    } else {
        echo "\n✗ Migrations table not found\n";
    }

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}