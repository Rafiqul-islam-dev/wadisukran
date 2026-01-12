<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    protected $orderService;
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    public function run(): void
    {
        $product = Product::find(24);
        if (!$product) {
            $this->command->warn('No products found. Create products first.');
            return;
        }
        $usersCount = User::where('user_type', 'agent')->count();
        if ($usersCount === 0) {
            $this->command->warn('No users found. Create users first.');
            return;
        }

        $start = now()->subDays(29)->startOfDay();
        $end   = now()->endOfDay();

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->copy();
        }

        // Seed 100 orders
        for ($i = 0; $i < 100; $i++) {
            $data = [];
            $data['user_id'] = User::where('user_type', 'agent')->whereHas('agent')->inRandomOrder()->first()?->id;
            $data['product_id'] = $product->id;
            $quantity = rand(1, 5);
            $totalPrice = ($product->price ?? 10) * $quantity;
            $gameCards = [];

            for ($c = 0; $c < $quantity; $c++) {
                $card = [
                    'selected_numbers' => $this->makeNumbers($product->pick_number),
                ];

                if ($product->prize_type === 'bet') {
                    $card['selected_play_types'] = $this->randomPlayTypes();
                }

                $gameCards[] = $card;
            }
            $data['game_cards'] = $gameCards;
            $data['quantity'] = $quantity;
            $data['total_price'] = $totalPrice;

            $this->orderService->createOrder($data);
        }

        $this->command->info('✅ Seeded 1000 orders for last 30 days with tickets.');
    }

    private function makeNumbers(int $pickNumber): array
    {
        // Example: generate unique 2-digit strings like "01".."99"
        $pool = range(1, 99);
        shuffle($pool);

        $nums = array_slice($pool, 0, max(1, $pickNumber));
        $nums = array_map(fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT), $nums);

        sort($nums); // keep consistent ordering (helps matching logic)
        return $nums;
    }

    private function randomPlayTypes(): array
    {
        $types = ['Straight', 'Ramble', 'Chance'];
        shuffle($types);

        // at least 1, at most 3
        $count = rand(1, 3);
        return array_slice($types, 0, $count);
    }
}
