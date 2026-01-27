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
use Carbon\Carbon;
use Inertia\Inertia;

use function PHPSTORM_META\type;

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
            ->with(['user', 'product', 'user.agent', 'tickets'])->limit(10)->latest()->paginate(10);
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
        $from = null;
        $to   = null;

        if ($request->date_from) {
            $from = Carbon::parse(
                $request->date_from . ' ' . ($request->time_from ?? '00:00:00')
            );
        }

        if ($request->date_to) {
            $to = Carbon::parse(
                $request->date_to . ' ' . ($request->time_to ?? '23:59:59')
            );
        }
        $products = Product::active()->orderBy('title')->get();
        $product_prizes = $request->product_id
            ? collect($this->product_prizes($request->product_id))
            : collect();

        $product = Product::find($request->product_id);

        $summery = [];
        if ($request->btn === 'search' && $request->pick_number && $request->product_id) {
            $match_type = ProductPrize::find($request->match_type);

            $types = $match_type
                ? [$match_type]
                : $product->prizes;

            $numbersStraight = collect($request->pick_number)->values();
            $numbersChance = collect($request->pick_number)->reverse()->values();
            $numbersSorted   = collect($request->pick_number)->sort()->values();
            $len             = $numbersStraight->count();

            $orders = OrderTicket::query()
                ->whereHas('order', function ($o) use ($request, $from, $to) {
                    $o->where('status', 'Printed')->where('product_id', $request->product_id);
                    if ($from) {
                        $o->where('created_at', '>=', $from);
                    }
                    if ($to) {
                        $o->where('created_at', '<=', $to);
                    }
                })
                ->when($request->match_type, function ($q) use ($match_type) {
                    $q->whereJsonContains('selected_play_types', $match_type->name);
                })
                ->get()
                ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $types, $product, $numbersChance) {
                    $data = ['id' => $order->id, 'selected_numbers' => $order->selected_numbers];
                    $isStraightWinner = false;
                    $isRumbleWinner = false;
                    $isChanceWinner = false;
                    $ticketTypes   = is_array($order->selected_play_types)
                        ? $order->selected_play_types
                        : (array) $order->selected_play_types;
                    $ticketNumbers = collect($order->selected_numbers)->values();


                    if ($product->prize_type === 'bet') {
                        foreach ($product->prizes->whereIn('name', ['Straight', 'Rumble']) as $type) {
                            if ($type->name === 'Straight' & in_array('Straight', $ticketTypes, true)) {
                                $isStraightWinner =
                                    $ticketNumbers->count() === $len &&
                                    $ticketNumbers->all() == $numbersStraight->all();
                                $data[$type->name] = $isStraightWinner;
                            } else if ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                                if ($isStraightWinner == false) {
                                    $isRumbleWinner =
                                        $ticketNumbers->count() === $len &&
                                        $ticketNumbers->sort()->values()->all() == $numbersSorted->all();
                                }
                                $data[$type->name] = $isRumbleWinner;
                            }
                        }
                        if (in_array('Chance', $ticketTypes, true)) {
                            $matchCount = $ticketNumbers->reverse()
                                ->values()
                                ->zip($numbersChance)
                                ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
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

                                if ($matchCount == (int) $chanceType->chance_number) {
                                    $data[$key] = true;
                                    $isChanceWinner = true;
                                }
                            }
                        }
                    } else {
                        $matchCount = $ticketNumbers->intersect($numbersStraight)->count();
                        $numberPrizes = $product->prizes
                            ->sortByDesc('name');


                        foreach ($numberPrizes as $prize) {
                            $key = 'Number ' . (int) $prize->name;
                            $data[$key] = false;

                            if ($isNumberWinner) continue;

                            if ($matchCount === (int) $prize->name) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        }
                    }
                    $orderHasWon = $isStraightWinner || $isRumbleWinner || $isChanceWinner || $isNumberWinner;

                    if ($orderHasWon) {
                        return $data;
                    }

                    return null;
                })->filter();

            foreach ($types as $type) {
                if (is_numeric($type->name)) {
                    $name = 'Number ' . $type->name;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[$name] = [
                            'match_type' => $name,
                            'winners' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->values(),
                            'total_amount' => ($orders->where($name, true)->count() * $type->prize)
                        ];
                    }
                } else if ($type->name === 'Chance') {
                    $name = $type->name . ' ' . $type->chance_number;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[$name] = [
                            'match_type' => $name,
                            'winners' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->values(),
                            'total_amount' => ($orders->where($name, true)->count() * $type->prize)
                        ];
                    }
                } else {
                    if ($orders->where($type->name, true)->count() > 0) {
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
        }

        return Inertia::render('Orders/ProbableWins', [
            'products' => $products,
            'filters' => request()->only([
                'product_id',
                'match_type',
                'pick_number',
                'date_from',
                'time_from',
                'date_to',
                'time_to'
            ]),
            'product_prizes' => $product_prizes,
            'product' => $product,
            'summary' => $summery
        ]);
    }
}
