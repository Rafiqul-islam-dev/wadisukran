<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\ProductPrize;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LockTicketStraightRumbleTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Remove old Straight/Rumble test data only.
            $oldOrderIds = Order::where('invoice_no', 'like', 'LOCKPRIZE-%')->pluck('id');

            if ($oldOrderIds->isNotEmpty()) {
                OrderTicket::whereIn('order_id', $oldOrderIds)->delete();
                Order::whereIn('id', $oldOrderIds)->delete();
            }

            $product = Product::withTrashed()
                ->where('prize_type', 'bet')
                ->where('pick_number', 3)
                ->where('type_number', 9)
                ->first();

            if (! $product) {
                $category = Category::firstOrCreate(
                    ['name' => 'Lock Ticket Prize Test Category'],
                    [
                        'description' => 'Only for lock ticket prize testing',
                        'status' => 1,
                        'draw_type' => 'daily',
                    ]
                );

                $product = Product::create([
                    'title' => 'LOCK PRIZE TEST COUPON',
                    'category_id' => $category->id,
                    'price' => 5.00,
                    'draw_type' => 'daily',
                    'draw_date' => null,
                    'draw_time' => null,
                    'image' => null,
                    'pick_number' => 3,
                    'prize_type' => 'bet',
                    'type_number' => 9,
                    'is_active' => true,
                    'order_by' => 998,
                ]);

                $product->product_number = '3';
                $product->save();
            }

            // Ensure Chance prize rows exist, so the same all-type tickets can also prove Chance coverage.
            foreach ([1 => 15, 2 => 100] as $chanceNumber => $prizeAmount) {
                ProductPrize::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'type' => 'bet',
                        'name' => 'Chance',
                        'chance_number' => $chanceNumber,
                    ],
                    [
                        'prize' => $prizeAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $price = (float) ($product->price ?? 5);
            $baseTime = now();
            $drawNumber = 'LOCK-PRIZE-' . $baseTime->format('Ymd');

            $allTypeAgent = $this->agent(
                'Lock Prize All Type Agent',
                'lockprize.alltype@wadishukran.com',
                '0500000101',
                'lock-prize-all-type-agent',
                'LOCKPRIZEALL'
            );

            $rumbleAgent = $this->agent(
                'Lock Prize Rumble Agent',
                'lockprize.rumble@wadishukran.com',
                '0500000102',
                'lock-prize-rumble-agent',
                'LOCKPRIZERUMBLE'
            );

            $inserted = 0;

            // Agent 1: 000-999 with Straight + Rumble + Chance selected.
            // Expected lock: Straight 1000/1000, Rumble 220/220, Chance 1 10/10, Chance 2 100/100.
            for ($i = 0; $i <= 999; $i++) {
                $number = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
                $numbers = [(int) $number[0], (int) $number[1], (int) $number[2]];
                $createdAt = $baseTime->copy()->addSeconds($i);

                $this->createTicket(
                    $allTypeAgent,
                    $product,
                    $numbers,
                    ['Straight', 'Rumble', 'Chance'],
                    $drawNumber,
                    'LOCKPRIZE-ALL-' . $baseTime->format('YmdHis') . '-' . $number,
                    $price,
                    $createdAt
                );

                $inserted++;
            }

            // Agent 2: all 220 unordered 3-digit combinations with Rumble only.
            // Expected lock: Rumble 220/220 only.
            $combinationIndex = 0;
            for ($a = 0; $a <= 9; $a++) {
                for ($b = $a; $b <= 9; $b++) {
                    for ($c = $b; $c <= 9; $c++) {
                        $createdAt = $baseTime->copy()->addMinutes(30)->addSeconds($combinationIndex);
                        $number = $a . $b . $c;

                        $this->createTicket(
                            $rumbleAgent,
                            $product,
                            [$a, $b, $c],
                            ['Rumble'],
                            $drawNumber,
                            'LOCKPRIZE-RUM-' . $baseTime->format('YmdHis') . '-' . str_pad((string) $combinationIndex, 3, '0', STR_PAD_LEFT),
                            $price,
                            $createdAt
                        );

                        $combinationIndex++;
                        $inserted++;
                    }
                }
            }

            $this->command?->info('✅ ' . $inserted . ' Straight/Rumble lock test orders inserted.');
            $this->command?->info('Open Draw > Lock Ticket and search today.');
            $this->command?->info('Expected: Lock Prize All Type Agent = Straight + Rumble + Chance lock.');
            $this->command?->info('Expected: Lock Prize Rumble Agent = Rumble lock.');
            $this->command?->info('Draw: ' . $drawNumber . ' | Product ID: ' . $product->id);
        });
    }

    private function agent(string $name, string $email, string $phone, string $username, string $trn): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'user_type' => 'agent',
                'name' => $name,
                'password' => Hash::make('12345678'),
                'phone' => $phone,
                'address' => 'Test Address',
                'status' => 'active',
                'join_date' => now(),
            ]
        );

        Agent::updateOrCreate(
            ['user_id' => $user->id],
            [
                'username' => $username,
                'commission' => 0,
                'trn' => $trn,
            ]
        );

        return $user;
    }

    private function createTicket(
        User $user,
        Product $product,
        array $numbers,
        array $playTypes,
        string $drawNumber,
        string $invoiceNo,
        float $price,
        $createdAt
    ): void {
        $order = Order::create([
            'user_id' => $user->id,
            'customer_id' => null,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_price' => $price,
            'invoice_no' => $invoiceNo,
            'sales_date' => $createdAt->format('d M, Y H:i:s'),
            'draw_number' => $drawNumber,
            'commission' => 0,
            'vat' => 0,
            'commission_percentage' => 0,
            'vat_percentage' => 0,
            'is_winner' => 0,
            'is_claimed' => 0,
            'status' => 'Printed',
            'qr_code' => 'test/lock-ticket-prize/' . Str::uuid(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        OrderTicket::create([
            'order_id' => $order->id,
            'selected_numbers' => $numbers,
            'selected_play_types' => $playTypes,
            'is_winner' => 0,
            'is_claimed' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
