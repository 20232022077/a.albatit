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

        Category::firstOrCreate(
            ['slug' => 'quraniyat'],
            ['name' => 'قرآنيات', 'description' => 'مقالات وخواطر وفوائد ومحتوى متنوع حول القرآن الكريم.', 'sort_order' => 1]
        );
    }
}
