<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\CompannySetting;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\ProductPrize;
use App\Services\CategoryService;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $categoryService;
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    public function index(Request $request)
    {
        $match_type = ProductPrize::find($request->match_type);
        // return $match_type;
        $orders = Order::when($request->user_id, function ($query, $userId) {
            $query->where('user_id', $userId);
        })
            ->when($request->date_from, function ($query, $dateFrom) use ($request) {
                $timeFrom = $request->time_from ?? '00:00:00';
                $query->where('created_at', '>=', "$dateFrom $timeFrom");
            })
            ->when($request->date_to, function ($query, $dateTo) use ($request) {
                $timeTo = $request->time_to ?? '23:59:59';
                $query->where('created_at', '<=', "$dateTo $timeTo");
            })
            ->when($request->category_id && $request->product_id, function ($query) use ($request) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id)
                        ->where('id', $request->product_id);
                });
            })
            ->when($match_type, function ($query, $matchType) {
                $query->whereHas('tickets', function ($q) use ($matchType) {
                    $q->whereJsonContains(
                        'selected_play_types',
                        ucfirst(strtolower($matchType->name))
                    );
                });
            })
            ->with(['user', 'product', 'user.agent', 'tickets'])->limit(10)->paginate(10);
        $users = User::select('id', 'name')->get();
        $company = CompannySetting::firstOrFail();
        $categories = $this->categoryService->activeCategories();
        $products = Product::where('category_id', $request->category_id)->active()->orderBy('title')->get();

        $product_prizes = [];
        if ($request->product_id) {
            $product = Product::find($request->product_id);
            if ($product->prize_type == 'bet') {
                foreach ($product->prizes as $prize) {
                    $product_prizes[] = [
                        'id' => $prize->id,
                        'name' => $prize->name,
                        'chance_number' => $prize->chance_number
                    ];
                }
            }
        }

        // return $product_prizes;


        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'users' => $users,
            'company' => $company,
            'categories' => $categories,
            'products' => $products,
            'product_prizes' => $product_prizes,
            'filters' => request()->only([
                'user_id',
                'date_from',
                'time_from',
                'date_to',
                'time_to',
                'match_type',
                'category_id',
                'product_id',
            ]),
        ]);
    }

    protected function product_prizes($product_id)
    {
        $product_prizes = [];
        $product = Product::find($product_id);

        if ($product->prize_type == 'bet') {
            foreach ($product->prizes as $prize) {
                $product_prizes[] = [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'chance_number' => $prize->chance_number
                ];
            }
        }

        return $product_prizes;
    }

    public function probableWins(Request $request)
    {
        $products = Product::active()->orderBy('title')->get();

        $product_prizes = $request->product_id
            ? collect($this->product_prizes($request->product_id))
            : collect();

        $product = Product::find($request->product_id);

        $summery = [];
        if ($request->btn === 'search' && $request->pick_number && $request->product_id) {
            $numbers = $request->pick_number
                ? collect($request->pick_number)->sort()->values()
                : collect();

            $match_type = ProductPrize::find($request->match_type);

            $types = $match_type
                ? [$match_type]
                : $product->prizes;

            $numbersStraight = collect($request->pick_number)->values();
            $numbersChance = collect($request->pick_number)->reverse()->values();
            // keep order
            $numbersSorted   = collect($request->pick_number)->sort()->values();   // for rumble
            $len             = $numbersStraight->count();

            $orders = OrderTicket::query()
                ->whereHas('order', fn($o) => $o->where('product_id', $request->product_id))
                ->when($request->match_type, function ($q) use ($match_type) {
                    $q->whereJsonContains('selected_play_types', $match_type->name);
                })
                ->get()
                ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $types, $product, $numbersChance) {
                    $data = ['id' => $order->id];
                    $isStraightWinner = false;
                    $isRumbleWinner = false;
                    $isChanceWinner = false;
                    $ticketTypes   = is_array($order->selected_play_types)
                        ? $order->selected_play_types
                        : (array) $order->selected_play_types;

                    $ticketNumbers = collect($order->selected_numbers)->values();
                    if($product->prize_type === 'bet'){
                        foreach ($product->prizes->whereIn('name', ['Straight', 'Rumble']) as $type) {
                            if ($type->name === 'Straight' & in_array('Straight', $ticketTypes, true)) {
                                $isStraightWinner =
                                    $ticketNumbers->count() === $len &&
                                    $ticketNumbers->all() === $numbersStraight->all();
                                $data[$type->name] = $isStraightWinner;
                            } else if ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                                if ($isStraightWinner == false) {
                                    $isRumbleWinner =
                                        $ticketNumbers->count() === $len &&
                                        $ticketNumbers->sort()->values()->all() === $numbersSorted->all();
                                }
                                $data[$type->name] = $isRumbleWinner;
                            }
                        }
                        if(in_array('Chance', $ticketTypes, true)){
                            $matchCount = $ticketNumbers
                                ->values()
                                ->zip($numbersChance)
                                ->filter(fn($pair) => $pair[0] === $pair[1])
                                ->count();

                            $chancePrizes = $product->prizes
                                ->where('name', 'Chance')
                                ->sortByDesc('chance_number')
                                ->values();


                            foreach ($chancePrizes as $chanceType) {

                                $key = $chanceType->name . ' ' . $chanceType->chance_number;
                                $data[$key] = false;

                                if ($isStraightWinner || $isRumbleWinner || $isChanceWinner) {
                                    continue;
                                }

                                // EXACT positional match
                                if ($matchCount == (int) $chanceType->chance_number) {
                                    $data[$key] = true;
                                    $isChanceWinner = true; // block lower tiers
                                }
                            }
                        }

                    }
                    return $data;
                });


            foreach ($types as $type) {
                if ($type->name === 'Chance') {
                    $name = $type->name . ' ' . $type->chance_number;
                    $summery[$name] = [
                        'match_type' => $name,
                        'winners' =>  $orders->where($name, true)->count(),
                        'prize_per_winner' => $type->prize,
                        'tickets' => $orders->where($name, true)->values(),
                        'total_amount' => ($orders->where($name, true)->count() * $type->prize)
                    ];
                } else {
                    $summery[$type->name] = [
                        'match_type' => $type->name,
                        'winners' =>  $orders->where($type->name, true)->count(),
                        'prize_per_winner' => $type->prize,
                        'tickets' => $orders->where($type->name, true)->values(),
                        'total_amount' => ($orders->where($type->name, true)->count() * $type->prize)
                    ];
                }
            }
        }


        // $numbers = $request->pick_number
        //     ? collect($request->pick_number)->sort()->values()
        //     : collect();
        // $numbersStraight = $request->pick_number
        //     ? collect($request->pick_number)->values()
        //     : collect();

        // $numbersRumble = $numbersStraight->reverse()->values();

        // $winJsonStraight = json_encode($numbersStraight->all(), JSON_UNESCAPED_UNICODE);
        // $winJsonRumble   = json_encode($numbersRumble->all(), JSON_UNESCAPED_UNICODE);
        // // return $winJsonRumble;

        // $len = $numbersStraight->count();


        // $types = $match_type ? [$match_type->name] : ($product_prizes->isNotEmpty()
        //     ? $product_prizes->pluck('name')->values()->toArray()
        //     : ['Straight', 'Rumble', 'Chance']
        // );

        // $applyWinnerRule = function ($q, string $type) use ($winJsonStraight, $winJsonRumble, $len, $numbers) {
        //     $q->whereJsonContains('selected_play_types', $type);

        //     // Straight -- win == 123 , ticket == 123
        //     // Rumble-- win == 123,  ticket == 321 --- jekono vabe millei hoice
        //     // Chances-- right to left how many number matches

        //     // Straight + Rumble = exact same numbers & length (based on your code)
        //     if ($type === 'Straight') {
        //         $q->whereRaw('selected_numbers = CAST(? AS JSON)', [$winJsonStraight]);
        //     }

        //     if ($type === 'Rumble') {
        //         $q->whereRaw('JSON_LENGTH(selected_numbers) = ?', [$len])
        //             // ticket contains all winning numbers (order independent)
        //             ->whereJsonContains('selected_numbers', $numbers->all());
        //     }

        //     // Chance = any number matches
        //     if ($type === 'Chance') {
        //         $q->where(function ($sub) use ($numbers) {
        //             foreach ($numbers as $n) {
        //                 $sub->orWhereJsonContains('selected_numbers', $n);
        //             }
        //         });
        //     }

        //     return $q;
        // };

        // $summary = collect($types)->map(function ($type) use ($request, $applyWinnerRule) {
        //     $winners = OrderTicket::query()
        //         ->whereHas('order', fn($o) => $o->where('product_id', $request->product_id))
        //         ->tap(fn($q) => $applyWinnerRule($q, $type))
        //         ->count();

        //     // Prize per winner (adjust column name to your DB)
        //     $prize = ProductPrize::query()
        //         ->where('product_id', $request->product_id)
        //         ->where('name', $type)
        //         ->value('prize') ?? 0;

        //     return [
        //         'match_type'       => $type,
        //         'winners'          => (int) $winners,
        //         'prize_per_winner' => (float) $prize,
        //         'total_amount'     => (float) ($winners * $prize),
        //     ];
        // })->values();

        // // ---- Winner Orders list (your existing logic) ----
        // $ordersQuery = Order::query()->where('product_id', $request->product_id);

        // if ($match_type) {
        //     $ordersQuery->whereHas('tickets', function ($q) use ($applyWinnerRule, $match_type) {
        //         $applyWinnerRule($q, $match_type->name);
        //     });
        // }

        // // You used get(); you can paginate if needed
        // $orders = $ordersQuery
        //     ->with(['user', 'product', 'user.agent', 'tickets'])
        //     ->latest()
        //     ->paginate(10);


        return Inertia::render('Orders/ProbableWins', [
            'products' => $products,
            'filters' => request()->only([
                'product_id',
                'match_type',
                'pick_number'
            ]),
            'product_prizes' => $product_prizes,
            'product' => $product,
            'summary' => $summery
        ]);
    }
}
