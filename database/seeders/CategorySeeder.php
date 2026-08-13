<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::firstOrCreate(
            ['slug' => 'quran-centrality'],
            ['name' => 'مركزية القرآن', 'description' => 'محتوى يبرز مكانة القرآن الكريم ومنهج التعامل معه.', 'sort_order' => 0]
        );
    }
}
