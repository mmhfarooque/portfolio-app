<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Landscapes',
                'description' => 'Breathtaking landscape photography capturing the beauty of nature.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Mountains',
                'description' => 'Majestic mountain peaks and alpine scenery.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Watercraft',
                'description' => 'Boats, ships, and maritime photography.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Sunsets',
                'description' => 'Golden hour and sunset photography.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Wildlife',
                'description' => 'Wildlife and animal photography in their natural habitat.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Forests',
                'description' => 'Dense forests, woodland paths, and tree photography.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Coastal',
                'description' => 'Beaches, ocean waves, and coastal scenery.',
                'sort_order' => 7,
            ],
            [
                'name' => 'Night Sky',
                'description' => 'Astrophotography, stars, and night landscapes.',
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                array_merge($category, ['slug' => Str::slug($category['name'])])
            );
        }
    }
}
