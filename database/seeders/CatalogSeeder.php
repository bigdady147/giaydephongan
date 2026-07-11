<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Giày Oxford',
            'Giày Derby',
            'Giày Loafer',
            'Giày Monk Strap',
            'Chelsea Boot',
            'Boot Da Nam',
            'Sandal Da Nam',
        ];

        foreach ($categories as $index => $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index]
            );
        }

        $brands = ['Hồng An', 'Pierre Cardin', 'Clarks', 'GEOX'];

        foreach ($brands as $name) {
            Brand::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
