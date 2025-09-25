<?php

echo "=== BellGas Laravel - Complete MySQL Setup ===\n";

try {
    $mysql = new PDO('mysql:host=127.0.0.1;port=3306;dbname=bellgas_laravel', 'root', '');
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ MySQL connection established\n";

    // Check current tables
    $stmt = $mysql->query("SHOW TABLES");
    $currentTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current tables: " . count($currentTables) . " (" . implode(', ', $currentTables) . ")\n";

    // Create essential tables if missing
    $essentialTables = [
        'addresses' => "CREATE TABLE addresses (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            street_address varchar(255) NOT NULL,
            suburb varchar(255) NOT NULL,
            state varchar(255) NOT NULL,
            postcode varchar(255) NOT NULL,
            country varchar(255) DEFAULT 'Australia',
            is_primary tinyint(1) DEFAULT 0,
            created_at timestamp NULL DEFAULT NULL,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY addresses_user_id_foreign (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'products' => "CREATE TABLE products (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            category_id bigint unsigned DEFAULT NULL,
            image_url varchar(255) DEFAULT NULL,
            created_at timestamp NULL DEFAULT NULL,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY products_slug_unique (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'orders' => "CREATE TABLE orders (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            order_number varchar(255) NOT NULL,
            user_id bigint unsigned NOT NULL,
            address_id bigint unsigned DEFAULT NULL,
            status enum('PENDING','PAID','PROCESSED','WAITING_PICKUP','ON_DELIVERY','DONE','CANCELLED') DEFAULT 'PENDING',
            fulfillment_method enum('PICKUP','DELIVERY') NOT NULL,
            subtotal_aud decimal(10,2) NOT NULL,
            shipping_cost_aud decimal(10,2) DEFAULT 0.00,
            total_aud decimal(10,2) NOT NULL,
            stripe_payment_intent_id varchar(255) DEFAULT NULL,
            customer_notes text,
            pickup_ready_at timestamp NULL DEFAULT NULL,
            delivered_at timestamp NULL DEFAULT NULL,
            completed_at timestamp NULL DEFAULT NULL,
            created_at timestamp NULL DEFAULT NULL,
            updated_at timestamp NULL DEFAULT NULL,
            payment_method varchar(255) DEFAULT NULL,
            stripe_session_id varchar(255) DEFAULT NULL,
            payment_status varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY orders_order_number_unique (order_number),
            KEY orders_user_id_foreign (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    echo "\nCreating essential tables...\n";
    foreach ($essentialTables as $tableName => $createSQL) {
        if (!in_array($tableName, $currentTables)) {
            echo "Creating $tableName... ";
            try {
                $mysql->exec($createSQL);
                echo "✓\n";
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "$tableName already exists ✓\n";
        }
    }

    // Create other tables quickly
    $otherTables = [
        'order_items' => "CREATE TABLE order_items (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint unsigned NOT NULL,
            product_variant_id bigint unsigned NOT NULL,
            quantity int NOT NULL,
            unit_price_aud decimal(8,2) NOT NULL,
            total_price_aud decimal(8,2) NOT NULL,
            created_at timestamp NULL DEFAULT NULL,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'product_variants' => "CREATE TABLE product_variants (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint unsigned NOT NULL,
            name varchar(255) NOT NULL,
            price_aud decimal(8,2) NOT NULL,
            stock_quantity int DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at timestamp NULL DEFAULT NULL,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($otherTables as $tableName => $createSQL) {
        if (!in_array($tableName, $currentTables)) {
            echo "Creating $tableName... ";
            try {
                $mysql->exec($createSQL);
                echo "✓\n";
            } catch (Exception $e) {
                echo "✗ Skip: " . substr($e->getMessage(), 0, 50) . "...\n";
            }
        }
    }

    echo "\n✓ MySQL setup completed!\n";
    echo "Ready for data migration from SQLite\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}