<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'LPG Cylinders',
                'slug' => 'lpg-cylinders',
                'description' => 'Standard LPG gas cylinders for household and commercial use',
                'image_url' => 'https://via.placeholder.com/300x200/ff6b35/ffffff?text=LPG+Cylinders',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Gas Appliances',
                'slug' => 'gas-appliances',
                'description' => 'Gas stoves, heaters, and other gas-powered appliances',
                'image_url' => 'https://via.placeholder.com/300x200/007bff/ffffff?text=Gas+Appliances',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Regulators & Fittings',
                'slug' => 'regulators-fittings',
                'description' => 'Gas regulators, hoses, and connection fittings',
                'image_url' => 'https://via.placeholder.com/300x200/28a745/ffffff?text=Regulators',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Safety Equipment',
                'slug' => 'safety-equipment',
                'description' => 'Gas safety equipment, detectors, and protective gear',
                'image_url' => 'https://via.placeholder.com/300x200/ffc107/ffffff?text=Safety',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Commercial Solutions',
                'slug' => 'commercial-solutions',
                'description' => 'Industrial and commercial gas solutions',
                'image_url' => 'https://via.placeholder.com/300x200/17a2b8/ffffff?text=Commercial',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}