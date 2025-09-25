<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPhoto;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Create FULL_TANK products
        $fullTankProduct = Product::create([
            'name' => 'LPG Full Tank',
            'slug' => 'lpg-full-tank',
            'description' => 'Complete LPG gas cylinder with full tank. Perfect for home cooking, heating, and outdoor activities. Includes safety valve and pressure gauge.',
            'category' => 'FULL_TANK',
            'is_active' => true,
        ]);

        // Create variants for Full Tank
        ProductVariant::create([
            'product_id' => $fullTankProduct->id,
            'name' => '9kg Cylinder',
            'weight_kg' => 9.00,
            'price_aud' => 89.95,
            'stock_quantity' => 25,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $fullTankProduct->id,
            'name' => '15kg Cylinder',
            'weight_kg' => 15.00,
            'price_aud' => 129.95,
            'stock_quantity' => 15,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $fullTankProduct->id,
            'name' => '45kg Cylinder',
            'weight_kg' => 45.00,
            'price_aud' => 249.95,
            'stock_quantity' => 8,
            'is_active' => true,
        ]);

        // Add photos for Full Tank
        ProductPhoto::create([
            'product_id' => $fullTankProduct->id,
            'filename' => 'full-tank-primary.jpg',
            'original_filename' => 'LPG Full Tank Primary.jpg',
            'url' => '/storage/products/full-tank-primary.jpg',
            'alt_text' => 'LPG Full Tank Cylinder',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        // Create REFILL products
        $refillProduct = Product::create([
            'name' => 'LPG Gas Refill',
            'slug' => 'lpg-gas-refill',
            'description' => 'Gas refill service for your existing LPG cylinder. Bring your empty cylinder and we\'ll refill it with premium quality LPG gas. Fast and convenient service.',
            'category' => 'REFILL',
            'is_active' => true,
        ]);

        // Create variants for Refill
        ProductVariant::create([
            'product_id' => $refillProduct->id,
            'name' => '9kg Refill',
            'weight_kg' => 9.00,
            'price_aud' => 32.95,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $refillProduct->id,
            'name' => '15kg Refill',
            'weight_kg' => 15.00,
            'price_aud' => 49.95,
            'stock_quantity' => 40,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $refillProduct->id,
            'name' => '45kg Refill',
            'weight_kg' => 45.00,
            'price_aud' => 119.95,
            'stock_quantity' => 25,
            'is_active' => true,
        ]);

        // Add photos for Refill
        ProductPhoto::create([
            'product_id' => $refillProduct->id,
            'filename' => 'gas-refill-primary.jpg',
            'original_filename' => 'LPG Gas Refill Service.jpg',
            'url' => '/storage/products/gas-refill-primary.jpg',
            'alt_text' => 'LPG Gas Refill Service',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        // Create Portable Gas product
        $portableProduct = Product::create([
            'name' => 'Portable LPG Canister',
            'slug' => 'portable-lpg-canister',
            'description' => 'Small portable LPG gas canister perfect for camping, outdoor cooking, and emergency backup. Lightweight and easy to transport.',
            'category' => 'FULL_TANK',
            'is_active' => true,
        ]);

        // Create variants for Portable
        ProductVariant::create([
            'product_id' => $portableProduct->id,
            'name' => '1kg Portable',
            'weight_kg' => 1.00,
            'price_aud' => 19.95,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $portableProduct->id,
            'name' => '2kg Portable',
            'weight_kg' => 2.00,
            'price_aud' => 29.95,
            'stock_quantity' => 75,
            'is_active' => true,
        ]);

        // Add photos for Portable
        ProductPhoto::create([
            'product_id' => $portableProduct->id,
            'filename' => 'portable-canister-primary.jpg',
            'original_filename' => 'Portable LPG Canister.jpg',
            'url' => '/storage/products/portable-canister-primary.jpg',
            'alt_text' => 'Portable LPG Gas Canister',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $this->command->info('Created ' . Product::count() . ' products with variants and photos');
    }
}