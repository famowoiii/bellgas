<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SQLite to MySQL Migration Tool ===\n";
echo "This script will migrate all data from SQLite to MySQL\n\n";

try {
    // Configure connections
    config([
        'database.connections.sqlite_source' => [
            'driver' => 'sqlite',
            'database' => database_path('database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.connections.mysql_target' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'bellgas_laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]
    ]);

    // Test connections
    echo "Testing SQLite connection... ";
    $sqliteConnection = DB::connection('sqlite_source');
    $sqliteConnection->getPdo();
    echo "✓ Connected\n";

    echo "Testing MySQL connection... ";
    $mysqlConnection = DB::connection('mysql_target');
    $mysqlConnection->getPdo();
    echo "✓ Connected\n\n";

    // Get all tables from SQLite (excluding system tables)
    $tables = $sqliteConnection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

    echo "Found " . count($tables) . " tables to migrate:\n";

    // Define table order for migration (to handle foreign key constraints)
    $migrationOrder = [
        'migrations', 'users', 'addresses', 'categories', 'products', 'product_variants',
        'product_photos', 'permissions', 'roles', 'model_has_permissions', 'model_has_roles',
        'role_has_permissions', 'orders', 'order_items', 'order_events', 'stock_reservations',
        'payment_events', 'pickup_tokens', 'carts', 'delivery_settings', 'payment_settings',
        'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens'
    ];

    $allTables = array_map(function($table) { return $table->name; }, $tables);

    // Add any tables not in our predefined order
    foreach ($allTables as $tableName) {
        if (!in_array($tableName, $migrationOrder)) {
            $migrationOrder[] = $tableName;
        }
    }

    // Filter to only include existing tables
    $tablesToMigrate = array_intersect($migrationOrder, $allTables);

    echo "Migration order: " . implode(', ', $tablesToMigrate) . "\n\n";

    // Disable foreign key checks temporarily
    $mysqlConnection->statement('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tablesToMigrate as $tableName) {
        echo "Migrating table: {$tableName}... ";

        try {
            // Get all data from SQLite
            $data = $sqliteConnection->table($tableName)->get();
            $recordCount = count($data);

            if ($recordCount > 0) {
                // Clear existing data in MySQL table
                $mysqlConnection->table($tableName)->truncate();

                // Insert data in chunks to avoid memory issues
                $chunkSize = 100;
                $chunks = $data->chunk($chunkSize);

                foreach ($chunks as $chunk) {
                    $insertData = [];
                    foreach ($chunk as $row) {
                        $insertData[] = (array) $row;
                    }

                    if (!empty($insertData)) {
                        $mysqlConnection->table($tableName)->insert($insertData);
                    }
                }
            }

            echo "✓ {$recordCount} records\n";

        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    // Re-enable foreign key checks
    $mysqlConnection->statement('SET FOREIGN_KEY_CHECKS = 1');

    echo "\n=== Migration Summary ===\n";
    echo "Verifying data migration...\n";

    foreach ($tablesToMigrate as $tableName) {
        $sqliteCount = $sqliteConnection->table($tableName)->count();
        $mysqlCount = $mysqlConnection->table($tableName)->count();

        $status = ($sqliteCount === $mysqlCount) ? '✓' : '✗';
        echo "{$status} {$tableName}: SQLite={$sqliteCount}, MySQL={$mysqlCount}\n";
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "You can now use MySQL as your primary database.\n";
    echo "Remember to backup your SQLite file before removing it.\n";

} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}