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
        $product_prizes = $request->product_id ? $this->product_prizes($request->product_id) : [];

        $product = Product::find($request->product_id);

        $match_type = ProductPrize::find($request->match_type);

        $numbers = $request->pick_number
            ? collect($request->pick_number)->sort()->values()
            : collect();

        $types = $match_type ? [$match_type->name] : ['Straight', 'Rumble', 'Chance'];

        $applyWinnerRule = function ($q, string $type) use ($numbers) {
            $q->whereJsonContains('selected_play_types', $type);

            // Straight + Rumble = exact same numbers & length (based on your code)
            if ($type === 'Straight' || $type === 'Rumble') {
                $q->whereRaw('JSON_LENGTH(selected_numbers) = ?', [count($numbers)])
                    ->whereJsonContains('selected_numbers', $numbers);
            }

            // Chance = any number matches
            if ($type === 'Chance') {
                $q->where(function ($sub) use ($numbers) {
                    foreach ($numbers as $n) {
                        $sub->orWhereJsonContains('selected_numbers', $n);
                    }
                });
            }

            return $q;
        };

        $summary = collect($types)->map(function ($type) use ($request, $applyWinnerRule) {
            $winners = OrderTicket::query()
                ->whereHas('order', fn($o) => $o->where('product_id', $request->product_id))
                ->tap(fn($q) => $applyWinnerRule($q, $type))
                ->count();

            // Prize per winner (adjust column name to your DB)
            $prize = ProductPrize::query()
                ->where('product_id', $request->product_id)
                ->where('name', $type)
                ->value('prize') ?? 0;

            return [
                'match_type'       => $type,
                'winners'          => (int) $winners,
                'prize_per_winner' => (float) $prize,
                'total_amount'     => (float) ($winners * $prize),
            ];
        })->values();

        // ---- Winner Orders list (your existing logic) ----
        $ordersQuery = Order::query()->where('product_id', $request->product_id);

        if ($match_type) {
            $ordersQuery->whereHas('tickets', function ($q) use ($applyWinnerRule, $match_type) {
                $applyWinnerRule($q, $match_type->name);
            });
        }

        // You used get(); you can paginate if needed
        $orders = $ordersQuery
            ->with(['user', 'product', 'user.agent', 'tickets'])
            ->latest()
            ->paginate(10);


        return Inertia::render('Orders/ProbableWins', [
            'products' => $products,
            'filters' => request()->only([
                'product_id',
                'match_type',
                'pick_number'
            ]),
            'product_prizes' => $product_prizes,
            'product' => $product,
            'orders' => $orders,
            'summary' => $summary
        ]);
    }
}
