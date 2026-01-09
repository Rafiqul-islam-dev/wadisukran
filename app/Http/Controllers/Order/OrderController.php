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

    public function probableWins(Request $request)
    {
        $products = Product::active()->orderBy('title')->get();

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

        $product = Product::find($request->product_id);
        $match_type = ProductPrize::find($request->match_type);
        // return $request->pick_number;
        $numbers = $request->pick_number ? collect($request->pick_number)->sort()->values() : '';

        // return $pickJson;

        $orders = Order::where('product_id', $request->product_id)
            ->when($match_type, function ($query, $matchType) use ($numbers) {

                $query->whereHas('tickets', function ($q) use ($matchType, $numbers) {
                    $q->whereJsonContains(
                        'selected_play_types',
                        $matchType->name
                    );
                    $q->whereRaw(
                        'JSON_LENGTH(selected_numbers) = ?',
                        [count($numbers)]
                    )->whereJsonContains('selected_numbers', $numbers);
                    if ($matchType->name === 'Rumble') {
                        $q->whereRaw('JSON_LENGTH(selected_numbers) = ?', [count($numbers)])
                            ->whereJsonContains('selected_numbers', $numbers);
                    }
                    if ($matchType->name === 'Chance') {
                        $q->where(function ($sub) use ($numbers) {
                            foreach ($numbers as $n) {
                                $sub->orWhereJsonContains('selected_numbers', $n);
                            }
                        });
                    }
                });
            })
            ->get();


        return Inertia::render('Orders/ProbableWins', [
            'products' => $products,
            'filters' => request()->only([
                'product_id',
                'match_type',
                'pick_number'
            ]),
            'product_prizes' => $product_prizes,
            'product' => $product,
            'orders' => $orders
        ]);
    }
}
