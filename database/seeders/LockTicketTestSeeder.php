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

class LockTicketTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Remove old test data first, so this seeder can be run multiple times safely.
            $oldOrderIds = Order::where('invoice_no', 'like', 'LOCKTEST-%')->pluck('id');

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
                    ['name' => 'Lock Ticket Test Category'],
                    [
                        'description' => 'Only for lock ticket testing',
                        'status' => 1,
                        'draw_type' => 'daily',
                    ]
                );

                $product = Product::create([
                    'title' => 'LOCK TEST COUPON',
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
                    'order_by' => 999,
                ]);

                // product_number is not in the model fillable list in this project, so set it separately.
                $product->product_number = '3';
                $product->save();
            }

            // Ensure Chance 1 and Chance 2 prizes exist because the detector uses product_prizes to know Chance levels.
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

            $testAgents = [
                [
                    'name' => 'Lock Ticket Test Agent 1',
                    'email' => 'locktest.agent1@wadishukran.com',
                    'phone' => '0500000001',
                    'username' => 'lock-ticket-test-agent-1',
                    'trn' => 'LOCKTEST1',
                ],
                [
                    'name' => 'Lock Ticket Test Agent 2',
                    'email' => 'locktest.agent2@wadishukran.com',
                    'phone' => '0500000002',
                    'username' => 'lock-ticket-test-agent-2',
                    'trn' => 'LOCKTEST2',
                ],
                [
                    'name' => 'Lock Ticket Test Agent 3',
                    'email' => 'locktest.agent3@wadishukran.com',
                    'phone' => '0500000003',
                    'username' => 'lock-ticket-test-agent-3',
                    'trn' => 'LOCKTEST3',
                ],
            ];

            // This draw number must be same for each agent's 100 tickets, otherwise the detector will not group them together.
            // The Lock Ticket page groups by Product + Draw No + Agent, so 3 agents will show as 3 separate lock cards.
            $drawNumber = 'LOCK-TEST-' . now()->format('Ymd');
            $baseTime = now();
            $price = (float) ($product->price ?? 5);
            $totalOrders = 0;

            foreach ($testAgents as $agentIndex => $testAgent) {
                $user = User::updateOrCreate(
                    ['email' => $testAgent['email']],
                    [
                        'user_type' => 'agent',
                        'name' => $testAgent['name'],
                        'password' => Hash::make('12345678'),
                        'phone' => $testAgent['phone'],
                        'address' => 'Test Address',
                        'status' => 'active',
                        'join_date' => now(),
                    ]
                );

                Agent::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'username' => $testAgent['username'],
                        'commission' => 0,
                        'trn' => $testAgent['trn'],
                    ]
                );

                // Create 100 tickets for each agent: 000, 001, 002 ... 099 with Chance selected.
                // This covers every possible last-2-digit outcome: 00-99.
                // So each agent should be detected as Chance 2 lock.
                for ($i = 0; $i <= 99; $i++) {
                    $twoDigit = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $numbers = [0, (int) $twoDigit[0], (int) $twoDigit[1]];
                    $createdAt = $baseTime->copy()->addMinutes($agentIndex)->addSeconds($i);

                    $order = Order::create([
                        'user_id' => $user->id,
                        'customer_id' => null,
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'total_price' => $price,
                        'invoice_no' => 'LOCKTEST-A' . ($agentIndex + 1) . '-' . $baseTime->format('YmdHis') . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                        'sales_date' => $createdAt->format('d M, Y H:i:s'),
                        'draw_number' => $drawNumber,
                        'commission' => 0,
                        'vat' => 0,
                        'commission_percentage' => 0,
                        'vat_percentage' => 0,
                        'is_winner' => 0,
                        'is_claimed' => 0,
                        'status' => 'Printed',
                        'qr_code' => 'test/lock-ticket/' . Str::uuid(),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    OrderTicket::create([
                        'order_id' => $order->id,
                        'selected_numbers' => $numbers,
                        'selected_play_types' => ['Chance'],
                        'is_winner' => 0,
                        'is_claimed' => 0,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $totalOrders++;
                }
            }

            $this->command?->info('✅ ' . $totalOrders . ' lock-ticket test orders inserted for 3 agents.');
            $this->command?->info('Open Draw > Lock Ticket and search today.');
            $this->command?->info('Expected result: Locked Found = 3');
            $this->command?->info('Agents: Lock Ticket Test Agent 1, 2, 3 | Draw: ' . $drawNumber . ' | Product ID: ' . $product->id);
        });
    }
}
