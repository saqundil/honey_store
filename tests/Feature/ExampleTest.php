<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_renders_products_from_the_database(): void
    {
        Product::create([
            'slug' => 'forest-honey',
            'image' => 'images/forest-honey.png',
            'sku' => 'FRS-01',
            'price_value' => 12.5,
            'currency' => '$',
            'currency_position' => 'prefix',
            'price_decimals' => 2,
            'sort_order' => 1,
            'is_active' => true,
            'translations' => [
                'en' => [
                    'name' => 'Forest Honey',
                    'excerpt' => 'Small-batch forest honey.',
                    'description' => 'Forest honey description.',
                    'origin' => 'Forest',
                    'texture' => 'Smooth',
                    'size' => '250 g',
                    'category' => 'Premium Honey',
                    'tags' => ['Honey'],
                    'highlights' => ['Rich flavor'],
                    'gallery' => [
                        ['src' => 'images/forest-honey.png', 'alt' => 'Forest Honey'],
                    ],
                    'badge' => 'New',
                ],
            ],
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Forest Honey');
    }

    public function test_a_product_order_is_persisted(): void
    {
        $product = Product::create([
            'slug' => 'mountain-honey',
            'image' => 'images/mountain-honey.png',
            'sku' => 'MNT-01',
            'price_value' => 8,
            'currency' => 'JOD',
            'currency_position' => 'suffix',
            'price_decimals' => 0,
            'sort_order' => 1,
            'is_active' => true,
            'translations' => [
                'en' => [
                    'name' => 'Mountain Honey',
                    'excerpt' => 'Crisp floral honey.',
                    'description' => 'Mountain honey description.',
                    'origin' => 'Mountains',
                    'texture' => 'Creamy',
                    'size' => '250 g',
                    'category' => 'Premium Honey',
                    'tags' => ['Honey'],
                    'highlights' => ['Creamy floral honey'],
                    'gallery' => [
                        ['src' => 'images/mountain-honey.png', 'alt' => 'Mountain Honey'],
                    ],
                    'badge' => 'Premium',
                ],
            ],
        ]);

        $response = $this->post(route('products.order', ['slug' => $product->slug]), [
            'customer_name' => 'Order Test',
            'email' => 'order@example.com',
            'phone' => '123456789',
            'quantity' => 3,
            'notes' => 'Please call before delivery.',
        ]);

        $response->assertRedirect(route('products.show', ['slug' => $product->slug]));
        $this->assertDatabaseHas('orders', [
            'product_id' => $product->id,
            'customer_name' => 'Order Test',
            'email' => 'order@example.com',
            'quantity' => 3,
            'currency' => 'JOD',
            'status' => 'pending',
        ]);
        $this->assertSame(1, Order::count());
    }
}
