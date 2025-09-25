import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

export class DatabaseHelper {
  static async reset() {
    try {
      // Reset database
      await execAsync('php artisan migrate:fresh --seed');
      console.log('Database reset successfully');
    } catch (error) {
      console.error('Failed to reset database:', error.message);
      throw error;
    }
  }

  static async seed(seeder = null) {
    try {
      const command = seeder ? `php artisan db:seed --class=${seeder}` : 'php artisan db:seed';
      await execAsync(command);
      console.log(`Database seeded successfully${seeder ? ` with ${seeder}` : ''}`);
    } catch (error) {
      console.error('Failed to seed database:', error.message);
      throw error;
    }
  }

  static async clearCache() {
    try {
      await execAsync('php artisan cache:clear');
      await execAsync('php artisan config:clear');
      await execAsync('php artisan view:clear');
      console.log('Cache cleared successfully');
    } catch (error) {
      console.error('Failed to clear cache:', error.message);
    }
  }

  static async createTestData() {
    try {
      // Create test products, categories, etc.
      await execAsync('php artisan db:seed --class=TestDataSeeder');
      console.log('Test data created successfully');
    } catch (error) {
      console.log('TestDataSeeder not found, skipping test data creation');
    }
  }
}