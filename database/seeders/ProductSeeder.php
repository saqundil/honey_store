<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $english = require lang_path('en/home.php');
        $arabic = require lang_path('ar/home.php');

        $englishProducts = collect(data_get($english, 'products.items', []))->keyBy('slug');
        $arabicProducts = collect(data_get($arabic, 'products.items', []))->keyBy('slug');
        $slugs = $englishProducts->keys()->merge($arabicProducts->keys())->unique()->values();

        DB::transaction(function () use ($slugs, $englishProducts, $arabicProducts): void {
            foreach ($slugs as $index => $slug) {
                $englishProduct = $englishProducts->get($slug, []);
                $arabicProduct = $arabicProducts->get($slug, []);
                $source = $englishProduct !== [] ? $englishProduct : $arabicProduct;

                if ($source === []) {
                    continue;
                }

                Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'image' => Arr::get($source, 'image', ''),
                        'sku' => Arr::get($source, 'sku', strtoupper($slug)),
                        'price_value' => (float) Arr::get($source, 'price_value', 0),
                        'currency' => Arr::get($source, 'currency', '$'),
                        'currency_position' => Arr::get($source, 'currency_position', 'prefix'),
                        'price_decimals' => (int) Arr::get($source, 'price_decimals', 2),
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'translations' => array_filter([
                            'en' => $this->translationPayload($englishProduct),
                            'ar' => $this->translationPayload($arabicProduct),
                        ]),
                    ],
                );
            }
        });
    }

    private function translationPayload(array $product): array
    {
        if ($product === []) {
            return [];
        }

        return [
            'name' => Arr::get($product, 'name', ''),
            'excerpt' => Arr::get($product, 'excerpt', ''),
            'description' => Arr::get($product, 'description', ''),
            'origin' => Arr::get($product, 'origin', ''),
            'texture' => Arr::get($product, 'texture', ''),
            'size' => Arr::get($product, 'size', ''),
            'category' => Arr::get($product, 'category', ''),
            'tags' => array_values(Arr::get($product, 'tags', [])),
            'highlights' => array_values(Arr::get($product, 'highlights', [])),
            'gallery' => array_values(Arr::get($product, 'gallery', [])),
            'badge' => Arr::get($product, 'badge'),
        ];
    }
}