# MySQL Setup and Migration Guide

This guide will help you install MySQL and migrate your Laravel application from SQLite to MySQL.

## 🗃️ Current Database Status

Your SQLite database contains:
- **28 tables** with important data
- **12 users** (including admin@bellgas.com.au)
- **8 orders** with order history
- **3 products** (LPG Full Tank, LPG Gas Refill, etc.)
- **Roles and permissions** system
- **Complete application data**

## 🚀 Step 1: Install MySQL

### Option A: Using XAMPP (Recommended for Windows)
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP with MySQL component
3. Start MySQL service through XAMPP Control Panel
4. MySQL will run on `localhost:3306` with user `root` and no password

### Option B: Using MySQL Installer
1. Download MySQL Community Server from https://dev.mysql.com/downloads/mysql/
2. Follow installation wizard
3. Set root password (update .env file accordingly)
4. Start MySQL service

### Option C: Using Docker
```bash
docker run --name mysql-bellgas -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=bellgas_laravel -p 3306:3306 -d mysql:8.0
```

## 🔧 Step 2: Update Environment Configuration

Your `.env` file has been updated to:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bellgas_laravel
DB_USERNAME=root
DB_PASSWORD=
```

**If you set a MySQL root password, update `DB_PASSWORD` accordingly.**

## 🗄️ Step 3: Create MySQL Database

Connect to MySQL and create the database:

### Using MySQL Command Line:
```sql
CREATE DATABASE bellgas_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Using phpMyAdmin (if using XAMPP):
1. Open http://localhost/phpmyadmin
2. Click "New" to create database
3. Name: `bellgas_laravel`
4. Collation: `utf8mb4_unicode_ci`

## 🔄 Step 4: Run Laravel Migrations

```bash
cd D:\sopek\bellgas-laravel
php artisan config:clear
php artisan migrate
```

This will create all tables in MySQL with the correct structure.

## 📋 Step 5: Migrate Data from SQLite

Run the migration script to transfer all your data:

```bash
php migrate_sqlite_to_mysql.php
```

This script will:
- ✅ Test both database connections
- ✅ Migrate all 28 tables in the correct order
- ✅ Handle foreign key constraints properly
- ✅ Verify data integrity after migration
- ✅ Preserve all your users, orders, and products

## 🧪 Step 6: Test the Application

1. Test database connection:
```bash
php artisan tinker --execute="DB::connection()->getPdo(); echo 'MySQL connection successful!';"
```

2. Verify data:
```bash
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count(); echo 'Orders: ' . App\Models\Order::count();"
```

3. Start the application:
```bash
php artisan serve
```

4. Test login with existing accounts:
   - Admin: admin@bellgas.com.au
   - Staff: staff@bellgas.com.au

## 🔒 Step 7: Security Considerations

1. **Backup SQLite file** before removing:
```bash
copy "database\database.sqlite" "database\database.sqlite.backup"
```

2. **Update production credentials** if deploying:
   - Use strong passwords
   - Restrict database user permissions
   - Enable SSL connections

## 🐛 Troubleshooting

### Connection Refused Error
- Ensure MySQL service is running
- Check if port 3306 is available
- Verify credentials in `.env` file

### Migration Errors
- Check if database exists
- Verify user permissions
- Ensure charset is utf8mb4

### Data Mismatch
- Re-run migration script
- Check foreign key constraints
- Verify table structures match

## 📊 Expected Results

After successful migration:
- ✅ All 28 tables migrated
- ✅ 12 users with login capabilities
- ✅ 8 orders with complete history
- ✅ 3 products ready for sale
- ✅ Roles and permissions intact
- ✅ Application fully functional

## 🎯 Next Steps

1. Run the migration script: `php migrate_sqlite_to_mysql.php`
2. Test all application features
3. Update any hardcoded SQLite references
4. Configure backup strategy for MySQL
5. Monitor application performance

---

**Need help?** Check the console output during migration for detailed progress and any errors.