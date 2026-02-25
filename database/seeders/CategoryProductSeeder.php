<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrize;
use Illuminate\Database\Seeder;

class CategoryProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Daily Draw',
                'description' => 'Exciting daily lottery draws with instant results every day.',
                'draw_type'   => 'daily',
            ],
            [
                'name'        => 'Weekly Jackpot',
                'description' => 'Big weekly jackpot draws every Saturday night.',
                'draw_type'   => 'once',
            ],
            [
                'name'        => 'Monthly Bonanza',
                'description' => 'Massive monthly prize pools drawn at end of each month.',
                'draw_type'   => 'daily',
            ],
            [
                'name'        => 'Special Events',
                'description' => 'Limited time special event draws with exclusive prizes.',
                'draw_type'   => 'daily',
            ],
            [
                'name'        => 'VIP Premium',
                'description' => 'Exclusive high-value draws for premium members only.',
                'draw_type'   => 'once',
            ],
        ];

        $productsMap = [
            'Daily Draw' => [
                'title'       => 'Lucky 3 Daily',
                'price'       => 2.00,
                'draw_date'   => '2025-07-01',
                'draw_time'   => '18:00:00',
                'pick_number' => 3,
                'type_number' => 9,
                'prize_type'  => 'bet',
                'image'       => 'uploads/products/product-1.png',
                'is_active'   => true,
                'prizes' => [
                    ['type' => 'bet', 'name' => 'Straight', 'chance_number' => null, 'prize' => 500],
                    ['type' => 'bet',   'name' => 'Rumble',   'chance_number' => null,  'prize' => 80],
                    ['type' => 'bet',   'name' => 'Chance',   'chance_number' => 3,  'prize' => 40],
                ],
            ],
            'Weekly Jackpot' => [
                'title'       => 'Super 6 Weekly',
                'price'       => 10.00,
                'draw_date'   => '2025-07-12',
                'draw_time'   => '20:00:00',
                'pick_number' => 6,
                'type_number' => 49,
                'prize_type'  => 'bet',
                'image'       => 'uploads/products/product-1.png',
                'is_active'   => true,
                'prizes' => [
                    ['type' => 'bet', 'name' => 'Straight',      'chance_number' => null, 'prize' => 1000000],
                    ['type' => 'bet',  'name' => 'Rumble', 'chance_number' => null, 'prize' => 50000],
                    ['type' => 'bet',   'name' => 'Chance',  'chance_number' => 2, 'prize' => 10000],
                ],
            ],
            'Monthly Bonanza' => [
                'title'       => 'Gold Rush Monthly',
                'price'       => 50.00,
                'draw_date'   => '2025-07-31',
                'draw_time'   => '21:00:00',
                'pick_number' => 5,
                'type_number' => 39,
                'prize_type'  => 'number',
                'image'       => 'uploads/products/product-1.png',
                'is_active'   => true,
                'prizes' => [
                    ['type' => 'number', 'name' => 'number',  'chance_number' => 1, 'prize' => 5000000],
                    ['type' => 'number',  'name' => 'number', 'chance_number' => 2, 'prize' => 250000],
                    ['type' => 'number',   'name' => 'number',  'chance_number' => 3, 'prize' => 25000],
                ],
            ],
            'Special Events' => [
                'title'       => 'Ramadan Special Draw',
                'price'       => 25.00,
                'draw_date'   => '2025-08-15',
                'draw_time'   => '19:30:00',
                'pick_number' => 4,
                'type_number' => 9,
                'image'       => 'uploads/products/product-1.png',
                'prize_type'  => 'number',
                'is_active'   => true,
                'prizes' => [
                    ['type' => 'number', 'name' => 'number',      'chance_number' => 2, 'prize' => 500000],
                    ['type' => 'number',  'name' => 'number', 'chance_number' => 3, 'prize' => 50000],
                    ['type' => 'number',   'name' => 'number',  'chance_number' => 4, 'prize' => 5000],
                ],
            ],
            'VIP Premium' => [
                'title'       => 'Platinum VIP Weekly',
                'price'       => 100.00,
                'draw_date'   => '2025-07-19',
                'draw_time'   => '22:00:00',
                'pick_number' => 6,
                'type_number' => 59,
                'prize_type'  => 'bet',
                'image'       => 'uploads/products/product-1.png',
                'is_active'   => true,
                'prizes' => [
                    ['type' => 'bet', 'name' => 'Straignt', 'chance_number' => null, 'prize' => 10000000],
                    ['type' => 'bet',  'name' => 'Rumble',  'chance_number' => null, 'prize' => 500000],
                    ['type' => 'bet',   'name' => 'Chance',   'chance_number' => 3, 'prize' => 50000],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::create([
                'name'        => $categoryData['name'],
                'description' => $categoryData['description'],
                'draw_type'   => $categoryData['draw_type'],
                'status'      => 1,
            ]);

            $productData = $productsMap[$categoryData['name']];
            $prizes      = $productData['prizes'];
            unset($productData['prizes']);

            $product = Product::create([
                ...$productData,
                'category_id' => $category->id,
                'draw_type'   => $category->draw_type,
            ]);

            foreach ($prizes as $prize) {
                if ($prize['prize'] > 0) {
                    ProductPrize::create([
                        'product_id'    => $product->id,
                        'type'          => $prize['type'],
                        'name'          => $prize['name'],
                        'chance_number' => $prize['chance_number'] ?? null,
                        'prize'         => $prize['prize'],
                    ]);
                }
            }
        }
    }
}
