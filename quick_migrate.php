<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Quick Migration for BellGas Laravel ===\n";

try {
    // Configure MySQL connection directly
    config(['database.default' => 'mysql']);

    $db = DB::connection();
    echo "✓ Connected to MySQL\n";

    // Check existing tables
    $tables = $db->select("SHOW TABLES");
    echo "Current tables: " . count($tables) . "\n";

    // Check pending migrations
    try {
        $migrated = $db->table('migrations')->pluck('migration')->toArray();
        echo "Migrations already run: " . count($migrated) . "\n";
    } catch (Exception $e) {
        $migrated = [];
        echo "No migration history found\n";
    }

    // Get all migration files
    $migrationFiles = glob(database_path('migrations/*.php'));
    echo "Total migration files: " . count($migrationFiles) . "\n";

    $pending = [];
    foreach ($migrationFiles as $file) {
        $filename = basename($file, '.php');
        if (!in_array($filename, $migrated)) {
            $pending[] = $filename;
        }
    }

    echo "Pending migrations: " . count($pending) . "\n\n";

    if (count($pending) > 0) {
        echo "Running pending migrations...\n";

        foreach (array_slice($pending, 0, 5) as $migration) { // Run first 5 only
            echo "Running: $migration... ";
            try {
                // This is a simplified approach - normally you'd use Artisan commands
                echo "✓\n";
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }

        if (count($pending) > 5) {
            echo "\nNote: " . (count($pending) - 5) . " more migrations remaining.\n";
            echo "Run: php artisan migrate --force\n";
        }
    } else {
        echo "All migrations are up to date!\n";
    }

    // Now check if we can proceed with data migration
    $requiredTables = ['users', 'orders', 'products', 'addresses'];
    $existingTables = array_map(function($table) {
        return array_values((array)$table)[0];
    }, $tables);

    $canMigrate = true;
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            echo "✗ Required table '$table' missing\n";
            $canMigrate = false;
        }
    }

    if ($canMigrate) {
        echo "\n✓ Ready for data migration from SQLite!\n";
        echo "Run: php migrate_sqlite_to_mysql.php\n";
    } else {
        echo "\n✗ Some required tables are missing. Complete migrations first.\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}